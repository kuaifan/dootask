/**
 * Web 输入后端（DOM 兜底）
 *
 * 在页面内用合成事件操作元素：原生 prototype value setter（绕过 React/Vue 值追踪）
 * + 完整事件序列 + 写后回读校验；自定义下拉/日期等用 open→click。无法处理的控件
 * （文件上传、动态明细表等）抛 unsupported_widget，由上层（app call/回问）兜底。
 *
 * 已知边界：合成事件 isTrusted=false，部分组件（拖拽/指针捕获/原生文件框）填不动。
 * 桌面端优先走 Electron CDP 可信输入后端（见 electron-backend.js）。
 */

const delay = (ms) => new Promise((r) => setTimeout(r, ms));

// 结构化错误：消息以 `<code>: ` 前缀，便于上层与模型识别
function fail(code, msg) {
    return new Error(`${code}: ${msg}`);
}

// 组件壳 → 内层可写控件
function resolveWritable(el) {
    if (!el) return el;
    if (el.matches && el.matches('input, textarea')) return el;
    if (el.isContentEditable) return el;
    const inner = el.querySelector && el.querySelector('input, textarea, [contenteditable="true"], [contenteditable=""]');
    return inner || el;
}

function fireInput(el, win) {
    const InputEvt = win.InputEvent || win.Event;
    el.dispatchEvent(new InputEvt('input', { bubbles: true }));
    el.dispatchEvent(new win.Event('change', { bubbles: true }));
}

// 原生 setter + 事件序列 + 回读
function setValue(rawEl, value, win) {
    const el = resolveWritable(rawEl);
    const v = value == null ? '' : String(value);
    const tag = (el.tagName || '').toLowerCase();

    if (el.isContentEditable) {
        el.focus();
        const sel = win.getSelection && win.getSelection();
        if (sel) {
            const range = el.ownerDocument.createRange();
            range.selectNodeContents(el);
            sel.removeAllRanges();
            sel.addRange(range);
        }
        const ok = el.ownerDocument.execCommand && el.ownerDocument.execCommand('insertText', false, v);
        if (!ok) { el.textContent = v; }
        fireInput(el, win);
        if (v && !(el.textContent || '').includes(v)) throw fail('value_not_applied', '富文本写入未生效');
        return { success: true, action: 'type', value: v };
    }

    if (tag !== 'input' && tag !== 'textarea') {
        throw fail('unsupported_widget', '元素不可输入文本');
    }

    el.focus();
    const proto = tag === 'textarea' ? win.HTMLTextAreaElement.prototype : win.HTMLInputElement.prototype;
    const desc = Object.getOwnPropertyDescriptor(proto, 'value');
    const setter = desc && desc.set;
    if (setter) setter.call(el, v); else el.value = v; // 兜底
    fireInput(el, win);
    el.dispatchEvent(new win.Event('blur', { bubbles: true }));
    if (el.value !== v) throw fail('value_not_applied', '写入后回读不一致');
    return { success: true, action: 'type', value: v };
}

function clickEl(el) {
    if (typeof el.focus === 'function') { try { el.focus(); } catch (e) {} }
    el.click();
    return { success: true, action: 'click' };
}

// 原生 select 直接设值；自定义下拉/日期 open→click 匹配项
async function selectOption(el, value, doc, win) {
    const v = value == null ? '' : String(value);
    const tag = (el.tagName || '').toLowerCase();

    if (tag === 'select') {
        const opt = [...el.options].find((o) =>
            o.value === v || (o.textContent || '').trim() === v || (o.textContent || '').includes(v));
        if (!opt) throw fail('unsupported_widget', `原生 select 无选项 ${v}`);
        el.value = opt.value;
        fireInput(el, win);
        return { success: true, action: 'select', value: v };
    }

    // 自定义下拉：点击展开 → 在选项/日历单元格里按文本匹配 → 点击
    el.click();
    await delay(180);
    const optSelectors = '[role="option"], [role="gridcell"], [role="menuitemradio"], .ivu-select-item, li[role]';
    const opts = [...doc.querySelectorAll(optSelectors)].filter((o) => o.offsetParent !== null || o.getClientRects().length);
    const exact = opts.find((o) => (o.textContent || '').trim() === v);
    const fuzzy = opts.find((o) => (o.textContent || '').trim().includes(v));
    const match = exact || fuzzy;
    if (!match) throw fail('unsupported_widget', `下拉未渲染匹配项 ${v}`);
    match.click();
    await delay(80);
    return { success: true, action: 'select', value: v };
}

function isChecked(el) {
    const t = el.matches && el.matches('input[type="checkbox"], input[type="radio"]')
        ? el : (el.querySelector && el.querySelector('input[type="checkbox"], input[type="radio"]'));
    if (t) return !!t.checked;
    const a = el.getAttribute && el.getAttribute('aria-checked');
    return a === 'true';
}

function setChecked(el, want) {
    const t = el.matches && el.matches('input[type="checkbox"], input[type="radio"]')
        ? el : (el.querySelector && el.querySelector('input[type="checkbox"], input[type="radio"]'));
    if (t) {
        if (!!t.checked !== want) t.click();
        return { success: true, action: want ? 'check' : 'uncheck', checked: !!t.checked };
    }
    const a = el.getAttribute && el.getAttribute('aria-checked');
    if (a != null) {
        if ((a === 'true') !== want) el.click();
        return { success: true, action: want ? 'check' : 'uncheck' };
    }
    throw fail('unsupported_widget', '非复选/单选元素');
}

export function createWebBackend() {
    return {
        name: 'web',
        async perform(element, action, value, ctx) {
            const win = (ctx && ctx.win) || element.ownerDocument.defaultView || window;
            const doc = (ctx && ctx.doc) || element.ownerDocument;
            switch (action) {
                case 'click':
                    return clickEl(element);
                case 'type':
                case 'fill':
                    return setValue(element, value, win);
                case 'clear':
                    return setValue(element, '', win);
                case 'select':
                    return selectOption(element, value, doc, win);
                case 'check':
                    return setChecked(element, true);
                case 'uncheck':
                    return setChecked(element, false);
                case 'toggle':
                    return setChecked(element, !isChecked(element));
                case 'focus':
                    element.focus();
                    return { success: true, action: 'focus' };
                case 'scroll':
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return { success: true, action: 'scroll' };
                case 'hover':
                    element.dispatchEvent(new win.MouseEvent('mouseenter', { bubbles: true }));
                    element.dispatchEvent(new win.MouseEvent('mouseover', { bubbles: true }));
                    return { success: true, action: 'hover' };
                default:
                    throw fail('not_actionable', `不支持的操作: ${action}`);
            }
        },
    };
}

export default createWebBackend;
