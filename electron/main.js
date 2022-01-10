const fs = require('fs')
const os = require("os");
const path = require('path')
const XLSX = require('xlsx');
const {app, BrowserWindow, ipcMain, dialog} = require('electron')
const utils = require('./utils');
const config = require('./package.json');
const log = require("electron-log");

let mainWindow = null,
    subWindow = [],
    willQuitApp = false,
    inheritClose = false,
    devloadUrl = "",
    devloadCachePath = path.resolve(__dirname, ".devload"),
    downloadList = [],
    downloadCacheFile = path.join(app.getPath('cache'), config.name + '.downloadCache');

if (fs.existsSync(devloadCachePath)) {
    devloadUrl = fs.readFileSync(devloadCachePath, 'utf8')
}
if (fs.existsSync(downloadCacheFile)) {
    downloadList = utils.jsonParse(fs.readFileSync(downloadCacheFile, 'utf8'), [])
}

function createMainWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
        center: true,
        autoHideMenuBar: true,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: true,
            contextIsolation: false
        }
    })
    mainWindow.webContents.setUserAgent(mainWindow.webContents.getUserAgent() + " MainTaskWindow/" + process.platform + "/" + os.arch() + "/1.0");

    if (devloadUrl) {
        mainWindow.loadURL(devloadUrl).then(r => {

        })
    } else {
        mainWindow.loadFile('./public/index.html').then(r => {

        })
    }

    mainWindow.on('page-title-updated', (event, title) => {
        if (title == "index.html") {
            event.preventDefault()
        }
    })

    mainWindow.on('close', (e) => {
        if (!willQuitApp) {
            e.preventDefault();
            if (inheritClose) {
                mainWindow.webContents.send("windowClose", {})
            } else {
                if (process.platform === 'darwin') {
                    app.hide();
                } else {
                    app.quit();
                }
            }
        }
    })

    mainWindow.webContents.session.on('will-download', (event, item) => {
        item.setSavePath(path.join(app.getPath('cache'), item.getFilename()));
        item.on('done', (event, state) => {
            try {
                const info = {
                    state,
                    name: item.getFilename(),
                    url: item.getURL(),
                    chain: item.getURLChain(),
                    savePath: item.getSavePath(),
                    mimeType: item.getMimeType(),
                    totalBytes: item.getTotalBytes(),
                };
                mainWindow.webContents.send("downloadDone", info)
                //
                if (info.state == "completed") {
                    // 下载完成
                    info.chain.some(url => {
                        let download = downloadList.find(item => item.url == url)
                        if (download) {
                            download.status = "completed"
                            download.info = info
                        }
                    })
                    fs.writeFileSync(downloadCacheFile, utils.jsonStringify(downloadList), 'utf8');
                } else {
                    // 下载失败
                    info.chain.some(url => {
                        downloadList = downloadList.filter(item => item.url != url)
                    })
                }
            } catch (e) { }
        })
    })
}

function createSubWindow(args) {
    if (!args) {
        return;
    }

    if (typeof args !== "object") {
        args = {
            path: args,
            config: {},
        }
    }

    let name = args.name || "auto_" + utils.randomString(6);
    let item = subWindow.find(item => item.name == name);
    let browser = item ? item.browser : null;
    if (browser) {
        browser.focus();
        if (args.force === false) {
            return;
        }
    } else {
        let config = args.config || {};
        if (typeof args.title !== "undefined") {
            config.title = args.title;
        }
        browser = new BrowserWindow(Object.assign({
            width: 1280,
            height: 800,
            center: true,
            parent: mainWindow,
            autoHideMenuBar: true,
            webPreferences: {
                preload: path.join(__dirname, 'preload.js'),
                devTools: args.devTools !== false,
                nodeIntegration: true,
                contextIsolation: false
            }
        }, config))
        browser.on('page-title-updated', (event, title) => {
            if (title == "index.html" || args.titleFixed === true) {
                event.preventDefault()
            }
        })
        browser.on('close', () => {
            let index = subWindow.findIndex(item => item.name == name);
            if (index > -1) {
                subWindow.splice(index, 1)
            }
        })
        subWindow.push({ name, browser })
    }
    browser.webContents.setUserAgent(browser.webContents.getUserAgent() + " SubTaskWindow/" + process.platform + "/" + os.arch() + "/1.0" + (args.userAgent ? (" " + args.userAgent) : ""));

    if (devloadUrl) {
        browser.loadURL(devloadUrl + '#' + (args.hash || args.path)).then(r => {

        })
    } else {
        browser.loadFile('./public/index.html', {
            hash: args.hash || args.path
        }).then(r => {

        })
    }
}

app.whenReady().then(() => {
    createMainWindow()

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) createMainWindow()
    })
})

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit()
    }
})

app.on('before-quit', () => {
    willQuitApp = true
})

/**
 * 继承关闭窗口事件
 */
ipcMain.on('inheritClose', (event) => {
    inheritClose = true
    event.returnValue = "ok"
})

/**
 * 下载文件
 * @param args {url}
 */
