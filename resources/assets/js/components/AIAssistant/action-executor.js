/**
 * 操作执行器
 *
 * 执行来自 MCP 工具的操作请求，包括：
 * - 导航操作（打开任务、切换项目、跳转页面等）
 * - 元素级操作（点击、输入等，作为兜底）
 *
 * 注意：数据操作（创建任务、发送消息等）应使用 MCP DooTask 工具直接调用 API，
 * 本模块只负责前端导航和 UI 操作。
 */

import { resolveActiveContext } from './active-context';
import { selectBackend } from './input-backends';
import emitter from '../../store/events';

/**
 * 创建操作执行器
 * @param {Object} store - Vuex store 实例
 * @param {Object} router - Vue Router 实例
 * @returns {Object} 执行器实例
 */
export function createActionExecutor(store, router) {
    return new ActionExecutor(store, router);
}

class ActionExecutor {
    constructor(store, router) {
        this.store = store;
        this.router = router;

        // 导航操作注册表
        this.actionHandlers = {
            // 打开资源详情（通过 Vuex action）
            open_task: this.openTask.bind(this),
            open_dialog: this.openDialog.bind(this),

            // 页面导航（通过 goForward）
            open_project: this.openProject.bind(this),
            open_file: this.openFile.bind(this),
            open_folder: this.openFolder.bind(this),

            // 功能页面导航
            navigate_to_dashboard: this.navigateToDashboard.bind(this),
            navigate_to_messenger: this.navigateToMessenger.bind(this),
            navigate_to_calendar: this.navigateToCalendar.bind(this),
            navigate_to_files: this.navigateToFiles.bind(this),

            // 关闭当前应用窗口（外壳层，不受 iframe 作用域限制）
            close_app: this.closeApp.bind(this),

            // 别名支持
            goto_task: this.openTask.bind(this),
            goto_project: this.openProject.bind(this),
            goto_dialog: this.openDialog.bind(this),
            navigate_to_task: this.openTask.bind(this),
            navigate_to_project: this.openProject.bind(this),
            navigate_to_dialog: this.openDialog.bind(this),
        };
    }

    /**
     * 执行导航操作
     * @param {string} actionName - 操作名称
     * @param {Object} params - 操作参数
     * @returns {Promise<Object>} 执行结果
     */
    async executeAction(actionName, params = {}) {
        // 智能解析操作名，支持 open_task_358 这样的格式
        const { normalizedAction, extractedParams } = this.parseActionName(actionName);
        const mergedParams = { ...extractedParams, ...params };

        const handler = this.actionHandlers[normalizedAction];
        if (!handler) {
            throw new Error(`不支持的操作: ${actionName}。支持的操作: ${Object.keys(this.actionHandlers).join(', ')}`);
        }

        try {
            const result = await handler(mergedParams);
            return {
                success: true,
                action: normalizedAction,
                result,
            };
        } catch (error) {
            throw new Error(`执行操作失败: ${error.message}`);
        }
    }

    /**
     * 解析操作名，提取嵌入的参数
     * 支持格式: open_task_358 -> { normalizedAction: 'open_task', extractedParams: { task_id: 358 } }
     */
    parseActionName(actionName) {
        const patterns = [
            { regex: /^(open_task|goto_task|navigate_to_task)_(\d+)$/, paramName: 'task_id' },
            { regex: /^(open_project|goto_project|navigate_to_project)_(\d+)$/, paramName: 'project_id' },
            { regex: /^(open_dialog|goto_dialog|navigate_to_dialog)_(\d+)$/, paramName: 'dialog_id' },
            { regex: /^(open_file)_(\d+)$/, paramName: 'file_id' },
            { regex: /^(open_folder)_(\d+)$/, paramName: 'folder_id' },
        ];

        for (const { regex, paramName } of patterns) {
            const match = actionName.match(regex);
            if (match) {
                return {
                    normalizedAction: match[1],
                    extractedParams: { [paramName]: parseInt(match[2], 10) },
                };
            }
        }

        return { normalizedAction: actionName, extractedParams: {} };
    }

    // ========== 打开资源详情 ==========

    /**
     * 打开任务详情
     */
    async openTask(params) {
        const taskId = params.task_id;
        if (!taskId) {
            throw new Error('缺少 task_id 参数');
        }

        this.store.dispatch('openTask', taskId);
        return { opened: true, task_id: taskId };
    }

    /**
     * 打开对话
     */
    async openDialog(params) {
        const dialogId = params.dialog_id;
        if (!dialogId) {
            throw new Error('缺少 dialog_id 参数');
        }

        // 支持高级参数：跳转到特定消息
        const dialogParams = params.msg_id
            ? { dialog_id: dialogId, search_msg_id: params.msg_id }
            : dialogId;

        this.store.dispatch('openDialog', dialogParams);
        return { opened: true, dialog_id: dialogId };
    }

    // ========== 页面导航 ==========

    /**
     * 打开/切换到项目
     */
    async openProject(params) {
        const projectId = params.project_id;
        if (!projectId) {
            throw new Error('缺少 project_id 参数');
        }

        window.$A.goForward({ name: 'manage-project', params: { projectId } });
        return { navigated: true, project_id: projectId };
    }

