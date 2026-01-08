// Node.js 核心模块
const fs = require('fs')
const os = require("os");
const path = require('path')
const spawn = require("child_process").spawn;

// Web 服务相关
const express = require('express')
const axios = require('axios');

// Electron 核心模块
const {
    app,
    ipcMain,
    dialog,
    clipboard,
    nativeImage,
    globalShortcut,
    nativeTheme,
    Tray,
    Menu,
    WebContentsView,
    BrowserWindow
} = require('electron')

// 禁用渲染器后台化
app.commandLine.appendSwitch('disable-renderer-backgrounding');
app.commandLine.appendSwitch('disable-backgrounding-occluded-windows');

// Electron 扩展和工具
const {autoUpdater} = require("electron-updater")
const Store = require("electron-store");
const loger = require("electron-log");
const electronConf = require('electron-config')
const Screenshots = require("electron-screenshots-tool").Screenshots;

// 本地模块和配置
const utils = require('./lib/utils');
const navigation = require('./lib/navigation');
const config = require('./package.json');
const electronDown = require("./electron-down");
const electronMenu = require("./electron-menu");
const { startMCPServer, stopMCPServer } = require("./lib/mcp");
const {onRenderer} = require("./lib/renderer");
const {onExport} = require("./lib/pdf-export");
const {allowedCalls, isWin} = require("./lib/other");

// 实例初始化
const userConf = new electronConf()
const store = new Store();

// 路径和缓存配置
const cacheDir = path.join(os.tmpdir(), 'dootask-cache')
const updaterLockFile = path.join(cacheDir, '.dootask_updater.lock');

// 应用状态标志
let isReady = false,
    willQuitApp = false,
    isDevelopMode = false;

// 服务器配置
let serverPort = 22223,
    mcpPort = 22224,
    serverPublicDir = path.join(__dirname, 'public'),
    serverUrl = "",
    serverTimer = null;

// 截图相关变量
let screenshotObj = null,
    screenshotKey = null;

// 窗口实例变量
let mainWindow = null,
    mainTray = null,
    preloadWindow = null,
    mediaWindow = null,
    webTabWindow = null;

// 窗口数组和状态
let childWindow = [],
    webTabView = [];

// 窗口配置和状态
let mediaType = null,
    webTabHeight = 40,
    webTabClosedByShortcut = false;

// 开发模式路径
let devloadPath = path.resolve(__dirname, ".devload");

// 窗口显示状态管理
let showState = {},
    onShowWindow = (win) => {
        try {
            if (typeof showState[win.webContents.id] === 'undefined') {
                showState[win.webContents.id] = true
                win.show();
            }
        } catch (e) {
            // loger.error(e)
        }
    }

// 开发模式加载
if (fs.existsSync(devloadPath)) {
    let devloadContent = fs.readFileSync(devloadPath, 'utf8')
    if (devloadContent.startsWith('http')) {
        serverUrl = devloadContent;
        isDevelopMode = true;
    }
}

// 缓存目录检查
if (!fs.existsSync(cacheDir)) {
    fs.mkdirSync(cacheDir, { recursive: true });
}

// 初始化下载
electronDown.initialize(() => {
    if (mainWindow) {
        mainWindow.webContents.send("openDownloadWindow", {})
    }
})

/**
 * 启动web服务
 */
