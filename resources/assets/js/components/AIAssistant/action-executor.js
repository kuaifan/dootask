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

import { findElementByRef } from './page-context-collector';

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

    // ========== 元素级操作 ==========

    /**
     * 设置当前的 refMap（由 operation-module 在获取上下文后调用）
     */
    setRefMap(refMap) {
        this.currentRefMap = refMap;
    }

    /**
     * 执行元素级操作
     * @param {string} elementUid - 元素标识 (e1, e2, ... 或选择器)
     * @param {string} action - 操作类型
     * @param {string} value - 操作值
     * @returns {Promise<Object>} 执行结果
     */
    async executeElementAction(elementUid, action, value) {
        const element = this.findElement(elementUid);
        if (!element) {
            throw new Error(`找不到元素: ${elementUid}`);
        }

        switch (action) {
            case 'click':
                element.click();
                return { success: true, action: 'click', element: elementUid };

            case 'type':
                if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA' || element.contentEditable === 'true') {
                    element.focus();
                    if (element.contentEditable === 'true') {
                        element.textContent = value || '';
                    } else {
                        element.value = value || '';
                    }
                    element.dispatchEvent(new Event('input', { bubbles: true }));
                    element.dispatchEvent(new Event('change', { bubbles: true }));
                    return { success: true, action: 'type', value, element: elementUid };
                }
                throw new Error('元素不支持输入操作');

            case 'select':
                if (element.tagName === 'SELECT') {
                    element.value = value;
                    element.dispatchEvent(new Event('change', { bubbles: true }));
                    return { success: true, action: 'select', value, element: elementUid };
                }
                // iView Select 组件 - 先点击打开下拉
                element.click();
                await this.delay(200);
                const options = document.querySelectorAll('.ivu-select-dropdown-list .ivu-select-item');
                for (const option of options) {
                    if (option.textContent.trim().includes(value)) {
                        option.click();
                        return { success: true, action: 'select', value, element: elementUid };
                    }
                }
                throw new Error(`找不到选项: ${value}`);

            case 'focus':
                element.focus();
                return { success: true, action: 'focus', element: elementUid };

            case 'scroll':
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return { success: true, action: 'scroll', element: elementUid };

            case 'hover':
                element.dispatchEvent(new MouseEvent('mouseenter', { bubbles: true }));
                element.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
                return { success: true, action: 'hover', element: elementUid };

            default:
                throw new Error(`不支持的元素操作: ${action}`);
        }
    }

    /**
     * 查找元素
     * 支持多种格式：e1, @e1, ref=e1, CSS选择器
     */
    findElement(identifier) {
        let ref = null;
        if (identifier.startsWith('@')) {
            ref = identifier.slice(1);
        } else if (identifier.startsWith('ref=')) {
            ref = identifier.slice(4);
        } else if (/^e\d+$/.test(identifier)) {
            ref = identifier;
        }

        // 如果是 ref 格式，使用 refMap 查找
        if (ref && this.currentRefMap) {
            const element = findElementByRef(ref, this.currentRefMap);
            if (element) return element;
        }

        // 尝试作为 CSS 选择器
        try {
            const element = document.querySelector(identifier);
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