    /**
     * 打开文件预览
     */
    async openFile(params) {
        const fileId = params.file_id;
        if (!fileId) {
            throw new Error('缺少 file_id 参数');
        }

        window.$A.goForward({ name: 'manage-file', params: { fileId } });
        return { navigated: true, file_id: fileId };
    }

    /**
     * 打开文件夹
     */
    async openFolder(params) {
        const folderId = params.folder_id;
        if (!folderId) {
            throw new Error('缺少 folder_id 参数');
        }

        window.$A.goForward({ name: 'manage-file', params: { folderId, fileId: null } });
        return { navigated: true, folder_id: folderId };
    }

    /**
     * 导航到仪表盘
     */
    async navigateToDashboard() {
        window.$A.goForward({ name: 'manage-dashboard' });
        return { navigated: true, page: 'dashboard' };
    }

    /**
     * 导航到消息页面
     */
    async navigateToMessenger() {
        window.$A.goForward({ name: 'manage-messenger' });
        return { navigated: true, page: 'messenger' };
    }

    /**
     * 导航到日历页面
     */
    async navigateToCalendar() {
        window.$A.goForward({ name: 'manage-calendar' });
        return { navigated: true, page: 'calendar' };
    }

    /**
     * 导航到文件管理页面
     */
    async navigateToFiles() {
        window.$A.goForward({ name: 'manage-file' });
        return { navigated: true, page: 'files' };
    }

    // ========== 应用窗口 ==========

    /**
     * 关闭当前打开的应用窗口（最前那个）
     *
     * 关闭属于外壳层动作，不受 iframe 作用域限制：投递事件给 MicroApps 组件，
     * 复用其现成的关闭逻辑。先用 store 状态判断有无打开应用，避免无应用时假报成功。
     */
    async closeApp() {
        const hasOpen = (this.store?.state?.microApps || []).some(a => a && a.isOpen);
        if (!hasOpen) {
            throw new Error('当前没有打开的应用');
        }
        emitter.emit('observeMicroApp:close');
        return { closed: true };
    }

    // ========== 元素级操作 ==========

    /**
     * 设置当前的 refMap 与活动上下文（由 operation-module 在获取上下文后调用）
     */
    setRefMap(refElements, context = null) {
        // refElements: Map<ref, Element>（描述层产出，直接持有元素）；容错旧的 plain object
        this.currentRefElements = refElements instanceof Map ? refElements : null;
        this.currentContext = context;
    }

    /**
     * 解析当前应执行元素操作的文档，并做失效守卫校验。
     * 当上次采集发生在某个微应用 iframe 内时，执行前重新解析最前上下文，
     * 若 frameKey 不一致（用户切了应用 / 重开过 / 刷新了页面）则拒绝，避免误操作。
     * @returns {Document}
     */
    resolveContextDoc() {
        const saved = this.currentContext;
        // 无上下文信息或主文档：直接用主文档
        if (!saved || saved.kind === 'main' || saved.frameKey === 'main') {
            return document;
        }
        const now = resolveActiveContext(this.store, saved.scope || 'auto');
        if (now.frameKey !== saved.frameKey || !now.reachable || !now.doc) {
            throw new Error('页面上下文已变更（用户切换了应用或刷新了页面），请重新获取页面上下文后再操作');
        }
        return now.doc;
    }

    /**
     * 执行元素级操作
     * @param {string} elementUid - 元素标识 (e1, e2, ... 或选择器)
     * @param {string} action - 操作类型
     * @param {string} value - 操作值
     * @returns {Promise<Object>} 执行结果
     */
    async executeElementAction(elementUid, action, value) {
        const doc = this.resolveContextDoc();
        const element = this.findElement(elementUid, doc);
        if (!element) {
            throw new Error(`element_not_found: 找不到元素 ${elementUid}（可能页面已变更，请重新获取页面上下文）`);
        }

        // 元素所在 window（主文档或微应用 iframe），合成事件需用它的构造器才被框架信任
        const win = element.ownerDocument.defaultView || window;

        // 选择输入后端：Electron CDP 可信输入优先，否则页面内合成事件（地板）
        if (!this.backend) this.backend = selectBackend();

        const result = await this.backend.perform(element, action, value, { doc, win, elementUid });
        return Object.assign({ element: elementUid }, result);
    }

    /**
     * 查找元素
     * 支持多种格式：e1, @e1, ref=e1, CSS选择器
     */
    findElement(identifier, doc = document) {
        let ref = null;
        if (identifier.startsWith('@')) {
            ref = identifier.slice(1);
        } else if (identifier.startsWith('ref=')) {
            ref = identifier.slice(4);
        } else if (/^e\d+$/.test(identifier)) {
            ref = identifier;
        }

        // ref 格式：从描述层产出的 ref→Element 实时 Map 直接取（最准）
        if (ref && this.currentRefElements) {
            const element = this.currentRefElements.get(ref);
            if (element && element.isConnected) return element;
            if (element && !element.isConnected) return null; // 失联（DOM 已变更）
        }

        // 尝试作为 CSS 选择器（兜底）
        try {
            const element = doc.querySelector(identifier);
            if (element) return element;
        } catch (e) {
            // 选择器无效，忽略
        }

        return null;
    }

    /**
     * 延迟工具方法
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

export default createActionExecutor;