async function startWebServer(force = false) {
    if (serverUrl && !force) {
        return Promise.resolve();
    }

    // 每次启动前清理缓存
    utils.clearServerCache();

    return new Promise((resolve, reject) => {
        // 创建Express应用
        const expressApp = express();

        // 健康检查
        expressApp.head('/health', (req, res) => {
            res.status(200).send('OK');
        });

        // 使用express.static中间件提供静态文件服务
        // Express内置了全面的MIME类型支持，无需手动配置
        expressApp.use(express.static(serverPublicDir, {
            // 设置默认文件
            index: ['index.html', 'index.htm'],
            // 启用etag缓存
            etag: true,
            // 设置缓存时间（开发环境可以设置较短）
            maxAge: '1h',
            // 启用压缩
            dotfiles: 'ignore',
            // 自定义头部
            setHeaders: (res, path, stat) => {
                const ext = path.split('.').pop().toLowerCase();
                // HTML、JS、CSS文件禁用缓存，方便开发调试
                if (['html', 'js', 'css'].includes(ext)){
                    res.set('Cache-Control', 'no-cache, no-store, must-revalidate');
                    res.set('Pragma', 'no-cache');
                    res.set('Expires', '0');
                }
            }
        }));

        // 404处理中间件
        expressApp.use((req, res) => {
            res.status(404).send('File not found');
        });

        // 错误处理中间件
        expressApp.use((err, req, res, next) => {
            // 不是ENOENT错误，记录error级别日志
            if (err.code !== 'ENOENT') {
                loger.error('Server error:', err);
                res.status(500).send('Internal Server Error');
                return;
            }

            // 没有path，说明是404错误
            if (!err.path) {
                loger.warn('File not found:', req.url);
                res.status(404).send('File not found');
                return;
            }

            // 不是临时文件错误，普通404
            if (!err.path.includes('.com.dootask.task.')) {
                loger.warn('File not found:', err.path);
                res.status(404).send('File not found');
                return;
            }

            // 防止死循环 - 如果已经是重定向请求，直接返回404
            if (req.query._dt_restored) {
                const redirectTime = parseInt(req.query._dt_restored);
                const timeDiff = Date.now() - redirectTime;
                // 10秒内的重定向认为是死循环，直接返回404
                if (timeDiff < 10000) {
                    loger.warn('Recent redirect detected, avoiding loop:', timeDiff + 'ms ago');
                    res.status(404).send('File not found');
                    return;
                }
            }

            loger.warn('Temporary file cleaned up by system:', err.path, req.url);

            // 临时文件被系统清理，尝试从serverPublicDir重新读取并恢复
            const requestedUrl = new URL(req.url, serverUrl);
            const requestedFile = path.join(serverPublicDir, requestedUrl.pathname === '/' ? '/index.html' : requestedUrl.pathname);
            try {
                // 检查文件是否存在于serverPublicDir
                fs.accessSync(requestedFile, fs.constants.F_OK);

                // 确保目标目录存在
                const targetDir = path.dirname(err.path);
                if (!fs.existsSync(targetDir)) {
                    fs.mkdirSync(targetDir, {recursive: true});
                }

                // 从ASAR文件中读取文件并写入到临时位置
                fs.writeFileSync(err.path, fs.readFileSync(requestedFile));

                // 文件恢复成功后，301重定向到带__redirect参数的URL
                requestedUrl.searchParams.set('_dt_restored', Date.now());
                res.redirect(301, requestedUrl.toString());
            } catch (accessErr) {
                // 文件不存在于serverPublicDir，返回404
                loger.warn('Source file not found:', requestedFile, 'Error:', accessErr.message);
                res.status(404).send('File not found');
            }
        });

        // 启动服务器
        const server = expressApp.listen(serverPort, 'localhost', () => {
            loger.info(`Express static file server running at http://localhost:${serverPort}/`);
            loger.info(`Serving files from: ${serverPublicDir}`);
            serverUrl = `http://localhost:${serverPort}/`;
            resolve(server);
            // 启动健康检查定时器
            serverTimeout();
        });

        // 错误处理
        server.on('error', (err) => {
            loger.error('Server error:', err);
            reject(err);
        });
    });
}

/**
 * 健康检查定时器
 */
