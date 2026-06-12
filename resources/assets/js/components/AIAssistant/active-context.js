/**
 * AI 助手活动上下文解析
 *
 * 页面操作（采集 / 元素操作）默认作用于"用户当前最前面看到的文档"：
 * 当有微应用插件以 iframe 形式打开并在最前时，操作其 iframe 内部 DOM；否则操作主文档。
 *
 * 仅支持同源 iframe（应用商店插件反代到主站同源路径，contentDocument 可达）；
 * 跨源或尚未就绪的应用返回 reachable=false，由上层（operation-module）优雅降级。
 *
 * micro-app（@micro-zoe with 沙箱）类型的子应用 DOM 本就渲染在主文档里，
 * 现采集器走主文档即可扫到，这里按主界面处理、不特殊穿透。
 */

// iframe 元素选择器（见 MicroApps/iframe.vue）
const APP_IFRAME_SELECTOR = 'iframe.micro-app-iframe-container';

/**
 * 是否 iframe 类型微应用（与 MicroApps/index.vue::isIframe 对齐）
 * @param {string} type
 * @returns {boolean}
 */
function isIframeType(type) {
    return typeof type === 'string' && /^iframe/i.test(type);
}

/**
 * 取最前打开的微应用（isOpen 且 lastOpenAt 最大）
 * @param {Object} store - Vuex store
 * @returns {Object|null}
 */
function frontmostApp(store) {
    const apps = (store?.state?.microApps || []).filter(a => a && a.isOpen);
    if (!apps.length) {
        return null;
    }
    return apps.reduce((a, b) => ((b.lastOpenAt || 0) > (a.lastOpenAt || 0) ? b : a));
}

/**
 * 定位某微应用对应的 iframe DOM 元素
 * @param {Object} app - microApps 中的 app 对象（含注入运行时变量后的 url）
 * @returns {HTMLIFrameElement|null}
 */
function findAppIframe(app) {
    if (!app || !app.url) {
        return null;
    }
    const frames = [...document.querySelectorAll(APP_IFRAME_SELECTOR)];
    if (!frames.length) {
        return null;
    }
    // 1. 精确匹配 src === app.url
    let hit = frames.find(f => f.src === app.url);
    if (hit) {
        return hit;
    }
    // 2. URL 规范化匹配（容忍尾斜杠/编码差异）
    try {
        const target = new URL(app.url, location.href).href;
        hit = frames.find(f => {
            try {
                return new URL(f.src, location.href).href === target;
            } catch (e) {
                return false;
            }
        });
        if (hit) {
            return hit;
        }
    } catch (e) {
        // ignore
    }
    // 3. 仅一个微应用 iframe 时直接用
    return frames.length === 1 ? frames[0] : null;
}

/**
 * 解析当前活动上下文
 * @param {Object} store - Vuex store 实例
 * @param {string} scope - 'auto'（默认，有同源微应用在最前就采它）| 'main'（强制主界面）| 'app'（强制最前微应用）
 * @returns {Object} 上下文：
 *   { kind:'main', scope, doc:document, appName, label, reachable:true, frameKey:'main' }
 *   { kind:'app', scope:'app', doc:iframeDoc, appName, appTitle, label, reachable:true, frameKey }
 *   { kind:'app', scope:'app', appName, appTitle, reachable:false, reason:'cross_origin'|'not_ready'|'no_app', frameKey }
 */
export function resolveActiveContext(store, scope = 'auto') {
    const mainCtx = {
        kind: 'main',
        scope: 'main',
        doc: document,
        appName: null,
        label: '主界面',
        reachable: true,
        frameKey: 'main',
    };

    if (scope === 'main') {
        return mainCtx;
    }

    const app = frontmostApp(store);

    if (!app) {
        // 显式要求 app 但没有打开任何微应用
        if (scope === 'app') {
            return {
                kind: 'app', scope: 'app', appName: null, appTitle: '',
                reachable: false, reason: 'no_app', frameKey: null,
            };
        }
        // auto 下无微应用 → 主界面
        return mainCtx;
    }

    // 非 iframe 类型（micro-app with 沙箱）：DOM 在主文档，按主界面处理
    if (!isIframeType(app.type)) {
        return { ...mainCtx, appName: app.name, appTitle: app.title || app.name };
    }

    const base = {
        kind: 'app',
        scope: 'app',
        appName: app.name,
        appTitle: app.title || app.name,
        frameKey: `app:${app.name}@${app.lastOpenAt || 0}`,
    };

    const iframe = findAppIframe(app);
    if (!iframe) {
        return { ...base, reachable: false, reason: 'not_ready' };
    }

    try {
        const doc = iframe.contentDocument;
        if (doc && doc.body && doc.body.children.length > 0) {
            return { ...base, reachable: true, doc, label: `微应用「${base.appTitle}」` };
        }
        return { ...base, reachable: false, reason: 'not_ready' };
    } catch (e) {
        // 跨源 iframe：contentDocument 抛异常
        return { ...base, reachable: false, reason: 'cross_origin' };
    }
}

export default resolveActiveContext;
