const fs = require('fs');
const fse = require('fs-extra');
const path = require('path')
const inquirer = require('inquirer');
const child_process = require('child_process');
const ora = require('ora');
const yauzl = require('yauzl');
const axios = require('axios');
const tar = require('tar');
const utils = require('./lib/utils');
const r2 = require('./lib/r2');
const { buildReleaseIndex } = require('./lib/release-index');
const config = require('../package.json')
const env = require('dotenv').config({ path: './.env' })
const argv = process.argv;
const {BUILD_FRONTEND, APPLEID, APPLEIDPASS, GITHUB_TOKEN, GITHUB_REPOSITORY} = process.env;

const electronDir = path.resolve(__dirname, "public");
const nativeCachePath = path.resolve(__dirname, ".native");
const devloadCachePath = path.resolve(__dirname, ".devload");
const packageFile = path.resolve(__dirname, "package.json");
const packageBakFile = path.resolve(__dirname, "package-bak.json");
const platforms = ["build-mac", "build-win"];
const architectures = ["arm64", "x64"];

let buildChecked = false,
    updaterChecked = false;

const shellQuote = (value) => `'${String(value).replace(/'/g, `'\\''`)}'`;
const elapsedSeconds = (startTime) => `${((Date.now() - startTime) / 1000).toFixed(1)}s`;

/**
 * 检测并下载更新器
 */
async function detectAndDownloadUpdater() {
    const updaterDir = path.resolve(__dirname, "updater");
    const latestVersionFile = path.join(updaterDir, "latest");

    // 创建updater目录
    if (!fs.existsSync(updaterDir)) {
        fs.mkdirSync(updaterDir, { recursive: true });
    }

    try {
        // 获取最新release
        const spinner = ora('检查更新器...').start();
        const response = await axios.get('https://api.github.com/repos/kuaifan/dootask-updater/releases/latest', {
            headers: GITHUB_TOKEN ? { 'Authorization': `token ${GITHUB_TOKEN}` } : {}
        });

        if (!response.data || !response.data.assets) {
            spinner.fail('检查更新器失败');
            return;
        }

        // 检查版本是否需要更新
        const latestVersion = response.data.tag_name || response.data.name;
        let currentVersion = '';
        if (fs.existsSync(latestVersionFile)) {
            currentVersion = fs.readFileSync(latestVersionFile, 'utf8').trim();
        }

        // 如果版本不一致，清空updater目录（保留latest文件）
        if (currentVersion !== latestVersion) {
            const files = fs.readdirSync(updaterDir);
            for (const file of files) {
                if (file === 'latest') continue;
                const filePath = path.join(updaterDir, file);
                if (fs.lstatSync(filePath).isDirectory()) {
                    fs.rmSync(filePath, { recursive: true });
                } else {
                    fs.unlinkSync(filePath);
                }
            }
        }

        // 过滤出binary开头的zip文件
        const assets = response.data.assets.filter(asset =>
            asset.name.startsWith('binary_') && asset.name.endsWith('.zip')
        );

        spinner.succeed('检查更新器成功');

        // 下载并解压每个文件
        for (const asset of assets) {
            const fileName = asset.name;
            // 解析平台和架构信息 (binary_0.1.0_linux-x86_64.zip => linux/x86_64)
            const match = fileName.match(/binary_[\d.]+_(.+)-(.+)\.zip$/);
            if (!match) continue;

            let [, platform, arch] = match;

            // 平台名称映射
            const platformMap = {
                'macos': 'mac',
                'windows': 'win'
            };
            platform = platformMap[platform] || platform;

            // 架构名称映射
            const archMap = {
                'x86_64': 'x64'
            };
            arch = archMap[arch] || arch;

            const targetDir = path.join(updaterDir, platform, arch);
            const zipPath = path.join(updaterDir, fileName);

            // 检查是否已经下载过
            if (fs.existsSync(targetDir)) {
                continue;
            }

            // 创建目标目录
            fs.mkdirSync(targetDir, { recursive: true });

            // 下载文件
            const downloadSpinner = ora(`下载 ${fileName}...`).start();
            try {
                const writer = fs.createWriteStream(zipPath);
                const response = await axios({
                    url: asset.browser_download_url,
                    method: 'GET',
                    responseType: 'stream',
                    headers: GITHUB_TOKEN ? { 'Authorization': `token ${GITHUB_TOKEN}` } : {}
                });

                response.data.pipe(writer);

                await new Promise((resolve, reject) => {
                    writer.on('finish', resolve);
                    writer.on('error', reject);
                });

                // 解压文件
                downloadSpinner.text = `解压 ${fileName}...`;
                await new Promise((resolve, reject) => {
                    yauzl.open(zipPath, { lazyEntries: true }, (err, zipfile) => {
                        if (err) reject(err);

                        zipfile.readEntry();
                        zipfile.on('entry', (entry) => {
                            if (/\/$/.test(entry.fileName)) {
                                zipfile.readEntry();
                            } else {
                                zipfile.openReadStream(entry, (err, readStream) => {
                                    if (err) reject(err);

                                    const outputPath = path.join(targetDir, path.basename(entry.fileName));
                                    const writer = fs.createWriteStream(outputPath);
                                    readStream.pipe(writer);
                                    writer.on('finish', () => {
                                        fs.chmodSync(outputPath, 0o755);
                                        zipfile.readEntry();
                                    });
                                });
                            }
                        });

                        zipfile.on('end', resolve);
                    });
                });

                // 删除zip文件
                fs.unlinkSync(zipPath);
                downloadSpinner.succeed(`下载并解压 ${fileName} 成功`);

                // 下载和解压成功后，保存最新版本号
                fs.writeFileSync(latestVersionFile, latestVersion, 'utf8');

            } catch (error) {
                downloadSpinner.fail(`下载 ${fileName} 失败: ${error.message}`);
                // 清理失败的下载
                if (fs.existsSync(zipPath)) {
                    fs.unlinkSync(zipPath);
                }
                if (fs.existsSync(targetDir)) {
                    fs.rmdSync(targetDir, { recursive: true });
                }
            }
        }

    } catch (error) {
        console.error('检查更新器失败:', error.message);
    }
}

/**
 * 下载并解压Drawio
 * @param drawioDestDir
 */
async function downloadAndExtractDrawio(drawioDestDir) {
    const tempDir = path.resolve(__dirname, ".temp");
    const tarFilePath = path.join(tempDir, "drawio-latest.tar.gz");

    try {
        // 创建临时目录
        if (!fs.existsSync(tempDir)) {
            fs.mkdirSync(tempDir, { recursive: true });
        }

        const spinner = ora('下载最新的Drawio文件...').start();

        // 1. 下载tar.gz文件
        const response = await axios({
            url: 'https://appstore.dootask.com/api/v1/download/drawio/latest',
            method: 'GET',
            responseType: 'stream'
        });

        const writer = fs.createWriteStream(tarFilePath);
        response.data.pipe(writer);

        await new Promise((resolve, reject) => {
            writer.on('finish', resolve);
            writer.on('error', reject);
        });

        spinner.text = '解压Drawio文件...';

        // 2. 解压tar.gz文件到临时目录
        const extractDir = path.join(tempDir, "extracted");
        if (fs.existsSync(extractDir)) {
            fse.removeSync(extractDir);
        }
        fs.mkdirSync(extractDir, { recursive: true });

        await tar.x({
            file: tarFilePath,
            cwd: extractDir
        });

        // 3. 查找符合版本号格式的文件夹
        const files = fs.readdirSync(extractDir);
        const versionRegex = /^v?\d+(\.\d+){1,2}$/;
        const versionDir = files.find(file => {
            const filePath = path.join(extractDir, file);
            return fs.lstatSync(filePath).isDirectory() && versionRegex.test(file);
        });

        if (!versionDir) {
            throw new Error('未找到符合版本号格式的文件夹');
        }

        // 4. 查找webapp文件夹
        const versionPath = path.join(extractDir, versionDir);
        const webappPath = path.join(versionPath, 'webapp');

        if (!fs.existsSync(webappPath)) {
            throw new Error('未找到webapp文件夹');
        }

        // 5. 复制webapp文件夹内容到目标目录
        fse.copySync(webappPath, drawioDestDir);

        spinner.succeed('Drawio文件下载并解压完成');

        // 清理临时文件
        fse.removeSync(tempDir);

    } catch (error) {
        console.warn('下载Drawio失败，使用默认版本:', error.message);
        // 清理临时文件
        if (fs.existsSync(tempDir)) {
            fse.removeSync(tempDir);
        }
    }
}

/**
 * 克隆 Drawio
 * @param systemInfo
 */
async function cloneDrawio(systemInfo) {
    child_process.execSync("git submodule update --quiet --init --depth=1", {stdio: "inherit"});
    const drawioSrcDir = path.resolve(__dirname, "../resources/drawio/src/main/webapp");
    const drawioDestDir = path.resolve(electronDir, "drawio/webapp");
    fse.copySync(drawioSrcDir, drawioDestDir)
    await downloadAndExtractDrawio(drawioDestDir);
    const preConfigFile = path.resolve(drawioDestDir, "js/PreConfig.js");
    if (!fs.existsSync(preConfigFile)) {
        console.error("克隆 Drawio 失败!");
        process.exit()
    }
    let preConfigString = fs.readFileSync(preConfigFile, 'utf8');
    preConfigString += "\nwindow.systemInfo = " + JSON.stringify(systemInfo) + ";\n";
    preConfigString += fs.readFileSync(path.resolve(__dirname, "drawio.js"), 'utf8');
    fs.writeFileSync(preConfigFile, preConfigString, 'utf8');
}

/**
 * 获取更新日志
 * @returns {string}
 */
function changeLog() {
    let filePath = path.resolve(__dirname, "../CHANGELOG.md");
    if (!fs.existsSync(filePath)) {
        return "";
    }
    let content = fs.readFileSync(filePath, 'utf8')
    let array = content.match(/## \[([0-9]+.+)\]/g)
    if (!array) {
        return ""
    }
    let start = content.indexOf(array[0]);
    if (array.length > 5) {
        content = content.substring(start, content.indexOf(array[5]))
    } else {
        content = content.substring(start)
    }
    return content;
}

/**
 * 上传单个文件到 R2 的 draft/<version>/ 目录（带进度/spinner）
 */
async function uploadDraftFile(client, localFile, version) {
    const filename = path.basename(localFile);
    const key = `draft/${version}/${filename}`;
    const startTime = Date.now();
    const spinner = ora(`Upload [0%] ${filename}`).start();
    try {
        await r2.uploadFile(client, localFile, key, (loaded, total) => {
            const pct = Math.min(99, Math.round((loaded / total) * 100)) + '%';
            spinner.text = `Upload [${pct}] ${filename}`;
        });
    } catch (error) {
        spinner.fail(`Upload [fail] ${filename} (${elapsedSeconds(startTime)}): ${error.message || error}`);
        throw error;
    }
    spinner.succeed(`Upload [100%] ${filename} (${elapsedSeconds(startTime)})`);
}

/**
 * 生成配置、编译应用
 * @param data
 */
async function startBuild(data) {
    if (BUILD_FRONTEND === 'build' && !buildChecked) {
        buildChecked = true
        fs.mkdirSync(electronDir, { recursive: true });
        fse.copySync(path.resolve(__dirname, "index.html"), path.resolve(electronDir, "index.html"))
        child_process.execSync("npx vite build -- fromcmd electronBuild", {stdio: "inherit"});
    }
    //
    const {platform, archs, publish, release, notarize} = data.configure
    // system info
    const systemInfo = {
        title: data.name,
        debug: "no",
        version: config.version,
        origin: "./",
        homeUrl:  utils.formatUrl(data.url),
        apiUrl:  utils.formatUrl(data.url) + "api/",
    }
    // information
    if (data.id === 'app') {
        console.log("\n=== 编译信息 ===");
        console.log("名称:", data.name);
        console.log("版本:", config.version + ` (${config.codeVerson})`);
        console.log("===============\n");
    } else {
        console.log("\n=== 编译信息 ===");
        console.log("名称:", data.name);
        console.log("应用ID:", data.id);
        console.log("版本:", config.version + ` (${config.codeVerson})`);
        console.log("系统:", platform.replace('build-', '').toUpperCase());
        console.log("架构:", archs.map(arch => arch.toUpperCase()).join(', '));
        console.log("发布:", publish ? `是（${release ? '提示升级' : '静默升级'}）` : '否');
        if (platform === 'build-mac') {
            console.log("公证:", notarize ? '是' : '否');
        }
        console.log("===============\n");
        // drawio
        await cloneDrawio(systemInfo)
        // updater
        if (!updaterChecked) {
            updaterChecked = true
            await detectAndDownloadUpdater()
        }
    }
    // language
    fse.copySync(path.resolve(__dirname, "../public/language"), path.resolve(electronDir, "language"))
    // config.js
    fs.writeFileSync(electronDir + "/config.js", "window.systemInfo = " + JSON.stringify(systemInfo), 'utf8');
    fs.writeFileSync(electronDir + "/dark", '', 'utf8');
    fs.writeFileSync(nativeCachePath, utils.formatUrl(data.url));
    fs.writeFileSync(devloadCachePath, "", 'utf8');
    // index.html
    let manifestFile = path.resolve(electronDir, "manifest.json");
    if (!fs.existsSync(manifestFile)) {
        console.error("manifest.json 未找到");
        return;
    }
    let manifestContent = JSON.parse(fs.readFileSync(manifestFile, 'utf8'));
    let indexFile = path.resolve(electronDir, "index.html");
    let indexString = fs.readFileSync(indexFile, 'utf8');
    indexString = indexString.replace(/<title>(.*?)<\/title>/g, `<title>${data.name}</title>`);
    indexString = indexString.replace("<!--style-->", `<link rel="stylesheet" type="text/css" href="./${manifestContent['resources/assets/js/app.js']['css'][0]}">`);
    indexString = indexString.replace("<!--script-->", `<script type="module" src="./${manifestContent['resources/assets/js/app.js']['file']}"></script>`);
    fs.writeFileSync(indexFile, indexString, 'utf8');
    //
    if (data.id === 'app') {
        const eeuiDir = path.resolve(__dirname, "../resources/mobile");
        const publicDir = path.resolve(__dirname, "../resources/mobile/src/public");
        const containerName = `dootask-eeui-${Date.now()}-${process.pid}`;
        fse.removeSync(publicDir)
        fse.copySync(electronDir, publicDir)
        if (argv[3] === "publish") {
            // Android config
            const gradleFile = path.resolve(eeuiDir, "platforms/android/eeuiApp/local.properties")
            let gradleResult = fs.existsSync(gradleFile) ? fs.readFileSync(gradleFile, 'utf8') : "";
            gradleResult = gradleResult.replace(/(versionCode|versionName)\s*=\s*(.+?)(\n|$)/g, '')
            gradleResult += `versionCode = ${config.codeVerson}\nversionName = ${config.version}\n`
            fs.writeFileSync(gradleFile, gradleResult, 'utf8')
            // iOS config
            const xcconfigFile = path.resolve(eeuiDir, "platforms/ios/eeuiApp/Config/Version.xcconfig")
            let xcconfigResult = fs.existsSync(xcconfigFile) ? fs.readFileSync(xcconfigFile, 'utf8') : "";
            xcconfigResult = xcconfigResult.replace(/(VERSION_CODE|VERSION_NAME)\s*=\s*(.+?)(\n|$)/g, '')
            xcconfigResult += `VERSION_CODE = ${config.codeVerson}\nVERSION_NAME = ${config.version}\n`
            fs.writeFileSync(xcconfigFile, xcconfigResult, 'utf8')
        }
        if (['build', 'publish'].includes(argv[3])) {
            child_process.execSync(
                `docker run -d --name ${containerName} -v ${shellQuote(eeuiDir)}:/work -w /work kuaifan/eeui-cli:0.0.1 sleep infinity`,
                {stdio: "ignore", cwd: "resources/mobile"}
            );
            try {
                if (!fs.existsSync(path.resolve(eeuiDir, "node_modules"))) {
                    child_process.execSync(`docker exec ${containerName} npm install`, {stdio: "inherit", cwd: "resources/mobile"});
                }
                child_process.execSync(`docker exec ${containerName} node /work/scripts/patch-eeui-build.js`, {stdio: "inherit", cwd: "resources/mobile"});
                child_process.execSync(`docker exec ${containerName} eeui build --simple`, {stdio: "inherit", cwd: "resources/mobile"});
            } finally {
                child_process.execSync(`docker rm -f ${containerName}`, {stdio: "ignore", cwd: "resources/mobile"});
            }
        } else {
            [
                path.resolve(publicDir, "../../platforms/ios/eeuiApp/bundlejs/eeui/public"),
                path.resolve(publicDir, "../../platforms/android/eeuiApp/app/src/main/assets/eeui/public"),
            ].some(dir => {
                fse.removeSync(dir)
                fse.copySync(electronDir, dir)
            })
        }
        return;
    }
    const output = `dist/${data.id.replace(/\./g, '-')}/${platform}`
    // package.json Backup
    fse.copySync(packageFile, packageBakFile)
    const recoveryPackage = (onlyRecovery) => {
        fse.copySync(packageBakFile, packageFile)
        if (onlyRecovery !== true) {
            process.exit()
        }
    }
    process.on('exit', recoveryPackage);
    process.on('SIGINT', recoveryPackage);
    process.on('SIGHUP', recoveryPackage);
    // package.json Generated
    const appConfig = require('./package.json')
    let appName = utils.getDomain(data.url)
    if (appName === "public") appName = "DooTask"
    appConfig.name = data.name;
    appConfig.version = config.version;
    appConfig.appId = data.id;
    appConfig.build.appId = data.id;
    appConfig.build.artifactName = appName + "-v${version}-${os}-${arch}.${ext}";
    appConfig.build.nsis.artifactName = appName + "-v${version}-${os}-${arch}.${ext}";
    // changelog
    appConfig.build.releaseInfo.releaseNotes = changeLog()
    if (!release) {
        appConfig.build.releaseInfo.releaseNotes = appConfig.build.releaseInfo.releaseNotes.replace(`## [${config.version}]`, `## [${config.version}-Silence]`)
    }
    // notarize
    if (notarize && APPLEID && APPLEIDPASS) {
        appConfig.build.afterSign = "./notarize.js"
    }
    // archs
    if (archs.length > 0) {
        appConfig.build.mac.target = appConfig.build.mac.target.map(target => {
            if (target.arch) target.arch = target.arch.filter(arch => archs.includes(arch))
            return target
        })
        appConfig.build.win.target = appConfig.build.win.target.map(target => {
            if (target.arch) target.arch = target.arch.filter(arch => archs.includes(arch))
            return target
        })
    }
    // GitHub (build and publish)
    if (publish === true && GITHUB_TOKEN && utils.strExists(GITHUB_REPOSITORY, "/")) {
        const repository = GITHUB_REPOSITORY.split("/")
        appConfig.build.publish = {
            "releaseType": "release",
            "provider": "github",
            "owner": repository[0],
            "repo": repository[1]
        }
        appConfig.build.directories.output = `${output}-github`;
        fs.writeFileSync(packageFile, JSON.stringify(appConfig, null, 4), 'utf8');
        child_process.execSync(`npm run ${platform}-publish`, {stdio: "inherit", cwd: "electron"});
    }
    // generic (build or publish) —— 有 R2_PUBLIC_URL 时自动更新源指向 R2 release/
    appConfig.build.publish = r2.R2_PUBLIC_URL
        ? { provider: 'generic', url: `${r2.R2_PUBLIC_URL.replace(/\/+$/, '')}/release` }
        : data.publish
    appConfig.build.directories.output = `${output}-generic`;
    fs.writeFileSync(packageFile, JSON.stringify(appConfig, null, 4), 'utf8');
    child_process.execSync(`npm run ${platform}`, {stdio: "inherit", cwd: "electron"});
    if (publish === true && r2.r2Configured()) {
        const client = r2.createR2Client()
        const outputDir = path.resolve(__dirname, appConfig.build.directories.output)
        if (fs.existsSync(outputDir)) {
            const files = fs.readdirSync(outputDir)
            for (const filename of files) {
                const localFile = path.join(outputDir, filename)
                if (!fs.statSync(localFile).isFile()) continue
                await uploadDraftFile(client, localFile, config.version)
            }
        }
    }
    // package.json Recovery
    recoveryPackage(true)
}