function serverTimeout() {
    clearTimeout(serverTimer)
    serverTimer = setTimeout(async () => {
        if (!serverUrl) {
            return;  // 没有服务器URL，直接返回
        }
        try {
            const res = await axios.head(serverUrl + 'health')
            if (res.status === 200) {
                serverTimeout()  // 健康检查通过，重新设置定时器
                return;
            }
            loger.error('Server health check failed with status: ' + res.status);
        } catch (err) {
            loger.error('Server health check error:', err);
        }
        // 如果健康检查失败，尝试重新启动服务器
        try {
            await startWebServer(true)
            loger.info('Server restarted successfully');
        } catch (error) {
            loger.error('Failed to restart server:', error);
        }
    }, 10000)
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
        backgroundColor: utils.getDefaultBackgroundColor(),
        webPreferences: {
            preload: path.join(__dirname, 'electron-preload.js'),
            webSecurity: true,
            nodeIntegration: true,
            contextIsolation: true,
            backgroundThrottling: false,
        }
    })

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
                if (['darwin', 'win32'].includes(process.platform)) {
                    if (mainWindow.isFullScreen()) {
                        mainWindow.once('leave-full-screen', () => {
                            mainWindow.hide();
                        })
                        mainWindow.setFullScreen(false)
                    } else {
                        mainWindow.hide();
                    }
                } else {
                    app.quit();
                }
            })
        }
    })

    // 设置 UA
    const originalUA = mainWindow.webContents.session.getUserAgent() || mainWindow.webContents.getUserAgent()
    mainWindow.webContents.setUserAgent(originalUA + " MainTaskWindow/" + process.platform + "/" + os.arch() + "/1.0");

    // 新窗口处理
    mainWindow.webContents.setWindowOpenHandler(({url}) => {
        if (allowedCalls.test(url)) {
            openExternal(url).catch(() => {})
        } else {
            utils.onBeforeOpenWindow(mainWindow.webContents, url).then(() => {
                openExternal(url).catch(() => {})
            })
        }
        return {action: 'deny'}
    })

    // 设置右键菜单
    electronMenu.webContentsMenu(mainWindow.webContents)

    // 设置导航快捷键（返回/前进）
    navigation.setup(mainWindow)

    // 加载地址
    utils.loadUrl(mainWindow, serverUrl)
}

/**
 * 创建更新程序子进程
 */
function createUpdaterWindow(updateTitle) {
    // 检查平台是否支持
    if (!['darwin', 'win32'].includes(process.platform)) {
        return;
    }

    try {
        // 构建updater应用路径
        let updaterPath;
        if (isWin) {
            updaterPath = path.join(process.resourcesPath, 'updater', 'updater.exe');
        } else {
            updaterPath = path.join(process.resourcesPath, 'updater', 'updater');
        }

        // 检查updater应用是否存在
        if (!fs.existsSync(updaterPath)) {
            loger.error('Updater not found:', updaterPath);
            return;
        }

        // 检查文件权限
        try {
            fs.accessSync(updaterPath, fs.constants.X_OK);
        } catch (e) {
            if (isWin) {
                try {
                    spawn('icacls', [updaterPath, '/grant', 'everyone:F'], { stdio: 'inherit', shell: true });
                } catch (e) {
                    loger.error('Failed to set executable permission:', e);
                }
            } else if (process.platform === 'darwin') {
                try {
                    spawn('chmod', ['+x', updaterPath], {stdio: 'inherit'});
                } catch (e) {
                    loger.error('Failed to set executable permission:', e);
                }
            }
        }

        // 创建锁文件
        fs.writeFileSync(updaterLockFile, Date.now().toString());

        // 启动子进程,传入锁文件路径作为第一个参数
        const child = spawn(updaterPath, [updaterLockFile], {
            detached: true,
            stdio: 'ignore',
            shell: isWin,
            env: {
                ...process.env,
                ELECTRON_RUN_AS_NODE: '1',
                UPDATER_TITLE: updateTitle || ''
            }
        });

        child.unref();

        child.on('error', (err) => {
            loger.error('Updater process error:', err);
        });

    } catch (e) {
        loger.error('Failed to create updater process:', e);
    }
}

/**
 * 创建预窗口
 */
function preCreateChildWindow() {
    if (preloadWindow) {
        return;
    }

    const browser = new BrowserWindow({
        width: 360,
        height: 360,
        minWidth: 360,
        minHeight: 360,
        center: true,
        show: false,
        autoHideMenuBar: true,
        backgroundColor: utils.getDefaultBackgroundColor(),
        webPreferences: {
            preload: path.join(__dirname, 'electron-preload.js'),
            webSecurity: true,
            nodeIntegration: true,
            contextIsolation: true,
        }
    });

    // 关闭事件
    browser.addListener('closed', () => {
        preloadWindow = null;
    })

    // 设置 UA
    const originalUA = browser.webContents.session.getUserAgent() || browser.webContents.getUserAgent()
    browser.webContents.setUserAgent(originalUA + " SubTaskWindow/" + process.platform + "/" + os.arch() + "/1.0");

    utils.loadUrl(browser, serverUrl, '/preload')

    preloadWindow = browser;
}

/**
 * 创建子窗口
 * @param args {name, path, hash, force, userAgent, config, webPreferences}
 * - config: {title, titleFixed, ...BrowserWindowConstructorOptions}
 */
