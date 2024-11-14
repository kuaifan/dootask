const fs = require('fs');
const fse = require('fs-extra');
const path = require('path')
const inquirer = require('inquirer');
const child_process = require('child_process');
const ora = require('ora');
const yauzl = require('yauzl');
const axios = require('axios');
const FormData =require('form-data');
const utils = require('./utils');
const config = require('../package.json')
const env = require('dotenv').config({ path: './.env' })
const argv = process.argv;
const {APPLEID, APPLEIDPASS, GH_TOKEN, GH_REPOSITORY, DP_KEY} = process.env;

const electronDir = path.resolve(__dirname, "public");
const nativeCachePath = path.resolve(__dirname, ".native");
const devloadCachePath = path.resolve(__dirname, ".devload");
const packageFile = path.resolve(__dirname, "package.json");
const packageBakFile = path.resolve(__dirname, "package-bak.json");
const platforms = ["build-mac", "build-win"];

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
        const spinner = ora('Fetching latest updater release...').start();
        const response = await axios.get('https://api.github.com/repos/kuaifan/dootask-updater/releases/latest', {
            headers: GH_TOKEN ? { 'Authorization': `token ${GH_TOKEN}` } : {}
        });
        
        if (!response.data || !response.data.assets) {
            spinner.fail('Failed to fetch updater release info');
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
                    fs.rmdirSync(filePath, { recursive: true });
                } else {
                    fs.unlinkSync(filePath);
                }
            }
        }

        // 过滤出binary开头的zip文件
        const assets = response.data.assets.filter(asset => 
            asset.name.startsWith('binary_') && asset.name.endsWith('.zip')
        );

        spinner.succeed('Found updater release files');

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
            const downloadSpinner = ora(`Downloading ${fileName}...`).start();
            try {
                const writer = fs.createWriteStream(zipPath);
                const response = await axios({
                    url: asset.browser_download_url,
                    method: 'GET',
                    responseType: 'stream',
                    headers: GH_TOKEN ? { 'Authorization': `token ${GH_TOKEN}` } : {}
                });

                response.data.pipe(writer);

                await new Promise((resolve, reject) => {
                    writer.on('finish', resolve);
                    writer.on('error', reject);
                });

                // 解压文件
                downloadSpinner.text = `Extracting ${fileName}...`;
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
                downloadSpinner.succeed(`Downloaded and extracted ${fileName}`);

                // 下载和解压成功后，保存最新版本号
                fs.writeFileSync(latestVersionFile, latestVersion, 'utf8');

            } catch (error) {
                downloadSpinner.fail(`Failed to download ${fileName}: ${error.message}`);
                // 清理失败的下载
                if (fs.existsSync(zipPath)) {
                    fs.unlinkSync(zipPath);
                }
                if (fs.existsSync(targetDir)) {
                    fs.rmdirSync(targetDir, { recursive: true });
                }
            }
        }

    } catch (error) {
        console.error('Failed to check updater:', error.message);
    }
}

/**
 * 克隆 Drawio
 * @param systemInfo
 */
