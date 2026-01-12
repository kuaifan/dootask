/**
 * Favicon 缓存模块
 *
 * 实现双层缓存机制（仿 Chrome）：
 * - 第一层：域名缓存 - 快速响应，在导航开始时立即查询
 * - 第二层：URL 缓存 - 精确匹配，在获取到 favicon URL 后查询
 */

const fs = require('fs')
const path = require('path')
const { app, net } = require('electron')

// 缓存目录
let cacheDir = null

// 内存缓存（加速读取）
const memoryCache = {
    domains: new Map(),  // domain -> base64
    urls: new Map()      // url -> base64
}

/**
 * 初始化缓存目录
 */
function ensureCacheDir() {
    if (cacheDir) return cacheDir

    cacheDir = path.join(app.getPath('userData'), 'favicon-cache')

    // 确保缓存目录存在
    if (!fs.existsSync(cacheDir)) {
        fs.mkdirSync(cacheDir, { recursive: true })
    }

    return cacheDir
}

/**
 * 将 URL 或域名转换为安全的文件名
 * @param {string} str - URL 或域名
 * @param {string} prefix - 前缀 ('domain_' 或 'url_')
 * @returns {string} 安全的文件名
 */
function toSafeFileName(str, prefix) {
    // 移除协议
    let safe = str.replace(/^https?:\/\//, '')
    // 将特殊字符替换为下划线
    safe = safe.replace(/[^a-zA-Z0-9.-]/g, '_')
    // 移除连续的下划线
    safe = safe.replace(/_+/g, '_')
    // 移除首尾下划线
    safe = safe.replace(/^_|_$/g, '')
    // 限制长度（避免文件名过长）
    if (safe.length > 200) {
        safe = safe.substring(0, 200)
    }
    return prefix + safe
}

/**
 * 从 URL 提取域名
 * @param {string} url
 * @returns {string|null}
 */
function extractDomain(url) {
    if (!url) return null
    try {
        const urlObj = new URL(url)
        return urlObj.hostname
    } catch {
        return null
    }
}

/**
 * 获取域名缓存的文件路径
 * @param {string} domain
 * @returns {string}
 */
function getDomainCachePath(domain) {
    ensureCacheDir()
    return path.join(cacheDir, toSafeFileName(domain, 'domain_'))
}

/**
 * 获取 URL 缓存的文件路径
 * @param {string} url
 * @returns {string}
 */
function getUrlCachePath(url) {
    ensureCacheDir()
    return path.join(cacheDir, toSafeFileName(url, 'url_'))
}

/**
 * 根据域名获取缓存的 favicon（第一层缓存）
 * @param {string} domain - 域名
 * @returns {string|null} - base64 数据或 null
 */
function getByDomain(domain) {
    if (!domain) return null

    // 先查内存缓存
    if (memoryCache.domains.has(domain)) {
        return memoryCache.domains.get(domain)
    }

    // 再查文件缓存
    const filePath = getDomainCachePath(domain)
    try {
        if (fs.existsSync(filePath)) {
            const data = fs.readFileSync(filePath, 'utf8')
            // 写入内存缓存
            memoryCache.domains.set(domain, data)
            return data
        }
    } catch {
        // 忽略读取错误
    }

    return null
}

/**
 * 根据 URL 获取缓存的 favicon（第二层缓存）
 * @param {string} url - favicon URL
 * @returns {string|null} - base64 数据或 null
 */
function getByUrl(url) {
    if (!url) return null

    // 先查内存缓存
    if (memoryCache.urls.has(url)) {
        return memoryCache.urls.get(url)
    }

    // 再查文件缓存
    const filePath = getUrlCachePath(url)
    try {
        if (fs.existsSync(filePath)) {
            const data = fs.readFileSync(filePath, 'utf8')
            // 写入内存缓存
            memoryCache.urls.set(url, data)
            return data
        }
    } catch {
        // 忽略读取错误
    }

    return null
}

/**
 * 保存 favicon 到缓存
 * @param {string} faviconUrl - favicon 的 URL
 * @param {string} pageUrl - 页面的 URL（用于提取域名）
 * @param {string} base64Data - base64 编码的 favicon 数据
 */
function save(faviconUrl, pageUrl, base64Data) {
    if (!base64Data) return

    const domain = extractDomain(pageUrl)

    // 保存到 URL 缓存
    if (faviconUrl) {
        const urlPath = getUrlCachePath(faviconUrl)
        memoryCache.urls.set(faviconUrl, base64Data)
        fs.writeFile(urlPath, base64Data, 'utf8', () => {})
    }

    // 保存到域名缓存
    if (domain) {
        const domainPath = getDomainCachePath(domain)
        memoryCache.domains.set(domain, base64Data)
        fs.writeFile(domainPath, base64Data, 'utf8', () => {})
    }
}

/**
 * 获取并缓存 favicon
 * 先查缓存，无缓存则下载并保存
 * @param {string} faviconUrl - favicon URL
 * @param {string} pageUrl - 页面 URL
 * @param {number} timeout - 超时时间（毫秒）
 * @returns {Promise<string|null>} - base64 数据或 null
 */
async function fetchAndCache(faviconUrl, pageUrl, timeout = 5000) {
    if (!faviconUrl || typeof faviconUrl !== 'string') {
        return null
    }

    // 如果已经是 base64，直接保存并返回
    if (faviconUrl.startsWith('data:')) {
        save(null, pageUrl, faviconUrl)
        return faviconUrl
    }

    // 先查 URL 缓存
    const cached = getByUrl(faviconUrl)
    if (cached) {
        // 更新域名缓存（确保域名缓存是最新的）
        const domain = extractDomain(pageUrl)
        if (domain && !memoryCache.domains.has(domain)) {
            save(null, pageUrl, cached)
        }
        return cached
    }

    // 下载 favicon
    const base64Data = await downloadFavicon(faviconUrl, timeout)

    if (base64Data) {
        // 保存到缓存
        save(faviconUrl, pageUrl, base64Data)
    }

    return base64Data
}

/**
 * 下载 favicon 并转换为 base64
 * @param {string} faviconUrl
 * @param {number} timeout
 * @returns {Promise<string|null>}
 */
function downloadFavicon(faviconUrl, timeout = 5000) {
    return new Promise((resolve) => {
        try {
            const request = net.request(faviconUrl)

            const timeoutId = setTimeout(() => {
                request.abort()
                resolve(null)
            }, timeout)

            const chunks = []

            request.on('response', (response) => {
                const contentType = response.headers['content-type']
                const isImage = contentType && (
                    contentType.includes('image/') ||
                    contentType.includes('icon')
                )

                if (response.statusCode !== 200 || !isImage) {
                    clearTimeout(timeoutId)
                    resolve(null)
                    return
                }

                response.on('data', (chunk) => {
                    chunks.push(chunk)
                })

                response.on('end', () => {
                    clearTimeout(timeoutId)
                    try {
                        const buffer = Buffer.concat(chunks)
                        if (buffer.length < 10) {
                            resolve(null)
                            return
                        }
                        let mimeType = 'image/png'
                        if (contentType) {
                            const match = contentType.match(/^([^;]+)/)
                            if (match) {
                                mimeType = match[1].trim()
                            }
                        }
                        const base64 = buffer.toString('base64')
                        resolve(`data:${mimeType};base64,${base64}`)
                    } catch {
                        resolve(null)
                    }
                })

                response.on('error', () => {
                    clearTimeout(timeoutId)
                    resolve(null)
                })
            })

            request.on('error', () => {
                clearTimeout(timeoutId)
                resolve(null)
            })

            request.end()
        } catch {
            resolve(null)
        }
    })
}

/**
 * 清空缓存
 */
function clearCache() {
    memoryCache.domains.clear()
    memoryCache.urls.clear()

    if (cacheDir && fs.existsSync(cacheDir)) {
        try {
            const files = fs.readdirSync(cacheDir)
            for (const file of files) {
                fs.unlinkSync(path.join(cacheDir, file))
            }
        } catch {
            // 忽略错误
        }
    }
}

/**
 * 清理过期的缓存文件
 * 默认清理 30 天未访问的文件
 * @param {number} maxAgeDays - 最大保留天数，默认 30
 */
function cleanExpiredCache(maxAgeDays = 30) {
    ensureCacheDir()

    if (!cacheDir || !fs.existsSync(cacheDir)) {
        return
    }

    const now = Date.now()
    const maxAgeMs = maxAgeDays * 24 * 60 * 60 * 1000

    try {
        const files = fs.readdirSync(cacheDir)
        for (const file of files) {
            const filePath = path.join(cacheDir, file)
            try {
                const stats = fs.statSync(filePath)
                // 使用访问时间（atime）判断是否过期
                if (now - stats.atimeMs > maxAgeMs) {
                    fs.unlinkSync(filePath)
                }
            } catch {
                // 忽略单个文件的错误
            }
        }
    } catch {
        // 忽略错误
    }
}

module.exports = {
    extractDomain,
    getByDomain,
    getByUrl,
    save,
    fetchAndCache,
    clearCache,
    cleanExpiredCache
}
