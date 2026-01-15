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
const {onRenderer, renderer} = require("./lib/renderer");
const {onExport} = require("./lib/pdf-export");
const {allowedCalls, isWin} = require("./lib/other");
const webTabManager = require("./lib/web-tab-manager");
const faviconCache = require("./lib/favicon-cache");

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
    mediaWindow = null;

// 窗口配置和状态
let mediaType = null;

// 开发模式路径
let devloadPath = path.resolve(__dirname, ".devload");

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
            webTabManager.onBeforeUnload(event, mainWindow).then(() => {
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
            renderer.openExternal(url).catch(() => {})
        } else {
            utils.onBeforeOpenWindow(mainWindow.webContents, url).then(() => {
                renderer.openExternal(url).catch(() => {})
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
 * 创建媒体浏览器窗口
 * @param args
 * @param type
 */
function createMediaWindow(args, type = 'image') {
    if (mediaWindow === null) {
        mediaWindow = new BrowserWindow({
            width: Math.floor(args.width) || 970,
            height: Math.floor(args.height) || 700,
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
        mediaWindow?.setBackgroundColor(backgroundColor);
        // 更新所有 webTab 窗口背景
        for (const [, windowData] of webTabManager.getWebTabWindows()) {
            windowData.window?.setBackgroundColor(nativeTheme.shouldUseDarkColors ? '#202124' : '#F1F3F4');
        }
        // 通知所有窗口
        BrowserWindow.getAllWindows().forEach(window => {
            window.webContents.send('systemThemeChanged', {
                theme: currentTheme,
            });
        });
        // 通知所有 webTab 视图（WebContentsView 中的网站内容）
        for (const [, windowData] of webTabManager.getWebTabWindows()) {
            windowData.views?.forEach(({ view }) => {
                if (view && !view.webContents.isDestroyed()) {
                    view.webContents.send('systemThemeChanged', {
                        theme: currentTheme,
                    });
                }
            });
        }
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

        // 清理过期的 favicon 缓存
        faviconCache.cleanExpiredCache()

        // 初始化 webTabManager
        webTabManager.init({
            getServerUrl: () => serverUrl,
            getUserConf: () => userConf,
            isWillQuitApp: () => willQuitApp,
            electronMenu: electronMenu,
        })
        webTabManager.registerIPC()

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
        // 预热预加载池（延迟启动，避免影响主窗口加载）
        webTabManager.warmupPreloadPool()
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
    // 清理预加载池
    webTabManager.clearPreloadPool()
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
 * 打开媒体浏览器
 */
ipcMain.on('openMediaViewer', (event, args) => {
    createMediaWindow(args, ['image', 'video'].includes(args.type) ? args.type : 'image');
    event.returnValue = "ok"
});

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
 * 关闭所有子窗口（包含所有 webTab 窗口、mediaWindow 和下载窗口）
 */
ipcMain.on('childWindowCloseAll', (event) => {
    webTabManager.closeAll()
    mediaWindow?.close()
    electronDown.close()
    event.returnValue = "ok"
})

/**
 * 销毁所有子窗口（包含所有 webTab 窗口、mediaWindow 和下载窗口）
 */
ipcMain.on('childWindowDestroyAll', (event) => {
    webTabManager.destroyAll()
    mediaWindow?.destroy()
    electronDown.destroy()
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
    // 广播给所有 BrowserWindow
    BrowserWindow.getAllWindows().forEach(window => {
        if (window.webContents.id !== event.sender.id) {
            window.webContents.send(channel, payload)
        }
    })
    // 广播给 webTabManager 中的所有 view
    for (const [, windowData] of webTabManager.getWebTabWindows()) {
        windowData.views?.forEach(({ view }) => {
            if (view && !view.webContents.isDestroyed() && view.webContents.id !== event.sender.id) {
                view.webContents.send(channel, payload)
            }
        })
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
    webTabManager.destroyAllWindowMode()
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
onRenderer(() => mainWindow)