function cloneDrawio(systemInfo) {
    child_process.execSync("git submodule update --quiet --init --depth=1", {stdio: "inherit"});
    const drawioSrcDir = path.resolve(__dirname, "../resources/drawio/src/main/webapp");
    const drawioCoverDir = path.resolve(__dirname, "../docker/drawio/webapp");
    const drawioDestDir = path.resolve(electronDir, "drawio/webapp");
    fse.copySync(drawioSrcDir, drawioDestDir)
    fse.copySync(drawioCoverDir, drawioDestDir)
    //
    const preConfigFile = path.resolve(drawioDestDir, "js/PreConfig.js");
    if (!fs.existsSync(preConfigFile)) {
        console.error("Clone Drawio error!");
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
 * 封装 axios 自动重试
 * @param data // {axios: object{}, onRetry: function, retryNumber: number}
 * @returns {Promise<unknown>}
 */
function axiosAutoTry(data) {
    return new Promise((resolve, reject) => {
        axios(data.axios).then(result => {
            resolve(result)
        }).catch(error => {
            if (typeof data.retryNumber == 'number' && data.retryNumber > 0) {
                data.retryNumber--;
                if (typeof data.onRetry === "function") {
                    data.onRetry()
                }
                if (error.code == 'ECONNABORTED' || error.code == 'ECONNRESET') {
                    // 中止，超时
                    return resolve(axiosAutoTry(data))
                } else {
                    if (error.response && error.response.status == 407) {
                        // 代理407
                        return setTimeout(v => {
                            resolve(axiosAutoTry(data))
                        }, 500 + Math.random() * 500)
                    } else if (error.response && error.response.status == 503) {
                        // 服务器异常
                        return setTimeout(v => {
                            resolve(axiosAutoTry(data))
                        }, 1000 + Math.random() * 500)
                    } else if (error.response && error.response.status == 429) {
                        // 并发超过限制
                        return setTimeout(v => {
                            resolve(axiosAutoTry(data))
                        }, 1000 + Math.random() * 1000)
                    }
                }
            }
            reject(error)
        })
    })
}

/**
 * 上传app应用
 * @param url
 */
function androidUpload(url) {
    if (!DP_KEY) {
        console.error("Missing Deploy Key or GitHub Token and Repository!");
        process.exit()
    }
    const releaseDir = path.resolve(__dirname, "../resources/mobile/platforms/android/eeuiApp/app/build/outputs/apk/release");
    if (!fs.existsSync(releaseDir)) {
        console.error("Release not found");
        process.exit()
    }
    fs.readdir(releaseDir, async (err, files) => {
        if (err) {
            console.warn(err)
        } else {
            const uploadOras = {}
            for (const filename of files) {
                const localFile = path.join(releaseDir, filename)
                if (/\.apk$/.test(filename) && fs.existsSync(localFile)) {
                    const fileStat = fs.statSync(localFile)
                    if (fileStat.isFile()) {
                        uploadOras[filename] = ora(`Upload [0%] ${filename}`).start()
                        const formData = new FormData()
                        formData.append("file", fs.createReadStream(localFile));
                        formData.append("file_num", 1);
                        await axiosAutoTry({
                            axios: {
                                method: 'post',
                                url: url,
                                data: formData,
                                headers: {
                                    'Publish-Version': config.version,
                                    'Publish-Key': DP_KEY,
                                    'Content-Type': 'multipart/form-data;boundary=' + formData.getBoundary(),
                                },
                                onUploadProgress: progress => {
                                    const complete = Math.min(99, Math.round(progress.loaded / progress.total * 100 | 0)) + '%'
                                    uploadOras[filename].text = `Upload [${complete}] ${filename}`
                                },
                            },
                            onRetry: _ => {
                                uploadOras[filename].warn(`Upload [retry] ${filename}`)
                                uploadOras[filename] = ora(`Upload [0%] ${filename}`).start()
                            },
                            retryNumber: 3
                        }).then(({status, data}) => {
                            if (status !== 200) {
                                uploadOras[filename].fail(`Upload [fail:${status}] ${filename}`)
                                return
                            }
                            if (!utils.isJson(data)) {
                                uploadOras[filename].fail(`Upload [fail:not json] ${filename}`)
                                return
                            }
                            if (data.ret !== 1) {
                                uploadOras[filename].fail(`Upload [fail:ret ${data.ret}] ${filename}`)
                                return
                            }
                            uploadOras[filename].succeed(`Upload [100%] ${filename}`)
                        }).catch(_ => {
                            uploadOras[filename].fail(`Upload [fail] ${filename}`)
                        })
                    }
                }
            }
        }
    });
}

/**
 * 通用发布
 * @param url
 * @param key
 * @param version
 * @param output
 */
function genericPublish({url, key, version, output}) {
    if (!/https*:\/\//i.test(url)) {
        console.warn("Publish url is invalid: " + url)
        return
    }
    const filePath = path.resolve(__dirname, output)
    if (!fs.existsSync(filePath)) {
        console.warn("Publish output not found: " + filePath)
        return
    }
    fs.readdir(filePath, async (err, files) => {
        if (err) {
            console.warn(err)
        } else {
            let uploadFileNum = 0;
            for (const filename of files) {
                const localFile = path.join(filePath, filename)
                if (fs.existsSync(localFile)) {
                    const fileStat = fs.statSync(localFile)
                    if (fileStat.isFile()) {
                        uploadFileNum += 1;
                    }
                }
            }
            const uploadOras = {}
            for (const filename of files) {
                const localFile = path.join(filePath, filename)
                if (fs.existsSync(localFile)) {
                    const fileStat = fs.statSync(localFile)
                    if (fileStat.isFile()) {
                        uploadOras[filename] = ora(`Upload [0%] ${filename}`).start()
                        const formData = new FormData()
                        formData.append("file", fs.createReadStream(localFile));
                        formData.append("file_num", uploadFileNum);
                        await axiosAutoTry({
                            axios: {
                                method: 'post',
                                url: url,
                                data: formData,
                                headers: {
                                    'Publish-Version': version,
                                    'Publish-Key': key,
                                    'Content-Type': 'multipart/form-data;boundary=' + formData.getBoundary(),
                                },
                                onUploadProgress: progress => {
                                    const complete = Math.min(99, Math.round(progress.loaded / progress.total * 100 | 0)) + '%'
                                    uploadOras[filename].text = `Upload [${complete}] ${filename}`
                                },
                            },
                            onRetry: _ => {
                                uploadOras[filename].warn(`Upload [retry] ${filename}`)
                                uploadOras[filename] = ora(`Upload [0%] ${filename}`).start()
                            },
                            retryNumber: 3
                        }).then(({status, data}) => {
                            if (status !== 200) {
                                uploadOras[filename].fail(`Upload [fail:${status}] ${filename}`)
                                return
                            }
                            if (!utils.isJson(data)) {
                                uploadOras[filename].fail(`Upload [fail:not json] ${filename}`)
                                return
                            }
                            if (data.ret !== 1) {
                                uploadOras[filename].fail(`Upload [fail:ret ${data.ret}] ${filename}`)
                                return
                            }
                            uploadOras[filename].succeed(`Upload [100%] ${filename}`)
                        }).catch(_ => {
                            uploadOras[filename].fail(`Upload [fail] ${filename}`)
                        })
                    }
                }
            }
        }
    });
}

/**
 * 生成配置、编译应用
 * @param data
 */
async function startBuild(data) {
    const {platform, publish, release, notarize} = data.configure
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
        console.log("Name: " + data.name);
        console.log("Version: " + config.version);
    } else {
        console.log("Name: " + data.name);
        console.log("AppId: " + data.id);
        console.log("Version: " + config.version);
        console.log("Platform: " + platform);
        console.log("Publish: " + (publish ? 'Yes' : 'No'));
        console.log("Release: " + (release ? 'Yes' : 'No'));
        console.log("Notarize: " + (notarize ? 'Yes' : 'No'));
        // drawio
        cloneDrawio(systemInfo)
        // detect and download updater
        await detectAndDownloadUpdater()
    }
    // language
    fse.copySync(path.resolve(__dirname, "../public/language"), path.resolve(electronDir, "language"))
    // config.js
    fs.writeFileSync(electronDir + "/config.js", "window.systemInfo = " + JSON.stringify(systemInfo), 'utf8');
    fs.writeFileSync(nativeCachePath, utils.formatUrl(data.url));
    fs.writeFileSync(devloadCachePath, "", 'utf8');
    // index.html
    let manifestFile = path.resolve(electronDir, "manifest.json");
    if (!fs.existsSync(manifestFile)) {
        console.error("manifest.json not found");
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
        const eeuiRun = `--rm -v ${eeuiDir}:/work -w /work kuaifan/eeui-cli:0.0.1`
        const publicDir = path.resolve(__dirname, "../resources/mobile/src/public");
        fse.removeSync(publicDir)
        fse.copySync(electronDir, publicDir)
        if (argv[3] === "setting") {
            child_process.execSync(`docker run -it ${eeuiRun} eeui setting`, {stdio: "inherit", cwd: "resources/mobile"});
        }
        if (argv[3] === "publish") {
            const gradleFile = path.resolve(eeuiDir, "platforms/android/eeuiApp/build.gradle")
            if (fs.existsSync(gradleFile)) {
                let gradleResult = fs.readFileSync(gradleFile, 'utf8')
                gradleResult = gradleResult.replace(/versionCode\s*=\s*(.+?)(\n|$)/, `versionCode = ${config.codeVerson}\n`)
                gradleResult = gradleResult.replace(/versionName\s*=\s*(.+?)(\n|$)/, `versionName = "${config.version}"\n`)
                fs.writeFileSync(gradleFile, gradleResult, 'utf8')
            }
            const xcconfigFile = path.resolve(eeuiDir, "platforms/ios/eeuiApp/Config/IdentityConfig.xcconfig")
            if (fs.existsSync(xcconfigFile)) {
                let xcconfigResult = fs.readFileSync(xcconfigFile, 'utf8')
                xcconfigResult = xcconfigResult.replace(/BASE_CODE_VERSON\s*=\s*(.+?)(\n|$)/, `BASE_CODE_VERSON = ${config.codeVerson}\n`)
                xcconfigResult = xcconfigResult.replace(/BASE_SHORT_VERSON\s*=\s*(.+?)(\n|$)/, `BASE_SHORT_VERSON = ${config.version}\n`)
                fs.writeFileSync(xcconfigFile, xcconfigResult, 'utf8')
            }
        }
        if (['setting', 'build', 'publish'].includes(argv[3])) {
            if (!fs.existsSync(path.resolve(eeuiDir, "node_modules"))) {
                child_process.execSync(`docker run ${eeuiRun} npm install`, {stdio: "inherit", cwd: "resources/mobile"});
            }
            child_process.execSync(`docker run ${eeuiRun} eeui build --simple`, {stdio: "inherit", cwd: "resources/mobile"});
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
    const econfig = require('./package.json')
    let appName = utils.getDomain(data.url)
    if (appName === "public") appName = "DooTask"
    econfig.name = data.name;
    econfig.version = config.version;
    econfig.build.appId = data.id;
    econfig.build.artifactName = appName + "-v${version}-${os}-${arch}.${ext}";
    econfig.build.nsis.artifactName = appName + "-v${version}-${os}-${arch}.${ext}";
    // changelog
    econfig.build.releaseInfo.releaseNotes = changeLog()
    if (!release) {
        econfig.build.releaseInfo.releaseNotes = econfig.build.releaseInfo.releaseNotes.replace(`## [${config.version}]`, `## [${config.version}-Silence]`)
    }
    // darwin notarize
    if (notarize && APPLEID && APPLEIDPASS) {
        econfig.build.afterSign = "./notarize.js"
    }
    // github (build && publish)
    if (publish === true && GH_TOKEN && utils.strExists(GH_REPOSITORY, "/")) {
        const repository = GH_REPOSITORY.split("/")
        econfig.build.publish = {
            "releaseType": "release",
            "provider": "github",
            "owner": repository[0],
            "repo": repository[1]
        }
        econfig.build.directories.output = `${output}-github`;
        fs.writeFileSync(packageFile, JSON.stringify(econfig, null, 2), 'utf8');
        child_process.execSync(`npm run ${platform}-publish`, {stdio: "inherit", cwd: "electron"});
    }
    // generic (build || publish)
    econfig.build.publish = data.publish
    econfig.build.directories.output = `${output}-generic`;
    fs.writeFileSync(packageFile, JSON.stringify(econfig, null, 2), 'utf8');
    child_process.execSync(`npm run ${platform}`, {stdio: "inherit", cwd: "electron"});
    if (publish === true && DP_KEY) {
        genericPublish({
            url: econfig.build.publish.url,
            key: DP_KEY,
            version: config.version,
            output: econfig.build.directories.output
        })
    }
    // package.json Recovery
    recoveryPackage(true)
}

/** ************************************************************************************/
/** ************************************************************************************/
/** ************************************************************************************/

if (["dev"].includes(argv[2])) {
    // 开发模式
    fs.writeFileSync(devloadCachePath, utils.formatUrl("127.0.0.1:" + env.parsed.APP_PORT), 'utf8');
    child_process.spawn("npx", ["vite", "--", "fromcmd", "electronDev"], {stdio: "inherit"});
    child_process.spawn("npm", ["run", "start-quiet"], {stdio: "inherit", cwd: "electron"});
} else if (["app"].includes(argv[2])) {
    // 编译前端页面给 App
    let mobileSrcDir = path.resolve(__dirname, "../resources/mobile");
    if (!fs.existsSync(mobileSrcDir)) {
        console.error("resources/mobile not found");
        process.exit()
    }
    startBuild({
        name: 'App',
        id: 'app',
        platform: '',
        url: 'http://public/',
        configure: {
            platform: '',
            publish: false,
            release: true,
            notarize: false,
        }
    })
} else if (["android-upload"].includes(argv[2])) {
    // 上传安卓文件（GitHub Actions）
    config.app.forEach(({publish}) => {
        if (publish.provider === 'generic') {
            androidUpload(publish.url)
        }
    })
} else if (["all", "win", "mac"].includes(argv[2])) {
    // 自动编译（GitHub Actions）
    platforms.filter(p => {
        return argv[2] === "all" || p.indexOf(argv[2]) !== -1
    }).forEach(async platform => {
        for (const data of config.app) {
            data.configure = {
                platform,
                publish: true,
                release: true,
                notarize: false,
            };
            await startBuild(data);
        }
    });
} else {
    // 手动编译（默认）
    const questions = [
        {
            type: 'list',
            name: 'platforms',
            message: "选择编译系统",
            choices: [{
                name: "MacOS",
                value: [platforms[0]]
            }, {
                name: "Window",
                value: [platforms[1]]
            }, {
                name: "All platforms",
                value: platforms
            }]
        },
        {
            type: 'list',
            name: 'publish',
            message: "选择是否要发布",
            choices: [{
                name: "No",
                value: false
            }, {
                name: "Yes",
                value: true
            }]
        },
        {
            type: 'list',
            name: 'release',
            message: "选择是否弹出升级提示框",
            choices: [{
                name: "Yes",
                value: true
            }, {
                name: "No",
                value: false
            }]
        },
        {
            type: 'list',
            name: 'notarize',
            message: "选择是否需要公证MacOS应用",
            choices: [{
                name: "No",
                value: false
            }, {
                name: "Yes",
                value: true
            }]
        }
    ];
    inquirer.prompt(questions).then(async answers => {
        if (answers.publish === true) {
            if (!DP_KEY && (!GH_TOKEN || !utils.strExists(GH_REPOSITORY, "/"))) {
                console.error("Missing Deploy Key or GitHub Token and Repository!");
                process.exit()
            }
        }
        if (answers.notarize === true) {
            if (!APPLEID || !APPLEIDPASS) {
                console.error("Missing Apple ID or Apple ID password!");
                process.exit()
            }
        }
        for (const platform of answers.platforms) {
            for (const data of config.app) {
                data.configure = answers;
                data.configure.platform = platform;
                await startBuild(data);
            }
        }
    });
}
