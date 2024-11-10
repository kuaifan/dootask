const fs = require('fs')
const os = require("os");
const path = require('path')
const {app, BrowserWindow, ipcMain, dialog, clipboard, nativeImage, shell, Tray, Menu, globalShortcut, Notification, BrowserView, nativeTheme} = require('electron')
const {autoUpdater} = require("electron-updater")
const log = require("electron-log");
const electronConf = require('electron-config')
const userConf = new electronConf()
const fsProm = require('fs/promises');
const PDFDocument = require('pdf-lib').PDFDocument;
const Screenshots = require("electron-screenshots-tool").Screenshots;
const crc = require('crc');
const zlib = require('zlib');
const utils = require('./utils');
const config = require('./package.json');
const electronMenu = require("./electron-menu");
const spawn = require("child_process").spawn;

const isMac = process.platform === 'darwin'
const isWin = process.platform === 'win32'
const allowedUrls = /^(?:https?|mailto|tel|callto):/i;
const allowedCalls = /^(?:mailto|tel|callto):/i;
let enableStoreBkp = true;
let dialogOpen = false;
let enablePlugins = false;

let mainWindow = null,
    mainTray = null,
    isReady = false,
    willQuitApp = false,
    devloadUrl = "",
    devloadCachePath = path.resolve(__dirname, ".devload");

let screenshotObj = null,
    screenshotKey = null;

let childWindow = [],
    webTabWindow = null,
    webTabView = [],
    webTabHeight = 38;

let showState = {},
    onShowWindow = (win) => {
        if (typeof showState[win.webContents.id] === 'undefined') {
            showState[win.webContents.id] = true
            win.setBackgroundColor('rgba(255, 255, 255, 0)')
            win.show();
        }
    }

if (fs.existsSync(devloadCachePath)) {
    devloadUrl = fs.readFileSync(devloadCachePath, 'utf8')
}

/**
 * 创建主窗口
 */
function createMainWindow() {
    mainWindow = new BrowserWindow({
        width: 1280,
        height: 800,
        minWidth: 360,
        minHeight: 360,
        center: true,
        autoHideMenuBar: true,
        webPreferences: {
            preload: path.join(__dirname, 'electron-preload.js'),
            webSecurity: true,
            nodeIntegration: true,
            contextIsolation: true,
            nativeWindowOpen: true
        }
    })
    const originalUA = mainWindow.webContents.session.getUserAgent() || mainWindow.webContents.getUserAgent()
    mainWindow.webContents.setUserAgent(originalUA + " MainTaskWindow/" + process.platform + "/" + os.arch() + "/1.0");
    mainWindow.webContents.setWindowOpenHandler(({url}) => {
        if (allowedCalls.test(url)) {
            return {action: 'allow'}
        }
        utils.onBeforeOpenWindow(mainWindow.webContents, url).then(() => {
            openExternal(url)
        })
        return {action: 'deny'}
    })
    electronMenu.webContentsMenu(mainWindow.webContents)

    if (devloadUrl) {
        mainWindow.loadURL(devloadUrl).then(_ => { }).catch(_ => { })
    } else {
        mainWindow.loadFile('./public/index.html').then(_ => { }).catch(_ => { })
    }

    mainWindow.on('page-title-updated', (event, title) => {
        if (title == "index.html") {
            event.preventDefault()
        }
    })

    mainWindow.on('focus', () => {
        mainWindow.webContents.send("browserWindowFocus", {})
    })

    mainWindow.on('blur', () => {
        mainWindow.webContents.send("browserWindowBlur", {})
    })

    mainWindow.on('close', event => {
        if (!willQuitApp) {
            utils.onBeforeUnload(event, mainWindow).then(() => {
                if (process.platform === 'win32') {
                    mainWindow.hide()
                } else if (process.platform === 'darwin') {
                    mainWindow.hide()
                } else {
                    app.quit()
                }
            })
        }
    })
}

/**
 * 创建子窗口
 * @param args {path, hash, title, titleFixed, force, userAgent, config, webPreferences}
 */
function createChildWindow(args) {
    if (!args) {
        return;
    }

    if (!utils.isJson(args)) {
        args = {path: args, config: {}}
    }

    let name = args.name || "auto_" + utils.randomString(6);
    let item = childWindow.find(item => item.name == name);
    let browser = item ? item.browser : null;
    if (browser) {
        browser.focus();
        if (args.force === false) {
            return;
        }
    } else {
        let config = args.config || {};
        let webPreferences = args.webPreferences || {};
        browser = new BrowserWindow(Object.assign({
            width: 1280,
            height: 800,
            minWidth: 360,
            minHeight: 360,
            center: true,
            show: false,
            parent: mainWindow,
            autoHideMenuBar: true,
            webPreferences: Object.assign({
                preload: path.join(__dirname, 'electron-preload.js'),
                webSecurity: true,
                nodeIntegration: true,
                contextIsolation: true,
                nativeWindowOpen: true
            }, webPreferences),
        }, config))

        browser.on('page-title-updated', (event, title) => {
            if (title == "index.html" || config.titleFixed === true) {
                event.preventDefault()
            }
        })

        browser.on('focus', () => {
            browser.webContents.send("browserWindowFocus", {})
        })

        browser.on('blur', () => {
            browser.webContents.send("browserWindowBlur", {})
        })

        browser.on('close', event => {
            if (!willQuitApp) {
                utils.onBeforeUnload(event, browser).then(() => {
                    browser.destroy()
                })
            }
        })

        browser.on('closed', () => {
            let index = childWindow.findIndex(item => item.name == name);
            if (index > -1) {
                childWindow.splice(index, 1)
            }
        })

        browser.once('ready-to-show', () => {
            onShowWindow(browser);
        })

        browser.webContents.once('dom-ready', () => {
            onShowWindow(browser);
        })

        childWindow.push({ name, browser })
    }
    const originalUA = browser.webContents.session.getUserAgent() || browser.webContents.getUserAgent()
    browser.webContents.setUserAgent(originalUA + " SubTaskWindow/" + process.platform + "/" + os.arch() + "/1.0" + (args.userAgent ? (" " + args.userAgent) : ""));
    browser.webContents.setWindowOpenHandler(({url}) => {
        if (allowedCalls.test(url)) {
            return {action: 'allow'}
        }
        utils.onBeforeOpenWindow(browser.webContents, url).then(() => {
            openExternal(url)
        })
        return {action: 'deny'}
    })
    electronMenu.webContentsMenu(browser.webContents)

    const hash = args.hash || args.path;
    if (/^https?:\/\//i.test(hash)) {
        browser.loadURL(hash).then(_ => { }).catch(_ => { })
        return;
    }
    if (devloadUrl) {
        browser.loadURL(devloadUrl + '#' + hash).then(_ => { }).catch(_ => { })
        return;
    }
    browser.loadFile('./public/index.html', {
        hash
    }).then(_ => {

    })
}

/**
 * 更新子窗口
 * @param browser
 * @param args
 */
function updateChildWindow(browser, args) {
    if (!args) {
        return;
    }

    if (!utils.isJson(args)) {
        args = {path: args, name: null}
    }

    const hash = args.hash || args.path;
    if (hash) {
        if (devloadUrl) {
            browser.loadURL(devloadUrl + '#' + hash).then(_ => { }).catch(_ => { })
        } else {
            browser.loadFile('./public/index.html', {
                hash
            }).then(_ => { }).catch(_ => { })
        }
    }
    if (args.name) {
        const er = childWindow.find(item => item.browser == browser);
        if (er) {
            er.name = args.name;
        }
    }
}

/**
 * 创建内置浏览器
 * @param args {url, ?}
 */
