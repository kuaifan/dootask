/**
 * WebTab 窗口管理模块
 *
 * 负责管理多标签浏览器窗口，支持：
 * - tab 模式：标签页模式（有导航栏）
 * - window 模式：独立窗口模式（无导航栏）
 */

const path = require('path')
const os = require('os')
const {
    ipcMain,
    clipboard,
    nativeTheme,
    screen,
    Menu,
    WebContentsView,
    BrowserWindow,
    dialog
} = require('electron')

const utils = require('./utils')
const navigation = require('./navigation')
const { allowedCalls, isMac } = require('./other')
const faviconCache = require('./favicon-cache')
const { renderer } = require('./renderer')

// ============================================================
// 状态变量
// ============================================================

// Map<windowId, {window, views: [{id, view, name, favicon}], activeTabId, mode: 'tab'|'window'}>
let webTabWindows = new Map()
let webTabWindowIdCounter = 1

// 标签名称到标签位置的映射，用于复用已存在的标签
// Map<name, {windowId, tabId}>
let webTabNameMap = new Map()

// 标签栏高度
const webTabHeight = 40

// 快捷键关闭状态 Map<windowId, boolean>
let webTabClosedByShortcut = new Map()

// 存储已声明关闭拦截的 webContents id
const closeInterceptors = new Set()

// ============================================================
// 预加载池
// ============================================================

// 预加载 view 池
let preloadViewPool = []

// 预加载配置
const PRELOAD_CONFIG = {
    poolSize: 1,           // 池大小
    warmupDelay: 1000,     // 启动后延迟创建时间（ms）
    refillDelay: 500,      // 取走后补充延迟时间（ms）
}

// 预加载定时器（用于防抖补充）
let preloadRefillTimer = null

// ============================================================
// 依赖注入
// ============================================================

let _context = null

/**
 * 初始化模块
 * @param {Object} context
 * @param {Function} context.getServerUrl - 获取服务器URL
 * @param {Function} context.getUserConf - 获取用户配置
 * @param {Function} context.isWillQuitApp - 是否即将退出应用
 * @param {Object} context.electronMenu - 菜单模块
 */
function init(context) {
    _context = context
}

/**
 * 获取服务器URL
 */
function getServerUrl() {
    return _context?.getServerUrl?.() || ''
}

/**
 * 获取用户配置
 */
function getUserConf() {
    return _context?.getUserConf?.()
}

/**
 * 是否即将退出应用
 */
function isWillQuitApp() {
    return _context?.isWillQuitApp?.() || false
}

/**
 * 获取菜单模块
 */
function getElectronMenu() {
    return _context?.electronMenu
}

// ============================================================
// 预加载函数
// ============================================================

/**
 * 创建预加载 view
 * 预加载 /preload 路由，完成基础 JS 文件加载
 * @returns {WebContentsView}
 */
function createPreloadView() {
    const serverUrl = getServerUrl()
    if (!serverUrl) {
        return null
    }

    const browserView = new WebContentsView({
        webPreferences: {
            preload: path.join(__dirname, '..', 'electron-preload.js'),
            nodeIntegration: true,
            contextIsolation: true,
        }
    })

    const originalUA = browserView.webContents.session.getUserAgent() || browserView.webContents.getUserAgent()
    browserView.webContents.setUserAgent(originalUA + " SubTaskWindow/" + process.platform + "/" + os.arch() + "/1.0")

    utils.loadUrl(browserView.webContents, serverUrl, '/preload')

    browserView._isPreloaded = true
    browserView._preloadReady = false
    browserView.webContents.on('did-finish-load', () => {
        browserView._preloadReady = true
    })

    return browserView
}

/**
 * 预热预加载池（延迟创建，避免影响主窗口加载）
 */
function warmupPreloadPool() {
    if (!getServerUrl()) return

    setTimeout(() => {
        while (preloadViewPool.length < PRELOAD_CONFIG.poolSize) {
            const view = createPreloadView()
            if (view) {
                preloadViewPool.push(view)
            } else {
                break
            }
        }
    }, PRELOAD_CONFIG.warmupDelay)
}

/**
 * 从池中获取预加载 view（优先取已就绪的）
 * @returns {WebContentsView|null}
 */
function getPreloadedView() {
    // 优先取已就绪的
    const readyIndex = preloadViewPool.findIndex(v => v._preloadReady && !v.webContents.isDestroyed())
    if (readyIndex >= 0) {
        const view = preloadViewPool.splice(readyIndex, 1)[0]
        scheduleRefillPool()
        return view
    }
    // 次选任意可用（可能还在加载中）
    const availableIndex = preloadViewPool.findIndex(v => !v.webContents.isDestroyed())
    if (availableIndex >= 0) {
        const view = preloadViewPool.splice(availableIndex, 1)[0]
        scheduleRefillPool()
        return view
    }
    return null
}

/**
 * 延迟补充预加载池
 */
function scheduleRefillPool() {
    if (preloadRefillTimer) clearTimeout(preloadRefillTimer)
    preloadRefillTimer = setTimeout(() => {
        preloadRefillTimer = null
        while (preloadViewPool.length < PRELOAD_CONFIG.poolSize) {
            const view = createPreloadView()
            if (view) {
                preloadViewPool.push(view)
            } else {
                break
            }
        }
    }, PRELOAD_CONFIG.refillDelay)
}

/**
 * 清理预加载池
 */
function clearPreloadPool() {
    if (preloadRefillTimer) {
        clearTimeout(preloadRefillTimer)
        preloadRefillTimer = null
    }
    preloadViewPool.forEach(view => {
        try {
            if (!view.webContents.isDestroyed()) {
                view.webContents.close()
            }
        } catch (e) {
            // ignore
        }
    })
    preloadViewPool = []
}

/**
 * 重建预加载池（清理后重新创建）
 */
function recreatePreloadPool() {
    clearPreloadPool()
    while (preloadViewPool.length < PRELOAD_CONFIG.poolSize) {
        const view = createPreloadView()
        if (view) {
            preloadViewPool.push(view)
        } else {
            break
        }
    }
}

/**
 * 向标签栏分发事件（仅 tab 模式有效，window 模式无标签栏）
 * @param {object} wd - windowData 对象
 * @param {object} data - 事件数据
 */
function dispatchToTabBar(wd, data) {
    if (!wd || !wd.window || wd.mode === 'window') {
        return Promise.resolve()
    }
    return utils.onDispatchEvent(wd.window.webContents, data)
}

/**
 * 内置浏览器 - 延迟发送导航状态
 */
function notifyNavigationState(item) {
    setTimeout(() => {
        const wd = webTabWindows.get(item.view.webTabWindowId)
        dispatchToTabBar(wd, {
            event: 'navigation-state',
            id: item.id,
            canGoBack: item.view.webContents.navigationHistory.canGoBack(),
            canGoForward: item.view.webContents.navigationHistory.canGoForward()
        })
    }, 100)
}

// ============================================================
// 核心函数
// ============================================================

/**
 * 创建内置浏览器窗口（支持多窗口）
 * @param args {url, windowId, position, afterId, insertIndex, name, force, userAgent, title, titleFixed, webPreferences, mode, ...}
 *   - mode: 'tab' | 'window'
 *     - 'window': 独立窗口模式（无导航栏）
 *     - 'tab': 标签页模式（默认，有导航栏）
 * @returns {number} 窗口ID
 */
