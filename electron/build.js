const fs = require('fs');
const fse = require('fs-extra');
const path = require('path')
const inquirer = require('inquirer');
const child_process = require('child_process');
const utils = require('./utils');
const config = require('../package.json')
const argv = process.argv;
const env = require('dotenv').config({ path: './.env' })

const electronDir = path.resolve(__dirname, "public");
const nativeCachePath = path.resolve(__dirname, ".native");
const devloadCachePath = path.resolve(__dirname, ".devload");
const packageFile = path.resolve(__dirname, "package.json");
const packageBakFile = path.resolve(__dirname, "package-bak.json");
const platform = ["build-mac", "build-mac-arm", "build-win"];

// 生成配置、编译应用
function startBuild(data, publish) {
    console.log("Name: " + data.name);
    console.log("AppId: " + data.id);
    console.log("Version: " + config.version);
    // config.js
    let systemInfo = {
        title: data.name,
        version: config.version,
        origin: "./",
        apiUrl:  utils.formatUrl(data.url) + "api/",
    }
    fs.writeFileSync(electronDir + "/config.js", "window.systemInfo = " + JSON.stringify(systemInfo, null, 2), 'utf8');
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
    fs.writeFileSync(packageFile, JSON.stringify(econfig, null, 2), 'utf8');
    // build
    child_process.spawnSync("npm", ["run", data.platform + (publish === true ? "-publish" : "")], {stdio: "inherit", cwd: "electron"});
    // package.json Recovery
    fse.copySync(packageBakFile, packageFile)
}

if (["dev"].includes(argv[2])) {
    // 开发模式
    fs.writeFileSync(devloadCachePath, utils.formatUrl("127.0.0.1:" + env.parsed.APP_PORT), 'utf8');
    child_process.spawn("npx", ["mix", "watch", "--hot", "--", "--env", "--electron"], {stdio: "inherit"});
    child_process.spawn("npm", ["run", "start-quiet"], {stdio: "inherit", cwd: "electron"});
} else if (platform.includes(argv[2])) {
    // 自动编译
    config.app.sites.forEach((data) => {
        if (data.name && data.id && data.url) {
            data.platform = argv[2];
            startBuild(data, true)
        }
    })
} else {
    // 自定义编译
    const questions = [
        {
            type: 'input',
            name: 'website',
            message: "请输入网站地址",
            default: () => {
                if (fs.existsSync(nativeCachePath)) {
                    return fs.readFileSync(nativeCachePath, 'utf8');
                }
                return undefined;
            },
            validate: function (value) {
                if (!utils.rightExists(value, "/")) {
                    return '网址必须以 "/" 结尾';
                }
                return value !== ''
            }
        },
        {
            type: 'list',
            name: 'platform',
            message: "选择编译系统平台",
            choices: [{
                name: "MacOS",
                value: [platform[0]]
            }, {
                name: "MacOS arm64",
                value: [platform[1]]
            }, {
                name: "Window x86_64",
                value: [platform[2]]
            }, {
                name: "All platforms",
                value: platform
            }]
        }
    ];
    inquirer.prompt(questions).then(answers => {
        answers.platform.forEach(platform => {
            startBuild({
                "name": config.name,
                "id": config.app.id,
                "url": answers.website,
                "platform": platform
            }, false)
        });
    });
}




