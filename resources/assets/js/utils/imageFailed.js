/**
 * 消息内加载失败的图片登记
 *
 * Safari 不缓存失败（如 404）的图片，虚拟列表回收/重挂载消息时 v-html 会重建 <img>，
 * 导致反复请求；且失败图片尺寸塌陷又会触发列表重新测量，形成循环。
 * 这里按地址记住失败的图片，重新渲染时直接输出占位图，不再请求。
 */

// 透明 1x1 gif，失败样式由 img[data-failed-src] 提供
export const imageFailedSrc = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"

const failedSet = new Set()

const normalize = src => `${src || ''}`.replace(/&amp;/g, '&')

export function markImageFailed(src) {
    if (src && !src.startsWith('data:')) {
        failedSet.add(normalize(src))
    }
}

export function unmarkImageFailed(src) {
    failedSet.delete(normalize(src))
}

export function isImageFailed(src) {
    return failedSet.size > 0 && failedSet.has(normalize(src))
}