function createWebTabWindow(args) {
    if (!args) {
        return
    }

    if (!utils.isJson(args)) {
        args = { url: args }
    }

    const mode = args.mode || 'tab'
    const isWindowMode = mode === 'window'

    // 如果有 name，先查找是否已存在同名标签/窗口
    if (args.name) {
        const existing = webTabNameMap.get(args.name)
        if (existing) {
            const existingWindowData = webTabWindows.get(existing.windowId)
            if (existingWindowData && existingWindowData.window && !existingWindowData.window.isDestroyed()) {
                const viewItem = existingWindowData.views.find(v => v.id === existing.tabId)
                if (viewItem && viewItem.view && !viewItem.view.webContents.isDestroyed()) {
                    // 激活已存在的标签/窗口
                    if (existingWindowData.window.isMinimized()) {
                        existingWindowData.window.restore()
                    }
                    existingWindowData.window.focus()
                    existingWindowData.window.show()
                    activateWebTabInWindow(existing.windowId, existing.tabId)

                    // force=true 时重新加载
                    if (args.force === true && args.url) {
                        utils.loadContentUrl(viewItem.view.webContents, getServerUrl(), args.url)
                    }
                    return existing.windowId
                }
            }
            // 标签已失效，清理映射
            webTabNameMap.delete(args.name)
        }
    }

    // 确定目标窗口ID
    let windowId = args.windowId
    let windowData = windowId ? webTabWindows.get(windowId) : null
    let webTabWindow = windowData ? windowData.window : null

    // 如果没有指定窗口或窗口不存在，查找可用窗口或创建新窗口
    if (!webTabWindow) {
        // window 模式总是创建新窗口；tab 模式尝试使用第一个可用的 tab 窗口
        if (!isWindowMode && !windowId) {
            for (const [id, data] of webTabWindows) {
                if (data.window && !data.window.isDestroyed() && data.mode !== 'window') {
                    windowId = id
                    windowData = data
                    webTabWindow = data.window
                    break
                }
            }
        }

        // 如果还是没有窗口，创建新窗口
        if (!webTabWindow) {
            windowId = webTabWindowIdCounter++
            // 从 args 中提取窗口尺寸
            const position = {
                x: args.x,
                y: args.y,
                width: args.width,
                height: args.height,
                minWidth: args.minWidth,
                minHeight: args.minHeight,
            }
            webTabWindow = createWebTabWindowInstance(windowId, position, mode)
            windowData = {
                window: webTabWindow,
                views: [],
                activeTabId: null,
                mode: mode
            }
            webTabWindows.set(windowId, windowData)
        }
    }

    if (webTabWindow.isMinimized()) {
        webTabWindow.restore()
    }
    webTabWindow.focus()
    webTabWindow.show()

    // 创建 tab 子视图
    const browserView = createWebTabView(windowId, args)

    // 确定插入位置
    let insertIndex = windowData.views.length
    if (args.afterId) {
        const afterIndex = windowData.views.findIndex(item => item.id === args.afterId)
        if (afterIndex > -1) {
            insertIndex = afterIndex + 1
        }
    }
    if (typeof args.insertIndex === 'number') {
        insertIndex = Math.max(0, Math.min(args.insertIndex, windowData.views.length))
    }

    // 插入到指定位置，包含 name 信息
    windowData.views.splice(insertIndex, 0, {
        id: browserView.webContents.id,
        view: browserView,
        name: args.name || null
    })

    // 如果有 name，注册到映射
    if (args.name) {
        webTabNameMap.set(args.name, {
            windowId: windowId,
            tabId: browserView.webContents.id
        })
    }

    // tab 模式通知标签栏创建标签；window 模式设置窗口标题
    if (isWindowMode) {
        // window 模式下，如果传入了 title 参数，设置窗口标题
        if (args.title) {
            webTabWindow.setTitle(args.title)
        }
    } else {
        // 从域名缓存获取 favicon（快速响应）
        const domain = faviconCache.extractDomain(args.url)
        const cachedFavicon = domain ? faviconCache.getByDomain(domain) : null

        // 如果有缓存，保存到视图对象
        if (cachedFavicon) {
            const viewItem = windowData.views.find(v => v.id === browserView.webContents.id)
            if (viewItem) {
                viewItem.favicon = cachedFavicon
            }
        }

        // tab 模式下通知标签栏创建新标签
        utils.onDispatchEvent(webTabWindow.webContents, {
            event: 'create',
            id: browserView.webContents.id,
            url: args.url,
            afterId: args.afterId,
            windowId: windowId,
            title: args.title,
            favicon: cachedFavicon || '',
        }).then(_ => { })
    }
    activateWebTabInWindow(windowId, browserView.webContents.id)

    return windowId
}

/**
 * 创建 WebTabWindow 实例
 * @param windowId
 * @param position {x, y, width, height}
 * @param mode 'tab' | 'window'
 * @returns {BrowserWindow}
 */