function createWebTabWindow(args) {
    if (!args) {
        return;
    }

    if (!utils.isJson(args)) {
        args = {url: args}
    }

    if (!allowedUrls.test(args.url)) {
        return;
    }

    // 创建父级窗口
    if (!webTabWindow) {
        let config = Object.assign(args.config || {}, userConf.get('webTabWindow', {}));
        let webPreferences = args.webPreferences || {};
        const titleBarOverlay = {
            height: webTabHeight
        }
        if (nativeTheme.shouldUseDarkColors) {
            titleBarOverlay.color = '#3B3B3D'
            titleBarOverlay.symbolColor = '#C5C5C5'
        }
        webTabWindow = new BrowserWindow(Object.assign({
            x: mainWindow.getBounds().x + webTabHeight,
            y: mainWindow.getBounds().y + webTabHeight,
            width: 1280,
            height: 800,
            minWidth: 360,
            minHeight: 360,
            center: true,
            show: false,
            autoHideMenuBar: true,
            titleBarStyle: 'hidden',
            titleBarOverlay,
            webPreferences: Object.assign({
                preload: path.join(__dirname, 'electron-preload.js'),
                webSecurity: true,
                nodeIntegration: true,
                contextIsolation: true,
                nativeWindowOpen: true
            }, webPreferences),
        }, config))

        if (nativeTheme.shouldUseDarkColors) {
            webTabWindow.setBackgroundColor('#3B3B3D')
        } else {
            webTabWindow.setBackgroundColor('#EFF0F4')
        }

        webTabWindow.on('resize', () => {
            resizeWebTab(0)
        })

        webTabWindow.on('enter-full-screen', () => {
            utils.onDispatchEvent(webTabWindow.webContents, {
                event: 'enter-full-screen',
            }).then(_ => { })
        })

        webTabWindow.on('leave-full-screen', () => {
            utils.onDispatchEvent(webTabWindow.webContents, {
                event: 'leave-full-screen',
            }).then(_ => { })
        })

        webTabWindow.on('close', event => {
            if (!willQuitApp) {
                closeWebTab(0)
                event.preventDefault()
            } else {
                userConf.set('webTabWindow', webTabWindow.getBounds())
            }
        })

        webTabWindow.on('closed', () => {
            webTabView.forEach(({view}) => {
                try {
                    view.webContents.close()
                } catch (e) {
                    //
                }
            })
            webTabView = []
            webTabWindow = null
        })

        webTabWindow.once('ready-to-show', () => {
            onShowWindow(webTabWindow);
        })

        webTabWindow.webContents.once('dom-ready', () => {
            onShowWindow(webTabWindow);
        })

        webTabWindow.webContents.on('before-input-event', (event, input) => {
            if (utils.isMetaOrControl(input) && input.key.toLowerCase() === 'r') {
                reloadWebTab(0)
                event.preventDefault()
            } else if (utils.isMetaOrControl(input) && input.shift && input.key.toLowerCase() === 'i') {
                devToolsWebTab(0)
            }
        })

        webTabWindow.loadFile('./render/tabs/index.html', {}).then(_ => {

        })
    }
    if (webTabWindow.isMinimized()) {
        webTabWindow.restore()
    }
    webTabWindow.focus();
    webTabWindow.show();

    // 创建子窗口
    const browserView = new BrowserView({
        useHTMLTitleAndIcon: true,
        useLoadingView: true,
        useErrorView: true,
        webPreferences: {
            preload: path.join(__dirname, 'electron-preload.js'),
        }
    })
    if (nativeTheme.shouldUseDarkColors) {
        browserView.setBackgroundColor('#575757')
    } else {
        browserView.setBackgroundColor('#FFFFFF')
    }
    browserView.setBounds({
        x: 0,
        y: webTabHeight,
        width: webTabWindow.getContentBounds().width || 1280,
        height: (webTabWindow.getContentBounds().height || 800) - webTabHeight,
    })
    browserView.webContents.on('destroyed', () => {
        closeWebTab(browserView.webContents.id)
    })
    browserView.webContents.setWindowOpenHandler(({url}) => {
        if (allowedCalls.test(url)) {
            return {action: 'allow'}
        }
        createWebTabWindow({url})
        return {action: 'deny'}
    })
    browserView.webContents.on('page-title-updated', (event, title) => {
        utils.onDispatchEvent(webTabWindow.webContents, {
            event: 'title',
            id: browserView.webContents.id,
            title: title,
            url: browserView.webContents.getURL(),
        }).then(_ => { })
    })
    browserView.webContents.on('did-fail-load', (event, errorCode, errorDescription, validatedURL, isMainFrame) => {
        if (!errorDescription) {
            return
        }
        utils.onDispatchEvent(webTabWindow.webContents, {
            event: 'title',
            id: browserView.webContents.id,
            title: errorDescription,
            url: browserView.webContents.getURL(),
        }).then(_ => { })
    })
    browserView.webContents.on('page-favicon-updated', (event, favicons) => {
        utils.onDispatchEvent(webTabWindow.webContents, {
            event: 'favicon',
            id: browserView.webContents.id,
            favicons
        }).then(_ => { })
    })
    browserView.webContents.on('did-start-loading', _ => {
        utils.onDispatchEvent(webTabWindow.webContents, {
            event: 'start-loading',
            id: browserView.webContents.id,
        }).then(_ => { })
    })
    browserView.webContents.on('did-stop-loading', _ => {
        utils.onDispatchEvent(webTabWindow.webContents, {
            event: 'stop-loading',
            id: browserView.webContents.id,
        }).then(_ => { })
        // 加载完成暗黑模式下把窗口背景色改成白色，避免透明网站背景色穿透
        if (nativeTheme.shouldUseDarkColors) {
            browserView.setBackgroundColor('#FFFFFF')
        }
    })
    browserView.webContents.on('before-input-event', (event, input) => {
        if (utils.isMetaOrControl(input) && input.key.toLowerCase() === 'r') {
            browserView.webContents.reload()
            event.preventDefault()
        } else if (utils.isMetaOrControl(input) && input.shift && input.key.toLowerCase() === 'i') {
            browserView.webContents.toggleDevTools()
        }
    })
    browserView.webContents.loadURL(args.url).then(_ => { }).catch(_ => { })

    webTabWindow.addBrowserView(browserView)
    webTabView.push({
        id: browserView.webContents.id,
        view: browserView
    })

    utils.onDispatchEvent(webTabWindow.webContents,  {
        event: 'create',
        id: browserView.webContents.id,
        url: args.url,
    }).then(_ => { })
    activateWebTab(browserView.webContents.id)
}

/**
 * 获取当前内置浏览器标签
 * @returns {Electron.BrowserView|undefined}
 */
function currentWebTab() {
    const views = webTabWindow.getBrowserViews()
    const view = views.length ? views[views.length - 1] : undefined
    if (!view) {
        return undefined
    }
    return webTabView.find(item => item.id == view.webContents.id)
}

/**
 * 重新加载内置浏览器标签
 * @param id
 */
function reloadWebTab(id) {
    const item = id === 0 ? currentWebTab() : webTabView.find(item => item.id == id)
    if (!item) {
        return
    }
    item.view.webContents.reload()
}

/**
 * 内置浏览器标签打开开发者工具
 * @param id
 */
function devToolsWebTab(id) {
    const item = id === 0 ? currentWebTab() : webTabView.find(item => item.id == id)
    if (!item) {
        return
    }
    item.view.webContents.toggleDevTools()
}

/**
 * 调整内置浏览器标签尺寸
 * @param id
 */
