const os = require('os')
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
const argv = process.argv;
const env = require('dotenv').config({ path: './.env' })

const electronDir = path.resolve(__dirname, "public");
const nativeCachePath = path.resolve(__dirname, ".native");
const devloadCachePath = path.resolve(__dirname, ".devload");
const packageFile = path.resolve(__dirname, "package.json");
const packageBakFile = path.resolve(__dirname, "package-bak.json");
const platforms = ["build-mac", "build-win"];
const comSuffix = os.type() == 'Windows_NT' ? '.cmd' : '';

// 克隆 Drawio
function cloneDrawio(systemInfo) {
    child_process.spawnSync("git", ["submodule", "update", "--quiet", "--init", "--depth=1"], {stdio: "inherit"});
    const drawioSrcDir = path.resolve(__dirname, "../resources/drawio/src/main/webapp");
    const drawioCoverDir = path.resolve(__dirname, "../docker/drawio/webapp");
    const drawioDestDir = path.resolve(electronDir, "drawio/webapp");
    fse.copySync(drawioSrcDir, drawioDestDir)
    fse.copySync(drawioCoverDir, drawioDestDir)
    //
    const preConfigFile = path.resolve(drawioDestDir, "js/PreConfig.js");
    if (!fs.existsSync(preConfigFile)) {
        console.log("clone drawio error!");
        process.exit()
    }
    let preConfigString = fs.readFileSync(preConfigFile, 'utf8');
    preConfigString += "\nwindow.systemInfo = " + JSON.stringify(systemInfo) + ";\n";
    preConfigString += fs.readFileSync(path.resolve(__dirname, "drawio.js"), 'utf8');
    fs.writeFileSync(preConfigFile, preConfigString, 'utf8');
}

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

// 通用发布
function genericPublish({url, key, version, output}) {
    if (!/https*:\/\//i.test(url)) {
        console.warn("publish url error: " + url)
        return
    }
    const filePath = path.resolve(__dirname, output)
    fs.readdir(filePath, async (err, files) => {
        if (err) {
            console.warn(err)
        } else {
            for (const filename of files) {
                const localFile = path.join(filePath, filename)
                if (fs.existsSync(localFile)) {
                    const fileStat = fs.statSync(localFile)
                    if (fileStat.isFile()) {
                        const uploadOra = ora(`${filename} uploading...`).start()
                        const formData = new FormData()
                        formData.append("file", fs.createReadStream(localFile));
                        await axios({
                            method: 'post',
                            url: url,
                            data: formData,
                            maxContentLength: Infinity,
                            maxBodyLength: Infinity,
                            headers: {
                                'Publish-Version': version,
                                'Publish-Key': key,
                                'Content-Type': 'multipart/form-data;boundary=' + formData.getBoundary(),
                            }
                        }).then(_ => {
                            uploadOra.succeed(`${filename} upload successful`)
                        }).catch(_ => {
                            uploadOra.fail(`${filename} upload fail`)
                        })
                    }
                }
            }
        }
    });
}

// 生成配置、编译应用
function startBuild(data, publish, release) {
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
        console.log("Platform: " + data.platform);
        console.log("Publish: " + (publish ? 'Yes' : 'No'));
        console.log("Release: " + (release ? 'Yes' : 'No'));
        // drawio
        cloneDrawio(systemInfo)
    }
    // language
    fse.copySync(path.resolve(__dirname, "../public/language"), path.resolve(electronDir, "language"))
    // config.js
    fs.writeFileSync(electronDir + "/config.js", "window.systemInfo = " + JSON.stringify(systemInfo), 'utf8');
    fs.writeFileSync(nativeCachePath, utils.formatUrl(data.url));
    fs.writeFileSync(devloadCachePath, "", 'utf8');
    // default (解决 Failed to load resource: net::ERR_FILE_NOT_FOUND 报错)
    fs.writeFileSync(electronDir + "/default", "default", 'utf8');
    // index.html
    let manifestFile = path.resolve(electronDir, "manifest.json");
    if (!fs.existsSync(manifestFile)) {
        console.log("manifest non-existent!");
        process.exit()
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
        const publicDir = path.resolve(__dirname, "../resources/mobile/src/public");
        fse.removeSync(publicDir)
        fse.copySync(electronDir, publicDir)
        if (argv[3] === "setting") {
            child_process.spawnSync("eeui", ["setting"], {stdio: "inherit", cwd: "resources/mobile"});
        }
        child_process.spawnSync("eeui", ["build", "--simple"], {stdio: "inherit", cwd: "resources/mobile"});
        return;
    }
    // package.json Backup
    fse.copySync(packageFile, packageBakFile)
    // package.json Generated
    const econfig = require('./package.json')
    let appName = utils.getDomain(data.url)
    if (appName === "public") appName = "DooTask"
    econfig.name = data.name;
    econfig.version = config.version;
    econfig.build.appId = data.id;
    econfig.build.directories.output = `dist/${data.id.replace(/\./g, '-')}/${data.platform}`;
    econfig.build.artifactName = appName + "-v${version}-${os}-${arch}.${ext}";
    econfig.build.nsis.artifactName = appName + "-v${version}-${os}-${arch}.${ext}";
    econfig.build.releaseInfo.releaseNotes = changeLog()
    if (release) {
        econfig.build.releaseInfo.releaseNotes = econfig.build.releaseInfo.releaseNotes.replace(`## [${config.version}]`, `## [${config.version}-Release]`)
    }
    if (publish !== true || !process.env.APPLEID || !process.env.APPLEIDPASS) {
        delete econfig.build.afterSign;
    }
    fs.writeFileSync(packageFile, JSON.stringify(econfig, null, 2), 'utf8');
    // build
    child_process.spawnSync("npm" + comSuffix, ["run", data.platform], {stdio: "inherit", cwd: "electron"});
    // package.json Recovery
    fse.copySync(packageBakFile, packageFile)
    // publish
    if (publish === true) {
        // generic
        if (process.env.DP_KEY) {
            genericPublish({
                url: data.publish,
                key: process.env.DP_KEY,
                version: config.version,
                output: econfig.build.directories.output
            })
        }
    }
}

if (["dev"].includes(argv[2])) {
    // 开发模式
    fs.writeFileSync(devloadCachePath, utils.formatUrl("127.0.0.1:" + env.parsed.APP_PORT), 'utf8');
    child_process.spawn("npx", ["vite", "--", "fromcmd", "electronDev"], {stdio: "inherit"});
    child_process.spawn("npm", ["run", "start-quiet"], {stdio: "inherit", cwd: "electron"});
} else if (["app"].includes(argv[2])) {
    // 编译给app
    let mobileSrcDir = path.resolve(__dirname, "../resources/mobile");
    if (!fs.existsSync(mobileSrcDir)) {
        console.log("mobile directory does not exist!");
        process.exit()
    }
    startBuild({
        name: 'App',
        id: 'app',
        platform: '',
        url: 'http://public/',
    }, false, false)
} else if (["workflows"].includes(argv[2])) {
    // 自动编译
    platforms.forEach(platform => {
        config.app.forEach(data => {
            data.platform = platform
            startBuild(data, true, false)
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
                name: "No",
                value: false
            }, {
                name: "Yes",
                value: true
            }]
        }
    ];
    inquirer.prompt(questions).then(answers => {
        answers.platforms.forEach(platform => {
            config.app.forEach(data => {
                data.platform = platform
                startBuild(data, answers.publish, answers.release)
            })
        });
    });
}
