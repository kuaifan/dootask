/**
 * Electron 输入后端（CDP 可信输入，渲染侧）
 *
 * 桌面端经 preload 暴露的 window.electron.sendAsync('pageInput', ...) 把输入意图
 * 转给主进程，由 webContents.debugger 走 CDP Input 域产生 isTrusted=true 的可信输入
 * （等价 Playwright 真正可靠的那一半，但第一方、在用户会话内，且能到达同源 iframe）。
 *
 * 仅覆盖最受益于可信输入的 type/click；其余操作（select 的 open→click、check、scroll
 * 等）页面内合成事件已足够，交回 Web 后端。任一 CDP 调用失败 → 回退 Web 后端。
 *
 * 注意：主进程 ipcMain.handle('pageInput') 见 electron/lib/page-input.js。
 */

export function isElectronInputAvailable() {
    return !!(typeof window !== 'undefined'
        && window.$A && window.$A.isElectron
        && window.electron && typeof window.electron.sendAsync === 'function');
}

function centerOf(el) {
    const r = el.getBoundingClientRect();
    return { x: Math.round(r.left + r.width / 2), y: Math.round(r.top + r.height / 2) };
}

// 选中元素现有内容，便于 insertText 覆盖
function selectAll(el, win) {
    try {
        if (typeof el.select === 'function') { el.select(); return; }
        if (el.isContentEditable) {
            const sel = win.getSelection && win.getSelection();
            if (sel) {
                const range = el.ownerDocument.createRange();
                range.selectNodeContents(el);
                sel.removeAllRanges();
                sel.addRange(range);
            }
        }
    } catch (e) { /* ignore */ }
}

export function createElectronBackend(getFallback) {
    const cdp = (op, args) => window.electron.sendAsync('pageInput', Object.assign({ op }, args));

    return {
        name: 'electron',
        async perform(element, action, value, ctx) {
            const win = (ctx && ctx.win) || element.ownerDocument.defaultView || window;
            try {
                if (action === 'type' || action === 'fill' || action === 'clear') {
                    const v = action === 'clear' ? '' : (value == null ? '' : String(value));
                    if (typeof element.focus === 'function') element.focus();
                    selectAll(element, win);
                    await cdp('insertText', { text: v }); // 覆盖当前选区
                    const got = element.value != null ? element.value : (element.textContent || '');
                    if (v && got !== v && !(got || '').includes(v)) {
                        throw new Error('value_not_applied: 可信输入回读不一致');
                    }
                    return { success: true, action: 'type', value: v, backend: 'electron' };
                }
                if (action === 'click') {
                    const { x, y } = centerOf(element);
                    await cdp('click', { x, y });
                    return { success: true, action: 'click', backend: 'electron' };
                }
            } catch (e) {
                // 可信输入失败 → 回退 Web 后端
                return getFallback().perform(element, action, value, ctx);
            }
            // 其余操作页面内合成已足够，交回 Web 后端
            return getFallback().perform(element, action, value, ctx);
        },
    };
}

export default createElectronBackend;
