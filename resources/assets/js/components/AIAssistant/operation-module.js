/**
 * AI 助手前端操作模块
 *
 * 集成 WebSocket 客户端、页面上下文收集器和操作执行器，
 * 提供给 AI 助手组件使用。
 */

import { OperationClient } from './operation-client';
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
        this.enabled = false;
        this.client = null;
        this.executor = null;
        this.sessionId = null;

        // 回调函数
        this.onSessionReady = options.onSessionReady;
        this.onSessionLost = options.onSessionLost;
        this.onError = options.onError;
    }

    /**
     * 启用操作模块
     */
    enable() {
        if (this.enabled) {
            return;
        }

        this.enabled = true;

        // 创建操作执行器
        this.executor = createActionExecutor(this.store, this.router);

        // 创建 WebSocket 客户端
        this.client = new OperationClient({
            getToken: () => this.store.state.userToken,
            onRequest: this.handleRequest.bind(this),
            onConnected: this.handleConnected.bind(this),
            onDisconnected: this.handleDisconnected.bind(this),
            onError: this.handleError.bind(this),
        });

        // 建立连接
        this.client.connect();

        // 设置心跳
        this.heartbeatTimer = setInterval(() => {
            if (this.client) {
                this.client.ping();
            }
        }, 30000);
    }

    /**
     * 禁用操作模块
     */
    disable() {
        if (!this.enabled) {
            return;
        }

        this.enabled = false;

        if (this.heartbeatTimer) {
            clearInterval(this.heartbeatTimer);
            this.heartbeatTimer = null;
        }

        if (this.client) {
            this.client.disconnect();
            this.client = null;
        }

        this.executor = null;
        this.sessionId = null;
    }

    /**
     * 处理来自 MCP 的请求
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
        if (!this.executor) {
            throw new Error('操作执行器未初始化');
        }

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
        if (!this.executor) {
            throw new Error('操作执行器未初始化');
        }

        const elementUid = payload?.element_uid;
        const action = payload?.action;
        const value = payload?.value;

        if (!elementUid || !action) {
            throw new Error('缺少必要参数');
        }

        return this.executor.executeElementAction(elementUid, action, value);
    }

    /**
     * 处理连接成功
     */
    handleConnected(sessionId) {
        this.sessionId = sessionId;
        this.onSessionReady?.(sessionId);
    }

    /**
     * 处理连接断开
     */
    handleDisconnected() {
        this.sessionId = null;
        this.onSessionLost?.();
    }

    /**
     * 处理错误
     */
    handleError(error) {
        this.onError?.(error);
    }

    /**
     * 获取当前 session ID
     */
    getSessionId() {
        return this.sessionId;
    }

    /**
     * 检查是否已连接
     */
    isConnected() {
        return this.client?.isConnected() || false;
    }

    /**
     * 重新连接
     */
    reconnect() {
        if (this.client) {
            this.client.connect();
        }
    }
}

export default createOperationModule;