ipcMain.on('downloadFile', (event, args) => {
    const download = downloadList.find(({url}) => url == args.url);
    if (download) {
        if (download.status == "completed") {
            if (fs.existsSync(download.info.savePath)) {
                log.warn("已下载完成", args)
                mainWindow.webContents.send("downloadDone", download.info)
            } else {
                log.info("开始重新下载", args)
                download.status = "progressing"
                mainWindow.webContents.downloadURL(args.url);
            }
        } else {
            log.warn("已在下载列表中", args)
        }
    } else {
        log.info("开始下载", args)
        downloadList.push(Object.assign(args, { status: "progressing" }))
        mainWindow.webContents.downloadURL(args.url);
    }
    event.returnValue = "ok"
})

/**
 * 打开文件
 * @param args {path}
 */
ipcMain.on('openFile', (event, args) => {
    utils.openFile(args.path)
    event.returnValue = "ok"
})

/**
 * 退出客户端
 */
ipcMain.on('windowQuit', (event) => {
    event.returnValue = "ok"
    app.quit();
})

/**
 * 创建路由窗口
 * @param args {path, ?}
 */
ipcMain.on('windowRouter', (event, args) => {
    createSubWindow(args)
    event.returnValue = "ok"
})

/**
 * 隐藏窗口（mac隐藏，其他关闭）
 */
ipcMain.on('windowHidden', (event) => {
    if (process.platform === 'darwin') {
        app.hide();
    } else {
        app.quit();
    }
    event.returnValue = "ok"
})

/**
 * 关闭窗口
 */
ipcMain.on('windowClose', (event) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    win.close()
    event.returnValue = "ok"
})

/**
 * 设置窗口尺寸
 * @param args {width, height, autoZoom, minWidth, minHeight, maxWidth, maxHeight}
 */
ipcMain.on('windowSize', (event, args) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    if (win) {
        if (args.width || args.height) {
            let [w, h] = win.getSize()
            const width = args.width || w
            const height = args.height || h
            win.setSize(width, height, args.animate === true)
            //
            if (args.autoZoom === true) {
                let move = false
                let [x, y] = win.getPosition()
                if (Math.abs(width - w) > 10) {
                    move = true
                    x -= (width - w) / 2
                }
                if (Math.abs(height - h) > 10) {
                    move = true
                    y -= (height - h) / 2
                }
                if (move) {
                    win.setPosition(Math.max(0, Math.floor(x)), Math.max(0, Math.floor(y)))
                }
            }
        }
        if (args.minWidth || args.minHeight) {
            win.setMinimumSize(args.minWidth || win.getMinimumSize()[0], args.minHeight || win.getMinimumSize()[1])
        }
        if (args.maxWidth || args.maxHeight) {
            win.setMaximumSize(args.maxWidth || win.getMaximumSize()[0], args.maxHeight || win.getMaximumSize()[1])
        }
    }
    event.returnValue = "ok"
})

/**
 * 设置窗口最小尺寸
 * @param args {minWidth, minHeight}
 */
ipcMain.on('windowMinSize', (event, args) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    if (win) {
        win.setMinimumSize(args.minWidth || win.getMinimumSize()[0], args.minHeight || win.getMinimumSize()[1])
    }
    event.returnValue = "ok"
})

/**
 * 设置窗口最大尺寸
 * @param args {maxWidth, maxHeight}
 */
ipcMain.on('windowMaxSize', (event, args) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    if (win) {
        win.setMaximumSize(args.maxWidth || win.getMaximumSize()[0], args.maxHeight || win.getMaximumSize()[1])
    }
    event.returnValue = "ok"
})

/**
 * 窗口居中
 */
ipcMain.on('windowCenter', (event) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    if (win) {
        win.center();
    }
    event.returnValue = "ok"
})

/**
 * 窗口最大化或恢复
 */
ipcMain.on('windowMax', (event) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    if (win.isMaximized()) {
        win.restore();
    } else {
        win.maximize();
    }
    event.returnValue = "ok"
})

/**
 * 给主窗口发送信息
 * @param args {channel, data}
 */
ipcMain.on('sendForwardMain', (event, args) => {
    if (mainWindow) {
        mainWindow.webContents.send(args.channel, args.data)
    }
    event.returnValue = "ok"
})

/**
 * 设置Dock标记
 * @param args
 */
ipcMain.on('setDockBadge', (event, args) => {
    if(process.platform !== 'darwin'){
        // Mac only
        return;
    }
    if (utils.runNum(args) > 0) {
        app.dock.setBadge(String(args))
    } else {
        app.dock.setBadge("")
    }
    event.returnValue = "ok"
})

/**
 * 保存sheets
 */
ipcMain.on('saveSheet', (event, data, filename, opts) => {
    const EXTENSIONS = "xls|xlsx|xlsm|xlsb|xml|csv|txt|dif|sylk|slk|prn|ods|fods|htm|html".split("|");
    dialog.showSaveDialog({
        title: 'Save file as',
        defaultPath: filename,
        filters: [{
            name: "Spreadsheets",
            extensions: EXTENSIONS
        }]
    }).then(o => {
        XLSX.writeFile(data, o.filePath, opts);
    });
    event.returnValue = "ok"
})
