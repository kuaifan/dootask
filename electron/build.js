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

/**
 * 正则提取域名
 * @param weburl
 * @returns {string|string}
 */
function getDomain(weburl) {
    let urlReg = /http(s)?:\/\/([^\/]+)/i;
    let domain = weburl.match(urlReg);
    return ((domain != null && domain.length > 0) ? domain[2] : "");
}

/**
 * 右边是否包含
 * @param string
 * @param find
 * @returns {boolean}
 */
function rightExists(string, find) {
    string += "";
    find += "";
    return (string.substring(string.length - find.length) === find);
}

/** ***************************************************************************************************/
/** ***************************************************************************************************/
/** ***************************************************************************************************/

const electronDir = path.resolve(__dirname, "public");
const nativeCachePath = path.resolve(__dirname, ".native");
const devloadCachePath = path.resolve(__dirname, ".devload");
const platform = ["build-mac", "build-mac-arm", "build-win"];

// 生成配置、编译应用
function step1(data, publish) {
    let systemInfo = `window.systemInformation = {
    version: "${config.version}",
    origin: "./",
    apiUrl: "${formatUrl(data.url)}api/"
}`;
    fs.writeFileSync(electronDir + "/config.js", systemInfo, 'utf8');
    fs.writeFileSync(nativeCachePath, formatUrl(data.url));
    fs.writeFileSync(devloadCachePath, "", 'utf8');
    //
    let packageFile = path.resolve(__dirname, "package.json");
    let packageString = fs.readFileSync(packageFile, 'utf8');
    packageString = packageString.replace(/"name":\s*"(.*?)"/, `"name": "${data.name}"`);
    packageString = packageString.replace(/"appId":\s*"(.*?)"/, `"appId": "${data.id}"`);
    packageString = packageString.replace(/"version":\s*"(.*?)"/, `"version": "${config.version}"`);
    packageString = packageString.replace(/"artifactName":\s*"(.*?)"/g, '"artifactName": "' + getDomain(data.url) + '-v${version}-${os}-${arch}.${ext}"');
    fs.writeFileSync(packageFile, packageString, 'utf8');
    //
    child_process.spawnSync("npm", ["run", data.platform + (publish === true ? "-publish" : "")], {stdio: "inherit", cwd: "electron"});
}

// 还原配置
function step2() {
    let packageFile = path.resolve(__dirname, "package.json");
    let packageString = fs.readFileSync(packageFile, 'utf8');
    packageString = packageString.replace(/"name":\s*"(.*?)"/, `"name": "${config.name}"`);
    packageString = packageString.replace(/"appId":\s*"(.*?)"/, `"appId": "${config.app.id}"`);
    packageString = packageString.replace(/"artifactName":\s*"(.*?)"/g, '"artifactName": "${productName}-v${version}-${os}-${arch}.${ext}"');
    fs.writeFileSync(packageFile, packageString, 'utf8');
}

if (["dev"].includes(argv[2])) {
    // 开发模式
    fs.writeFileSync(devloadCachePath, formatUrl("127.0.0.1:" + env.parsed.APP_PORT), 'utf8');
    child_process.spawn("npx", ["mix", "watch", "--hot", "--", "--env", "--electron"], {stdio: "inherit"});
    child_process.spawn("npm", ["run", "start-quiet"], {stdio: "inherit", cwd: "electron"});
} else if (platform.includes(argv[2])) {
    // 自动编译
    config.app.sites.forEach((data) => {
        if (data.name && data.id && data.url) {
            data.platform = argv[2];
            step1(data, true)
        }
    })
    step2();
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
                if (!rightExists(value, "/")) {
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
            step1({
                "name": config.name,
                "id": config.app.id,
                "url": answers.website,
                "platform": platform
            }, false)
        });
        step2();
    });
}




