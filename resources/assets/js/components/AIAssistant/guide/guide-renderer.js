/**
 * AI 页面引导渲染器（单例状态机）
 *
 * 渲染层用 driver.js（高亮元素默认可点击 + 稳定定位 + 平滑过渡）；
 * 编排层自研：脚本 schema、四级元素定位、跨页 pre_action 导航、找不到时降级。
 *
 * 两个入口汇聚到这里：
 *  - 通道A：AI 回复中的 ```ai-guide 围栏脚本 → DialogMarkdown「带我去」按钮点击
 *  - 通道B：AI 调 show_guide MCP 工具 → operation-module WebSocket 请求
 *
 * 元素定位四级 fallback：
 *  L1 selector（精确 CSS）→ L2 text（可访问名称匹配）→ L3 query（向量语义匹配）
 *  → L4 降级为居中纯文字气泡（不中断引导）
 */

import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import './guide.css';
import { createActionExecutor } from '../action-executor';
import {
    collectElements,
    isElementVisible,
    findElementByRef,
    searchByVector,
} from '../page-context-collector';
import emitter from '../../../store/events';

// L3 向量匹配单次超时
const VECTOR_TIMEOUT = 3000;
// pre_action 后等待页面响应的固定延迟
const PRE_ACTION_DELAY = 300;
// pre_action 引发的路由变化宽限期
const NAV_GRACE_MS = 3000;
// 目标元素未显式指定 wait 时的默认等待窗口（导航/弹窗后晚渲染兜底）
const DEFAULT_WAIT = 1500;

const state = {
    active: false,
    script: null,
    stepIndex: 0,
    store: null,
    router: null,
    driver: null,
    executor: null,
    removeRouteHook: null,
    navGraceUntil: 0,
    // 自增运行序号：异步步骤解析期间引导被关闭/重启时丢弃旧结果
    runSeq: 0,
};