function createChildWindow(args) {
    if (!args) {
        return;
    }

    if (!utils.isJson(args)) {
        args = {path: args, config: {}}
    }

    const name = args.name || "auto_" + utils.randomString(6);
    const wind = childWindow.find(item => item.name == name);
    let browser = wind ? wind.browser : null;
    let isPreload = false;
    // 清理已销毁但仍被引用的窗口，避免对失效对象调用方法
    if (browser && browser.isDestroyed && browser.isDestroyed()) {
        const index = childWindow.findIndex(item => item.name == name);
        if (index > -1) {
            childWindow.splice(index, 1);
        }
        browser = null;
    }
    if (browser) {
        browser.focus();
        if (args.force === false) {
            return;
        }
    } else {
        const config = args.config || {};
        const webPreferences = args.webPreferences || {};
        const options = Object.assign({
            width: 1280,
            height: 800,
            minWidth: 360,
            minHeight: 360,
            center: true,
            show: false,
            autoHideMenuBar: true,
            backgroundColor: utils.getDefaultBackgroundColor(),
            webPreferences: Object.assign({
                preload: path.join(__dirname, 'electron-preload.js'),
                webSecurity: true,
                nodeIntegration: true,
                contextIsolation: true,
            }, webPreferences),
        }, config)

        options.width = utils.normalizeSize(options.width, 1280)
        options.height = utils.normalizeSize(options.height, 800)
        options.minWidth = utils.normalizeSize(options.minWidth, 360)
        options.minHeight = utils.normalizeSize(options.minHeight, 360)
        if (!options.webPreferences.contextIsolation) {
            delete options.webPreferences.preload;
        }
        if (options.parent) {
            options.parent = mainWindow
        }

        if (preloadWindow && !preloadWindow.isDestroyed?.() && Object.keys(webPreferences).length === 0) {
            // 使用预加载窗口
            browser = preloadWindow;
            preloadWindow = null;
            isPreload = true;
            options.title && browser.setTitle(options.title);
            options.parent && browser.setParentWindow(options.parent);
            browser.setSize(options.width, options.height);
            browser.setMinimumSize(options.minWidth, options.minHeight);
            browser.center();
            browser.setAutoHideMenuBar(options.autoHideMenuBar);
            browser.removeAllListeners("closed");
            setTimeout(() => onShowWindow(browser), 300)
            process.nextTick(() => setTimeout(() => onShowWindow(browser), 50));
        } else {
            // 创建新窗口
            browser = new BrowserWindow(options)
            loger.info("create new window")
        }

        browser.on('page-title-updated', (event, title) => {
            if (title == "index.html" || options.titleFixed === true) {
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
                    browser.hide()
                    setTimeout(() => {
                        browser.destroy()
                    }, 100)
                })
            }
        })

        browser.on('closed', () => {
            const index = childWindow.findIndex(item => item.browser === browser);
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

    // 设置 UA
    const originalUA = browser.webContents.session.getUserAgent() || browser.webContents.getUserAgent()
    browser.webContents.setUserAgent(originalUA + " SubTaskWindow/" + process.platform + "/" + os.arch() + "/1.0" + (args.userAgent ? (" " + args.userAgent) : ""));

    // 新窗口处理
    browser.webContents.setWindowOpenHandler(({url}) => {
        if (allowedCalls.test(url)) {
            openExternal(url).catch(() => {})
        } else {
            utils.onBeforeOpenWindow(browser.webContents, url).then(() => {
                openExternal(url).catch(() => {})
            })
        }
        return {action: 'deny'}
    })

    // 设置右键菜单
    electronMenu.webContentsMenu(browser.webContents)

    // 设置导航快捷键（返回/前进）
    navigation.setup(browser)

    // 加载地址
    const hash = `${args.hash || args.path}`;
    if (/^https?:/i.test(hash)) {
        browser.loadURL(hash)
            .then(_ => { })
            .catch(_ => { })
    } else if (isPreload) {
        browser
            .webContents
            .executeJavaScript(`if(typeof window.__initializeApp === 'function'){window.__initializeApp('${hash}')}else{throw new Error('no function')}`, true)
            .catch(() => {
                utils.loadUrl(browser, serverUrl, hash)
            });
    } else {
        utils.loadUrl(browser, serverUrl, hash)
    }

    // 预创建下一个窗口
    preCreateChildWindow();
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
        utils.loadUrl(browser, serverUrl, hash)
    }
    if (args.name) {
        const er = childWindow.find(item => item.browser == browser);
        if (er) {
            er.name = args.name;
        }
    }
}

