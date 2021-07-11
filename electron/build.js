const fs = require('fs');
const path = require('path')
const inquirer = require('inquirer');
const child_process = require('child_process');
const config = require('../package.json')
const argv = process.argv;
const env = require('dotenv').config({ path: './.env' })

/**
 * 删除文件夹及文件
 * @param path
 */
function deleteFile(path) {
    let files = [];
    if (fs.existsSync(path)) {
        files = fs.readdirSync(path);
        files.forEach(function (file, index) {
            let curPath = path + "/" + file;
            if (fs.statSync(curPath).isDirectory()) {
                deleteFile(curPath);
            } else {
                fs.unlinkSync(curPath);
            }
        });
        fs.rmdirSync(path);
    }
}

/**
 * 复制文件
 * @param srcPath
 * @param tarPath
 * @param cb
 */
function copyFile(srcPath, tarPath, cb) {
    let rs = fs.createReadStream(srcPath)
    rs.on('error', function (err) {
        if (err) {
            console.log('read error', srcPath)
        }
        cb && cb(err)
    })
    let ws = fs.createWriteStream(tarPath)
    ws.on('error', function (err) {
        if (err) {
            console.log('write error', tarPath)
        }
        cb && cb(err)
    })
    ws.on('close', function (ex) {
        cb && cb(ex)
    })
    rs.pipe(ws)
}

/**
 * 给地址加上前后
 * @param str
 * @returns {string}
 */
function formatUrl(str) {
    let url;
    if (str.substring(0, 7) === "http://" ||
        str.substring(0, 8) === "https://") {
        url = str.trim();
    } else {
        url = "http://" + str.trim();
    }
    if (url.substring(url.length - 1) != "/") {
        url += "/"
    }
    return url;
}

/** ***************************************************************************************************/
/** ***************************************************************************************************/
/** ***************************************************************************************************/

const electronDir = path.resolve(__dirname, "public");
const nativeCachePath = path.resolve(__dirname, ".native");
const devloadCachePath = path.resolve(__dirname, ".devload");

if (argv[2] === "--build") {
    if (fs.existsSync(electronDir)) {
        deleteFile(electronDir);
    }
    fs.mkdirSync(electronDir);
    copyFile(path.resolve(__dirname, "index.html"), electronDir + "/index.html")

    const platform = ["build-mac-intel", "build-mac-m1", "build-win"];
    const questions = [
        {
            type: 'input',
            name: 'targetUrl',
            message: "请输入网站地址",
            default: () => {
                if (fs.existsSync(nativeCachePath)) {
                    return fs.readFileSync(nativeCachePath, 'utf8');
                }
                return undefined;
            },
            validate: function (value) {
                return value !== ''
            }
        },
        {
            type: 'list',
            name: 'platform',
            message: "选择编译系统平台",
            choices: [{
                name: "MacOS Intel",
                value: [platform[0]]
            }, {
                name: "MacOS M1",
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
        let data = `window.systemInformation = {
        version: "${config.version}",
        origin: "./",
        apiUrl: "${formatUrl(answers.targetUrl)}api/"
    }`;
        fs.writeFileSync(nativeCachePath, formatUrl(answers.targetUrl));
        fs.writeFileSync(electronDir + "/config.js", data, 'utf8');
        //
        fs.writeFileSync(devloadCachePath, "", 'utf8');
        let packageFile = path.resolve(__dirname, "package.json");
        let packageString = fs.readFileSync(packageFile, 'utf8');
        packageString = packageString.replace(/"version":\s*"(.*?)"/, `"version": "${config.version}"`);
        packageString = packageString.replace(/"name":\s*"(.*?)"/, `"name": "${config.name}"`);
        fs.writeFileSync(packageFile, packageString, 'utf8');
        //
        child_process.spawnSync("mix", ["--production", "--", "--env", "--electron"], {stdio: "inherit"});
        answers.platform.forEach(arg => {
            child_process.spawn("npm", ["run", arg], {stdio: "inherit", cwd: "electron"});
        })
    });
} else {
    fs.writeFileSync(devloadCachePath, formatUrl("127.0.0.1:" + env.parsed.APP_PORT), 'utf8');
    child_process.spawn("mix", ["watch", "--hot", "--", "--env", "--electron"], {stdio: "inherit"});
    child_process.spawn("npm", ["run", "start-quiet"], {stdio: "inherit", cwd: "electron"});
}