function escapeHtml(s) {
    return (s || '').replace(/[&<>"']/g, c => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]));
}

/**
 * 校验并归一引导脚本（宽容解析：未知字段忽略、缺 content 的步骤剔除）
 * @returns {{valid: boolean, script?: Object, error?: string}}
 */
export function validateScript(raw) {
    let script = raw;
    if (typeof raw === 'string') {
        try {
            script = JSON.parse(raw);
        } catch (e) {
            return { valid: false, error: 'JSON 解析失败' };
        }
    }
    if (!script || typeof script !== 'object') {
        return { valid: false, error: '脚本不是对象' };
    }
    if (script.version !== 1) {
        return { valid: false, error: `不支持的脚本版本: ${script.version}` };
    }
    if (!Array.isArray(script.steps)) {
        return { valid: false, error: '缺少 steps 数组' };
    }
    const steps = script.steps
        .filter(s => s && typeof s === 'object' && typeof s.content === 'string' && s.content.trim())
        .map(s => ({
            title: typeof s.title === 'string' ? s.title : '',
            content: s.content.trim(),
            pre_action: s.pre_action && typeof s.pre_action === 'object' ? s.pre_action : null,
            target: s.target && typeof s.target === 'object' ? s.target : null,
            placement: typeof s.placement === 'string' ? s.placement : 'auto',
        }));
    if (!steps.length) {
        return { valid: false, error: '没有有效步骤' };
    }
    return {
        valid: true,
        script: {
            version: 1,
            title: typeof script.title === 'string' ? script.title : '',
            steps,
        },
    };
}

/**
 * 启动引导。脚本无效时抛 Error（operation-module 据此回传 AI）。
 */
export function startGuide(raw, { store, router }) {
    const check = validateScript(raw);
    if (!check.valid) {
        throw new Error(`引导脚本无效: ${check.error}`);
    }
    if (state.active) {
        stopGuide({ silent: true });
    }

    state.active = true;
    state.script = check.script;
    state.stepIndex = 0;
    state.store = store;
    state.router = router;
    state.executor = createActionExecutor(store, router);
    state.runSeq++;

    // 通知 AI 浮窗收起，避免遮挡目标元素
    emitter.emit('aiGuideStarted');

    createDriver();
    watchRoute();
    // 首步直接展示（不自动执行 pre_action）：需要跳转的动作等用户点「下一步」再执行
    goToStep(0);
    return { total_steps: check.script.steps.length };
}

/**
 * 结束引导
 */
export function stopGuide({ silent = false } = {}) {
    if (!state.active) {
        return;
    }
    state.active = false;
    state.runSeq++;
    if (state.removeRouteHook) {
        state.removeRouteHook();
        state.removeRouteHook = null;
    }
    if (state.driver) {
        try {
            state.driver.destroy();
        } catch (e) {
            // ignore
        }
        state.driver = null;
    }
    state.script = null;
    state.executor = null;
    if (!silent) {
        $A.messageSuccess('引导已结束');
    }
}

function createDriver() {
    // 深色模式：DooTask 给 html 加全局 invert 滤镜，遮罩用浅色经反相后才呈暗色
    const dark = typeof document !== 'undefined' && document.body.classList.contains('dark-mode-reverse');
    state.driver = driver({
        allowClose: false,            // 不允许点遮罩误关，提供显式「跳过引导」
        overlayColor: dark ? 'rgb(220, 220, 220)' : 'rgb(0, 0, 0)',
        overlayOpacity: 0.5,
        stagePadding: 6,
        stageRadius: 6,
        smoothScroll: true,
        animate: true,
        popoverClass: 'ai-guide-popover',
        // disableActiveInteraction 默认 false → 高亮元素可点击（可点 + 下一步并存）
    });
}

/**
 * 加载态：解析/导航期间显示居中提示
 */
function showLoading() {
    if (!state.driver) {
        return;
    }
    state.driver.highlight({
        popover: {
            description: `<div class="ai-guide-loading">${escapeHtml($A.L('正在生成操作引导…'))}</div>`,
            showButtons: [],
            popoverClass: 'ai-guide-popover',
        },
    });
}

function mapSide(placement) {
    if (placement === 'top' || placement === 'bottom' || placement === 'left' || placement === 'right') {
        return placement;
    }
    return undefined; // auto/center 交给 driver 自适应
}

/**
 * 渲染某一步到 driver 气泡（el 为空 → 居中纯文字）
 */
function renderStep(index, el, degraded) {
    if (!state.driver) {
        return;
    }
    const total = state.script.steps.length;
    const step = state.script.steps[index];
    const isLast = index + 1 >= total;
    const prefix = degraded
        ? `<p class="ai-guide-degraded">${escapeHtml($A.L('未找到目标元素，以下为操作说明'))}</p>`
        : '';

    state.driver.highlight({
        element: el || undefined,
        popover: {
            title: step.title ? escapeHtml(step.title) : undefined,
            description: prefix + escapeHtml(step.content),
            side: mapSide(step.placement),
            align: 'start',
            showButtons: index > 0 ? ['next', 'previous'] : ['next'],
            showProgress: total > 1,
            progressText: `${index + 1} / ${total}`,
            nextBtnText: isLast ? $A.L('完成') : $A.L('下一步'),
            prevBtnText: $A.L('上一步'),
            popoverClass: 'ai-guide-popover',
            onNextClick: () => advance(),
            onPrevClick: () => {
                if (state.stepIndex > 0) {
                    goToStep(state.stepIndex - 1);
                }
            },
            onPopoverRender: (popover) => addSkipButton(popover),
        },
    });
}

// 在气泡底部插入「跳过引导」按钮（driver 默认按钮区无此项）
function addSkipButton(popover) {
    if (!popover || !popover.footerButtons) {
        return;
    }
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ai-guide-skip-btn';
    btn.textContent = $A.L('跳过引导');
    btn.addEventListener('click', () => stopGuide());
    popover.footerButtons.insertBefore(btn, popover.footerButtons.firstChild);
}

function watchRoute() {
    if (!state.router || typeof state.router.afterEach !== 'function') {
        return;
    }
    state.removeRouteHook = state.router.afterEach(() => {
        // pre_action 自己发起的导航在宽限期内豁免
        if (!state.active || Date.now() < state.navGraceUntil) {
            return;
        }
        stopGuide({ silent: true });
        $A.messageInfo('页面已切换，引导已结束');
    });
}

/**
 * 点「下一步/完成」：先执行【当前步】的 pre_action（跳转/代点都在用户确认后才发生），
 * 再进入下一步（leave-semantics）。最后一步执行完动作即结束。
 */
async function advance() {
    const seq = state.runSeq;
    const cur = state.script.steps[state.stepIndex];
    const isLast = state.stepIndex + 1 >= state.script.steps.length;

    if (cur.pre_action) {
        showLoading();
        try {
            await runPreAction(cur.pre_action);
        } catch (e) {
            console.warn('[AIGuide] pre_action failed:', e);
            $A.messageWarning(e?.message || '步骤执行失败');
        }
        if (seq !== state.runSeq) return;
        await delay(PRE_ACTION_DELAY);
        if (seq !== state.runSeq) return;
    }

    if (isLast) {
        stopGuide();
        return;
    }
    goToStep(state.stepIndex + 1);
}

/**
 * 展示某一步：定位 target（不执行任何动作）。target 找不到 → 降级为纯文字。
 */
async function goToStep(index) {
    const seq = state.runSeq;
    const step = state.script.steps[index];
    state.stepIndex = index;
    showLoading();

    let el = null;
    let degraded = false;
    try {
        if (step.target) {
            el = await resolveTarget(step.target);
            if (seq !== state.runSeq) return;
            if (!el) {
                degraded = true;
            }
        }
    } catch (e) {
        if (seq !== state.runSeq) return;
        console.warn('[AIGuide] resolve target failed, degrade to text:', e);
        degraded = !!step.target;
        el = null;
    }

    renderStep(index, el, degraded);
}

async function runPreAction(preAction) {
    if (preAction.type === 'action' && preAction.name) {
        state.navGraceUntil = Date.now() + NAV_GRACE_MS;
        await state.executor.executeAction(preAction.name, preAction.params || {});
        return;
    }
    if (preAction.type === 'click' && preAction.target) {
        const el = await resolveTarget(preAction.target);
        if (!el) {
            throw new Error('未找到要点击的元素');
        }
        state.navGraceUntil = Date.now() + NAV_GRACE_MS;
        el.click();
    }
}

/**
 * 四级元素定位；wait>0 时 MutationObserver 等待动态元素
 * @returns {Promise<Element|null>}
 */
async function resolveTarget(target) {
    let el = resolveSync(target);
    if (el) {
        return el;
    }
    // L3 向量匹配（开销大，立即跑一次）
    el = await resolveByVector(target);
    if (el) {
        return el;
    }
    // 默认给 1.5s 等待窗口：导航/弹窗后目标可能晚渲染，未显式指定也兜底重试
    const wait = Math.min(Math.max(parseInt(target.wait, 10) || DEFAULT_WAIT, 0), 15000);
    if (!wait) {
        return null;
    }
    // 等待动态元素：mutation 200ms 防抖只重跑廉价的 L1/L2，超时前最后跑一次 L3
    el = await new Promise(resolve => {
        let timer = null;
        let done = false;
        const finish = (result) => {
            if (done) return;
            done = true;
            observer.disconnect();
            clearTimeout(timeoutTimer);
            clearTimeout(timer);
            resolve(result);
        };
        const observer = new MutationObserver(() => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                const found = resolveSync(target);
                if (found) {
                    finish(found);
                }
            }, 200);
        });
        observer.observe(document.body, { childList: true, subtree: true });
        const timeoutTimer = setTimeout(() => finish(null), wait);
    });
    if (el) {
        return el;
    }
    return resolveByVector(target);
}

