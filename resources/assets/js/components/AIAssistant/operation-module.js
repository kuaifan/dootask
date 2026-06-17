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
import { resolveActiveContext } from './active-context';

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
        const scope = payload?.scope || 'auto';

        // 解析当前活动上下文：主界面，或最前打开的同源微应用 iframe
        const active = resolveActiveContext(this.store, scope);

        // 微应用打开但不可读（跨源 / 未就绪 / 指定 app 但无应用）：优雅降级，不采集
        if (active.kind === 'app' && !active.reachable) {
            const reasonText = active.reason === 'cross_origin'
                ? '当前应用为跨源页面，无法读取其内部元素'
                : active.reason === 'no_app'
                    ? '当前没有打开任何微应用'
                    : '当前应用尚未加载完成';
            const base = collectPageContext(this.store, { include_elements: false });
            base.elements = [];
            base.element_count = 0;
            base.total_count = 0;
            base.has_more = false;
            base.frame = {
                scope: 'app',
                app_name: active.appName || null,
                operable: false,
                reachable: false,
                reason: active.reason,
            };
            base.hint = `${reasonText}。可改用主界面（scope=main）操作，或改用数据命令完成。`;
            this.executor.setRefMap(new Map(), null);
            return base;
        }

        const doc = active.doc || document;

        let context = collectPageContext(this.store, {
            doc,
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
                doc,
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
                    context.refElements = allContext.refElements; // 全量 Map 覆盖向量命中的 ref
                    context.ref_map = {};
                    for (const el of vectorMatches) {
                        if (el.ref) {
                            context.ref_map[el.ref] = { role: el.role, name: el.name };
                        }
                    }
                }
            }
        }

        // 标注本次采集所在的上下文（主界面 / 微应用），让模型清楚在操作谁
        context.frame = {
            scope: active.scope,
            app_name: active.appName || null,
            operable: true,
        };

        // 将 ref→Element 实时 Map 与活动上下文存入 executor，供后续元素操作解析（含失效守卫）
        if (context.refElements && this.executor) {
            this.executor.setRefMap(context.refElements, active);
        }
        delete context.refElements; // Map 不参与序列化回包

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
