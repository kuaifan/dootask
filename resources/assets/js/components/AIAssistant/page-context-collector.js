/**
 * 页面上下文收集器
 *
 * 借鉴 agent-browser 项目的设计思想，基于 ARIA 角色收集页面元素。
 * 提供结构化的页面快照，包括可交互元素和内容元素。
 *
 * 描述层（元素发现 + 可访问名/角色 + ariaSnapshot 文本 + ref）复用 Playwright
 * 注入脚本的 ariaSnapshot 子集（见 ./aria/aria-bundle.js）；本文件负责分页、
 * 关键词/向量过滤、available_actions 等业务包装，并持有 ref→Element 实时 Map。
 */

import { buildSnapshot } from './aria/aria-bundle';

// ========== ARIA 角色分类 ==========

// 可交互角色 - 这些元素可以被点击、输入等
const INTERACTIVE_ROLES = new Set([
    'button', 'link', 'textbox', 'checkbox', 'radio',
    'combobox', 'listbox', 'menuitem', 'menuitemcheckbox',
    'menuitemradio', 'option', 'searchbox', 'slider',
    'spinbutton', 'switch', 'tab', 'treeitem', 'gridcell',
]);

// 内容角色 - 这些元素包含重要内容
const CONTENT_ROLES = new Set([
    'heading', 'cell', 'columnheader', 'rowheader',
    'listitem', 'article', 'region', 'main', 'navigation',
    'img', 'figure',
]);

// HTML 元素到 ARIA 角色的映射
const ELEMENT_ROLE_MAP = {
    'button': 'button',
    'a': 'link',
    'input': (el) => {
        const type = el.type?.toLowerCase() || 'text';
        switch (type) {
            case 'checkbox': return 'checkbox';
            case 'radio': return 'radio';
            case 'submit':
            case 'reset':
            case 'button': return 'button';
            case 'search': return 'searchbox';
            case 'range': return 'slider';
            case 'number': return 'spinbutton';
            default: return 'textbox';
        }
    },
    'textarea': 'textbox',
    'select': 'combobox',
    'option': 'option',
    'h1': 'heading',
    'h2': 'heading',
    'h3': 'heading',
    'h4': 'heading',
    'h5': 'heading',
    'h6': 'heading',
    'img': 'img',
    'nav': 'navigation',
    'main': 'main',
    'article': 'article',
    'li': 'listitem',
    'td': 'cell',
    'th': 'columnheader',
};

// ========== 元素收集器 ==========

/**
 * 收集当前页面上下文
 * @param {Object} store - Vuex store 实例
 * @param {Object} options - 收集选项
 * @param {boolean} options.include_elements - 是否包含可交互元素
 * @param {boolean} options.interactive_only - 仅返回可交互元素
 * @param {number} options.max_elements - 每页最大元素数量，默认 50
 * @param {number} options.offset - 跳过前 N 个元素（分页用），默认 0
 * @param {string} options.container - 容器选择器，只扫描该容器内的元素
 * @param {string} options.query - 搜索关键词，用于过滤相关元素
 * @returns {Object} 页面上下文
 */
export function collectPageContext(store, options = {}) {
    const routeName = store?.state?.routeName;
    const doc = options.doc || document;
    const includeElements = options.include_elements !== false;
    const interactiveOnly = options.interactive_only || false;
    const maxElements = options.max_elements || 50;
    const offset = options.offset || 0;
    const container = options.container || null;
    const query = options.query || '';

    // 基础上下文
    const context = {
        page_type: routeName || 'unknown',
        page_url: (doc.location && doc.location.href) || window.location.href,
        page_title: doc.title,
        timestamp: Date.now(),
        elements: [],
        element_count: 0,
        total_count: 0,
        offset: offset,
        has_more: false,
        available_actions: getAvailableActions(routeName, store),
    };

    // 收集可交互元素
    if (includeElements) {
        const result = collectElements({
            doc,
            interactiveOnly,
            maxElements,
            offset,
            container,
            query,
        });
        context.elements = result.elements;
        context.element_count = result.elements.length;
        context.total_count = result.totalCount;
        context.has_more = result.hasMore;
        context.ref_map = result.refMap;
        context.snapshot = result.snapshot;        // ariaSnapshot YAML（模型主消费）
        context.refElements = result.refElements;  // Map<ref,Element>（实时，供执行器；序列化前删除）
        // 标记是否经过关键词过滤
        if (query) {
            context.query = query;
            context.keyword_matched = result.keywordMatched;
        }
    }

    return context;
}