function resizeWebTab(id) {
    const item = id === 0 ? currentWebTab() : webTabView.find(item => item.id == id)
    if (!item) {
        return
    }
    item.view.setBounds({
        x: 0,
        y: webTabHeight,
        width: webTabWindow.getContentBounds().width || 1280,
        height: (webTabWindow.getContentBounds().height || 800) - webTabHeight,
    })
}

/**
 * 切换内置浏览器标签
 * @param id
 */
function activateWebTab(id) {
    const item = id === 0 ? currentWebTab() : webTabView.find(item => item.id == id)
    if (!item) {
        return
    }
    resizeWebTab(item.id)
    webTabWindow.setTopBrowserView(item.view)
    item.view.webContents.focus()
    utils.onDispatchEvent(webTabWindow.webContents,  {
        event: 'switch',
        id: item.id,
    }).then(_ => { })
}

/**
 * 关闭内置浏览器标签
 * @param id
 */
function closeWebTab(id) {
    const item = id === 0 ? currentWebTab() : webTabView.find(item => item.id == id)
    if (!item) {
        return
    }
    if (webTabView.length === 1) {
        webTabWindow.hide()
    }
    webTabWindow.removeBrowserView(item.view)
    try {
        item.view.webContents.close()
    } catch (e) {
        //
    }

    const index = webTabView.findIndex(({id}) => item.id == id)
    if (index > -1) {
        webTabView.splice(index, 1)
    }

    utils.onDispatchEvent(webTabWindow.webContents, {
        event: 'close',
        id: item.id,
    }).then(_ => { })

    if (webTabView.length === 0) {
        userConf.set('webTabWindow', webTabWindow.getBounds())
        webTabWindow.destroy()
    } else {
        activateWebTab(0)
    }
}

const getTheLock = app.requestSingleInstanceLock()
if (!getTheLock) {
    app.quit()
} else {
    app.on('second-instance', () => {
        utils.setShowWindow(mainWindow)
    })
    app.on('ready', () => {
        isReady = true
        // SameSite
        utils.useCookie()
        // 创建主窗口
        createMainWindow()
        // 创建托盘
        if (['darwin', 'win32'].includes(process.platform) && utils.isJson(config.trayIcon)) {
            mainTray = new Tray(path.join(__dirname, config.trayIcon[devloadUrl ? 'dev' : 'prod'][process.platform === 'darwin' ? 'mac' : 'win']));
            mainTray.on('click', () => {
                utils.setShowWindow(mainWindow)
            })
            mainTray.setToolTip(config.name)
            if (process.platform === 'win32') {
                const trayMenu = Menu.buildFromTemplate([{
                    label: '显示',
                    click: () => {
                        utils.setShowWindow(mainWindow)
                    }
                }, {
                    label: '退出',
                    click: () => {
                        app.quit()
                    }
                }])
                mainTray.setContextMenu(trayMenu)
            }
        }
        //
        if (process.platform === 'win32') {
            app.setAppUserModelId(config.name)
        }
        // 截图对象
        screenshotObj = new Screenshots({
            singleWindow: true,
            mainWindow: mainWindow
        })
    })
}

app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
        if (isReady) {
            createMainWindow()
        }
    } else if (mainWindow) {
        if (!mainWindow.isVisible()) {
            mainWindow.show()
        }
    }
})

app.on('window-all-closed', () => {
    if (willQuitApp || process.platform !== 'darwin') {
        app.quit()
    }
})

app.on('before-quit', () => {
    willQuitApp = true
})

app.on("will-quit",function(){
    globalShortcut.unregisterAll();
})

/**
 * 设置菜单语言包
 * @param args {path}
 */
