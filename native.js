const fs = require('fs');
const path  = require('path')
const package  = require('./package.json')

// 删除
function deleteFile(path) {
    let files = [];
    if( fs.existsSync(path) ) {
        files = fs.readdirSync(path);
        files.forEach(function(file,index){
            let curPath = path + "/" + file;
            if(fs.statSync(curPath).isDirectory()) {
                deleteFile(curPath);
            } else {
                fs.unlinkSync(curPath);
            }
        });
        fs.rmdirSync(path);
    }
}

// 复制文件
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

// 复制文件夹所有
function copyDir(srcDir, tarDir, cb) {
    if (fs.existsSync(tarDir)) {
        fs.readdir(srcDir, function (err, files) {
            let count = 0
            let checkEnd = function () {
                ++count == files.length && cb && cb()
            }
            if (err) {
                checkEnd()
                return
            }
            files.forEach(function (file) {
                let srcPath = path.join(srcDir, file)
                let tarPath = path.join(tarDir, file)
                fs.stat(srcPath, function (err, stats) {
                    if (stats.isDirectory()) {
                        fs.mkdir(tarPath, function (err) {
                            if (err) {
                                return
                            }
                            copyDir(srcPath, tarPath, checkEnd)
                        })
                    } else {
                        copyFile(srcPath, tarPath, checkEnd)
                    }
                })
            })
            //为空时直接回调
            files.length === 0 && cb && cb()
        })
    } else {
        fs.mkdir(tarDir, function (err) {
            if (err) {
                return
            }
            copyDir(srcDir, tarDir, cb)
        })
    }
}

/** ***************************************************************************************************/
/** ***************************************************************************************************/
/** ***************************************************************************************************/

const electronDir = path.resolve("electron/public");
if (fs.existsSync(electronDir)) {
    deleteFile(electronDir);
}
fs.mkdirSync(electronDir);

[
    'audio',
    'css',
    'images',
    'js',
].forEach(function (item) {
    copyDir(path.resolve("public/" + item), electronDir + "/" + item)
})

copyFile(path.resolve("electron/index.html"), electronDir + "/index.html")

fs.writeFileSync(electronDir + "/config.js", `window.systemInformation = {
    version: "${package.version}",
    origin: "./",
    apiUrl: "http://127.0.0.1:2222/api/"
}`, 'utf8');