function createWebTabWindowInstance(windowId, position, mode = 'tab') {
    const isWindowMode = mode === 'window'
    const { width: screenWidth, height: screenHeight } = screen.getPrimaryDisplay().workAreaSize
    const isHighRes = screenWidth >= 2560

    // 根据屏幕分辨率计算默认尺寸
    const screenDefault = {
        width: isHighRes ? 1440 : 1024,
        height: isHighRes ? 900 : 768,
        minWidth: 400,
        minHeight: 300,
    }

    // 计算窗口尺寸和位置
    const windowOptions = {
        show: false,
        autoHideMenuBar: true,
        webPreferences: {
            preload: path.join(__dirname, '..', 'electron-preload.js'),
            webSecurity: true,
            nodeIntegration: true,
            contextIsolation: true,
        },
    }

    const userConf = getUserConf()

    if (isWindowMode) {
        // window 模式：使用 position 参数或屏幕默认值，永远居中
        Object.assign(windowOptions, {
            width: Math.floor(position?.width ?? screenDefault.width),
            height: Math.floor(position?.height ?? screenDefault.height),
            minWidth: Math.floor(position?.minWidth ?? screenDefault.minWidth),
            minHeight: Math.floor(position?.minHeight ?? screenDefault.minHeight),
            backgroundColor: utils.getDefaultBackgroundColor(),
            center: true,
        })
    } else {
        // tab 模式：使用 savedBounds 或屏幕默认值
        const savedBounds = userConf?.get('webTabWindow') || {}
        const maxX = Math.floor(screenWidth * 0.9)
        const maxY = Math.floor(screenHeight * 0.9)
        Object.assign(windowOptions, {
            width: savedBounds.width ?? screenDefault.width,
            height: savedBounds.height ?? screenDefault.height,
            minWidth: screenDefault.minWidth,
            minHeight: screenDefault.minHeight,
            backgroundColor: nativeTheme.shouldUseDarkColors ? '#202124' : '#F1F3F4',
            center: true,
        })
        // 恢复保存的位置，并限制在屏幕 90% 范围内
        if (savedBounds.x !== undefined && savedBounds.y !== undefined) {
            Object.assign(windowOptions, {
                x: Math.min(savedBounds.x, maxX),
                y: Math.min(savedBounds.y, maxY),
                center: false,
            })
        }
    }

    // tab 模式使用隐藏标题栏 + titleBarOverlay
    if (!isWindowMode) {
        const titleBarOverlay = {
            height: webTabHeight
        }
        if (nativeTheme.shouldUseDarkColors) {
            Object.assign(titleBarOverlay, {
                color: '#3B3B3D',
                symbolColor: '#C5C5C5',
            })
        }
        Object.assign(windowOptions, {
            titleBarStyle: 'hidden',
            titleBarOverlay: titleBarOverlay,
        })
    }

    const webTabWindow = new BrowserWindow(windowOptions)

    // 保存窗口ID到窗口对象
    webTabWindow.webTabWindowId = windowId

    const originalClose = webTabWindow.close
    webTabWindow.close = function() {
        webTabClosedByShortcut.set(windowId, true)
        return originalClose.apply(this, arguments)
    }

    webTabWindow.on('resize', () => {
        resizeWebTabInWindow(windowId, 0)
    })

    webTabWindow.on('enter-full-screen', () => {
        const wd = webTabWindows.get(windowId)
        dispatchToTabBar(wd, {
            event: 'enter-full-screen',
        })
    })

    webTabWindow.on('leave-full-screen', () => {
        const wd = webTabWindows.get(windowId)
        dispatchToTabBar(wd, {
            event: 'leave-full-screen',
        })
    })

    webTabWindow.on('close', event => {
        const isShortcut = webTabClosedByShortcut.get(windowId)
        if (isShortcut) {
            webTabClosedByShortcut.set(windowId, false)
        }

        const windowData = webTabWindows.get(windowId)
        if (!windowData) return

        // 只有 tab 模式才保存 bounds
        if (windowData.mode !== 'window') {
            userConf?.set('webTabWindow', webTabWindow.getBounds())
        }

        // 应用退出时不检查
        if (isWillQuitApp()) return

        // 检查页面是否有未保存数据
        if (isShortcut) {
            // 快捷键关闭：只检查当前激活的标签
            event.preventDefault()

            // 获取当前激活标签
            const activeTab = windowData.views.find(v => v.id === windowData.activeTabId)
            if (!activeTab) return

            // 构造代理 window 对象
            const proxyWindow = Object.create(webTabWindow, {
                webContents: { get: () => activeTab.view.webContents }
            })

            // 检查并等待用户确认
            onBeforeUnload(event, proxyWindow).then(() => {
                closeWebTabInWindow(windowId, 0)
            })
            return
        }

        // 点击窗口关闭按钮：依次检查并关闭每个标签页
        const checkAndCloseTabs = async () => {
            // 复制标签列表，因为关闭时会修改原数组
            const tabs = [...windowData.views]
            for (const tab of tabs) {
                // 先激活要检查的标签，让用户看到是哪个标签有未保存数据
                activateWebTabInWindow(windowId, tab.id)
                const proxyWindow = Object.create(webTabWindow, {
                    webContents: { get: () => tab.view.webContents }
                })
                // 检查并等待用户确认（用户取消则 Promise 不 resolve，中断循环）
                await onBeforeUnload(event, proxyWindow)
                // 确认后关闭这个标签（最后一个标签关闭时窗口会自动销毁）
                closeWebTabInWindow(windowId, tab.id)
            }
        }
        checkAndCloseTabs()
    })

    webTabWindow.on('closed', () => {
        const windowData = webTabWindows.get(windowId)
        if (windowData) {
            windowData.views.forEach(({ view, name }) => {
                // 清理 name 映射
                if (name) {
                    webTabNameMap.delete(name)
                }
                try {
                    view.webContents.close()
                } catch (e) {
                    //
                }
            })
            webTabWindows.delete(windowId)
        }
        webTabClosedByShortcut.delete(windowId)
    })

    webTabWindow.once('ready-to-show', () => {
        onShowWindow(webTabWindow)
    })

    webTabWindow.webContents.once('dom-ready', () => {
        onShowWindow(webTabWindow)
    })

    webTabWindow.webContents.on('before-input-event', (event, input) => {
        if (utils.isMetaOrControl(input) && input.key.toLowerCase() === 'r') {
            reloadWebTabInWindow(windowId, 0)
            event.preventDefault()
        } else if (utils.isMetaOrControl(input) && input.key.toLowerCase() === 'w') {
            webTabClosedByShortcut.set(windowId, true)
        } else if (utils.isMetaOrControl(input) && input.shift && input.key.toLowerCase() === 'i') {
            devToolsWebTabInWindow(windowId, 0)
        } else {
            const item = currentWebTabInWindow(windowId)
            if (item) {
                navigation.handleInput(event, input, item.view.webContents)
            }
        }
    })

    // 设置鼠标侧键和触控板手势导航
    navigation.setupWindowEvents(webTabWindow, () => {
        const item = currentWebTabInWindow(windowId)
        return item ? item.view.webContents : null
    })

    // tab 模式加载标签栏界面，window 模式不需要
    if (!isWindowMode) {
        webTabWindow.loadFile(path.join(__dirname, '..', 'render', 'tabs', 'index.html'), { query: { windowId: String(windowId) } }).then(_ => { }).catch(_ => { })
    }

    return webTabWindow
}

/**
 * 创建 WebTab 视图
 * @param windowId
 * @param args
 * @returns {WebContentsView}
 */