/** ************************************************************************************/
/** ************************************************************************************/
/** ************************************************************************************/

if (["dev"].includes(argv[2])) {
    // 开发模式
    fs.writeFileSync(devloadCachePath, utils.formatUrl("localhost:" + env.parsed.APP_PORT), 'utf8');
    child_process.spawn("npx", ["vite", "--", "fromcmd", "electronDev"], {stdio: "inherit"});
    child_process.spawn("npm", ["run", "start-quiet"], {stdio: "inherit", cwd: "electron"});
} else if (["app"].includes(argv[2])) {
    // 编译前端页面给 App
    let mobileSrcDir = path.resolve(__dirname, "../resources/mobile");
    if (!fs.existsSync(mobileSrcDir)) {
        console.error("resources/mobile 未找到");
        process.exit()
    }
    startBuild({
        name: 'App',
        id: 'app',
        platform: '',
        url: 'http://public/',
        configure: {
            platform: '',
            archs: [],
            publish: false,
            release: true,
            notarize: false,
        }
    })
} else if (["android-upload"].includes(argv[2])) {
    // 上传安卓文件到 R2 draft（GitHub Actions）
    (async () => {
        if (!r2.r2Configured()) {
            console.error("缺少 R2_* 环境变量（R2_ACCESS_KEY_ID/R2_SECRET_ACCESS_KEY/R2_ENDPOINT/R2_BUCKET）")
            process.exit(1)
        }
        const client = r2.createR2Client()
        const releaseDir = path.resolve(__dirname, "../resources/mobile/platforms/android/eeuiApp/app/build/outputs/apk/release");
        if (!fs.existsSync(releaseDir)) {
            console.error("发布文件未找到")
            process.exit(1)
        }
        const files = fs.readdirSync(releaseDir)
        for (const filename of files) {
            const localFile = path.join(releaseDir, filename)
            if (/\.apk$/.test(filename) && fs.existsSync(localFile) && fs.statSync(localFile).isFile()) {
                await uploadDraftFile(client, localFile, config.version)
            }
        }
    })().catch(err => {
        console.error(err.message || err)
        process.exit(1)
    })
} else if (["release"].includes(argv[2])) {
    // R2 内提升：draft/<version> → release/（当前版扁平，旧版归档 release/<prev>/）
    (async () => {
        if (!r2.r2Configured()) {
            console.error("缺少 R2_* 环境变量")
            process.exit(1)
        }
        const client = r2.createR2Client()
        const version = config.version
        const draftPrefix = `draft/${version}/`
        const draftKeys = await r2.listKeys(client, draftPrefix)
        if (!draftKeys.length) {
            console.error(`draft/${version}/ 为空，无法发布`)
            process.exit(1)
        }
        const names = draftKeys.map(k => k.slice(draftPrefix.length))

        // 读 manifest 取上一发布版
        const manifest = JSON.parse(await r2.getText(client, 'manifest.json') || '{"draft":null,"release":null}')
        const prev = manifest.release

        // 1. 归档上一版扁平文件 → release/<prev>/
        if (prev && prev !== version) {
            const prevRootKeys = await r2.listKeys(client, 'release/', '/')
            for (const key of prevRootKeys) {
                const name = key.slice('release/'.length)
                await r2.copyObject(client, key, `release/${prev}/${name}`)
            }
        }

        // 2. 清空扁平根层（仅根层对象，版本归档子目录不动）
        const rootKeys = await r2.listKeys(client, 'release/', '/')
        await r2.deleteKeys(client, rootKeys)

        // 3. 铺新扁平：安装包/blockmap/zip 先，latest*.yml 最后
        const ymls = names.filter(n => /\.ya?ml$/i.test(n))
        const others = names.filter(n => !/\.ya?ml$/i.test(n))
        for (const name of others) await r2.copyObject(client, `${draftPrefix}${name}`, `release/${name}`)
        for (const name of ymls) await r2.copyObject(client, `${draftPrefix}${name}`, `release/${name}`)

        // 4. 下载索引
        const index = buildReleaseIndex(names)
        await r2.putText(client, 'release/index.json', JSON.stringify({ version, files: index }, null, 2))

        // 5. 更新 manifest，清理 draft
        await r2.putText(client, 'manifest.json', JSON.stringify({ draft: null, release: version }, null, 2))
        await r2.deleteKeys(client, draftKeys)

        console.log(`Release published: v${version}`)
    })().catch(err => {
        console.error(err.message || err)
        process.exit(1)
    })
} else if (["upload-changelog"].includes(argv[2])) {
    // 上传 changelog 到 R2（GitHub Actions）
    (async () => {
        if (!r2.r2Configured()) {
            console.error("缺少 R2_* 环境变量")
            process.exit(1)
        }
        const changelogPath = path.resolve(__dirname, "../CHANGELOG.md")
        if (!fs.existsSync(changelogPath)) {
            console.error("CHANGELOG.md 未找到")
            process.exit(1)
        }
        const client = r2.createR2Client()
        const content = fs.readFileSync(changelogPath, 'utf8')
        await r2.putText(client, 'changelog.md', content)
        console.log('Changelog uploaded')
    })().catch(err => {
        console.error(err.message || err)
        process.exit(1)
    })
} else if (["all", "win", "mac"].includes(argv[2])) {
    // 自动编译（GitHub Actions）
    platforms.filter(p => {
        return argv[2] === "all" || p.indexOf(argv[2]) !== -1
    }).forEach(async platform => {
        for (const data of config.app) {
            data.configure = {
                platform,
                archs: architectures,
                publish: true,
                release: true,
                notarize: false,
            };
            await startBuild(data);
        }
    });
} else {
    // 手编译（默认）
    let cachedConfig = {};
    try {
        const buildConfigPath = path.join(__dirname, '.build');
        if (fs.existsSync(buildConfigPath)) {
            const configContent = fs.readFileSync(buildConfigPath, 'utf-8');
            if (configContent.trim()) {
                cachedConfig = JSON.parse(configContent);
            }
        }
    } catch (error) {
        console.warn('读取缓存配置失败:', error.message);
    }

    const questions = [
        {
            type: 'checkbox',
            name: 'platform',
            message: "选择编译系统",
            choices: [
                {
                    name: "MacOS",
                    value: platforms[0],
                },
                {
                    name: "Windows",
                    value: platforms[1]
                }
            ],
            default: (cachedConfig && cachedConfig.platform) || [],
            validate: (answer) => {
                if (answer.length < 1) {
                    return '请至少选择一个系统';
                }
                return true;
            }
        },
        {
            type: 'checkbox',
            name: 'arch',
            message: "选择系统架构",
            choices: ({platform}) => {
                const array = [
                    {
                        name: "arm64",
                        value: architectures[0],
                    },
                    {
                        name: "x64",
                        value: architectures[1]
                    }
                ]
                if (platform.find(item => item === 'build-mac')) {
                    array.push({
                        name: "通用" + (platform.length > 1 ? " (仅MacOS)" : ""),
                        value: 'universal'
                    })
                }
                return array;
            },
            default: (cachedConfig && cachedConfig.arch) || [],
            validate: (answer) => {
                if (answer.length < 1) {
                    return '请至少选择一个架构';
                }
                return true;
            }
        },
        {
            type: 'list',
            name: 'publish',
            message: "选择是否发布",
            choices: [{
                name: "否",
                value: false
            }, {
                name: "是",
                value: true
            }],
            default: (cachedConfig && cachedConfig.publish !== undefined) ?
                (cachedConfig.publish ? 1 : 0) : 0
        },
        {
            type: 'list',
            name: 'release',
            message: "选择升级方式",
            when: ({publish}) => publish,
            choices: [{
                name: "弹出提示",
                value: true
            }, {
                name: "静默",
                value: false
            }],
            default: (cachedConfig && cachedConfig.release !== undefined) ?
                (cachedConfig.release ? 0 : 1) : 0
        },
        {
            type: 'list',
            name: 'notarize',
            message: ({platform}) => platform.length > 1 ? "选择是否公证（仅MacOS）" : "选择是否公证",
            when: ({platform}) => platform.find(item => item === 'build-mac'),
            choices: [{
                name: "否",
                value: false
            }, {
                name: "是",
                value: true
            }],
            default: (cachedConfig && cachedConfig.notarize !== undefined) ?
                (cachedConfig.notarize ? 1 : 0) : 0
        }
    ];

    // 开始提问
    const prompt = inquirer.createPromptModule();
    prompt(questions)
        .then(async answers => {
            answers = Object.assign({
                release: false,
                notarize: false
            }, answers);

            // 缓存当前配置
            try {
                fs.writeFileSync(path.join(__dirname, '.build'), JSON.stringify(answers, null, 4), 'utf-8');
            } catch (error) {
                console.warn('保存配置缓存失败:', error.message);
            }

            // 发布判断环境变量
            if (answers.publish) {
                if (!r2.r2Configured() && !(GITHUB_TOKEN && utils.strExists(GITHUB_REPOSITORY, "/"))) {
                    console.error("发布需要 R2_* 或 GITHUB_TOKEN + GITHUB_REPOSITORY, 请检查环境变量!");
                    process.exit()
                }
            }

            // 公证判断环境变量
            if (answers.notarize === true) {
                if (!APPLEID || !APPLEIDPASS) {
                    console.error("公证需要 Apple ID 和 Apple ID 密码, 请检查环境变量!");
                    process.exit()
                }
            }

            // 开始构建
            for (const platform of answers.platform) {
                for (const data of config.app) {
                    data.configure = {
                        platform,
                        archs: answers.arch,
                        publish: answers.publish,
                        release: answers.release,
                        notarize: answers.notarize
                    };
                    await startBuild(data);
                }
            }
        })
        .catch(_ => { });
}
