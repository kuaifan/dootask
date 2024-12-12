/**
 * 将服务器资源路径转换为本地资源路径
 * @type {(function(*): (*))|*}
 */
const convertLocalResourcePath = (() => {
    let initialized = false
    let appPreUrl = null
    let serverPreUrl = null
    let urlRegex = null

    // 将 escapeRegExp 移到闭包内部
    const escapeRegExp = (string) => {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
    }

    // 初始化函数
    const initialize = () => {
        if (initialized) return

        // 设置应用前缀URL
        if ($A.isEEUiApp) {
            appPreUrl = $A.eeuiAppRewriteUrl('../public/')
            if ($A.isAndroid()) {
                appPreUrl = appPreUrl.replace(/^file:\/\/assets\//, 'file:///android_asset/')
            }
        } else if ($A.Electron) {
            appPreUrl = "local-asset://public/"
        }

        // 如果没有特殊前缀，提前返回
        if (!appPreUrl) return

        // 获取服务器URL
        serverPreUrl = $A.mainUrl()
        const escapedPreUrl = escapeRegExp(serverPreUrl)

        // 固定的模式
        const patterns = [
            'images/',
            // 可以继续添加其他路径...
        ]

        // 计算转义后的模式
        const escapedPatterns = patterns.map(pattern => escapeRegExp(pattern))

        // 编译正则表达式
        urlRegex = new RegExp(`${escapedPreUrl}(${escapedPatterns.join('|')})`)

        initialized = true
    }

    // 返回实际的处理函数
    return (url) => {
        // 第一次调用时初始化
        initialize()

        // 如果没有特殊前缀，直接返回原URL
        if (!appPreUrl) {
            return url
        }

        // 正则判断必须是 gif、icns、ico、jpg、jpeg、png、svg 结尾
        if (!/\.(jpe?g|webp|png|gif|icns|ico|svg)$/i.test(url)) {
            return url
        }

        if (urlRegex.test(url)) {
            return url.replace(serverPreUrl, appPreUrl)
        }
        return url
    }
})()

/**
 * 是否是本地资源路径
 * @param url
 * @returns {*}
 */
const isLocalResourcePath = (url) => {
    return url && (
        url.startsWith('file://') ||
        url.startsWith('local-asset://')
    )
}

export {convertLocalResourcePath, isLocalResourcePath}