function createWebTabView(windowId, args) {
    const windowData = webTabWindows.get(windowId)
    if (!windowData) return null

    const webTabWindow = windowData.window
    const isWindowMode = windowData.mode === 'window'
    const effectiveTabHeight = isWindowMode ? 0 : webTabHeight
    const electronMenu = getElectronMenu()
    const serverUrl = getServerUrl()

    // 尝试复用预加载 view（本地站点且无特殊配置）
    let browserView = null
    let isPreloaded = false
    const isLocalUrl = !args.url || !args.url.startsWith('http') ||
                       utils.getDomain(args.url) === utils.getDomain(serverUrl)
    const hasCustomPreferences = args.webPreferences && Object.keys(args.webPreferences).length > 0

    if (isLocalUrl && !hasCustomPreferences) {
        browserView = getPreloadedView()
        if (browserView) isPreloaded = true
    }

    if (!browserView) {
        const viewOptions = {
            webPreferences: Object.assign({
                preload: path.join(__dirname, '..', 'electron-preload.js'),
                nodeIntegration: true,
                contextIsolation: true
            }, args.webPreferences || {})
        }
        if (!viewOptions.webPreferences.contextIsolation) {
            delete viewOptions.webPreferences.preload
        }
        browserView = new WebContentsView(viewOptions)
    }

    if (args.backgroundColor) {
        browserView.setBackgroundColor(args.backgroundColor)
    } else {
        browserView.setBackgroundColor(utils.getDefaultBackgroundColor())
    }

    browserView.setBounds({
        x: 0,
        y: effectiveTabHeight,
        width: webTabWindow.getContentBounds().width || 1280,
        height: (webTabWindow.getContentBounds().height || 800) - effectiveTabHeight,
    })

    // 保存所属窗口ID和元数据
    browserView.webTabWindowId = windowId
    browserView.tabName = args.name || null
    browserView.titleFixed = args.titleFixed || false

    // 设置自定义 UserAgent
    if (!isPreloaded && args.userAgent) {
        const originalUA = browserView.webContents.getUserAgent()
        browserView.webContents.setUserAgent(
            originalUA + " SubTaskWindow/" + process.platform + "/" + os.arch() + "/1.0 " + args.userAgent
        )
    }

    browserView.webContents.on('destroyed', () => {
        if (browserView.tabName) {
            webTabNameMap.delete(browserView.tabName)
        }
        if (browserView._loadingChecker) {
            clearInterval(browserView._loadingChecker)
            browserView._loadingChecker = null
        }
        closeWebTabInWindow(windowId, browserView.webContents.id)
    })
    browserView.webContents.setWindowOpenHandler(({ url }) => {
        if (allowedCalls.test(url)) {
            renderer.openExternal(url).catch(() => {})
        } else if (isWindowMode) {
            // window 模式下打开外部浏览器
            utils.onBeforeOpenWindow(browserView.webContents, url).then(() => {
                renderer.openExternal(url).catch(() => {})
            })
        } else if (url && url !== 'about:blank') {
            // tab 模式下创建新标签
            createWebTabWindow({ url, afterId: browserView.webContents.id, windowId: browserView.webTabWindowId })
        }
        return { action: 'deny' }
    })
    browserView.webContents.on('page-title-updated', (_, title) => {
        // titleFixed 时不更新标题
        if (browserView.titleFixed) {
            return
        }

        // 使用动态窗口ID，支持标签在窗口间转移
        const currentWindowId = browserView.webTabWindowId
        const wd = webTabWindows.get(currentWindowId)
        if (!wd || !wd.window) return

        // 根据模式更新标题
        if (wd.mode === 'window') {
            // window 模式下直接设置窗口标题
            wd.window.setTitle(title)
        } else {
            // tab 模式下通知标签栏更新标题
            utils.onDispatchEvent(wd.window.webContents, {
                event: 'title',
                id: browserView.webContents.id,
                title: title,
                url: browserView.webContents.getURL(),
            }).then(_ => { })
        }
    })
    browserView.webContents.on('did-fail-load', (event, errorCode, errorDescription, validatedURL, isMainFrame) => {
        if (!errorDescription) {
            return
        }
        // 主框架加载失败时，展示内置的错误页面
        if (isMainFrame) {
            const originalUrl = validatedURL || args.url || ''
            const filePath = path.join(__dirname, '..', 'render', 'tabs', 'error.html')
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
        // 使用动态窗口ID，支持标签在窗口间转移
        const currentWindowId = browserView.webTabWindowId
        const wd = webTabWindows.get(currentWindowId)
        if (!wd || !wd.window) return
        dispatchToTabBar(wd, {
            event: 'title',
            id: browserView.webContents.id,
            title: errorDescription,
            url: browserView.webContents.getURL(),
        })
    })
    browserView.webContents.on('page-favicon-updated', async (_, favicons) => {
        // 使用动态窗口ID，支持标签在窗口间转移
        const currentWindowId = browserView.webTabWindowId
        const wd = webTabWindows.get(currentWindowId)
        if (!wd || !wd.window) return

        const tabId = browserView.webContents.id
        const faviconUrl = favicons[favicons.length - 1] || ''
        const pageUrl = browserView.webContents.getURL()

        // 使用缓存模块获取 favicon（先查缓存，无则下载并缓存）
        const base64Favicon = await faviconCache.fetchAndCache(faviconUrl, pageUrl)

        // 保存验证后的 favicon 到视图对象
        const viewItem = wd.views.find(v => v.id === tabId)
        if (viewItem) {
            viewItem.favicon = base64Favicon || ''
        }

        // 发送验证后的 favicon 给前端
        dispatchToTabBar(wd, {
            event: 'favicon',
            id: tabId,
            favicon: base64Favicon || ''
        })
    })
    // 页面加载状态管理，忽略SPA路由切换(isSameDocument)
    browserView._loadingActive = false
    browserView._loadingChecker = null
    const dispatchLoading = (event) => {
        const wd = webTabWindows.get(browserView.webTabWindowId)
        dispatchToTabBar(wd, {
            event,
            id: browserView.webContents.id,
        })
    }
    const startLoading = () => {
        if (browserView._loadingActive) return
        browserView._loadingActive = true
        dispatchLoading('start-loading')
        if (!browserView._loadingChecker) {
            browserView._loadingChecker = setInterval(() => {
                if (browserView.webContents.isDestroyed() || !browserView.webContents.isLoading()) {
                    stopLoading()
                }
            }, 3000)
        }
    }
    const stopLoading = () => {
        if (browserView._loadingChecker) {
            clearInterval(browserView._loadingChecker)
            browserView._loadingChecker = null
        }
        if (!browserView._loadingActive) return
        browserView._loadingActive = false
        dispatchLoading('stop-loading')
    }
    browserView.webContents.on('did-start-navigation', (_, _url, _isInPlace, isMainFrame, _frameProcessId, _frameRoutingId, _navigationId, isSameDocument) => {
        if (isMainFrame && !isSameDocument) {
            startLoading()
        }
    })
    browserView.webContents.on('did-stop-loading', _ => {
        stopLoading()
    })
    browserView.webContents.on('before-input-event', (event, input) => {
        // 使用动态窗口ID，支持标签在窗口间转移
        const currentWindowId = browserView.webTabWindowId
        if (utils.isMetaOrControl(input) && input.key.toLowerCase() === 'r') {
            browserView.webContents.reload()
            event.preventDefault()
        } else if (utils.isMetaOrControl(input) && input.key.toLowerCase() === 'w') {
            webTabClosedByShortcut.set(currentWindowId, true)
        } else if (utils.isMetaOrControl(input) && input.shift && input.key.toLowerCase() === 'i') {
            browserView.webContents.toggleDevTools()
        } else {
            navigation.handleInput(event, input, browserView.webContents)
        }
    })

    if (!isPreloaded) {
        const originalUA = browserView.webContents.session.getUserAgent() || browserView.webContents.getUserAgent()
        browserView.webContents.setUserAgent(originalUA + " SubTaskWindow/" + process.platform + "/" + os.arch() + "/1.0")
    }

    electronMenu?.webContentsMenu(browserView.webContents, true)

    // 加载业务路由（预加载 view 通过 __initializeApp 触发路由切换）
    if (isPreloaded) {
        const targetUrl = args.url || ''
        browserView.webContents.executeJavaScript(
            `window.__initializeApp && window.__initializeApp('${targetUrl.replace(/'/g, "\\'")}')`
        ).catch(() => {
            utils.loadContentUrl(browserView.webContents, serverUrl, args.url)
        })
    } else {
        utils.loadContentUrl(browserView.webContents, serverUrl, args.url)
    }

    browserView.setVisible(true)

    webTabWindow.contentView.addChildView(browserView)

    return browserView
}

/**
 * 获取当前内置浏览器标签
 * @returns {object|undefined}
 */
function currentWebTab() {
    // 找到第一个活跃窗口
    for (const [windowId, windowData] of webTabWindows) {
        if (windowData.window && !windowData.window.isDestroyed()) {
            return currentWebTabInWindow(windowId)
        }
    }
    return undefined
}

/**
 * 获取指定窗口的当前内置浏览器标签
 * @param windowId
 * @returns {object|undefined}
 */
function currentWebTabInWindow(windowId) {
    const windowData = webTabWindows.get(windowId)
    if (!windowData || !windowData.window) return undefined

    const webTabView = windowData.views
    const webTabWindow = windowData.window

    // 第一：使用当前可见的标签
    try {
        const item = webTabView.find(({ view }) => view?.getVisible && view.getVisible())
        if (item) {
            return item
        }
    } catch (e) {}
    // 第二：使用当前聚焦的 webContents
    try {
        const focused = require('electron').webContents.getFocusedWebContents?.()
        if (focused) {
            const item = webTabView.find(it => it.id === focused.id)
            if (item) {
                return item
            }
        }
    } catch (e) {}
    // 兜底：根据 children 顺序选择最上层的可用视图
    const children = webTabWindow.contentView?.children || []
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
 * 根据 tabId 查找所属窗口ID
 * @param tabId
 * @returns {number|null}
 */
function findWindowIdByTabId(tabId) {
    for (const [windowId, windowData] of webTabWindows) {
        if (windowData.views.some(v => v.id === tabId)) {
            return windowId
        }
    }
    return null
}

/**
 * 重新加载内置浏览器标签
 * @param windowId
 * @param id
 */
function reloadWebTabInWindow(windowId, id) {
    const windowData = webTabWindows.get(windowId)
    if (!windowData) return

    const item = id === 0 ? currentWebTabInWindow(windowId) : windowData.views.find(item => item.id == id)
    if (!item) {
        return
    }
    item.view.webContents.reload()
}

/**
 * 内置浏览器标签打开开发者工具
 * @param windowId
 * @param id
 */
function devToolsWebTabInWindow(windowId, id) {
    const windowData = webTabWindows.get(windowId)
    if (!windowData) return

    const item = id === 0 ? currentWebTabInWindow(windowId) : windowData.views.find(item => item.id == id)
    if (!item) {
        return
    }
    item.view.webContents.toggleDevTools()
}

/**
 * 调整内置浏览器标签尺寸
 * @param windowId
 * @param id
 */
function resizeWebTabInWindow(windowId, id) {
    const windowData = webTabWindows.get(windowId)
    if (!windowData || !windowData.window) return

    const webTabWindow = windowData.window
    const isWindowMode = windowData.mode === 'window'
    const effectiveTabHeight = isWindowMode ? 0 : webTabHeight

    const item = id === 0 ? currentWebTabInWindow(windowId) : windowData.views.find(item => item.id == id)
    if (!item) {
        return
    }
    item.view.setBounds({
        x: 0,
        y: effectiveTabHeight,
        width: webTabWindow.getContentBounds().width || 1280,
        height: (webTabWindow.getContentBounds().height || 800) - effectiveTabHeight,
    })
}

/**
 * 切换内置浏览器标签
 * @param windowId
 * @param id
 */
function activateWebTabInWindow(windowId, id) {
    const windowData = webTabWindows.get(windowId)
    if (!windowData || !windowData.window) return

    const webTabView = windowData.views
    const webTabWindow = windowData.window
    const item = id === 0 ? currentWebTabInWindow(windowId) : webTabView.find(item => item.id == id)
    if (!item) {
        return
    }
    windowData.activeTabId = item.id
    webTabView.forEach(({ id: vid, view }) => {
        view.setVisible(vid === item.id)
    })
    resizeWebTabInWindow(windowId, item.id)
    item.view.webContents.focus()
    dispatchToTabBar(windowData, {
        event: 'switch',
        id: item.id,
    })
}

/**
 * 关闭内置浏览器标签
 * @param windowId
 * @param id
 */
function closeWebTabInWindow(windowId, id) {
    const windowData = webTabWindows.get(windowId)
    if (!windowData || !windowData.window) return

    const webTabView = windowData.views
    const webTabWindow = windowData.window
    const isWindowMode = windowData.mode === 'window'
    const userConf = getUserConf()

    const item = id === 0 ? currentWebTabInWindow(windowId) : webTabView.find(item => item.id == id)
    if (!item) {
        return
    }

    // window 模式下直接关闭整个窗口
    if (isWindowMode) {
        webTabView.forEach(({ name }) => {
            if (name) webTabNameMap.delete(name)
        })
        webTabWindow.destroy()
        return
    }

    if (webTabView.length === 1) {
        webTabWindow.hide()
    }
    webTabWindow.contentView.removeChildView(item.view)

    // 清理 name 映射
    if (item.name) {
        webTabNameMap.delete(item.name)
    }

    try {
        item.view.webContents.close()
    } catch (e) {
        //
    }

    const index = webTabView.findIndex(({ id }) => item.id == id)
    if (index > -1) {
        webTabView.splice(index, 1)
    }

    dispatchToTabBar(windowData, {
        event: 'close',
        id: item.id,
    })

    if (webTabView.length === 0) {
        userConf?.set('webTabWindow', webTabWindow.getBounds())
        webTabWindow.destroy()
    } else {
        activateWebTabInWindow(windowId, 0)
    }
}

/**
 * 安全关闭标签（检查未保存数据后关闭）
 * @param windowId 窗口ID
 * @param tabId 标签ID
 */
function safeCloseWebTab(windowId, tabId) {
    const windowData = webTabWindows.get(windowId)
    if (!windowData) {
        closeWebTabInWindow(windowId, tabId)
        return
    }

    const tab = windowData.views.find(v => v.id === tabId)
    if (!tab) {
        closeWebTabInWindow(windowId, tabId)
        return
    }

    const proxyWindow = Object.create(windowData.window, {
        webContents: { get: () => tab.view.webContents }
    })
    onBeforeUnload({ preventDefault: () => {} }, proxyWindow).then(() => {
        closeWebTabInWindow(windowId, tabId)
    })
}

/**
 * 分离标签到新窗口
 * @param windowId 源窗口ID
 * @param tabId 标签ID
 * @param screenX 屏幕X坐标
 * @param screenY 屏幕Y坐标
 * @returns {number|null} 新窗口ID
 */
function detachWebTab(windowId, tabId, screenX, screenY) {
    const sourceWindowData = webTabWindows.get(windowId)
    if (!sourceWindowData) return null

    const tabIndex = sourceWindowData.views.findIndex(v => v.id === tabId)
    if (tabIndex === -1) return null

    const tabItem = sourceWindowData.views[tabIndex]
    const view = tabItem.view
    const favicon = tabItem.favicon || ''
    const tabName = tabItem.name || null
    const sourceWindow = sourceWindowData.window
    const userConf = getUserConf()

    // 从源窗口移除视图
    sourceWindow.contentView.removeChildView(view)
    sourceWindowData.views.splice(tabIndex, 1)

    // 通知源窗口标签已关闭
    dispatchToTabBar(sourceWindowData, {
        event: 'close',
        id: tabId,
    })

    // 创建新窗口，使用源窗口的尺寸
    const sourceBounds = sourceWindow.getBounds()
    const newWindowId = webTabWindowIdCounter++

    // 先创建窗口实例
    const newWindow = createWebTabWindowInstance(newWindowId, {
        width: sourceBounds.width,
        height: sourceBounds.height,
    })

    // 计算窗口位置，让鼠标大致在标签区域
    const navAreaWidth = isMac ? 280 : 200
    const targetX = Math.round(screenX - navAreaWidth)
    const targetY = Math.round(screenY - Math.floor(webTabHeight / 2))

    // 显示窗口前先设置位置
    newWindow.setPosition(targetX, targetY)

    const newWindowData = {
        window: newWindow,
        views: [{
            id: tabId,
            name: tabName,
            view,
            favicon
        }],
        activeTabId: tabId,
        mode: 'tab'
    }
    webTabWindows.set(newWindowId, newWindowData)

    // 更新视图所属窗口
    view.webTabWindowId = newWindowId

    // 更新 name 映射中的 windowId
    if (tabName) {
        webTabNameMap.set(tabName, {
            windowId: newWindowId,
            tabId: tabId
        })
    }

    // 添加视图到新窗口
    newWindow.contentView.addChildView(view)
    view.setBounds({
        x: 0,
        y: webTabHeight,
        width: newWindow.getContentBounds().width || 1280,
        height: (newWindow.getContentBounds().height || 800) - webTabHeight,
    })
    view.setVisible(true)

    // 显示新窗口
    newWindow.show()

    // 再次确保位置正确
    newWindow.setPosition(targetX, targetY)
    newWindow.focus()

    // 通知新窗口创建标签（传递完整状态信息）
    newWindow.webContents.once('dom-ready', () => {
        const isLoading = view.webContents.isLoading()
        dispatchToTabBar(newWindowData, {
            event: 'create',
            id: tabId,
            url: view.webContents.getURL(),
            windowId: newWindowId,
            title: view.webContents.getTitle(),
            state: isLoading ? 'loading' : 'loaded',
            favicon,
        })
        dispatchToTabBar(newWindowData, {
            event: 'switch',
            id: tabId,
        })
    })

    // 处理源窗口
    if (sourceWindowData.views.length === 0) {
        // 源窗口没有标签了，关闭它
        userConf?.set('webTabWindow', sourceWindow.getBounds())
        sourceWindow.destroy()
    } else {
        // 激活源窗口的下一个标签
        activateWebTabInWindow(windowId, 0)
    }

    return newWindowId
}

/**
 * 将标签附加到目标窗口
 * @param sourceWindowId 源窗口ID
 * @param tabId 标签ID
 * @param targetWindowId 目标窗口ID
 * @param insertIndex 插入位置（可选）
 * @returns {boolean} 是否成功
 */
function attachWebTab(sourceWindowId, tabId, targetWindowId, insertIndex) {
    if (sourceWindowId === targetWindowId) return false

    const sourceWindowData = webTabWindows.get(sourceWindowId)
    const targetWindowData = webTabWindows.get(targetWindowId)
    if (!sourceWindowData || !targetWindowData) return false

    const tabIndex = sourceWindowData.views.findIndex(v => v.id === tabId)
    if (tabIndex === -1) return false

    const tabItem = sourceWindowData.views[tabIndex]
    const view = tabItem.view
    const favicon = tabItem.favicon || ''
    const tabName = tabItem.name || null
    const sourceWindow = sourceWindowData.window
    const targetWindow = targetWindowData.window
    const userConf = getUserConf()

    // 从源窗口移除视图
    sourceWindow.contentView.removeChildView(view)
    sourceWindowData.views.splice(tabIndex, 1)

    // 通知源窗口标签已关闭
    dispatchToTabBar(sourceWindowData, {
        event: 'close',
        id: tabId,
    })

    // 更新视图所属窗口
    view.webTabWindowId = targetWindowId

    // 更新 name 映射中的 windowId
    if (tabName) {
        webTabNameMap.set(tabName, {
            windowId: targetWindowId,
            tabId: tabId
        })
    }

    // 确定插入位置
    const actualInsertIndex = typeof insertIndex === 'number'
        ? Math.max(0, Math.min(insertIndex, targetWindowData.views.length))
        : targetWindowData.views.length

    // 添加到目标窗口，保留 name 信息
    targetWindowData.views.splice(actualInsertIndex, 0, {
        id: tabId,
        name: tabName,
        view,
        favicon
    })
    targetWindow.contentView.addChildView(view)

    // 调整视图尺寸
    view.setBounds({
        x: 0,
        y: webTabHeight,
        width: targetWindow.getContentBounds().width || 1280,
        height: (targetWindow.getContentBounds().height || 800) - webTabHeight,
    })

    // 通知目标窗口创建标签（传递完整状态信息）
    const isLoading = view.webContents.isLoading()
    dispatchToTabBar(targetWindowData, {
        event: 'create',
        id: tabId,
        url: view.webContents.getURL(),
        windowId: targetWindowId,
        title: view.webContents.getTitle(),
        insertIndex: actualInsertIndex,
        state: isLoading ? 'loading' : 'loaded',
        favicon,
    })

    // 激活新添加的标签
    activateWebTabInWindow(targetWindowId, tabId)

    // 聚焦目标窗口
    targetWindow.focus()

    // 处理源窗口
    if (sourceWindowData.views.length === 0) {
        userConf?.set('webTabWindow', sourceWindow.getBounds())
        sourceWindow.destroy()
    } else {
        activateWebTabInWindow(sourceWindowId, 0)
    }

    return true
}

/**
 * 获取所有 webTab 窗口信息（用于跨窗口拖拽检测）
 * @returns {Array}
 */
function getAllWebTabWindowsInfo() {
    const result = []
    for (const [windowId, windowData] of webTabWindows) {
        if (windowData.window && !windowData.window.isDestroyed()) {
            const bounds = windowData.window.getBounds()
            result.push({
                windowId,
                bounds,
                tabBarBounds: {
                    x: bounds.x,
                    y: bounds.y,
                    width: bounds.width,
                    height: webTabHeight
                },
                tabCount: windowData.views.length
            })
        }
    }
    return result
}

/**
 * 从事件发送者获取窗口ID
 * @param sender
 * @returns {number|null}
 */
function getWindowIdFromSender(sender) {
    const win = BrowserWindow.fromWebContents(sender)
    if (win && win.webTabWindowId) {
        return win.webTabWindowId
    }
    return null
}

// ============================================================
// 辅助函数
// ============================================================

// 窗口显示状态管理
let showState = {}

function onShowWindow(win) {
    try {
        if (typeof showState[win.webContents.id] === 'undefined') {
            showState[win.webContents.id] = true
            win.show()
        }
    } catch (e) {
        // loger.error(e)
    }
}

// ============================================================
// 对外接口
// ============================================================

/**
 * 获取所有 webTab 窗口（用于主题更新等）
 * @returns {Map}
 */
function getWebTabWindows() {
    return webTabWindows
}

/**
 * 销毁所有 webTab 窗口
 */
function destroyAll() {
    for (const [, windowData] of webTabWindows) {
        if (windowData.window && !windowData.window.isDestroyed()) {
            windowData.window.destroy()
        }
    }
}

/**
 * 关闭所有 webTab 窗口
 * 通过逐个关闭标签实现，当最后一个标签关闭时窗口自动销毁
 */
function closeAll() {
    // 复制 windowId 列表，避免遍历时 Map 被修改
    const windowIds = [...webTabWindows.keys()]
    for (const windowId of windowIds) {
        const windowData = webTabWindows.get(windowId)
        if (windowData && windowData.window && !windowData.window.isDestroyed()) {
            // 复制 tabId 列表
            const tabIds = windowData.views.map(v => v.id)
            // 逐个关闭标签，最后一个关闭时窗口自动销毁
            for (const tabId of tabIds) {
                closeWebTabInWindow(windowId, tabId)
            }
        }
    }
}

/**
 * 关闭所有 mode='window' 的窗口
 */
function closeAllWindowMode() {
    for (const [, data] of webTabWindows) {
        if (data.mode === 'window' && data.window && !data.window.isDestroyed()) {
            data.window.close()
        }
    }
}

/**
 * 销毁所有 mode='window' 的窗口
 */
function destroyAllWindowMode() {
    for (const [, data] of webTabWindows) {
        if (data.mode === 'window' && data.window && !data.window.isDestroyed()) {
            data.window.destroy()
        }
    }
}

// ============================================================
// 关闭拦截管理
// ============================================================

/**
 * 注册关闭拦截（前端声明需要拦截关闭事件）
 * @param webContentsId
 */
function registerCloseInterceptor(webContentsId) {
    closeInterceptors.add(webContentsId)
}

/**
 * 取消关闭拦截
 * @param webContentsId
 */
function unregisterCloseInterceptor(webContentsId) {
    closeInterceptors.delete(webContentsId)
}

/**
 * 检查是否有关闭拦截
 * @param webContentsId
 * @returns {boolean}
 */
function hasCloseInterceptor(webContentsId) {
    return closeInterceptors.has(webContentsId)
}

/**
 * 窗口关闭事件
 * @param event
 * @param app
 * @param timeout
 */
function onBeforeUnload(event, app, timeout = 5000) {
    return new Promise(resolve => {
        const contents = app.webContents
        if (contents != null && !contents.isDestroyed()) {
            // 检查是否有声明拦截，没有声明则直接关闭，不执行 JS
            if (!hasCloseInterceptor(contents.id)) {
                resolve()
                return
            }

            // 有声明拦截，执行 JS（带超时保护）
            const timeoutPromise = new Promise(r => setTimeout(() => r({ __timeout: true }), timeout))
            const jsPromise = contents.executeJavaScript(`if(typeof window.__onBeforeUnload === 'function'){window.__onBeforeUnload()}`, true)

            Promise.race([jsPromise, timeoutPromise]).then(options => {
                if (utils.isJson(options)) {
                    // 超时，直接允许关闭
                    if (options.__timeout) {
                        resolve()
                        return
                    }
                    // 显示确认对话框
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
}

// ============================================================
// IPC 注册
// ============================================================

/**
 * 注册所有 webTab 相关的 IPC 事件
 */
function registerIPC() {
    const electronMenu = getElectronMenu()

    /**
     * 重建预加载池
     */
    ipcMain.on('recreatePreloadPool', (event) => {
        recreatePreloadPool()
        event.returnValue = "ok"
    })

    /**
     * 获取路由窗口信息（从 webTabWindows 中查找 mode='window' 的窗口）
     */
    ipcMain.handle('getChildWindow', (event, args) => {
        let windowData, viewItem
        if (!args) {
            // 通过发送者查找
            const sender = event.sender
            for (const [, data] of webTabWindows) {
                if (data.mode === 'window') {
                    const found = data.views.find(v => v.view.webContents === sender)
                    if (found) {
                        windowData = data
                        viewItem = found
                        break
                    }
                }
            }
        } else {
            // 通过名称查找
            const location = webTabNameMap.get(args)
            if (location) {
                windowData = webTabWindows.get(location.windowId)
                if (windowData && windowData.mode === 'window') {
                    viewItem = windowData.views.find(v => v.id === location.tabId)
                }
            }
        }
        if (windowData && viewItem) {
            return {
                name: viewItem.name,
                id: viewItem.view.webContents.id,
                url: viewItem.view.webContents.getURL()
            }
        }
        return null
    })

    /**
     * 统一窗口打开接口
     */
    ipcMain.on('openWindow', (event, args) => {
        createWebTabWindow(args)
        event.returnValue = "ok"
    })

    /**
     * 更新当前窗口/标签页的 URL 和名称
     */
    ipcMain.on('updateWindow', (event, args) => {
        if (!args) {
            event.returnValue = "ok"
            return
        }

        if (!utils.isJson(args)) {
            args = { path: args }
        }

        const sender = event.sender
        let windowId, windowData, viewItem

        // 通过发送者查找窗口和视图
        for (const [id, data] of webTabWindows) {
            const found = data.views.find(v => v.view.webContents === sender)
            if (found) {
                windowId = id
                windowData = data
                viewItem = found
                break
            }
        }

        if (!windowData || !viewItem) {
            event.returnValue = "ok"
            return
        }

        // 更新 URL
        if (args.path) {
            utils.loadContentUrl(viewItem.view.webContents, getServerUrl(), args.path)
        }

        // 更新名称
        if (args.name && args.name !== viewItem.name) {
            const oldName = viewItem.name
            viewItem.name = args.name

            // 更新 webTabNameMap
            if (oldName) {
                webTabNameMap.delete(oldName)
            }
            webTabNameMap.set(args.name, {
                windowId: windowId,
                tabId: viewItem.id
            })
        }

        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 激活标签
     */
    ipcMain.on('webTabActivate', (event, args) => {
        let windowId, tabId
        if (typeof args === 'object' && args !== null) {
            windowId = args.windowId
            tabId = args.tabId
        } else {
            tabId = args
            windowId = findWindowIdByTabId(tabId)
        }
        if (windowId) {
            activateWebTabInWindow(windowId, tabId)
        }
        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 重排标签顺序
     */
    ipcMain.on('webTabReorder', (event, args) => {
        let windowId, newOrder
        if (Array.isArray(args)) {
            newOrder = args
            if (newOrder.length > 0) {
                windowId = findWindowIdByTabId(newOrder[0])
            }
        } else if (typeof args === 'object' && args !== null) {
            windowId = args.windowId
            newOrder = args.newOrder
        }

        if (!windowId || !Array.isArray(newOrder) || newOrder.length === 0) {
            event.returnValue = "ok"
            return
        }

        const windowData = webTabWindows.get(windowId)
        if (!windowData) {
            event.returnValue = "ok"
            return
        }

        windowData.views.sort((a, b) => {
            const indexA = newOrder.indexOf(a.id)
            const indexB = newOrder.indexOf(b.id)
            return indexA - indexB
        })
        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 关闭标签
     */
    ipcMain.on('webTabClose', (event, args) => {
        let windowId, tabId
        if (typeof args === 'object' && args !== null) {
            windowId = args.windowId
            tabId = args.tabId
        } else {
            tabId = args
            windowId = findWindowIdByTabId(tabId)
        }
        if (windowId) {
            safeCloseWebTab(windowId, tabId)
        }
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
        renderer.openExternal(item.view.webContents.getURL()).catch(() => {})
        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 显示更多菜单
     */
    ipcMain.on('webTabShowMenu', (event, args) => {
        const windowId = args?.windowId
        const tabId = args?.tabId
        const windowData = windowId ? webTabWindows.get(windowId) : null
        const webTabWindow = windowData?.window

        if (!webTabWindow || webTabWindow.isDestroyed()) {
            event.returnValue = "ok"
            return
        }

        const item = currentWebTabInWindow(windowId)
        const webContents = item?.view?.webContents
        const currentUrl = webContents?.getURL() || ''
        const canBrowser = !utils.isLocalHost(currentUrl)

        const menuTemplate = [
            {
                label: electronMenu?.language?.reload || 'Reload',
                click: () => {
                    if (webContents && !webContents.isDestroyed()) {
                        webContents.reload()
                    }
                }
            },
            {
                label: electronMenu?.language?.copyLinkAddress || 'Copy Link',
                enabled: canBrowser,
                click: () => {
                    if (currentUrl) {
                        clipboard.writeText(currentUrl)
                    }
                }
            },
            {
                label: electronMenu?.language?.openInDefaultBrowser || 'Open in Browser',
                enabled: canBrowser,
                click: () => {
                    if (currentUrl) {
                        renderer.openExternal(currentUrl).catch(() => {})
                    }
                }
            },
            { type: 'separator' },
            {
                label: electronMenu?.language?.moveToNewWindow || 'Move to New Window',
                enabled: windowData?.views?.length > 1,
                click: () => {
                    if (tabId) {
                        const bounds = webTabWindow.getBounds()
                        detachWebTab(windowId, tabId, bounds.x + 50, bounds.y + 50)
                    }
                }
            },
            { type: 'separator' },
            {
                label: electronMenu?.language?.print || 'Print',
                click: () => {
                    if (webContents && !webContents.isDestroyed()) {
                        webContents.print()
                    }
                }
            }
        ]

        const menu = Menu.buildFromTemplate(menuTemplate)
        menu.popup({
            window: webTabWindow,
            x: args?.x,
            y: args?.y
        })
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
        destroyAll()
        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 后退
     */
    ipcMain.on('webTabGoBack', (event, args) => {
        const windowId = args?.windowId || getWindowIdFromSender(event.sender)
        const item = windowId ? currentWebTabInWindow(windowId) : currentWebTab()
        if (!item) {
            event.returnValue = "ok"
            return
        }
        if (item.view.webContents.navigationHistory.canGoBack()) {
            item.view.webContents.navigationHistory.goBack()
            notifyNavigationState(item)
        }
        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 前进
     */
    ipcMain.on('webTabGoForward', (event, args) => {
        const windowId = args?.windowId || getWindowIdFromSender(event.sender)
        const item = windowId ? currentWebTabInWindow(windowId) : currentWebTab()
        if (!item) {
            event.returnValue = "ok"
            return
        }
        if (item.view.webContents.navigationHistory.canGoForward()) {
            item.view.webContents.navigationHistory.goForward()
            notifyNavigationState(item)
        }
        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 刷新
     */
    ipcMain.on('webTabReload', (event, args) => {
        const windowId = args?.windowId || getWindowIdFromSender(event.sender)
        const item = windowId ? currentWebTabInWindow(windowId) : currentWebTab()
        if (!item) {
            event.returnValue = "ok"
            return
        }
        item.view.webContents.reload()
        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 停止加载
     */
    ipcMain.on('webTabStop', (event, args) => {
        const windowId = args?.windowId || getWindowIdFromSender(event.sender)
        const item = windowId ? currentWebTabInWindow(windowId) : currentWebTab()
        if (!item) {
            event.returnValue = "ok"
            return
        }
        item.view.webContents.stop()
        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 获取导航状态
     */
    ipcMain.on('webTabGetNavigationState', (event, args) => {
        const windowId = args?.windowId || getWindowIdFromSender(event.sender)
        const item = windowId ? currentWebTabInWindow(windowId) : currentWebTab()
        if (!item) {
            event.returnValue = "ok"
            return
        }

        const canGoBack = item.view.webContents.navigationHistory.canGoBack()
        const canGoForward = item.view.webContents.navigationHistory.canGoForward()

        const wd = webTabWindows.get(item.view.webTabWindowId)
        dispatchToTabBar(wd, {
            event: 'navigation-state',
            id: item.id,
            canGoBack,
            canGoForward
        })

        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 分离标签到新窗口
     */
    ipcMain.on('webTabDetach', (event, args) => {
        const { windowId, tabId, screenX, screenY } = args
        detachWebTab(windowId, tabId, screenX, screenY)
        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 将标签附加到目标窗口
     */
    ipcMain.on('webTabAttach', (event, args) => {
        const { sourceWindowId, tabId, targetWindowId, insertIndex } = args
        attachWebTab(sourceWindowId, tabId, targetWindowId, insertIndex)
        event.returnValue = "ok"
    })

    /**
     * 内置浏览器 - 获取所有窗口信息
     */
    ipcMain.handle('webTabGetAllWindows', () => {
        return getAllWebTabWindowsInfo()
    })

    /**
     * 关闭窗口（或关闭 tab）
     */
    ipcMain.on('windowClose', (event) => {
        const tabId = event.sender.id
        const windowId = findWindowIdByTabId(tabId)
        if (windowId !== null) {
            safeCloseWebTab(windowId, tabId)
        } else {
            const win = BrowserWindow.fromWebContents(event.sender)
            win?.close()
        }
        event.returnValue = "ok"
    })

    /**
     * 销毁窗口（或销毁 tab）
     */
    ipcMain.on('windowDestroy', (event) => {
        const tabId = event.sender.id
        const windowId = findWindowIdByTabId(tabId)
        if (windowId !== null) {
            closeWebTabInWindow(windowId, tabId)
        } else {
            const win = BrowserWindow.fromWebContents(event.sender)
            win?.destroy()
        }
        event.returnValue = "ok"
    })

    /**
     * 注册关闭拦截（前端声明需要拦截关闭事件）
     */
    ipcMain.on('registerCloseInterceptor', (event) => {
        registerCloseInterceptor(event.sender.id)
        event.returnValue = "ok"
    })

    /**
     * 取消关闭拦截
     */
    ipcMain.on('unregisterCloseInterceptor', (event) => {
        unregisterCloseInterceptor(event.sender.id)
        event.returnValue = "ok"
    })
}

// ============================================================
// 导出
// ============================================================

module.exports = {
    // 初始化
    init,
    registerIPC,

    // 核心功能
    createWebTabWindow,
    closeWebTabInWindow,
    activateWebTabInWindow,
    findWindowIdByTabId,

    // 预加载
    warmupPreloadPool,
    clearPreloadPool,

    // 对外接口
    getWebTabWindows,
    closeAll,
    destroyAll,
    closeAllWindowMode,
    destroyAllWindowMode,

    // 关闭拦截管理
    registerCloseInterceptor,
    unregisterCloseInterceptor,
    hasCloseInterceptor,
    onBeforeUnload,
}
