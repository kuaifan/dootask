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
const platform = ["build-mac", "build-win"];
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
    if (!fse.existsSync(preConfigFile)) {
        console.log("clone drawio error!");
        process.exit()
    }
    let preConfigString = fs.readFileSync(preConfigFile, 'utf8');
    preConfigString += "\nwindow.systemInfo = " + JSON.stringify(systemInfo) + ";\n";
    preConfigString += fs.readFileSync(path.resolve(__dirname, "drawio.js"), 'utf8');
    fs.writeFileSync(preConfigFile, preConfigString, 'utf8');
}

// 通用发布
function genericPublish(url, version) {
    const filePath = path.resolve(__dirname, "dist")
    fs.readdir(filePath, async (err, files) => {
        if (err) {
            console.warn(err)
        } else {
            for (const filename of files) {
                const localFile = path.join(filePath, filename)
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
                            'Generic-Version': version,
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
    });
}

// 生成配置、编译应用
function startBuild(data, publish) {
    // information
    console.log("Name: " + data.name);
    console.log("AppId: " + data.id);
    console.log("Version: " + config.version);
    console.log("Publish: " + (publish ? 'Yes' : 'No'));
    let systemInfo = {
        title: data.name,
        version: config.version,
        origin: "./",
        homeUrl:  utils.formatUrl(data.url),
        apiUrl:  utils.formatUrl(data.url) + "api/",
    }
    // drawio
    cloneDrawio(systemInfo)
    // config.js
    fs.writeFileSync(electronDir + "/config.js", "window.systemInfo = " + JSON.stringify(systemInfo), 'utf8');
    fs.writeFileSync(nativeCachePath, utils.formatUrl(data.url));
    fs.writeFileSync(devloadCachePath, "", 'utf8');
    // index.html
    let indexFile = path.resolve(electronDir, "index.html");
    let indexString = fs.readFileSync(indexFile, 'utf8');
    indexString = indexString.replace(/<title>(.*?)<\/title>/g, `<title>${data.name}</title>`);
    fs.writeFileSync(indexFile, indexString, 'utf8');
    // package.json Backup
    fse.copySync(packageFile, packageBakFile)
    // package.json Generated
    const econfig = require('./package.json')
    econfig.name = data.name;
    econfig.version = config.version;
    econfig.build.appId = data.id;
    econfig.build.artifactName = utils.getDomain(data.url) + "-v${version}-${os}-${arch}.${ext}";
    econfig.build.nsis.artifactName = utils.getDomain(data.url) + "-v${version}-${os}-${arch}.${ext}";
    econfig.build.pkg.mustClose = [data.id];
    if (!process.env.APPLEID || !process.env.APPLEIDPASS) {
        delete econfig.build.afterSign;
    }
    if (process.env.RELEASE_BODY) {
        econfig.build.releaseInfo.releaseNotes = process.env.RELEASE_BODY
    }
    if (utils.isJson(data.publish)) {
        econfig.build.publish = data.publish
    }
    fs.writeFileSync(packageFile, JSON.stringify(econfig, null, 2), 'utf8');
    // build
    child_process.spawnSync("npm" + comSuffix, ["run", data.platform + (publish === true ? "-publish" : "")], {stdio: "inherit", cwd: "electron"});
    // package.json Recovery
    fse.copySync(packageBakFile, packageFile)
    // generic publish
    if (publish === true && econfig.build.publish.provider === "generic") {
        genericPublish(econfig.build.publish.url, config.version)
    }
}

if (["dev"].includes(argv[2])) {
    // 开发模式
    fs.writeFileSync(devloadCachePath, utils.formatUrl("127.0.0.1:" + env.parsed.APP_PORT), 'utf8');
    child_process.spawn("npx", ["mix", "watch", "--hot", "--", "--env", "--electron"], {stdio: "inherit"});
    child_process.spawn("npm", ["run", "start-quiet"], {stdio: "inherit", cwd: "electron"});
} else if (platform.includes(argv[2])) {
    // 自动编译
    let provider = process.env.PROVIDER === "generic" ? "generic" : "github"
    config.app.forEach(data => {
        if (data.publish.provider === provider) {
            data.platform = argv[2];
            startBuild(data, true)
        }
    })
} else {
    // 自定义编译
    let appChoices = [];
    config.app.forEach(data => {
        appChoices.push({
            name: data.name,
            value: data
        })
    })
    const questions = [
        {
            type: 'list',
            name: 'app',
            message: "选择编译应用",
            choices: appChoices
        },
        {
            type: 'list',
            name: 'platform',
            message: "选择编译系统",
            choices: [{
                name: "MacOS",
                value: [platform[0]]
            }, {
                name: "Window",
                value: [platform[1]]
            }, {
                name: "All platforms",
                value: platform
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
        }
    ];
    inquirer.prompt(questions).then(answers => {
        answers.platform.forEach(platform => {
            let data = answers.app;
            data.platform = platform
            startBuild(data, answers.publish)
        });
    });
}