/**
 * 创建媒体浏览器窗口
 * @param args
 * @param type
 */
function createMediaWindow(args, type = 'image') {
    if (mediaWindow === null) {
        mediaWindow = new BrowserWindow({
            width: args.width || 970,
            height: args.height || 700,
            minWidth: 360,
            minHeight: 360,
            autoHideMenuBar: true,
            webPreferences: {
                nodeIntegration: true,
                contextIsolation: false,
                webSecurity: false,
                plugins: true
            },
            show: false
        });

        // 监听关闭事件
        mediaWindow.addListener('close', event => {
            if (!willQuitApp) {
                event.preventDefault()
                if (mediaWindow.isFullScreen()) {
                    mediaWindow.once('leave-full-screen', () => {
                        mediaWindow.hide();
                    })
                    mediaWindow.setFullScreen(false)
                } else {
                    mediaWindow.webContents.send('on-close');
                    mediaWindow.hide();
                }
            }
        })

        // 监听关闭事件
        mediaWindow.addListener('closed', () => {
            mediaWindow = null;
            mediaType = null;
        })

        // 设置右键菜单
        electronMenu.webContentsMenu(mediaWindow.webContents)
    } else {
        // 直接显示
        mediaWindow.show();
    }

    // 加载图片浏览器的HTML
    if (mediaType === type) {
        // 更新窗口
        mediaWindow.webContents.send('load-media', args);
    } else {
        // 重置窗口
        mediaType = type;
        let filePath = './render/viewer/index.html';
        if (type === 'video') {
            filePath = './render/video/index.html';
        }
        mediaWindow.loadFile(filePath, {}).then(_ => { }).catch(_ => { })
    }

    // 窗口准备好后事件
    mediaWindow.removeAllListeners("ready-to-show");
    mediaWindow.addListener('ready-to-show', () => {
        mediaWindow.show();
        mediaWindow.webContents.send('load-media', args);
    });
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

    // 创建父级窗口
    if (!webTabWindow) {
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
            backgroundColor: nativeTheme.shouldUseDarkColors ? '#575757' : '#FFFFFF',
            webPreferences: {
                preload: path.join(__dirname, 'electron-preload.js'),
                webSecurity: true,
                nodeIntegration: true,
                contextIsolation: true,
            },
        }, userConf.get('webTabWindow') || {}))

        const originalClose = webTabWindow.close;
        webTabWindow.close = function() {
            webTabClosedByShortcut = true;
            return originalClose.apply(this, arguments);
        };

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
            if (webTabClosedByShortcut) {
                webTabClosedByShortcut = false
                if (!willQuitApp) {
                    closeWebTab(0)
                    event.preventDefault()
                    return
                }
            }
            userConf.set('webTabWindow', webTabWindow.getBounds())
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
            } else if (utils.isMetaOrControl(input) && input.key.toLowerCase() === 'w') {
                webTabClosedByShortcut = true
            } else if (utils.isMetaOrControl(input) && input.shift && input.key.toLowerCase() === 'i') {
                devToolsWebTab(0)
            } else {
                const item = currentWebTab()
                if (item) {
                    navigation.handleInput(event, input, item.view.webContents)
                }
            }
        })

        // 设置鼠标侧键和触控板手势导航
        navigation.setupWindowEvents(webTabWindow, () => {
            const item = currentWebTab()
            return item ? item.view.webContents : null
        })

        webTabWindow.loadFile('./render/tabs/index.html', {}).then(_ => { }).catch(_ => { })
    }
    if (webTabWindow.isMinimized()) {
        webTabWindow.restore()
    }
    webTabWindow.focus();
    webTabWindow.show();

    // 创建 tab 子窗口
    const viewOptions = args.config || {}
    viewOptions.webPreferences = Object.assign({
        preload: path.join(__dirname, 'electron-preload.js'),
        nodeIntegration: true,
        contextIsolation: true
    }, args.webPreferences || {})
    if (!viewOptions.webPreferences.contextIsolation) {
        delete viewOptions.webPreferences.preload;
    }
    const browserView = new WebContentsView(viewOptions)
    if (args.backgroundColor) {
        browserView.setBackgroundColor(args.backgroundColor)
    } else if (nativeTheme.shouldUseDarkColors) {
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
            openExternal(url).catch(() => {})
        } else {
            createWebTabWindow({url})
        }
        return {action: 'deny'}
    })
    browserView.webContents.on('page-title-updated', (event, title) => {
        if (!webTabWindow) return
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
        // 主框架加载失败时，展示内置的错误页面
        if (isMainFrame) {
            const originalUrl = validatedURL || args.url || ''
            const filePath = path.join(__dirname, 'render', 'tabs', 'error.html')
            browserView.webContents.loadFile(filePath, {
                query: {
                    id: String(browserView.webContents.id),
                    url: originalUrl,
                    code: String(errorCode),
                    desc: errorDescription,
                }
            }).then(_ => { }).catch(_ => { })
            return
        }
        if (!webTabWindow) return
        utils.onDispatchEvent(webTabWindow.webContents, {
            event: 'title',
            id: browserView.webContents.id,
            title: errorDescription,
            url: browserView.webContents.getURL(),
        }).then(_ => { })
    })
    browserView.webContents.on('page-favicon-updated', (event, favicons) => {
        if (!webTabWindow) return
        utils.onDispatchEvent(webTabWindow.webContents, {
            event: 'favicon',
            id: browserView.webContents.id,
            favicons
        }).then(_ => { })
    })
    browserView.webContents.on('did-start-loading', _ => {
        webTabView.forEach(({id: vid, view}) => {
            view.setVisible(vid === browserView.webContents.id)
        })
        if (!webTabWindow) return
        utils.onDispatchEvent(webTabWindow.webContents, {
            event: 'start-loading',
            id: browserView.webContents.id,
        }).then(_ => { })
    })
    browserView.webContents.on('did-stop-loading', _ => {
        if (!webTabWindow) return
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
        } else if (utils.isMetaOrControl(input) && input.key.toLowerCase() === 'w') {
            webTabClosedByShortcut = true
        } else if (utils.isMetaOrControl(input) && input.shift && input.key.toLowerCase() === 'i') {
            browserView.webContents.toggleDevTools()
        } else {
            navigation.handleInput(event, input, browserView.webContents)
        }
    })

    const originalUA = browserView.webContents.session.getUserAgent() || browserView.webContents.getUserAgent()
    browserView.webContents.setUserAgent(originalUA + " SubTaskWindow/" + process.platform + "/" + os.arch() + "/1.0");

    electronMenu.webContentsMenu(browserView.webContents, true)

    browserView.webContents.loadURL(args.url).then(_ => { }).catch(_ => { })
    browserView.setVisible(true)

    webTabWindow.contentView.addChildView(browserView)
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
 * @returns {Electron.WebContentsView|undefined}
 */
