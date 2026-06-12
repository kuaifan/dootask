/**
 * AI 助手前端操作模块
 *
 * 集成页面上下文收集器和操作执行器，供 AI 助手组件执行页面操作。
 * 传输层已并入主程序常驻 WebSocket（/ws）：后端经 assistant/operation/dispatch
 * 推送 type=operation 消息，浮窗组件收到后调用本模块 handleRequest 执行并回包，
 * 不再单独连接 MCP 的 operation WebSocket。
 */

import { collectPageContext, searchByVector } from './page-context-collector';
import { createActionExecutor } from './action-executor';

/**
 * 创建操作模块实例
 * @param {Object} options
 * @param {Object} options.store - Vuex store 实例
 * @param {Object} options.router - Vue Router 实例
 * @returns {Object} 操作模块实例
 */
export function createOperationModule(options = {}) {
    return new OperationModule(options);
}

class OperationModule {
    constructor(options) {
        this.store = options.store;
        this.router = options.router;
        this.executor = null;
    }

    /**
     * 确保操作执行器已创建（惰性初始化）
     */
    ensureExecutor() {
        if (!this.executor) {
            this.executor = createActionExecutor(this.store, this.router);
        }
        return this.executor;
    }

    /**
     * 处理一次页面操作请求
     * @param {string} action 操作类型
     * @param {Object} payload 操作参数
     */
    async handleRequest(action, payload) {
        switch (action) {
            case 'get_page_context':
                return this.getPageContext(payload);

            case 'execute_action':
                return this.executeAction(payload);

            case 'execute_element_action':
                return this.executeElementAction(payload);

            default:
                throw new Error(`未知的操作类型: ${action}`);
        }
    }

    /**
     * 获取页面上下文
     */
    async getPageContext(payload) {
        this.ensureExecutor();

        const includeElements = payload?.include_elements !== false;
        const interactiveOnly = payload?.interactive_only || false;
        const maxElements = payload?.max_elements || 100;
        const query = payload?.query || '';
        const offset = payload?.offset || 0;
        const container = payload?.container || null;

        let context = collectPageContext(this.store, {
            include_elements: includeElements,
            interactive_only: interactiveOnly,
            max_elements: maxElements,
            offset,
            container,
            query,
        });

        // 如果有 query 且关键词匹配失败，尝试向量搜索
        if (query && !context.keyword_matched) {
            const allContext = collectPageContext(this.store, {
                include_elements: true,
                interactive_only: interactiveOnly,
                max_elements: 200,
                offset: 0,
                container,
            });

            if (allContext.elements.length > 0) {
                const vectorMatches = await searchByVector(this.store, query, allContext.elements, 10);
                if (vectorMatches.length > 0) {
                    context.elements = vectorMatches;
                    context.element_count = vectorMatches.length;
                    context.total_count = vectorMatches.length;
                    context.has_more = false;
                    context.vector_matched = true;
                    context.ref_map = {};
                    for (const el of vectorMatches) {
                        if (el.ref) {
                            context.ref_map[el.ref] = {
                                role: el.role,
                                name: el.name,
                                selector: el.selector,
                                nth: el.nth,
                            };
                        }
                    }
                }
            }
        }

        // 将 refMap 存储到 executor，供后续元素操作使用
        if (context.ref_map && this.executor) {
            this.executor.setRefMap(context.ref_map);
        }

        return context;
    }

    /**
     * 执行业务操作
     */
    async executeAction(payload) {
        this.ensureExecutor();

        const actionName = payload?.name;
        const params = payload?.params || {};

        if (!actionName) {
            throw new Error('缺少操作名称');
        }

        return this.executor.executeAction(actionName, params);
    }

    /**
     * 执行元素操作
     */
    async executeElementAction(payload) {
        this.ensureExecutor();

        const elementUid = payload?.element_uid;
        const action = payload?.action;
        const value = payload?.value;

        if (!elementUid || !action) {
            throw new Error('缺少必要参数');
        }

        return this.executor.executeElementAction(elementUid, action, value);
    }
}

export default createOperationModule;
