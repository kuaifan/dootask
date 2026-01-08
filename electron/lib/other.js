// 平台检测常量
const isMac = process.platform === 'darwin'
const isWin = process.platform === 'win32'

// URL 和调用验证正则
const allowedUrls = /^(?:https?|mailto|tel|callto):/i;
const allowedCalls = /^(?:mailto|tel|callto):/i;

module.exports = {
    isMac,
    isWin,

    allowedUrls,
    allowedCalls
}