function currentWebTab() {
    // 第一：使用当前可见的标签
    try {
        const item = webTabView.find(({view}) => view?.getVisible && view.getVisible())
        if (item) {
            return item
        }
    } catch (e) {}
    // 第二：使用当前聚焦的 webContents
    try {
        const focused = webContents.getFocusedWebContents?.()
        if (focused) {
            const item = webTabView.find(it => it.id === focused.id)
            if (item) {
                return item
            }
        }
    } catch (e) {}
    // 兜底：根据 children 顺序选择最上层的可用视图
    const children = webTabWindow.contentView.children || []
    for (let i = children.length - 1; i >= 0; i--) {
        const id = children[i]?.webContents?.id
        const item = webTabView.find(it => it.id === id)
        if (item) {
            return item
        }
    }
    return undefined
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
    webTabView.forEach(({id: vid, view}) => {
        view.setVisible(vid === item.id)
    })
    resizeWebTab(item.id)
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
    webTabWindow.contentView.removeChildView(item.view)
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

/**
 * 监听主题变化
 */
function monitorThemeChanges() {
    let currentTheme = nativeTheme.shouldUseDarkColors ? 'dark' : 'light';
    nativeTheme.on('updated', () => {
        const newTheme = nativeTheme.shouldUseDarkColors ? 'dark' : 'light';
        if (currentTheme === newTheme) {
            return
        }
        currentTheme = newTheme;
        // 更新背景
        const backgroundColor = utils.getDefaultBackgroundColor()
        mainWindow?.setBackgroundColor(backgroundColor);
        preloadWindow?.setBackgroundColor(backgroundColor);
        mediaWindow?.setBackgroundColor(backgroundColor);
        childWindow.some(({browser}) => browser.setBackgroundColor(backgroundColor))
        webTabWindow?.setBackgroundColor(nativeTheme.shouldUseDarkColors ? '#575757' : '#FFFFFF')
        // 通知所有窗口
        BrowserWindow.getAllWindows().forEach(window => {
            window.webContents.send('systemThemeChanged', {
                theme: currentTheme,
            });
        });
    })
}

const getTheLock = app.requestSingleInstanceLock()
if (!getTheLock) {
    app.quit()
} else {
    app.on('second-instance', () => {
        utils.setShowWindow(mainWindow)
    })
    app.on('ready', async () => {
        isReady = true
        isWin && app.setAppUserModelId(config.appId)
        // 启动 Web 服务器
        try {
            await startWebServer()
        } catch (error) {
            dialog.showErrorBox('启动失败', `Web 服务器启动失败：${error.message}`);
            app.quit();
            return;
        }
        // SameSite
        utils.useCookie()
        // 创建主窗口
        createMainWindow()
        // 预创建子窗口
        preCreateChildWindow()
        // 监听主题变化
        monitorThemeChanges()
        // 创建托盘
        if (['darwin', 'win32'].includes(process.platform) && utils.isJson(config.trayIcon)) {
            mainTray = new Tray(path.join(__dirname, config.trayIcon[isDevelopMode ? 'dev' : 'prod'][process.platform === 'darwin' ? 'mac' : 'win']));
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
        // 删除updater锁文件（如果存在）
        if (fs.existsSync(updaterLockFile)) {
            try {
                fs.unlinkSync(updaterLockFile);
            } catch (e) {
                //忽略错误
            }
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

app.on("will-quit", () => {
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
 * 显示预加载窗口（用于调试）
 */
ipcMain.on('showPreloadWindow', (event) => {
    if (preloadWindow) {
        onShowWindow(preloadWindow)
    }
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
 * 打开媒体浏览器
 */
ipcMain.on('openMediaViewer', (event, args) => {
    createMediaWindow(args, ['image', 'video'].includes(args.type) ? args.type : 'image');
    event.returnValue = "ok"
});

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
    openExternal(item.view.webContents.getURL()).catch(() => {})
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
 * 内置浏览器 - 后退
 */
ipcMain.on('webTabGoBack', (event) => {
    const item = currentWebTab()
    if (!item) {
        return
    }
    if (item.view.webContents.canGoBack()) {
        item.view.webContents.goBack()
        // 导航后更新状态
        setTimeout(() => {
            utils.onDispatchEvent(webTabWindow.webContents, {
                event: 'navigation-state',
                id: item.id,
                canGoBack: item.view.webContents.canGoBack(),
                canGoForward: item.view.webContents.canGoForward()
            }).then(_ => { })
        }, 100)
    }
    event.returnValue = "ok"
})

/**
 * 内置浏览器 - 前进
 */
ipcMain.on('webTabGoForward', (event) => {
    const item = currentWebTab()
    if (!item) {
        return
    }
    if (item.view.webContents.canGoForward()) {
        item.view.webContents.goForward()
        // 导航后更新状态
        setTimeout(() => {
            utils.onDispatchEvent(webTabWindow.webContents, {
                event: 'navigation-state',
                id: item.id,
                canGoBack: item.view.webContents.canGoBack(),
                canGoForward: item.view.webContents.canGoForward()
            }).then(_ => { })
        }, 100)
    }
    event.returnValue = "ok"
})

/**
 * 内置浏览器 - 刷新
 */
ipcMain.on('webTabReload', (event) => {
    const item = currentWebTab()
    if (!item) {
        return
    }
    item.view.webContents.reload()
    // 刷新完成后会触发 did-stop-loading 事件，在那里会更新导航状态
    event.returnValue = "ok"
})

/**
 * 内置浏览器 - 停止加载
 */
ipcMain.on('webTabStop', (event) => {
    const item = currentWebTab()
    if (!item) {
        return
    }
    item.view.webContents.stop()
    event.returnValue = "ok"
})

/**
 * 内置浏览器 - 获取导航状态
 */
ipcMain.on('webTabGetNavigationState', (event) => {
    const item = currentWebTab()
    if (!item) {
        return
    }

    const canGoBack = item.view.webContents.canGoBack()
    const canGoForward = item.view.webContents.canGoForward()

    utils.onDispatchEvent(webTabWindow.webContents, {
        event: 'navigation-state',
        id: item.id,
        canGoBack,
        canGoForward
    }).then(_ => { })

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
    preloadWindow?.close()
    mediaWindow?.close()
    electronDown.close()
    event.returnValue = "ok"
})

/**
 * 销毁所有子窗口
 */
ipcMain.on('childWindowDestroyAll', (event) => {
    childWindow.some(({browser}) => {
        browser && browser.destroy()
    })
    preloadWindow?.destroy()
    mediaWindow?.destroy()
    electronDown.destroy()
    event.returnValue = "ok"
})

/**
 * 刷新预加载窗口（用于更换语言和主题时触发）
 */
ipcMain.on('reloadPreloadWindow', (event) => {
    if (preloadWindow) {
        preloadWindow.webContents.reload()
    }
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
 * 给所有窗口广播指令（除了本身）
 * @param args {type, payload}
 */
ipcMain.on('broadcastCommand', (event, args) => {
    const channel = args.channel || args.command
    const payload = args.payload || args.data
    BrowserWindow.getAllWindows().forEach(window => {
        if (window.webContents.id !== event.sender.id) {
            window.webContents.send(channel, payload)
        }
    })
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
 * MCP 服务器状态切换
 * @param args
 */
ipcMain.on('mcpServerToggle', (event, args) => {
    const { running } = args;
    if (running === 'running') {
        startMCPServer(mainWindow, mcpPort)
    } else {
        stopMCPServer()
    }
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
        loger.error('copyImageAt error:', e)
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
                    screenshotObj.view.webContents.executeJavaScript(`if(typeof window.__initializeShortcuts==='undefined'){window.__initializeShortcuts=true;document.addEventListener('keydown',function(e){console.log(e);if(e.keyCode===27){window.screenshots.cancel()}})}`, true).catch(() => {});
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
    utils.showNotification(args, mainWindow)
    event.returnValue = "ok"
})

/**
 * 保存缓存
 */
ipcMain.on('setStore', (event, args) => {
    if (utils.isJson(args)) {
        store.set(args.key, args.value)
    }
    event.returnValue = "ok"
})

/**
 * 获取缓存
 */
ipcMain.handle('getStore', (event, args) => {
    return store.get(args)
});

/**
 * 清理服务器缓存
 */
ipcMain.on('clearServerCache', (event) => {
    utils.clearServerCache();
    event.returnValue = "ok";
});

//================================================================
// Update
//================================================================

let autoUpdating = 0
if (autoUpdater) {
    autoUpdater.logger = loger
    autoUpdater.autoDownload = false
    autoUpdater.autoInstallOnAppQuit = true
    autoUpdater.on('update-available', info => {
        mainWindow.webContents.send("updateAvailable", info)
    })
    autoUpdater.on('update-downloaded', info => {
        mainWindow.webContents.send("updateDownloaded", info)
    })
}

/**
 * 检查更新
 */
ipcMain.on('updateCheckAndDownload', (event, args) => {
    event.returnValue = "ok"
    if (autoUpdating + 3600 > utils.dayjs().unix()) {
        return  // 限制1小时仅执行一次
    }
    if (!autoUpdater) {
        return
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
ipcMain.on('updateQuitAndInstall', (event, args) => {
    if (!utils.isJson(args)) {
        args = {}
    }
    event.returnValue = "ok"

    // 关闭所有子窗口
    willQuitApp = true
    childWindow.some(({browser}) => {
        browser && browser.destroy()
    })
    preloadWindow?.destroy()
    mediaWindow?.destroy()
    electronDown.destroy()

    // 启动更新子窗口
    createUpdaterWindow(args.updateTitle)

    // 退出并安装更新
    setTimeout(_ => {
        mainWindow.hide()
        autoUpdater?.quitAndInstall(true, true)
    }, 600)
})

//================================================================
//================================================================
//================================================================

onExport()
onRenderer(mainWindow)