// 归一化文本，吸收常见近义动词差异（创建/新建/新增/添加），提升标签匹配命中率
function _normText(s) {
    return (s || '').toLowerCase().replace(/创建|新建|新增|添加/g, '建').replace(/\s+/g, '');
}

/**
 * L1 selector → L2 text（同步、廉价）
 */
function resolveSync(target) {
    // L1：精确 CSS 选择器
    if (target.selector) {
        try {
            const el = document.querySelector(target.selector);
            if (el && isElementVisible(el)) {
                return el;
            }
        } catch (e) {
            // 选择器非法，忽略
        }
    }
    // L2：可访问名称匹配（精确 → 归一双向包含）
    if (target.text) {
        const { elements, refMap } = collectElements({ maxElements: 500 });
        const q = _normText(target.text);
        const score = (info) => {
            const name = _normText(info.name);
            if (!name) return 0;
            if (name === q) return 3;
            if (name.includes(q)) return 2;
            // 元素名较短且被 query 包含（如 query "创建项目卡片" 含元素 "新建项目"）
            if (name.length >= 2 && q.includes(name)) return 1;
            return 0;
        };
        let best = null;
        let bestScore = 0;
        for (const info of elements) {
            const s = score(info);
            if (s > bestScore) {
                const el = findElementByRef(info.ref, refMap);
                if (el && isElementVisible(el)) {
                    best = el;
                    bestScore = s;
                    if (s === 3) break;
                }
            }
        }
        if (best) {
            return best;
        }
    }
    return null;
}

/**
 * L3 query 向量语义匹配（走 assistant/match_elements，3s 超时）
 */
async function resolveByVector(target) {
    if (!target.query || !state.store) {
        return null;
    }
    try {
        const { elements, refMap } = collectElements({ maxElements: 200 });
        if (!elements.length) {
            return null;
        }
        const matches = await Promise.race([
            searchByVector(state.store, target.query, elements, 1),
            delay(VECTOR_TIMEOUT).then(() => []),
        ]);
        if (matches && matches.length) {
            const el = findElementByRef(matches[0].ref, refMap);
            if (el && isElementVisible(el)) {
                return el;
            }
        }
    } catch (e) {
        // 向量匹配失败静默降级
    }
    return null;
}

function delay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

export function isGuideActive() {
    return state.active;
}

export default startGuide;

// 暴露到 window 供调试与 Playwright 测试使用
if (typeof window !== 'undefined') {
    window.__startAiGuide = (script, ctx = {}) => {
        let { store, router } = ctx;
        if (!store || !router) {
            const root = document.getElementById('app')?.__vue__;
            store = store || root?.$store;
            router = router || root?.$router;
        }
        return startGuide(script, { store, router });
    };
    window.__stopAiGuide = () => stopGuide({ silent: true });
}
