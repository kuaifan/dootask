const fs = require('fs');
const fse = require('fs-extra');
const path = require('path')
const inquirer = require('inquirer');
const child_process = require('child_process');
const ora = require('ora');
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
function startBuild(data) {
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
    // 编译给app
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
    }, false, false)
} else if (["android-upload"].includes(argv[2])) {
    config.app.forEach(({publish}) => {
        if (publish.provider === 'generic') {
            androidUpload(publish.url)
        }
    })
} else if (["all", "win", "mac"].includes(argv[2])) {
    // 自动编译
    platforms.filter(p => {
        return argv[2] === "all" || p.indexOf(argv[2]) !== -1
    }).forEach(platform => {
        config.app.forEach(data => {
            data.configure = {
                platform,
                publish: true,
                release: true,
                notarize: false,
            }
            startBuild(data)
        })
    })
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
    inquirer.prompt(questions).then(answers => {
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
        answers.platforms.forEach(platform => {
            config.app.forEach(data => {
                data.configure = answers
                data.configure.platform = platform
                startBuild(data)
            })
        });
    });
}
