// AI 助手页面操作 · CDP 可信输入（主进程）
//
// 渲染端经 window.electron.sendAsync('pageInput', {op, ...}) 调用（preload 已有的
// invoke 通道）；这里用 webContents.debugger 走 CDP Input 域，产生 isTrusted=true 的
// 可信输入，等价 Playwright 真正可靠的那一半，但第一方、在用户会话内，且同源 iframe 可达。
//
// 目标 webContents = event.sender（发起的渲染进程；同源 iframe 在其内，视口坐标可达）。
// 任一步骤失败抛错 → 渲染端 electron-backend 捕获后回退 Web 后端。

const { ipcMain } = require('electron')

const attached = new WeakSet()

function ensureAttached(wc) {
    if (attached.has(wc)) return
    if (!wc.debugger.isAttached()) {
        wc.debugger.attach('1.3') // 抛错则上抛，渲染端回退
    }
    attached.add(wc)
    wc.once('destroyed', () => attached.delete(wc))
}

async function dispatchClick(wc, x, y) {
    const base = { x, y, button: 'left', buttons: 1, clickCount: 1 }
    await wc.debugger.sendCommand('Input.dispatchMouseEvent', { type: 'mouseMoved', x, y })
    await wc.debugger.sendCommand('Input.dispatchMouseEvent', Object.assign({ type: 'mousePressed' }, base))
    await wc.debugger.sendCommand('Input.dispatchMouseEvent', Object.assign({ type: 'mouseReleased' }, base))
}

async function handle(event, args) {
    const wc = event.sender
    const op = args && args.op
    ensureAttached(wc)
    switch (op) {
        case 'insertText':
            // 覆盖当前选区（渲染端已 selectAll）
            await wc.debugger.sendCommand('Input.insertText', { text: String(args.text == null ? '' : args.text) })
            return { ok: true }
        case 'click':
            await dispatchClick(wc, Math.round(args.x), Math.round(args.y))
            return { ok: true }
        case 'key': {
            const key = String(args.key || '')
            await wc.debugger.sendCommand('Input.dispatchKeyEvent', { type: 'keyDown', key })
            await wc.debugger.sendCommand('Input.dispatchKeyEvent', { type: 'keyUp', key })
            return { ok: true }
        }
        default:
            throw new Error('unknown_op: ' + op)
    }
}

function registerPageInput() {
    ipcMain.handle('pageInput', handle)
}

module.exports = { registerPageInput }