ipcMain.on('setMenuLanguage', (event, args) => {
    if (utils.isJson(args)) {
        electronMenu.setLanguage(args)
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
ipcMain.on('openChildWindow', (event, args) => {
    createChildWindow(args)
    event.returnValue = "ok"
})

/**
 * 更新路由窗口
 * @param args {?name, ?path} // name: 不是要更改的窗口名，是要把窗口名改成什么， path: 地址
 */
ipcMain.on('updateChildWindow', (event, args) => {
    const browser = BrowserWindow.fromWebContents(event.sender);
    updateChildWindow(browser, args)
    event.returnValue = "ok"
})

/**
 * 获取路由窗口信息
 */
ipcMain.handle('getChildWindow', (event, args) => {
    let child;
    if (!args) {
        const browser = BrowserWindow.fromWebContents(event.sender);
        child = childWindow.find(({browser: win}) => win === browser)
    } else {
        child = childWindow.find(({name}) => name === args)
    }
    if (child) {
        return {
            name: child.name,
            id: child.browser.webContents.id,
            url: child.browser.webContents.getURL()
        }
    }
    return null;
});

/**
 * 创建路由窗口（todo 已废弃）
 * @param args {path, ?}
 */
ipcMain.on('windowRouter', (event, args) => {
    createChildWindow(args)
    event.returnValue = "ok"
})

/**
 * 更新路由窗口（todo 已废弃）
 * @param args {?name, ?path} // name: 不是要更改的窗口名，是要把窗口名改成什么， path: 地址
 */
ipcMain.on('updateRouter', (event, args) => {
    const browser = BrowserWindow.fromWebContents(event.sender);
    updateChildWindow(browser, args)
    event.returnValue = "ok"
})

/**
 * 内置浏览器 - 打开创建
 * @param args {url, ?}
 */
ipcMain.on('openWebTabWindow', (event, args) => {
    createWebTabWindow(args)
    event.returnValue = "ok"
})

/**
 * 内置浏览器 - 激活标签
 * @param id
 */
ipcMain.on('webTabActivate', (event, id) => {
    activateWebTab(id)
    event.returnValue = "ok"
})

/**
 * 内置浏览器 - 关闭标签
 * @param id
 */
ipcMain.on('webTabClose', (event, id) => {
    closeWebTab(id)
    event.returnValue = "ok"
})

/**
 * 内置浏览器 - 在外部浏览器打开
 */
ipcMain.on('webTabExternal', (event) => {
    const item = currentWebTab()
    if (!item) {
        return
    }
    openExternal(item.view.webContents.getURL())
    event.returnValue = "ok"
})

/**
 * 内置浏览器 - 打开开发者工具
 */
ipcMain.on('webTabOpenDevTools', (event) => {
    const item = currentWebTab()
    if (!item) {
        return
    }
    item.view.webContents.openDevTools()
    event.returnValue = "ok"
})

/**
 * 内置浏览器 - 销毁所有标签及窗口
 */
ipcMain.on('webTabDestroyAll', (event) => {
    if (webTabWindow) {
        webTabWindow.destroy()
    }
    event.returnValue = "ok"
})

/**
 * 隐藏窗口（mac、win隐藏，其他关闭）
 */
ipcMain.on('windowHidden', (event) => {
    if (['darwin', 'win32'].includes(process.platform)) {
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
 * 销毁窗口
 */
ipcMain.on('windowDestroy', (event) => {
    const win = BrowserWindow.fromWebContents(event.sender);
    win.destroy()
    event.returnValue = "ok"
})

/**
 * 关闭所有子窗口
 */
ipcMain.on('childWindowCloseAll', (event) => {
    childWindow.some(({browser}) => {
        browser && browser.close()
    })
    event.returnValue = "ok"
})

/**
 * 销毁所有子窗口
 */
ipcMain.on('childWindowDestroyAll', (event) => {
    childWindow.some(({browser}) => {
        browser && browser.destroy()
    })
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
 * 设置Dock标记（window闪烁、macos标记）
 * @param args
 */
ipcMain.on('setDockBadge', (event, args) => {
    if (process.platform === 'win32') {
        // Window flash
        if (!mainWindow.isFocused()) {
            mainWindow.once('focus', () => mainWindow.flashFrame(false))
            mainWindow.flashFrame(true)
        }
        return;
    }
    if (process.platform !== 'darwin') {
        // Mac only
        return;
    }
    let num = args;
    let tray = true;
    if (utils.isJson(args)) {
        num = args.num
        tray = !!args.tray
    }
    let text = typeof num === "string" ? num : (utils.runNum(num) > 0 ? String(num) : "")
    app.dock.setBadge(text)
    if (tray && mainTray) {
        mainTray.setTitle(text)
    }
    event.returnValue = "ok"
})

/**
 * 复制Base64图片
 * @param args
 */
ipcMain.on('copyBase64Image', (event, args) => {
    const { base64 } = args;
    if (base64) {
        const img = nativeImage.createFromDataURL(base64)
        clipboard.writeImage(img)
    }
    event.returnValue = "ok"
})

/**
 * 复制图片根据坐标
 * @param args
 */
ipcMain.on('copyImageAt', (event, args) => {
    try {
        event.sender.copyImageAt(args.x, args.y);
    } catch (e) {
        // log.error(e)
    }
    event.returnValue = "ok"
})

/**
 * 保存图片
 * @param args
 */
ipcMain.on('saveImageAt', async (event, args) => {
    await electronMenu.saveImageAs(args.url, args.params)
    event.returnValue = "ok"
})

/**
 * 绑定截图快捷键
 * @param args
 */
ipcMain.on('bindScreenshotKey', (event, args) => {
    const { key } = args;
    if (screenshotKey !== key) {
        if (screenshotKey) {
            globalShortcut.unregister(screenshotKey)
            screenshotKey = null
        }
        if (key) {
            screenshotKey = key
            globalShortcut.register(key, () => {
                screenshotObj.startCapture().then(_ => {
                    screenshotObj.view.webContents.executeJavaScript('if(typeof window.__initializeShortcuts===\'undefined\'){window.__initializeShortcuts=true;document.addEventListener(\'keydown\',function(e){console.log(e);if(e.keyCode===27){window.screenshots.cancel()}})}', true).catch(() => {});
                    screenshotObj.view.webContents.focus()
                })
            })
        }
    }
    event.returnValue = "ok"
})

/**
 * 执行截图
 */
ipcMain.on('openScreenshot', (event) => {
    if (screenshotObj) {
        screenshotObj.startCapture().then(_ => {})
    }
    event.returnValue = "ok"
})

/**
 * 关闭截图
 */
ipcMain.on('closeScreenshot', (event) => {
    if (screenshotObj && screenshotObj.window?.isFocused()) {
        screenshotObj.endCapture().then(_ => {});
    }
    event.returnValue = "ok"
})

/**
 * 通知
 */
ipcMain.on('openNotification', (event, args) => {
    const notifiy = new Notification(args);
    notifiy.addListener('click', _ => {
        mainWindow.webContents.send("clickNotification", args)
    })
    notifiy.addListener('reply', (event, reply) => {
        mainWindow.webContents.send("replyNotification", Object.assign(args, {reply}))
    })
    notifiy.show()
    event.returnValue = "ok"
})

//================================================================
// Update
//================================================================

let autoUpdating = 0
autoUpdater.logger = log
autoUpdater.autoDownload = false
autoUpdater.autoInstallOnAppQuit = true
autoUpdater.on('update-available', info => {
    mainWindow.webContents.send("updateAvailable", info)
})
autoUpdater.on('update-downloaded', info => {
    mainWindow.webContents.send("updateDownloaded", info)
})

/**
 * 检查更新
 */
ipcMain.on('updateCheckAndDownload', (event, args) => {
    event.returnValue = "ok"
    if (autoUpdating + 3600 > utils.dayjs().unix()) {
        return  // 限制1小时仅执行一次
    }
    if (args.provider) {
        autoUpdater.setFeedURL(args)
    }
    autoUpdater.checkForUpdates().then(info => {
        if (!info) {
            return
        }
        if (utils.compareVersion(config.version, info.updateInfo.version) >= 0) {
            return
        }
        if (args.apiVersion) {
            if (utils.compareVersion(info.updateInfo.version, args.apiVersion) <= 0) {
                // 客户端版本 <= 接口版本
                autoUpdating = utils.dayjs().unix()
                autoUpdater.downloadUpdate().then(_ => {}).catch(_ => {})
            }
        } else {
            autoUpdating = utils.dayjs().unix()
            autoUpdater.downloadUpdate().then(_ => {}).catch(_ => {})
        }
    })
})

/**
 * 将主窗口激活到顶层
 */
ipcMain.on('mainWindowTop', (event) => {
    mainWindow.moveTop()
    event.returnValue = "ok"
})

/**
 * 将主窗口激活
 */
ipcMain.on('mainWindowActive', (event) => {
    if (!mainWindow.isVisible()) {
        mainWindow.show()
    }
    mainWindow.focus()
    event.returnValue = "ok"
})

/**
 * 退出并安装更新
 */
ipcMain.on('updateQuitAndInstall', (event) => {
    event.returnValue = "ok"
    willQuitApp = true
    childWindow.some(({browser}) => {
        browser && browser.destroy()
    })
    setTimeout(_ => {
        autoUpdater.quitAndInstall(true, true)
    }, 1)
})

//================================================================
// Pdf export
//================================================================

const MICRON_TO_PIXEL = 264.58 		//264.58 micron = 1 pixel
const PIXELS_PER_INCH = 100.117		// Usually it is 100 pixels per inch but this give better results
const PNG_CHUNK_IDAT = 1229209940;
const LARGE_IMAGE_AREA = 30000000;

//NOTE: Key length must not be longer than 79 bytes (not checked)
function writePngWithText(origBuff, key, text, compressed, base64encoded) {
    let isDpi = key == 'dpi';
    let inOffset = 0;
    let outOffset = 0;
    let data = text;
    let dataLen = isDpi ? 9 : key.length + data.length + 1; //we add 1 zeros with non-compressed data, for pHYs it's 2 of 4-byte-int + 1 byte

    //prepare compressed data to get its size
    if (compressed) {
        data = zlib.deflateRawSync(encodeURIComponent(text));
        dataLen = key.length + data.length + 2; //we add 2 zeros with compressed data
    }

    let outBuff = Buffer.allocUnsafe(origBuff.length + dataLen + 4); //4 is the header size "zTXt", "tEXt" or "pHYs"

    try {
        let magic1 = origBuff.readUInt32BE(inOffset);
        inOffset += 4;
        let magic2 = origBuff.readUInt32BE(inOffset);
        inOffset += 4;

        if (magic1 != 0x89504e47 && magic2 != 0x0d0a1a0a) {
            throw new Error("PNGImageDecoder0");
        }

        outBuff.writeUInt32BE(magic1, outOffset);
        outOffset += 4;
        outBuff.writeUInt32BE(magic2, outOffset);
        outOffset += 4;
    } catch (e) {
        log.error(e.message, {stack: e.stack});
        throw new Error("PNGImageDecoder1");
    }

    try {
        while (inOffset < origBuff.length) {
            let length = origBuff.readInt32BE(inOffset);
            inOffset += 4;
            let type = origBuff.readInt32BE(inOffset)
            inOffset += 4;

            if (type == PNG_CHUNK_IDAT) {
                // Insert zTXt chunk before IDAT chunk
                outBuff.writeInt32BE(dataLen, outOffset);
                outOffset += 4;

                let typeSignature = isDpi ? 'pHYs' : (compressed ? "zTXt" : "tEXt");
                outBuff.write(typeSignature, outOffset);

                outOffset += 4;

                if (isDpi) {
                    let dpm = Math.round(parseInt(text) / 0.0254) || 3937; //One inch is equal to exactly 0.0254 meters. 3937 is 100dpi

                    outBuff.writeInt32BE(dpm, outOffset);
                    outBuff.writeInt32BE(dpm, outOffset + 4);
                    outBuff.writeInt8(1, outOffset + 8);
                    outOffset += 9;

                    data = Buffer.allocUnsafe(9);
                    data.writeInt32BE(dpm, 0);
                    data.writeInt32BE(dpm, 4);
                    data.writeInt8(1, 8);
                } else {
                    outBuff.write(key, outOffset);
                    outOffset += key.length;
                    outBuff.writeInt8(0, outOffset);
                    outOffset++;

                    if (compressed) {
                        outBuff.writeInt8(0, outOffset);
                        outOffset++;
                        data.copy(outBuff, outOffset);
                    } else {
                        outBuff.write(data, outOffset);
                    }

                    outOffset += data.length;
                }

                let crcVal = 0xffffffff;
                crcVal = crc.crcjam(typeSignature, crcVal);
                crcVal = crc.crcjam(data, crcVal);

                // CRC
                outBuff.writeInt32BE(crcVal ^ 0xffffffff, outOffset);
                outOffset += 4;

                // Writes the IDAT chunk after the zTXt
                outBuff.writeInt32BE(length, outOffset);
                outOffset += 4;
                outBuff.writeInt32BE(type, outOffset);
                outOffset += 4;

                origBuff.copy(outBuff, outOffset, inOffset);

                // Encodes the buffer using base64 if requested
                return base64encoded ? outBuff.toString('base64') : outBuff;
            }

            outBuff.writeInt32BE(length, outOffset);
            outOffset += 4;
            outBuff.writeInt32BE(type, outOffset);
            outOffset += 4;

            origBuff.copy(outBuff, outOffset, inOffset, inOffset + length + 4);// +4 to move past the crc

            inOffset += length + 4;
            outOffset += length + 4;
        }
    } catch (e) {
        log.error(e.message, {stack: e.stack});
        throw e;
    }
}

//TODO Create a lightweight html file similar to export3.html for exporting to vsdx
function exportVsdx(event, args, directFinalize) {
    let win = new BrowserWindow({
        width: 1280,
        height: 800,
        show: false,
        webPreferences: {
            preload: path.join(__dirname, 'electron-preload.js'),
            webSecurity: true,
            nodeIntegration: true,
            contextIsolation: true,
            nativeWindowOpen: true
        },
    })

    let loadEvtCount = 0;

    function loadFinished() {
        loadEvtCount++;

        if (loadEvtCount == 2) {
            win.webContents.send('export-vsdx', args);

            ipcMain.once('export-vsdx-finished', (evt, data) => {
                let hasError = false;

                if (data == null) {
                    hasError = true;
                }

                //Set finalize here since it is call in the reply below
                function finalize() {
                    win.destroy();
                }

                if (directFinalize === true) {
                    event.finalize = finalize;
                } else {
                    //Destroy the window after response being received by caller
                    ipcMain.once('export-finalize', finalize);
                }

                if (hasError) {
                    event.reply('export-error');
                } else {
                    event.reply('export-success', data);
                }
            });
        }
    }

    //Order of these two events is not guaranteed, so wait for them async.
    //TOOD There is still a chance we catch another window 'app-load-finished' if user created multiple windows quickly
    ipcMain.once('app-load-finished', loadFinished);
    win.webContents.on('did-finish-load', loadFinished);
}

async function mergePdfs(pdfFiles, xml) {
    //Pass throgh single files
    if (pdfFiles.length == 1 && xml == null) {
        return pdfFiles[0];
    }

    try {
        const pdfDoc = await PDFDocument.create();
        pdfDoc.setCreator(config.name);

        if (xml != null) {
            //Embed diagram XML as file attachment
            await pdfDoc.attach(Buffer.from(xml).toString('base64'), config.name + '.xml', {
                mimeType: 'application/vnd.jgraph.mxfile',
                description: config.name + ' Content'
            });
        }

        for (let i = 0; i < pdfFiles.length; i++) {
            const pdfFile = await PDFDocument.load(pdfFiles[i].buffer);
            const pages = await pdfDoc.copyPages(pdfFile, pdfFile.getPageIndices());
            pages.forEach(p => pdfDoc.addPage(p));
        }

        const pdfBytes = await pdfDoc.save();
        return Buffer.from(pdfBytes);
    } catch (e) {
        throw new Error('Error during PDF combination: ' + e.message);
    }
}

//TODO Use canvas to export images if math is not used to speedup export (no capturePage). Requires change to export3.html also
function exportDiagram(event, args, directFinalize) {
    if (args.format == 'vsdx') {
        exportVsdx(event, args, directFinalize);
        return;
    }

    let browser = null;

    try {
        browser = new BrowserWindow({
            webPreferences: {
                preload: path.join(__dirname, 'electron-preload.js'),
                backgroundThrottling: false,
                contextIsolation: true,
                disableBlinkFeatures: 'Auxclick' // Is this needed?
            },
            show: false,
            frame: false,
            enableLargerThanScreen: true,
            transparent: args.format == 'png' && (args.bg == null || args.bg == 'none'),
        });

        if (devloadUrl) {
            browser.loadURL(devloadUrl + 'drawio/webapp/export3.html').then(_ => {

            })
        } else {
            browser.loadFile('./public/drawio/webapp/export3.html').then(_ => {

            })
        }

        const contents = browser.webContents;
        let pageByPage = (args.format == 'pdf' && !args.print), from, to, pdfs;

        if (pageByPage) {
            from = args.allPages ? 0 : parseInt(args.from || 0);
            to = args.allPages ? 1000 : parseInt(args.to || 1000) + 1; //The 'to' will be corrected later
            pdfs = [];

            args.from = from;
            args.to = from;
            args.allPages = false;
        }

        contents.on('did-finish-load', function () {
            //Set finalize here since it is call in the reply below
            function finalize() {
                browser.destroy();
            }

            if (directFinalize === true) {
                event.finalize = finalize;
            } else {
                //Destroy the window after response being received by caller
                ipcMain.once('export-finalize', finalize);
            }

            function renderingFinishHandler(evt, renderInfo) {
                if (renderInfo == null) {
                    event.reply('export-error');
                    return;
                }

                let pageCount = renderInfo.pageCount, bounds = null;
                //For some reason, Electron 9 doesn't send this object as is without stringifying. Usually when variable is external to function own scope
                try {
                    bounds = JSON.parse(renderInfo.bounds);
                } catch (e) {
                    bounds = null;
                }

                let pdfOptions = {pageSize: 'A4'};
                let hasError = false;

                if (bounds == null || bounds.width < 5 || bounds.height < 5) //very small page size never return from printToPDF
                {
                    //A workaround to detect errors in the input file or being empty file
                    hasError = true;
                } else {
                    pdfOptions = {
                        printBackground: true,
                        pageSize: {
                            width: bounds.width / PIXELS_PER_INCH,
                            height: (bounds.height + 2) / PIXELS_PER_INCH //the extra 2 pixels to prevent adding an extra empty page
                        },
                        margins: {
                            top: 0,
                            bottom: 0,
                            left: 0,
                            right: 0
                        } // no margin
                    }
                }

                let base64encoded = args.base64 == '1';

                if (hasError) {
                    event.reply('export-error');
                } else if (args.format == 'png' || args.format == 'jpg' || args.format == 'jpeg') {
                    //Adds an extra pixel to prevent scrollbars from showing
                    let newBounds = {
                        width: Math.ceil(bounds.width + bounds.x) + 1,
                        height: Math.ceil(bounds.height + bounds.y) + 1
                    };
                    browser.setBounds(newBounds);

                    //TODO The browser takes sometime to show the graph (also after resize it takes some time to render)
                    //	 	1 sec is most probably enough (for small images, 5 for large ones) BUT not a stable solution
                    setTimeout(function () {
                        browser.capturePage().then(function (img) {
                            //Image is double the given bounds, so resize is needed!
                            let tScale = 1;

                            //If user defined width and/or height, enforce it precisely here. Height override width
                            if (args.h) {
                                tScale = args.h / newBounds.height;
                            } else if (args.w) {
                                tScale = args.w / newBounds.width;
                            }

                            newBounds.width *= tScale;
                            newBounds.height *= tScale;
                            img = img.resize(newBounds);

                            let data = args.format == 'png' ? img.toPNG() : img.toJPEG(args.jpegQuality || 90);

                            if (args.dpi != null && args.format == 'png') {
                                data = writePngWithText(data, 'dpi', args.dpi);
                            }

                            if (args.embedXml == "1" && args.format == 'png') {
                                data = writePngWithText(data, "mxGraphModel", args.xml, true,
                                    base64encoded);
                            } else {
                                if (base64encoded) {
                                    data = data.toString('base64');
                                }
                            }

                            event.reply('export-success', data);
                        });
                    }, bounds.width * bounds.height < LARGE_IMAGE_AREA ? 1000 : 5000);
                } else if (args.format == 'pdf') {
                    if (args.print) {
                        pdfOptions = {
                            scaleFactor: args.pageScale,
                            printBackground: true,
                            pageSize: {
                                width: args.pageWidth * MICRON_TO_PIXEL,
                                //This height adjustment fixes the output. TODO Test more cases
                                height: (args.pageHeight * 1.025) * MICRON_TO_PIXEL
                            },
                            marginsType: 1 // no margin
                        };

                        contents.print(pdfOptions, (success, errorType) => {
                            //Consider all as success
                            event.reply('export-success', {});
                        });
                    } else {
                        contents.printToPDF(pdfOptions).then(async (data) => {
                            pdfs.push(data);
                            to = to > pageCount ? pageCount : to;
                            from++;

                            if (from < to) {
                                args.from = from;
                                args.to = from;
                                ipcMain.once('render-finished', renderingFinishHandler);
                                contents.send('render', args);
                            } else {
                                data = await mergePdfs(pdfs, args.embedXml == '1' ? args.xml : null);
                                event.reply('export-success', data);
                            }
                        })
                            .catch((error) => {
                                event.reply('export-error', error);
                            });
                    }
                } else if (args.format == 'svg') {
                    contents.send('get-svg-data');

                    ipcMain.once('svg-data', (evt, data) => {
                        event.reply('export-success', data);
                    });
                } else {
                    event.reply('export-error', 'Error: Unsupported format');
                }
            }

            ipcMain.once('render-finished', renderingFinishHandler);

            if (args.format == 'xml') {
                ipcMain.once('xml-data', (evt, data) => {
                    event.reply('export-success', data);
                });

                ipcMain.once('xml-data-error', () => {
                    event.reply('export-error');
                });
            }

            args.border = args.border || 0;
            args.scale = args.scale || 1;

            contents.send('render', args);
        });
    } catch (e) {
        if (browser != null) {
            browser.destroy();
        }

        event.reply('export-error', e);
        console.log('export-error', e);
    }
}

ipcMain.on('export', exportDiagram);

//================================================================
// Renderer Helper functions
//================================================================

const {O_SYNC, O_CREAT, O_WRONLY, O_TRUNC, O_RDONLY} = fs.constants;
const DRAFT_PREFEX = '.$';
const OLD_DRAFT_PREFEX = '~$';
const DRAFT_EXT = '.dtmp';
const BKP_PREFEX = '.$';
const OLD_BKP_PREFEX = '~$';
const BKP_EXT = '.bkp';

/**
 * Checks the file content type
 * Confirm content is xml, pdf, png, jpg, svg, vsdx ...
 */
function checkFileContent(body, enc) {
    if (body != null) {
        let head, headBinay;

        if (typeof body === 'string') {
            if (enc == 'base64') {
                headBinay = Buffer.from(body.substring(0, 22), 'base64');
                head = headBinay.toString();
            } else {
                head = body.substring(0, 16);
                headBinay = Buffer.from(head);
            }
        } else {
            head = new TextDecoder("utf-8").decode(body.subarray(0, 16));
            headBinay = body;
        }

        let c1 = head[0],
            c2 = head[1],
            c3 = head[2],
            c4 = head[3],
            c5 = head[4],
            c6 = head[5],
            c7 = head[6],
            c8 = head[7],
            c9 = head[8],
            c10 = head[9],
            c11 = head[10],
            c12 = head[11],
            c13 = head[12],
            c14 = head[13],
            c15 = head[14],
            c16 = head[15];

        let cc1 = headBinay[0],
            cc2 = headBinay[1],
            cc3 = headBinay[2],
            cc4 = headBinay[3],
            cc5 = headBinay[4],
            cc6 = headBinay[5],
            cc7 = headBinay[6],
            cc8 = headBinay[7],
            cc9 = headBinay[8],
            cc10 = headBinay[9],
            cc11 = headBinay[10],
            cc12 = headBinay[11],
            cc13 = headBinay[12],
            cc14 = headBinay[13],
            cc15 = headBinay[14],
            cc16 = headBinay[15];

        if (c1 == '<') {
            // text/html
            if (c2 == '!'
                || ((c2 == 'h'
                    && (c3 == 't' && c4 == 'm' && c5 == 'l'
                        || c3 == 'e' && c4 == 'a' && c5 == 'd')
                    || (c2 == 'b' && c3 == 'o' && c4 == 'd'
                        && c5 == 'y')))
                || ((c2 == 'H'
                    && (c3 == 'T' && c4 == 'M' && c5 == 'L'
                        || c3 == 'E' && c4 == 'A' && c5 == 'D')
                    || (c2 == 'B' && c3 == 'O' && c4 == 'D'
                        && c5 == 'Y')))) {
                return true;
            }

            // application/xml
            if (c2 == '?' && c3 == 'x' && c4 == 'm' && c5 == 'l'
                && c6 == ' ') {
                return true;
            }

            // application/svg+xml
            if (c2 == 's' && c3 == 'v' && c4 == 'g' && c5 == ' ') {
                return true;
            }
        }

        // big and little (identical) endian UTF-8 encodings, with BOM
        // application/xml
        if (cc1 == 0xef && cc2 == 0xbb && cc3 == 0xbf) {
            if (c4 == '<' && c5 == '?' && c6 == 'x') {
                return true;
            }
        }

        // big and little endian UTF-16 encodings, with byte order mark
        // application/xml
        if (cc1 == 0xfe && cc2 == 0xff) {
            if (cc3 == 0 && c4 == '<' && cc5 == 0 && c6 == '?' && cc7 == 0
                && c8 == 'x') {
                return true;
            }
        }

        // application/xml
        if (cc1 == 0xff && cc2 == 0xfe) {
            if (c3 == '<' && cc4 == 0 && c5 == '?' && cc6 == 0 && c7 == 'x'
                && cc8 == 0) {
                return true;
            }
        }

        // big and little endian UTF-32 encodings, with BOM
        // application/xml
        if (cc1 == 0x00 && cc2 == 0x00 && cc3 == 0xfe && cc4 == 0xff) {
            if (cc5 == 0 && cc6 == 0 && cc7 == 0 && c8 == '<' && cc9 == 0
                && cc10 == 0 && cc11 == 0 && c12 == '?' && cc13 == 0
                && cc14 == 0 && cc15 == 0 && c16 == 'x') {
                return true;
            }
        }

        // application/xml
        if (cc1 == 0xff && cc2 == 0xfe && cc3 == 0x00 && cc4 == 0x00) {
            if (c5 == '<' && cc6 == 0 && cc7 == 0 && cc8 == 0 && c9 == '?'
                && cc10 == 0 && cc11 == 0 && cc12 == 0 && c13 == 'x'
                && cc14 == 0 && cc15 == 0 && cc16 == 0) {
                return true;
            }
        }

        // application/pdf (%PDF-)
        if (cc1 == 37 && cc2 == 80 && cc3 == 68 && cc4 == 70 && cc5 == 45) {
            return true;
        }

        // image/png
        if ((cc1 == 137 && cc2 == 80 && cc3 == 78 && cc4 == 71 && cc5 == 13
                && cc6 == 10 && cc7 == 26 && cc8 == 10) ||
            (cc1 == 194 && cc2 == 137 && cc3 == 80 && cc4 == 78 && cc5 == 71 && cc6 == 13 //Our embedded PNG+XML
                && cc7 == 10 && cc8 == 26 && cc9 == 10)) {
            return true;
        }

        // image/jpeg
        if (cc1 == 0xFF && cc2 == 0xD8 && cc3 == 0xFF) {
            if (cc4 == 0xE0 || cc4 == 0xEE) {
                return true;
            }

            /**
             * File format used by digital cameras to store images.
             * Exif Format can be read by any application supporting
             * JPEG. Exif Spec can be found at:
             * http://www.pima.net/standards/it10/PIMA15740/Exif_2-1.PDF
             */
            if ((cc4 == 0xE1) && (c7 == 'E' && c8 == 'x' && c9 == 'i'
                && c10 == 'f' && cc11 == 0)) {
                return true;
            }
        }

        // vsdx, vssx (also zip, jar, odt, ods, odp, docx, xlsx, pptx, apk, aar)
        if (cc1 == 0x50 && cc2 == 0x4B && cc3 == 0x03 && cc4 == 0x04) {
            return true;
        } else if (cc1 == 0x50 && cc2 == 0x4B && cc3 == 0x03 && cc4 == 0x06) {
            return true;
        }

        // mxfile, mxlibrary, mxGraphModel
        if (c1 == '<' && c2 == 'm' && c3 == 'x') {
            return true;
        }
    }

    return false;
}

function isConflict(origStat, stat) {
    return stat != null && origStat != null && stat.mtimeMs != origStat.mtimeMs;
}

function getDraftFileName(fileObject) {
    let filePath = fileObject.path;
    let draftFileName = '', counter = 1, uniquePart = '';

    do {
        draftFileName = path.join(path.dirname(filePath), DRAFT_PREFEX + path.basename(filePath) + uniquePart + DRAFT_EXT);
        uniquePart = '_' + counter++;
    } while (fs.existsSync(draftFileName));

    return draftFileName;
}

async function getFileDrafts(fileObject) {
    let filePath = fileObject.path;
    let draftsPaths = [], drafts = [], draftFileName, counter = 1, uniquePart = '';

    do {
        draftsPaths.push(draftFileName);
        draftFileName = path.join(path.dirname(filePath), DRAFT_PREFEX + path.basename(filePath) + uniquePart + DRAFT_EXT);
        uniquePart = '_' + counter++;
    } while (fs.existsSync(draftFileName)); //TODO this assume continuous drafts names

    //Port old draft files to new prefex
    counter = 1;
    uniquePart = '';
    let draftExists = false;

    do {
        draftFileName = path.join(path.dirname(filePath), OLD_DRAFT_PREFEX + path.basename(filePath) + uniquePart + DRAFT_EXT);
        draftExists = fs.existsSync(draftFileName);

        if (draftExists) {
            const newDraftFileName = path.join(path.dirname(filePath), DRAFT_PREFEX + path.basename(filePath) + uniquePart + DRAFT_EXT);
            await fsProm.rename(draftFileName, newDraftFileName);
            draftsPaths.push(newDraftFileName);
        }

        uniquePart = '_' + counter++;
    } while (draftExists); //TODO this assume continuous drafts names

    //Skip the first null element
    for (let i = 1; i < draftsPaths.length; i++) {
        try {
            let stat = await fsProm.lstat(draftsPaths[i]);
            drafts.push({
                data: await fsProm.readFile(draftsPaths[i], 'utf8'),
                created: stat.ctimeMs,
                modified: stat.mtimeMs,
                path: draftsPaths[i]
            });
        } catch (e) {
        } // Ignore
    }

    return drafts;
}

async function saveDraft(fileObject, data) {
    if (!checkFileContent(data)) {
        throw new Error('Invalid file data');
    } else {
        let draftFileName = fileObject.draftFileName || getDraftFileName(fileObject);
        await fsProm.writeFile(draftFileName, data, 'utf8');

        if (isWin) {
            try {
                // Add Hidden attribute:
                spawn('attrib', ['+h', draftFileName], {shell: true});
            } catch (e) {
            }
        }

        return draftFileName;
    }
}

async function saveFile(fileObject, data, origStat, overwrite, defEnc) {
    if (!checkFileContent(data)) {
        throw new Error('Invalid file data');
    }

    let retryCount = 0;
    let backupCreated = false;
    let bkpPath = path.join(path.dirname(fileObject.path), BKP_PREFEX + path.basename(fileObject.path) + BKP_EXT);
    const oldBkpPath = path.join(path.dirname(fileObject.path), OLD_BKP_PREFEX + path.basename(fileObject.path) + BKP_EXT);
    let writeEnc = defEnc || fileObject.encoding;

    let writeFile = async function () {
        let fh;

        try {
            // O_SYNC is for sync I/O and reduce risk of file corruption
            fh = await fsProm.open(fileObject.path, O_SYNC | O_CREAT | O_WRONLY | O_TRUNC);
            await fsProm.writeFile(fh, data, writeEnc);
        } finally {
            await fh?.close();
        }

        let stat2 = await fsProm.stat(fileObject.path);
        // Workaround for possible writing errors is to check the written
        // contents of the file and retry 3 times before showing an error
        let writtenData = await fsProm.readFile(fileObject.path, writeEnc);

        if (data != writtenData) {
            retryCount++;

            if (retryCount < 3) {
                return await writeFile();
            } else {
                throw new Error('all saving trials failed');
            }
        } else {
            //We'll keep the backup file in case the original file is corrupted. TODO When should we delete the backup file?
            if (backupCreated) {
                //fs.unlink(bkpPath, (err) => {}); //Ignore errors!

                //Delete old backup file with old prefix
                if (fs.existsSync(oldBkpPath)) {
                    fs.unlink(oldBkpPath, (err) => {
                    }); //Ignore errors
                }
            }

            return stat2;
        }
    };

    async function doSaveFile(isNew) {
        if (enableStoreBkp && !isNew) {
            //Copy file to backup file (after conflict and stat is checked)
            let bkpFh;

            try {
                //Use file read then write to open the backup file direct sync write to reduce the chance of file corruption
                let fileContent = await fsProm.readFile(fileObject.path, writeEnc);
                bkpFh = await fsProm.open(bkpPath, O_SYNC | O_CREAT | O_WRONLY | O_TRUNC);
                await fsProm.writeFile(bkpFh, fileContent, writeEnc);
                backupCreated = true;
            } catch (e) {
                if (__DEV__) {
                    console.log('Backup file writing failed', e); //Ignore
                }
            } finally {
                await bkpFh?.close();

                if (isWin) {
                    try {
                        // Add Hidden attribute:
                        spawn('attrib', ['+h', bkpPath], {shell: true});
                    } catch (e) {
                    }
                }
            }
        }

        return await writeFile();
    }

    if (overwrite) {
        return await doSaveFile(true);
    } else {
        let stat = fs.existsSync(fileObject.path) ?
            await fsProm.stat(fileObject.path) : null;

        if (stat && isConflict(origStat, stat)) {
            throw new Error('conflict');
        } else {
            return await doSaveFile(stat == null);
        }
    }
}

async function writeFile(path, data, enc) {
    if (!checkFileContent(data, enc)) {
        throw new Error('Invalid file data');
    } else {
        return await fsProm.writeFile(path, data, enc);
    }
}

function getAppDataFolder() {
    try {
        let appDataDir = app.getPath('appData');
        let drawioDir = appDataDir + '/' + config.name;

        if (!fs.existsSync(drawioDir)) //Usually this dir already exists
        {
            fs.mkdirSync(drawioDir);
        }

        return drawioDir;
    } catch (e) {
    }

    return '.';
}

function getDocumentsFolder() {
    //On windows, misconfigured Documents folder cause an exception
    try {
        return app.getPath('documents');
    } catch (e) {
    }

    return '.';
}

function checkFileExists(pathParts) {
    let filePath = path.join(...pathParts);
    return {exists: fs.existsSync(filePath), path: filePath};
}

async function showOpenDialog(defaultPath, filters, properties) {
    let win = BrowserWindow.getFocusedWindow();

    return dialog.showOpenDialog(win, {
        defaultPath: defaultPath,
        filters: filters,
        properties: properties
    });
}

async function showSaveDialog(defaultPath, filters) {
    let win = BrowserWindow.getFocusedWindow();

    return dialog.showSaveDialog(win, {
        defaultPath: defaultPath,
        filters: filters
    });
}

async function installPlugin(filePath) {
    if (!enablePlugins) return {};

    let pluginsDir = path.join(getAppDataFolder(), '/plugins');

    if (!fs.existsSync(pluginsDir)) {
        fs.mkdirSync(pluginsDir);
    }

    let pluginName = path.basename(filePath);
    let dstFile = path.join(pluginsDir, pluginName);

    if (fs.existsSync(dstFile)) {
        throw new Error('fileExists');
    } else {
        await fsProm.copyFile(filePath, dstFile);
    }

    return {pluginName: pluginName, selDir: path.dirname(filePath)};
}

function getPluginFile(plugin) {
    if (!enablePlugins) return null;

    const prefix = path.join(getAppDataFolder(), '/plugins/');
    const pluginFile = path.join(prefix, plugin);

    if (pluginFile.startsWith(prefix) && fs.existsSync(pluginFile)) {
        return pluginFile;
    }

    return null;
}

function uninstallPlugin(plugin) {
    const pluginFile = getPluginFile(plugin);

    if (pluginFile != null) {
        fs.unlinkSync(pluginFile);
    }
}

function dirname(path_p) {
    return path.dirname(path_p);
}

async function readFile(filename, encoding) {
    let data = await fsProm.readFile(filename, encoding);

    if (checkFileContent(data, encoding)) {
        return data;
    }

    throw new Error('Invalid file data');
}

async function fileStat(file) {
    return await fsProm.stat(file);
}

async function isFileWritable(file) {
    try {
        await fsProm.access(file, fs.constants.W_OK);
        return true;
    } catch (e) {
        return false;
    }
}

function clipboardAction(method, data) {
    if (method == 'writeText') {
        clipboard.writeText(data);
    } else if (method == 'readText') {
        return clipboard.readText();
    } else if (method == 'writeImage') {
        clipboard.write({
            image:
                nativeImage.createFromDataURL(data.dataUrl), html: '<img src="' +
                data.dataUrl + '" width="' + data.w + '" height="' + data.h + '">'
        });
    }
}

async function deleteFile(file) {
    // Reading the header of the file to confirm it is a file we can delete
    let fh = await fsProm.open(file, O_RDONLY);
    let buffer = Buffer.allocUnsafe(16);
    await fh.read(buffer, 0, 16);
    await fh.close();

    if (checkFileContent(buffer)) {
        await fsProm.unlink(file);
    }
}

function windowAction(method) {
    let win = BrowserWindow.getFocusedWindow();

    if (win) {
        if (method == 'minimize') {
            win.minimize();
        } else if (method == 'maximize') {
            win.maximize();
        } else if (method == 'unmaximize') {
            win.unmaximize();
        } else if (method == 'close') {
            win.close();
        } else if (method == 'isMaximized') {
            return win.isMaximized();
        } else if (method == 'removeAllListeners') {
            win.removeAllListeners();
        }
    }
}

function openExternal(url) {
    //Only open http(s), mailto, tel, and callto links
    if (allowedUrls.test(url)) {
        shell.openExternal(url);
        return true;
    }

    return false;
}

function watchFile(path) {
    let win = BrowserWindow.getFocusedWindow();

    if (win) {
        fs.watchFile(path, (curr, prev) => {
            try {
                win.webContents.send('fileChanged', {
                    path: path,
                    curr: curr,
                    prev: prev
                });
            } catch (e) {
            } // Ignore
        });
    }
}

function unwatchFile(path) {
    fs.unwatchFile(path);
}

function getCurDir() {
    return __dirname;
}

ipcMain.on("rendererReq", async (event, args) => {
    try {
        let ret = null;

        switch (args.action) {
            case 'saveFile':
                ret = await saveFile(args.fileObject, args.data, args.origStat, args.overwrite, args.defEnc);
                break;
            case 'writeFile':
                ret = await writeFile(args.path, args.data, args.enc);
                break;
            case 'saveDraft':
                ret = await saveDraft(args.fileObject, args.data);
                break;
            case 'getFileDrafts':
                ret = await getFileDrafts(args.fileObject);
                break;
            case 'getDocumentsFolder':
                ret = await getDocumentsFolder();
                break;
            case 'checkFileExists':
                ret = await checkFileExists(args.pathParts);
                break;
            case 'showOpenDialog':
                dialogOpen = true;
                ret = await showOpenDialog(args.defaultPath, args.filters, args.properties);
                ret = ret.filePaths;
                dialogOpen = false;
                break;
            case 'showSaveDialog':
                dialogOpen = true;
                ret = await showSaveDialog(args.defaultPath, args.filters);
                ret = ret.canceled ? null : ret.filePath;
                dialogOpen = false;
                break;
            case 'installPlugin':
                ret = await installPlugin(args.filePath);
                break;
            case 'uninstallPlugin':
                ret = await uninstallPlugin(args.plugin);
                break;
            case 'getPluginFile':
                ret = await getPluginFile(args.plugin);
                break;
            case 'isPluginsEnabled':
                ret = enablePlugins;
                break;
            case 'dirname':
                ret = await dirname(args.path);
                break;
            case 'readFile':
                ret = await readFile(args.filename, args.encoding);
                break;
            case 'clipboardAction':
                ret = await clipboardAction(args.method, args.data);
                break;
            case 'deleteFile':
                ret = await deleteFile(args.file);
                break;
            case 'fileStat':
                ret = await fileStat(args.file);
                break;
            case 'isFileWritable':
                ret = await isFileWritable(args.file);
                break;
            case 'windowAction':
                ret = await windowAction(args.method);
                break;
            case 'openExternal':
                ret = await openExternal(args.url);
                break;
            case 'watchFile':
                ret = await watchFile(args.path);
                break;
            case 'unwatchFile':
                ret = await unwatchFile(args.path);
                break;
            case 'getCurDir':
                ret = await getCurDir();
                break;
        }

        event.reply('mainResp', {success: true, data: ret, reqId: args.reqId});
    } catch (e) {
        event.reply('mainResp', {error: true, msg: e.message, e: e, reqId: args.reqId});
    }
});