/**
 * 根据页面类型获取可用的导航操作
 * @param {string} routeName - 路由名称
 * @param {Object} store - Vuex store 实例
 * @returns {Array} 可用操作列表
 */
function getAvailableActions(routeName, store) {
    // 通用导航操作 - 在所有页面都可用
    const commonActions = [
        {
            name: 'navigate_to_dashboard',
            description: '跳转到仪表盘',
        },
        {
            name: 'navigate_to_messenger',
            description: '跳转到消息页面',
        },
        {
            name: 'navigate_to_calendar',
            description: '跳转到日历页面',
        },
        {
            name: 'navigate_to_files',
            description: '跳转到文件管理页面',
        },
    ];

    // 关闭当前应用 - 仅在有微应用打开时可用（外壳层，不受 iframe 作用域限制）
    const appWindowActions = [];
    if ((store?.state?.microApps || []).some(a => a && a.isOpen)) {
        appWindowActions.push({
            name: 'close_app',
            description: '关闭当前打开的应用窗口',
        });
    }

    // 根据页面类型添加特定操作
    const pageSpecificActions = [];

    switch (routeName) {
        case 'manage-project':
            // 项目页面：可以打开任务
            pageSpecificActions.push({
                name: 'open_task',
                description: '打开任务详情',
                params: { task_id: '任务ID' },
            });
            break;

        case 'manage-messenger':
            // 消息页面：可以打开特定对话
            pageSpecificActions.push({
                name: 'open_dialog',
                description: '打开/切换对话',
                params: { dialog_id: '对话ID', msg_id: '(可选)跳转到指定消息' },
            });
            break;

        case 'manage-file':
            // 文件页面：可以打开文件夹或文件
            pageSpecificActions.push(
                {
                    name: 'open_folder',
                    description: '打开文件夹',
                    params: { folder_id: '文件夹ID' },
                },
                {
                    name: 'open_file',
                    description: '打开文件预览',
                    params: { file_id: '文件ID' },
                }
            );
            break;

        case 'manage-dashboard':
            // 仪表盘：可以快速跳转到项目或打开任务
            pageSpecificActions.push(
                {
                    name: 'open_project',
                    description: '打开/切换到项目',
                    params: { project_id: '项目ID' },
                },
                {
                    name: 'open_task',
                    description: '打开任务详情',
                    params: { task_id: '任务ID' },
                }
            );
            break;

        default:
            // 其他页面：提供基础的打开操作
            pageSpecificActions.push(
                {
                    name: 'open_project',
                    description: '打开/切换到项目',
                    params: { project_id: '项目ID' },
                },
                {
                    name: 'open_task',
                    description: '打开任务详情',
                    params: { task_id: '任务ID' },
                },
                {
                    name: 'open_dialog',
                    description: '打开对话',
                    params: { dialog_id: '对话ID' },
                }
            );
    }

    return [...appWindowActions, ...pageSpecificActions, ...commonActions];
}

/**
 * 收集页面元素（基于 Playwright ariaSnapshot 子集发现可交互元素）
 *
 * 元素发现 / 可访问名·角色 / ref 分配全部来自 buildSnapshot；本函数只做关键词
 * 过滤、分页与结构化包装，并返回全量 ref→Element 实时 Map 供执行器解析。
 * @param {Object} options
 * @param {number} options.maxElements - 每页最大元素数量
 * @param {number} options.offset - 跳过前 N 个元素
 * @param {string} options.container - 容器选择器
 * @param {string} options.query - 搜索关键词
 * @returns {Object} { elements, refMap, refElements, snapshot, totalCount, hasMore, keywordMatched }
 */
