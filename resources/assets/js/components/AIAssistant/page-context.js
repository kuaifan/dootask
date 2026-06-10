/**
 * AI 助手页面上下文（弱提示词工厂）
 *
 * 只输出"页面类型 / 实体 id / 实体名称 / 对话类型"四类核心字段；
 * 详细数据（描述、统计、成员等）由 AI 通过工具（MCP）自取。
 *
 * - buildWeakPrompt(store, routeParams) → {contextKey, pageLabel, entity} | null
 * - renderWeakPromptText(weak, switching) → "[当前页面|页面切换] 页面类型(实体_id=xx,名称:xx)"
 */

/**
 * 根据当前页面 / 弹窗构建弱提示词数据
 * @returns {{contextKey: string, pageLabel: string, entity: Object|null}|null}
 */
export function buildWeakPrompt(store, routeParams = {}) {
    // 弹窗优先（任务详情 / 对话详情）
    const taskId = store.state.taskId;
    if (taskId > 0) {
        return buildTaskWeak(store, taskId);
    }
    const dialogModalShow = store.state.dialogModalShow;
    const dialogId = store.state.dialogId;
    if (dialogModalShow && dialogId > 0) {
        return buildDialogWeak(store, dialogId);
    }

    const routeName = store.state.routeName;
    switch (routeName) {
        case 'manage-dashboard':
            return weak('dashboard', '工作仪表盘');
        case 'manage-project':
            return buildProjectWeak(store);
        case 'manage-messenger':
            return buildMessengerWeak(store);
        case 'manage-calendar':
            return weak('calendar', '日历页');
        case 'manage-file':
            return weak('file-list', '文件列表页');
        case 'single-task':
        case 'single-task-content':
            return buildTaskWeak(store, routeParams.taskId);
        case 'single-dialog':
            return buildDialogWeak(store, routeParams.dialogId);
        case 'single-file':
            return buildFileWeak(routeParams.codeOrFileId);
        case 'single-file-task':
            return buildFileWeak(routeParams.fileId);
        case 'single-report-edit':
            return buildReportWeak(routeParams.reportEditId, '工作汇报编辑');
        case 'single-report-detail':
            return buildReportWeak(routeParams.reportDetailId, '工作汇报详情');
        default:
            return null;
    }
}

/**
 * 渲染弱提示词文本
 * @param {Object|null} weak - buildWeakPrompt 返回值
 * @param {boolean} switching - true=[页面切换] / false=[当前页面]
 * @returns {string}
 */
export function renderWeakPromptText(weak, switching) {
    if (!weak) {
        return '';
    }
    const prefix = switching ? '[页面切换]' : '[当前页面]';
    const segments = [];
    const entity = weak.entity;
    if (entity) {
        if (entity.id !== undefined && entity.id !== null && entity.id !== '') {
            segments.push(`${entity.type}_id=${entity.id}`);
        }
        if (entity.name) {
            segments.push(`名称:${entity.name}`);
        }
        if (entity.dialogType) {
            segments.push(`对话类型:${entity.dialogType}`);
        }
    }
    const detail = segments.length ? `(${segments.join(',')})` : '';
    return `${prefix} ${weak.pageLabel}${detail}`;
}

// ===== 内部构造器 =====

function weak(contextKey, pageLabel, entity = null) {
    return {contextKey, pageLabel, entity};
}

function buildTaskWeak(store, taskId) {
    const id = Number(taskId);
    if (!id) {
        return weak('task', '任务详情页');
    }
    const task = (store.state.cacheTasks || []).find(t => t.id === id);
    return weak(`task:${id}`, '任务详情页', {
        type: 'task',
        id,
        name: task?.name || '',
    });
}

function buildDialogWeak(store, dialogId) {
    const id = Number(dialogId);
    if (!id) {
        return weak('dialog', '对话页');
    }
    const dialog = (store.state.cacheDialogs || []).find(d => d.id === id);
    return weak(`dialog:${id}`, '对话页', {
        type: 'dialog',
        id,
        name: dialog?.name || '',
        dialogType: mapDialogType(dialog?.type),
    });
}

function buildProjectWeak(store) {
    const project = store.getters.projectData;
    if (!project?.id) {
        return weak('project-list', '项目列表页');
    }
    return weak(`project:${project.id}`, '项目详情页', {
        type: 'project',
        id: project.id,
        name: project.name || '',
    });
}

function buildMessengerWeak(store) {
    const dialogId = store.state.dialogId;
    if (!dialogId) {
        return weak('messenger', '消息列表页');
    }
    return buildDialogWeak(store, dialogId);
}

function buildFileWeak(fileId) {
    if (!fileId) {
        return weak('file', '文件页');
    }
    return weak(`file:${fileId}`, '文件页', {
        type: 'file',
        id: fileId,
        name: '',
    });
}

function buildReportWeak(reportId, label) {
    if (!reportId) {
        return weak('report', label);
    }
    return weak(`report:${reportId}`, label, {
        type: 'report',
        id: reportId,
        name: '',
    });
}

function mapDialogType(type) {
    if (!type) {
        return '';
    }
    if (type === 'group') return '群聊';
    if (type === 'user') return '私聊';
    return String(type);
}
