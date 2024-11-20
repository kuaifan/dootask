const fs = require("fs");
const os = require("os");
const path = require('path')
const dayjs = require("dayjs");
const http = require('http')
const https = require('https')
const crypto = require('crypto')
const {shell, dialog, session, Notification, nativeTheme} = require("electron");
const loger = require("electron-log");
const Store = require("electron-store");
const store = new Store();

const utils = {
    /**
     * 时间对象
     * @param v
     * @returns {*|dayjs.Dayjs}
     */
    dayjs(v = undefined) {
        if (/^\d{13,}$/.test(v)) {
            return dayjs(Number(v));
        }
        if (/^\d{10,}$/.test(v)) {
            return dayjs(Number(v) * 1000);
        }
        if (v === null) {
            v = 0
        }
        return dayjs(v);
    },

    /**
     * 是否数组
     * @param obj
     * @returns {boolean}
     */
    isArray(obj) {
        return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == '[object array]' && typeof obj.length == "number";
    },

    /**
     * 是否数组对象
     * @param obj
     * @returns {boolean}
     */
    isJson(obj) {
        return typeof (obj) == "object" && Object.prototype.toString.call(obj).toLowerCase() == "[object object]" && typeof obj.length == "undefined";
    },

    /**
     * 将一个 JSON 字符串转换为对象（已try）
     * @param str
     * @param defaultVal
     * @returns {*}
     */
    jsonParse(str, defaultVal = undefined) {
        if (str === null) {
            return defaultVal ? defaultVal : {};
        }
        if (typeof str === "object") {
            return str;
        }
        try {
            return JSON.parse(str.replace(/\n/g,"\\n").replace(/\r/g,"\\r"));
        } catch (e) {
            return defaultVal ? defaultVal : {};
        }
    },

    /**
     * 将 JavaScript 值转换为 JSON 字符串（已try）
     * @param json
     * @param defaultVal
     * @returns {string}
     */
    jsonStringify(json, defaultVal = undefined) {
        if (typeof json !== 'object') {
            return json;
        }
        try{
            return JSON.stringify(json);
        }catch (e) {
            return defaultVal ? defaultVal : "";
        }
    },

    /**
     * 随机数字
     * @param str
     * @param fixed
     * @returns {number}
     */
    runNum(str, fixed = null) {
        let _s = Number(str);
        if (_s + "" === "NaN") {
            _s = 0;
        }
        if (fixed && /^[0-9]*[1-9][0-9]*$/.test(fixed)) {
            _s = _s.toFixed(fixed);
            let rs = _s.indexOf('.');
            if (rs < 0) {
                _s += ".";
                for (let i = 0; i < fixed; i++) {
                    _s += "0";
                }
            }
        }
        return _s;
    },

    /**
     * 随机字符串
     * @param len
     * @returns {string}
     */
    randomString(len) {
        len = len || 32;
        let $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678oOLl9gqVvUuI1';
        let maxPos = $chars.length;
        let pwd = '';
        for (let i = 0; i < len; i++) {
            pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
        }
        return pwd;
    },

    /**
     * 字符串是否包含
     * @param string
     * @param find
     * @param lower
     * @returns {boolean}
     */
    strExists(string, find, lower = false) {
        string += "";
        find += "";
        if (lower !== true) {
            string = string.toLowerCase();
            find = find.toLowerCase();
        }
        return (string.indexOf(find) !== -1);
    },

    /**
     * 字符串是否左边包含
     * @param string
     * @param find
     * @param lower
     * @returns {boolean}
     */
    leftExists(string, find, lower = false) {
        string += "";
        find += "";
        if (lower !== true) {
            string = string.toLowerCase();
            find = find.toLowerCase();
        }
        return (string.substring(0, find.length) === find);
    },

    /**
     * 删除左边字符串
     * @param string
     * @param find
     * @param lower
     * @returns {string}
     */
    leftDelete(string, find, lower = false) {
        string += "";
        find += "";
        if (utils.leftExists(string, find, lower)) {
            string = string.substring(find.length)
        }
        return string ? string : '';
    },

    /**
     * 字符串是否右边包含
     * @param string
     * @param find
     * @param lower
     * @returns {boolean}
     */
    rightExists(string, find, lower = false) {
        string += "";
        find += "";
        if (lower !== true) {
            string = string.toLowerCase();
            find = find.toLowerCase();
        }
        return (string.substring(string.length - find.length) === find);
    },

    /**
     * 打开文件
     * @param filePath
     */
    openFile(filePath) {
        if (!fs.existsSync(filePath)) {
            return
        }
        shell.openPath(filePath).then(() => {
        })
    },

    /**
     * 删除文件夹及文件
     * @param filePath
     */
    deleteFile(filePath) {
        let files = [];
        if (fs.existsSync(filePath)) {
            files = fs.readdirSync(filePath);
            files.forEach(function (file) {
                let curPath = filePath + "/" + file;
                if (fs.statSync(curPath).isDirectory()) {
                    utils.deleteFile(curPath);
                } else {
                    fs.unlinkSync(curPath);
                }
            });
            fs.rmdirSync(filePath);
        }
    },

    /**
     * 复制文件
     * @param srcPath
     * @param tarPath
     * @param cb
     */
    copyFile(srcPath, tarPath, cb) {
        let rs = fs.createReadStream(srcPath)
        rs.on('error', function (err) {
            if (err) {
                loger.log('read error', srcPath)
            }
            cb && cb(err)
        })
        let ws = fs.createWriteStream(tarPath)
        ws.on('error', function (err) {
            if (err) {
                loger.log('write error', tarPath)
            }
            cb && cb(err)
        })
        ws.on('close', function (ex) {
            cb && cb(ex)
        })
        rs.pipe(ws)
    },

    /**
     * 给地址加上前后
     * @param str
     * @returns {string}
     */
    formatUrl(str) {
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
    },

    /**
     * 正则提取域名
     * @param weburl
     * @returns {string|string}
     */
    getDomain(weburl) {
        let urlReg = /http(s)?:\/\/([^\/]+)/i;
        let domain = (weburl + "").match(urlReg);
        return ((domain != null && domain.length > 0) ? domain[2] : "");
    },

    /**
     * 显示窗口
     * @param win
     */
    setShowWindow(win) {
        if (win) {
            if (win.isMinimized()) {
                win.restore()
            }
            win.focus()
            win.show()
        }
    },

    /**
     * 窗口关闭事件
     * @param event
     * @param app
     */
    onBeforeUnload(event, app) {
        return new Promise(resolve => {
            const contents = app.webContents
            if (contents != null) {
                contents.executeJavaScript(`if(typeof window.__onBeforeUnload === 'function'){window.__onBeforeUnload()}`, true).then(options => {
                    if (utils.isJson(options)) {
                        let choice = dialog.showMessageBoxSync(app, options)
                        if (choice === 1) {
                            contents.executeJavaScript(`if(typeof window.__removeBeforeUnload === 'function'){window.__removeBeforeUnload()}`, true).catch(() => {});
                            resolve()
                        }
                    } else if (options !== true) {
                        resolve()
                    }
                }).catch(_ => {
                    resolve()
                })
                event.preventDefault()
            } else {
                resolve()
            }
        })
    },

    /**
     * 新窗口打开事件
     * @param webContents
     * @param url
     * @returns {Promise<unknown>}
     */
    onBeforeOpenWindow(webContents, url) {
        return new Promise(resolve => {
            const dataStr = JSON.stringify({url: url})
            webContents.executeJavaScript(`if(typeof window.__onBeforeOpenWindow === 'function'){window.__onBeforeOpenWindow(${dataStr})}`, true).then(options => {
                if (options !== true) {
                    resolve()
                }
            }).catch(_ => {
                resolve()
            })
        })
    },

    /**
     * 分发事件
     * @param webContents
     * @param data
     * @returns {Promise<unknown>}
     */
    onDispatchEvent(webContents, data) {
        return new Promise(resolve => {
            const dataStr = JSON.stringify(data)
            webContents.executeJavaScript(`window.__onDispatchEvent(${dataStr})`, true).then(options => {
                resolve(options)
            }).catch(_ => {
                resolve()
            })
        })
    },

    /**
     * 版本比较
     * @param version1
     * @param version2
     * @returns number  0: 相同，1: version1大，-1: version2大
     */
    compareVersion(version1, version2) {
        let pA = 0, pB = 0;

        // 版本号完全相同
        if (version1 === version2) {
            return 0
        }

        // 寻找当前区间的版本号
        const findDigit = (str, start) => {
            let i = start;
            while (str[i] !== '.' && i < str.length) {
                i++;
            }
            return i;
        }

        while (pA < version1.length && pB < version2.length) {
            const nextA = findDigit(version1, pA);
            const nextB = findDigit(version2, pB);
            const numA = +version1.substr(pA, nextA - pA);
            const numB = +version2.substr(pB, nextB - pB);
            if (numA !== numB) {
                return numA > numB ? 1 : -1;
            }
            pA = nextA + 1;
            pB = nextB + 1;
        }

        // 若arrayA仍有小版本号
        while (pA < version1.length) {
            const nextA = findDigit(version1, pA);
            const numA = +version1.substr(pA, nextA - pA);
            if (numA > 0) {
                return 1;
            }
            pA = nextA + 1;
        }

        // 若arrayB仍有小版本号
        while (pB < version2.length) {
            const nextB = findDigit(version2, pB);
            const numB = +version2.substr(pB, nextB - pB);
            if (numB > 0) {
                return -1;
            }
            pB = nextB + 1;
        }

        // 版本号完全相同
        return 0;
    },

    /**
     * electron15 后，解决跨域cookie无法携带，
     */
    useCookie() {
        const filter = {urls: ['https://*/*', 'http://*/*']};
        session.defaultSession.webRequest.onHeadersReceived(filter, (details, callback) => {
            if (details.responseHeaders && details.responseHeaders['Set-Cookie']) {
                for (let i = 0; i < details.responseHeaders['Set-Cookie'].length; i++) {
                    details.responseHeaders['Set-Cookie'][i] += ';SameSite=None;Secure';
                }
            }
            callback({responseHeaders: details.responseHeaders});
        });
    },

    /**
     * win mac meta control
     * @param input
     * @returns {boolean | Point | HTMLElement}
     */
    isMetaOrControl(input) {
        if (process.platform === 'win32') {
            return input.control
        } else {
            return input.meta
        }
    },

    /**
     * MIME类型判断
     * @param filePath
     * @returns {*|string}
     */
    getMimeType(filePath) {
        const ext = path.extname(filePath).toLowerCase()
        const mimeTypes = {
            '.jpg': 'image/jpeg',
            '.jpeg': 'image/jpeg',
            '.png': 'image/png',
            '.gif': 'image/gif',
            '.svg': 'image/svg+xml',
            '.webp': 'image/webp'
        }
        return mimeTypes[ext] || 'application/octet-stream'
    },

    /**
     * 显示系统通知
     * @param {Object} args - 通知参数
     * @param {string} args.title - 通知标题
     * @param {string} args.body - 通知内容
     * @param {string} [args.icon] - 通知图标路径或URL
     * @param {Electron.BrowserWindow} [window] - 主窗口实例
     * @returns {Promise<void>}
     */
    async showNotification(args, window = null) {
        try {
            // 如果是网络图片，进行缓存处理（仅Windows）
            if (process.platform === 'win32' && args.icon && /^https?:\/\//i.test(args.icon)) {
                args.icon = await utils.getCachedImage(args.icon);
            }

            const notifiy = new Notification(args);
            notifiy.addListener('click', _ => {
                if (window && window.webContents) {
                    window.webContents.send("clickNotification", args)
                    if (!window.isVisible()) {
                        window.show();
                    }
                    window.focus();
                }
            })
            notifiy.addListener('reply', (event, reply) => {
                if (window && window.webContents) {
                    window.webContents.send("replyNotification", Object.assign(args, {reply}))
                }
            })
            notifiy.show()
        } catch (error) {
            loger.error('显示通知失败:', error);
        }
    },

    /**
     * 获取缓存的图片路径
     * @param {string} imageUrl - 图片URL
     * @returns {Promise<string>} 缓存的图片路径
     */
    async getCachedImage(imageUrl) {
        // 生成图片URL的唯一标识
        const urlHash = crypto.createHash('md5').update(imageUrl).digest('hex');
        const cacheDir = path.join(os.tmpdir(), 'dootask-cache', 'images');
        const cachePath = path.join(cacheDir, `${urlHash}.png`);

        try {
            // 确保缓存目录存在
            if (!fs.existsSync(cacheDir)) {
                fs.mkdirSync(cacheDir, { recursive: true });
            }

            // 检查缓存是否存在
            if (!fs.existsSync(cachePath)) {
                await utils.downloadImage(imageUrl, cachePath);
            }

            return cachePath;
        } catch (error) {
            loger.error('处理缓存图片失败:', error);
            return ''; // 返回空字符串，通知将使用默认图标
        }
    },

    /**
     * 下载图片
     * @param {string} url - 图片URL
     * @param {string} filePath - 保存路径
     * @returns {Promise<void>}
     */
    downloadImage(url, filePath) {
        return new Promise((resolve, reject) => {
            const file = fs.createWriteStream(filePath);

            // 根据协议选择http或https
            const protocol = url.startsWith('https') ? https : http;

            const request = protocol.get(url, {
                headers: {
                    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                }
            }, (response) => {
                // 处理重定向
                if (response.statusCode === 301 || response.statusCode === 302) {
                    file.close();
                    fs.unlink(filePath, () => {});
                    return utils.downloadImage(response.headers.location, filePath)
                        .then(resolve)
                        .catch(reject);
                }

                // 检查内容类型
                const contentType = response.headers['content-type'];
                if (!contentType || !contentType.startsWith('image/')) {
                    file.close();
                    fs.unlink(filePath, () => {});
                    reject(new Error(`非图片类型: ${contentType}`));
                    return;
                }

                if (response.statusCode !== 200) {
                    file.close();
                    fs.unlink(filePath, () => {});
                    reject(new Error(`下载失败，状态码: ${response.statusCode}`));
                    return;
                }

                let downloadedBytes = 0;
                response.on('data', (chunk) => {
                    downloadedBytes += chunk.length;
                });

                response.pipe(file);

                file.on('finish', () => {
                    // 检查文件大小
                    if (downloadedBytes === 0) {
                        file.close();
                        fs.unlink(filePath, () => {});
                        reject(new Error('下载的文件大小为0'));
                        return;
                    }
                    file.close();
                    resolve();
                });
            });

            request.on('error', (err) => {
                file.close();
                fs.unlink(filePath, () => {});
                reject(err);
            });

            // 设置超时
            request.setTimeout(30000, () => {
                request.destroy();
                file.close();
                fs.unlink(filePath, () => {});
                reject(new Error('下载超时'));
            });
        });
    },

    /**
     * 是否本地资源路径
     * @param {string} url
     * @returns {boolean}
     */
    isLocalAssetPath(url) {
        return url.startsWith('local-asset://')
    },

    /**
     * 本地资源路径还原
     * @param {string} url
     * @returns {string}
     */
    localAssetRestoreRealPath(url) {
        if (!utils.isLocalAssetPath(url)) {
            return url
        }

        let p0 = url.replace(/^local-asset:\/\//, '')

        const p1 = path.join(__dirname, '.', p0)
        if (fs.existsSync(p1)) {
            return p1
        }

        const p2 = path.join(__dirname, '..', p0)
        if (fs.existsSync(p2)) {
            return p2
        }
        return url
    },

    /**
     * 加载URL或文件
     * @param browser
     * @param url
     * @param hash
     */
    loadUrlOrFile(browser, url, hash = null) {
        if (url) {
            if (hash) {
                url = `${url}#${hash}`.replace(/\/*#\/*/g, '/')
            }
            browser.loadURL(url).then(_ => { }).catch(_ => { })
        } else {
            const options = {}
            if (hash) {
                options.hash = hash
            }
            browser.loadFile('./public/index.html', options).then(_ => { }).catch(_ => { })
        }
    },

    /**
     * 获取主题名称
     * @returns {string|*}
     */
    getThemName() {
        const themeConf = store.get("themeConf");
        if (["dark", "light"].includes(themeConf)) {
            return themeConf;
        }
        return nativeTheme.shouldUseDarkColors ? "dark" : "light";
    },

    /**
     * 获取默认背景颜色
     * @returns {string}
     */
    getDefaultBackgroundColor() {
        if (utils.getThemName() === "dark") {
            return "#0D0D0D";
        } else {
            return "#FFFFFF";
        }
    }
}

module.exports = utils;