export function collectElements(options = {}) {
    const {
        doc = document,
        maxElements = 50,
        offset = 0,
        container = null,
        query = '',
    } = options;

    const empty = { elements: [], refMap: {}, refElements: new Map(), snapshot: '', totalCount: 0, hasMore: false, keywordMatched: false };

    // 确定快照根元素
    let rootElement = doc.body || doc.documentElement || doc;
    if (container) {
        rootElement = doc.querySelector(container);
        if (!rootElement) return empty;
    }

    // 描述层：Playwright ariaSnapshot 子集 → YAML 文本 + 全量 ref→Element Map（仅可交互元素分配 ref）
    let snap;
    try {
        snap = buildSnapshot(rootElement, { mode: 'ai' });
    } catch (e) {
        return empty;
    }
    const refElements = snap.elements || new Map(); // Map<ref, Element>，覆盖全部可交互元素

    // 基于 ref→Element 构建结构化列表（顺序即 DOM 顺序，ref 与 YAML 一致）
    const all = [];
    for (const [ref, el] of refElements.entries()) {
        if (!el || !el.isConnected) continue;
        const info = extractElementInfo(el);
        if (!info) continue;
        info.ref = ref;
        all.push(info);
    }

    // 关键词过滤（无匹配则回退到全部）
    let filtered = all;
    let keywordMatched = false;
    if (query) {
        const q = query.toLowerCase();
        filtered = all.filter((info) => (info.name || '').toLowerCase().includes(q)
            || (info.placeholder || '').toLowerCase().includes(q)
            || (info.aria_label || '').toLowerCase().includes(q)
            || (info.title || '').toLowerCase().includes(q));
        keywordMatched = filtered.length > 0;
        if (filtered.length === 0) filtered = all;
    }

    // 分页（结构化列表分页；refElements 始终为全量，执行器可解析任意 ref）
    const totalCount = filtered.length;
    const endIndex = Math.min(offset + maxElements, totalCount);
    const hasMore = endIndex < totalCount;

    const elements = [];
    const refMap = {};
    for (let i = offset; i < endIndex; i++) {
        const info = filtered[i];
        elements.push(info);
        refMap[info.ref] = { role: info.role, name: info.name };
    }

    return { elements, refMap, refElements, snapshot: snap.text, totalCount, hasMore, keywordMatched };
}

/**
 * 提取元素信息
 * @param {Element} el
 * @param {number} index
 * @returns {Object|null}
 */
function extractElementInfo(el, index) {
    const tagName = el.tagName.toLowerCase();
    const role = getElementRole(el);

    // 获取元素名称/文本
    const name = getElementName(el);

    // 生成选择器
    const selector = generateSelector(el);

    // 基础信息
    const info = {
        role,
        tag: tagName,
        name: name || undefined,
        selector,
    };

    // 添加特定属性
    if (el.id) {
        info.id = el.id;
    }

    if (el.type && (tagName === 'input' || tagName === 'button')) {
        info.input_type = el.type;
    }

    if (el.placeholder) {
        info.placeholder = el.placeholder;
    }

    if (el.value && (tagName === 'input' || tagName === 'textarea')) {
        info.value = el.value.substring(0, 50);
    }

    if (el.href && tagName === 'a') {
        info.href = el.href;
    }

    if (el.checked !== undefined) {
        info.checked = el.checked;
    }

    if (el.title) {
        info.title = el.title;
    }

    // 添加 aria 属性
    const ariaLabel = el.getAttribute('aria-label');
    if (ariaLabel) {
        info.aria_label = ariaLabel;
    }

    return info;
}

/**
 * 获取元素的 ARIA 角色
 * @param {Element} el
 * @returns {string}
 */
function getElementRole(el) {
    // 首先检查显式的 role 属性
    const explicitRole = el.getAttribute('role');
    if (explicitRole) {
        return explicitRole;
    }

    // 使用映射表
    const tagName = el.tagName.toLowerCase();
    const roleMapping = ELEMENT_ROLE_MAP[tagName];

    if (typeof roleMapping === 'function') {
        return roleMapping(el);
    }

    if (typeof roleMapping === 'string') {
        return roleMapping;
    }

    // 检查是否可点击
    if (el.onclick || el.hasAttribute('onclick') ||
        el.style.cursor === 'pointer' ||
        (el.ownerDocument.defaultView || window).getComputedStyle(el).cursor === 'pointer') {
        return 'button';
    }

    return 'generic';
}

/**
 * 获取元素的可访问名称
 * @param {Element} el
 * @returns {string}
 */
