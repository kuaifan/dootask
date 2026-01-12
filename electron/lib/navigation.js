/**
 * 窗口导航相关工具函数
 *
 * 规则：
 * - 顶层页面是 localhost 时：禁用顶层 goBack/goForward，
 *   避免影响应用主路由历史（例如 Vue hash 路由）。
 * - 若此时焦点在 iframe 内：允许 iframe 自己执行 history.back()/history.forward()（不影响顶层路由）。
 */

const utils = require('./utils')

/**
 * @typedef {'back'|'forward'} NavDirection
 */

function getWebContentsUrl(webContents) {
    try {
        return webContents?.getURL?.() || ''
    } catch (e) {
        return ''
    }
}

function isBackKey(input) {
    return (input.alt && input.key === 'ArrowLeft') || (input.meta && input.key === '[')
}

function isForwardKey(input) {
    return (input.alt && input.key === 'ArrowRight') || (input.meta && input.key === ']')
}

/**
 * 尝试从 Electron 提供的 focusedFrame 获取当前聚焦 frame（兼容属性/方法两种形态）
 * @param webContents
 * @returns {Electron.WebFrameMain|null}
 */
function getFocusedFrameDirect(webContents) {
    const focused = webContents?.focusedFrame
    if (!focused) {
        return null
    }
    try {
        return typeof focused === 'function' ? focused.call(webContents) : focused
    } catch (e) {
        return null
    }
}

/**
 * 获取当前聚焦的 Frame（优先返回更深层的 iframe）
 * @param webContents
 * @returns {Promise<Electron.WebFrameMain|null>}
 */
async function getFocusedFrame(webContents) {
    const mainFrame = webContents?.mainFrame
    if (!mainFrame) {
        return null
    }

    const direct = getFocusedFrameDirect(webContents)
    if (direct) {
        return direct
    }

    const frames = Array.isArray(mainFrame.framesInSubtree) && mainFrame.framesInSubtree.length > 0
        ? mainFrame.framesInSubtree
        : [mainFrame]

    // document.hasFocus() 可能在主文档与子 frame 同时为 true，因此取“最深”的那个
    const focusedList = await Promise.all(frames.map((frame) => {
        if (!frame?.executeJavaScript) {
            return Promise.resolve(false)
        }
        return frame
            .executeJavaScript('document.hasFocus && document.hasFocus()')
            .then(Boolean)
            .catch(() => false)
    }))

    for (let i = focusedList.length - 1; i >= 0; i--) {
        if (focusedList[i]) {
            return frames[i]
        }
    }

    return mainFrame
}

/**
 * 在“聚焦 iframe”内执行前进/后退（不影响顶层路由历史）
 * @param webContents
 * @param {NavDirection} direction
 * @returns {Promise<boolean>}
 */
async function navigateFocusedSubframe(webContents, direction) {
    const mainFrame = webContents?.mainFrame
    if (!mainFrame) {
        return false
    }

    const frame = await getFocusedFrame(webContents)
    if (!frame || frame === mainFrame) {
        return false
    }

    const js = direction === 'forward' ? 'history.forward()' : 'history.back()'
    try {
        await frame.executeJavaScript(js)
        return true
    } catch (e) {
        return false
    }
}

/**
 * 尝试在顶层 webContents 上执行前进/后退
 * @param webContents
 * @param {NavDirection} direction
 * @returns {boolean}
 */
function navigateTopLevel(webContents, direction) {
    if (!webContents) {
        return false
    }
    if (direction === 'back') {
        if (!webContents.navigationHistory.canGoBack()) {
            return false
        }
        webContents.navigationHistory.goBack()
        return true
    }
    if (direction === 'forward') {
        if (!webContents.navigationHistory.canGoForward()) {
            return false
        }
        webContents.navigationHistory.goForward()
        return true
    }
    return false
}

/**
 * 统一导航入口：
 * - 顶层 intranet：阻止顶层导航，尝试让聚焦 iframe 自己 history.back/forward
 * - 外网：正常顶层导航
 *
 * @param event
 * @param webContents
 * @param {NavDirection} direction
 * @returns {boolean} 是否“接管”了这次操作（intranet 总是接管）
 */
function handleDirection(event, webContents, direction) {
    const url = getWebContentsUrl(webContents)
    const intranet = utils.isLocalHost(url)

    if (intranet) {
        event?.preventDefault?.()
        navigateFocusedSubframe(webContents, direction)
            .catch(() => {})
        return true
    }

    const ok = navigateTopLevel(webContents, direction)
    if (ok) {
        event?.preventDefault?.()
    }
    return ok
}

function resolveDirectionFromInput(input) {
    if (isBackKey(input)) return 'back'
    if (isForwardKey(input)) return 'forward'
    return null
}

function resolveDirectionFromAppCommand(cmd) {
    if (cmd === 'browser-backward') return 'back'
    if (cmd === 'browser-forward') return 'forward'
    return null
}

function resolveDirectionFromSwipe(direction) {
    // macOS swipe: left = back, right = forward（与当前用户验证一致）
    if (direction === 'left') return 'back'
    if (direction === 'right') return 'forward'
    return null
}

function handleInput(event, input, webContents) {
    const direction = resolveDirectionFromInput(input)
    if (!direction) return false
    return handleDirection(event, webContents, direction)
}

function setupWindowEvents(win, getWebContents) {
    if (!win) return

    win.on('app-command', (event, cmd) => {
        const direction = resolveDirectionFromAppCommand(cmd)
        if (!direction) return
        const webContents = getWebContents?.()
        if (!webContents) return
        handleDirection(event, webContents, direction)
    })

    win.on('swipe', (event, direction) => {
        const navDirection = resolveDirectionFromSwipe(direction)
        if (!navDirection) return
        const webContents = getWebContents?.()
        if (!webContents) return
        handleDirection(event, webContents, navDirection)
    })
}

function setup(win) {
    if (!win || !win.webContents) return

    win.webContents.on('before-input-event', (event, input) => {
        handleInput(event, input, win.webContents)
    })

    setupWindowEvents(win, () => win.webContents)
}

module.exports = {
    isBackKey,
    isForwardKey,
    getWebContentsUrl,
    getFocusedFrame,
    navigateFocusedSubframe,
    handleInput,
    setupWindowEvents,
    setup,
}