export function getElementName(el) {
    // 优先级：aria-label > aria-labelledby > 内容文本 > title > placeholder > alt

    const ariaLabel = el.getAttribute('aria-label');
    if (ariaLabel) return ariaLabel.trim().substring(0, 100);

    const ariaLabelledBy = el.getAttribute('aria-labelledby');
    if (ariaLabelledBy) {
        const labelEl = el.ownerDocument.getElementById(ariaLabelledBy);
        if (labelEl) {
            return getTextContent(labelEl).substring(0, 100);
        }
    }

    // 对于输入元素，查找关联的 label
    if (el.id) {
        const label = el.ownerDocument.querySelector(`label[for="${el.id}"]`);
        if (label) {
            return getTextContent(label).substring(0, 100);
        }
    }

    // 获取元素内的文本内容
    const text = getTextContent(el);
    if (text) return text.substring(0, 100);

    // 其他属性
    if (el.title) return el.title.substring(0, 100);
    if (el.placeholder) return el.placeholder.substring(0, 100);
    if (el.alt) return el.alt.substring(0, 100);
    if (el.value && (el.tagName === 'INPUT' || el.tagName === 'BUTTON')) {
        return el.value.substring(0, 100);
    }

    return '';
}

/**
 * 获取元素的文本内容（排除子元素中隐藏的文本）
 * @param {Element} el
 * @returns {string}
 */
function getTextContent(el) {
    // 克隆元素以避免修改原始 DOM
    const clone = el.cloneNode(true);

    // 移除脚本和样式
    clone.querySelectorAll('script, style, [hidden], [aria-hidden="true"]').forEach(e => e.remove());

    // 获取文本并清理
    let text = clone.textContent || clone.innerText || '';
    text = text.replace(/\s+/g, ' ').trim();

    return text;
}

/**
 * 检查元素是否可见
 * @param {Element} el
 * @returns {boolean}
 */
export function isElementVisible(el) {
    if (!el) return false;

    // 元素所在的 window（主文档或微应用 iframe 文档），视口尺寸与样式都取它自己的
    const win = el.ownerDocument.defaultView || window;

    // 检查元素本身
    const style = win.getComputedStyle(el);

    if (style.display === 'none') return false;
    if (style.visibility === 'hidden') return false;
    if (style.opacity === '0') return false;

    // 检查边界框
    const rect = el.getBoundingClientRect();
    if (rect.width === 0 && rect.height === 0) return false;

    // 检查是否在视口内或附近（允许稍微超出）
    const viewportHeight = win.innerHeight;
    const viewportWidth = win.innerWidth;

    // 元素完全在视口外
    if (rect.bottom < -100 || rect.top > viewportHeight + 100) return false;
    if (rect.right < -100 || rect.left > viewportWidth + 100) return false;

    // 检查父元素的可见性
    let parent = el.parentElement;
    while (parent) {
        const parentStyle = win.getComputedStyle(parent);
        if (parentStyle.display === 'none') return false;
        if (parentStyle.visibility === 'hidden') return false;
        parent = parent.parentElement;
    }

    return true;
}

/**
 * 检查元素是否禁用
 * @param {Element} el
 * @returns {boolean}
 */
function isElementDisabled(el) {
    if (el.disabled) return true;
    if (el.getAttribute('aria-disabled') === 'true') return true;

    // 检查是否在禁用的 fieldset 中
    const fieldset = el.closest('fieldset');
    if (fieldset && fieldset.disabled) {
        // legend 中的元素不受影响
        const legend = fieldset.querySelector('legend');
        if (legend && legend.contains(el)) return false;
        return true;
    }

    // 检查 iView 组件的禁用状态
    if (el.classList.contains('ivu-btn-disabled')) return true;
    if (el.classList.contains('ivu-input-disabled')) return true;
    if (el.classList.contains('ivu-select-disabled')) return true;

    return false;
}

/**
 * 生成元素选择器
 * @param {Element} el
 * @returns {string}
 */
function generateSelector(el) {
    // 如果有 ID，直接使用
    if (el.id) {
        return `#${el.id}`;
    }

    // 尝试生成唯一选择器
    const parts = [];
    let current = el;
    let depth = 0;

    while (current && current !== el.ownerDocument.body && depth < 5) {
        let selector = current.tagName.toLowerCase();

        // 添加重要的类名（排除动态类）
        if (current.className && typeof current.className === 'string') {
            const classes = current.className
                .split(' ')
                .filter(c => c && !c.startsWith('ivu-') && !c.includes('--') && !c.includes('active') && !c.includes('hover'))
                .slice(0, 2);
            if (classes.length) {
                selector += '.' + classes.join('.');
            }
        }

        // 添加有用的属性
        if (current.getAttribute('role')) {
            selector += `[role="${current.getAttribute('role')}"]`;
        } else if (current.getAttribute('data-id')) {
            selector += `[data-id="${current.getAttribute('data-id')}"]`;
        } else if (current.getAttribute('name')) {
            selector += `[name="${current.getAttribute('name')}"]`;
        }

        parts.unshift(selector);
        current = current.parentElement;
        depth++;
    }

    return parts.join(' > ');
}

/**
 * 根据 ref 查找元素
 * @param {string} ref - 元素引用 (e1, e2, ...)
 * @param {Object} refMap - 引用映射表
 * @returns {Element|null}
 */
export function findElementByRef(ref, refMap, doc = document) {
    const refData = refMap[ref];
    if (!refData) {
        return null;
    }

    // 首先尝试使用选择器 + name 双重匹配
    if (refData.selector) {
        const elements = doc.querySelectorAll(refData.selector);

        if (elements.length === 1) {
            return elements[0];
        }

        // 多个匹配时，用 name 进一步筛选
        if (elements.length > 1 && refData.name) {
            for (const el of elements) {
                const elName = getElementName(el);
                if (elName === refData.name) {
                    return el;
                }
            }
        }

        // 如果 name 匹配失败，尝试用 nth（仅作为最后手段）
        if (refData.nth !== undefined && elements.length > refData.nth) {
            return elements[refData.nth];
        }

        if (elements.length > 0) {
            return elements[0];
        }
    }

    // 回退到角色+名称匹配
    const roleSelector = `[role="${refData.role}"]`;
    const candidates = doc.querySelectorAll(roleSelector);

    for (const candidate of candidates) {
        if (refData.name) {
            const candidateName = getElementName(candidate);
            if (candidateName === refData.name) {
                return candidate;
            }
        }
    }

    return null;
}

/**
 * 通过向量搜索匹配元素
 * @param {Object} store - Vuex store 实例
 * @param {string} query - 搜索查询
 * @param {Array} elements - 元素列表
 * @param {number} topK - 返回结果数量
 * @returns {Promise<Array>} 匹配的元素列表
 */
export async function searchByVector(store, query, elements, topK = 10) {
    if (!query || !elements || elements.length === 0) {
        return [];
    }

    // 只传有 name 的元素，减少 API 调用
    const minimalElements = elements
        .filter(el => el.name && el.name.trim())
        .map(el => ({
            ref: el.ref,
            name: el.name,
        }));

    if (minimalElements.length === 0) {
        return [];
    }

    try {
        const response = await store.dispatch('call', {
            url: 'assistant/match_elements',
            method: 'post',
            data: {
                query,
                elements: minimalElements,
                top_k: topK,
            },
        });

        const matches = response?.data?.matches;
        if (matches && matches.length > 0) {
            const refOrder = matches.map(m => m.element.ref);
            return elements
                .filter(el => refOrder.includes(el.ref))
                .sort((a, b) => refOrder.indexOf(a.ref) - refOrder.indexOf(b.ref));
        }
    } catch (e) {
        // 向量搜索失败，静默处理
    }

    return [];
}

export default collectPageContext;

// 暴露到 window 供调试使用
if (typeof window !== 'undefined') {
    window.__testPageContext = (options = {}) => {
        // 简化版，不需要 store；传 frameSrc 可在匹配的微应用 iframe 内采集（验证用）
        let doc = document;
        if (options.frameSrc) {
            const f = [...document.querySelectorAll('iframe')].find(x => (x.src || '').includes(options.frameSrc));
            if (f && f.contentDocument) {
                doc = f.contentDocument;
            }
        }
        const context = {
            page_url: (doc.location && doc.location.href) || window.location.href,
            page_title: doc.title,
            timestamp: Date.now(),
        };

        const result = collectElements({
            doc,
            interactiveOnly: options.interactive_only || false,
            maxElements: options.max_elements || 50,
            offset: options.offset || 0,
            container: options.container || null,
        });

        context.elements = result.elements;
        context.element_count = result.elements.length;
        context.total_count = result.totalCount;
        context.offset = options.offset || 0;
        context.has_more = result.hasMore;
        context.ref_map = result.refMap;

        return context;
    };
}
