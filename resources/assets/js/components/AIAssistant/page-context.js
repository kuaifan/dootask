/**
 * AI 助手页面上下文配置
 *
 * 设计原则：
 * - 提供当前页面/场景的上下文数据
 * - 传递实体 ID 和关键信息（让 AI 能调用 MCP 工具或理解场景）
 * - 不限定 AI 的能力范围
 */

/**
 * 获取当前页面的 AI 上下文
 * @param {Object} store - Vuex store 实例
 * @param {Object} routeParams - 路由参数
 * @returns {Object} { systemPrompt }
 */
export function getPageContext(store, routeParams = {}) {
    // 优先检测弹窗场景
    const taskId = store.state.taskId;
    if (taskId > 0) {
        return getSingleTaskContext(store, { taskId });
    }

    const dialogModalShow = store.state.dialogModalShow;
    const dialogId = store.state.dialogId;
    if (dialogModalShow && dialogId > 0) {
        return getSingleDialogContext(store, { dialogId });
    }

    const routeName = store.state.routeName;

    const contextMap = {
        // 主要管理页面
        'manage-dashboard': getDashboardContext,
        'manage-project': getProjectContext,
        'manage-messenger': getMessengerContext,
        'manage-calendar': getCalendarContext,
        'manage-file': getFileContext,
        // 独立页面
        'single-task': getSingleTaskContext,
        'single-task-content': getSingleTaskContext,
        'single-dialog': getSingleDialogContext,
        'single-file': getSingleFileContext,
        'single-file-task': getSingleFileTaskContext,
        'single-report-edit': getSingleReportEditContext,
        'single-report-detail': getSingleReportDetailContext,
    };

    const getContext = contextMap[routeName];
    if (getContext) {
        return getContext(store, routeParams);
    }

    return getDefaultContext();
}

/**
 * 仪表盘上下文
 */
function getDashboardContext(store) {
    const dashboardTask = store.getters.dashboardTask || {};
    const assistTask = store.getters.assistTask || [];

    const overdueCount = dashboardTask.overdue_count || 0;
    const todayCount = dashboardTask.today_count || 0;
    const todoCount = dashboardTask.todo_count || 0;
    const assistCount = assistTask.length || 0;

    const lines = ['用户正在查看工作仪表盘。'];

    if (overdueCount > 0 || todayCount > 0 || todoCount > 0 || assistCount > 0) {
        lines.push('', '任务概况：');
        if (overdueCount > 0) lines.push(`- 逾期任务：${overdueCount} 个`);
        if (todayCount > 0) lines.push(`- 今日到期：${todayCount} 个`);
        if (todoCount > 0) lines.push(`- 待办任务：${todoCount} 个`);
        if (assistCount > 0) lines.push(`- 协助任务：${assistCount} 个`);
    }

    return {
        systemPrompt: lines.join('\n'),
    };
}

/**
 * 项目详情上下文
 */
function getProjectContext(store) {
    const project = store.getters.projectData || {};
    const columns = store.state.cacheColumns || [];
    const tasks = store.state.cacheTasks || [];

    if (!project.id) {
        return {
            systemPrompt: '用户正在查看项目列表。',
        };
    }

    const lines = [
        '用户正在查看项目详情页面。',
        '',
        '当前项目：',
        `- project_id：${project.id}`,
    ];

    if (project.name) {
        lines.push(`- 名称：${project.name}`);
    }
    if (project.desc) {
        const desc = project.desc.length > 200 ? project.desc.substring(0, 200) + '...' : project.desc;
        lines.push(`- 描述：${desc}`);
    }

    // 任务统计
    const projectTasks = tasks.filter(t => t.project_id === project.id);
    if (projectTasks.length > 0) {
        const completedCount = projectTasks.filter(t => t.complete_at).length;
        const overdueCount = projectTasks.filter(t => !t.complete_at && t.end_at && new Date(t.end_at) < new Date()).length;

        lines.push('', '任务统计：');
        lines.push(`- 总任务：${projectTasks.length} 个`);
        lines.push(`- 已完成：${completedCount} 个`);
        if (overdueCount > 0) {
            lines.push(`- 已逾期：${overdueCount} 个`);
        }
    }

    // 看板列
    const projectColumns = columns.filter(c => c.project_id === project.id);
    if (projectColumns.length > 0) {
        const columnNames = projectColumns.map(c => c.name).join('、');
        lines.push('', `看板列：${columnNames}`);
    }

    return {
        systemPrompt: lines.join('\n'),
    };
}

/**
 * 消息对话上下文
 */
function getMessengerContext(store) {
    const dialogId = store.state.dialogId;
    const dialogs = store.state.cacheDialogs || [];
    const dialog = dialogs.find(d => d.id === dialogId);

    if (!dialog) {
        return {
            systemPrompt: '用户正在查看消息列表。',
        };
    }

    const dialogType = dialog.type === 'group' ? '群聊' : '私聊';
    const lines = [
        '用户正在使用消息功能。',
        '',
        '当前对话：',
        `- dialog_id：${dialog.id}`,
        `- 类型：${dialogType}`,
    ];

    if (dialog.name) {
        lines.push(`- 名称：${dialog.name}`);
    }

    return {
        systemPrompt: lines.join('\n'),
    };
}

/**
 * 日历上下文
 */
function getCalendarContext() {
    return {
        systemPrompt: '用户正在查看日历。',
    };
}

/**
 * 文件管理上下文
 */
function getFileContext() {
    return {
        systemPrompt: '用户正在查看文件管理页面。',
    };
}

/**
 * 单任务页面上下文
 */
function getSingleTaskContext(store, routeParams) {
    const taskId = routeParams.taskId;

    if (!taskId) {
        return {
            systemPrompt: '用户正在查看任务页面。',
        };
    }

    return {
        systemPrompt: [
            '用户正在查看任务详情页面。',
            '',
            '当前任务：',
            `- task_id：${taskId}`,
        ].join('\n'),
    };
}

/**
 * 单对话页面上下文
 */
function getSingleDialogContext(store, routeParams) {
    const dialogId = routeParams.dialogId;

    if (!dialogId) {
        return {
            systemPrompt: '用户正在查看对话页面。',
        };
    }

    return {
        systemPrompt: [
            '用户正在查看对话窗口。',
            '',
            '当前对话：',
            `- dialog_id：${dialogId}`,
        ].join('\n'),
    };
}

/**
 * 单文件页面上下文
 */
function getSingleFileContext(store, routeParams) {
    const fileId = routeParams.codeOrFileId;

    if (!fileId) {
        return {
            systemPrompt: '用户正在查看文件页面。',
        };
    }

    return {
        systemPrompt: [
            '用户正在查看文件。',
            '',
            '当前文件：',
            `- file_id：${fileId}`,
        ].join('\n'),
    };
}

/**
 * 任务附件文件页面上下文
 */
function getSingleFileTaskContext(store, routeParams) {
    const fileId = routeParams.fileId;

    if (!fileId) {
        return {
            systemPrompt: '用户正在查看文件页面。',
        };
    }

    return {
        systemPrompt: [
            '用户正在查看任务附件。',
            '',
            '当前文件：',
            `- file_id：${fileId}`,
        ].join('\n'),
    };
}

/**
 * 工作汇报编辑页面上下文
 */
function getSingleReportEditContext(store, routeParams) {
    const reportId = routeParams.reportEditId;

    if (!reportId) {
        return {
            systemPrompt: '用户正在编辑工作汇报。',
        };
    }

    return {
        systemPrompt: [
            '用户正在编辑工作汇报。',
            '',
            '当前汇报：',
            `- report_id：${reportId}`,
        ].join('\n'),
    };
}

/**
 * 工作汇报详情页面上下文
 */
function getSingleReportDetailContext(store, routeParams) {
    const reportId = routeParams.reportDetailId;

    if (!reportId) {
        return {
            systemPrompt: '用户正在查看工作汇报。',
        };
    }

    return {
        systemPrompt: [
            '用户正在查看工作汇报。',
            '',
            '当前汇报：',
            `- report_id：${reportId}`,
        ].join('\n'),
    };
}

/**
 * 默认上下文
 */
function getDefaultContext() {
    return {
        systemPrompt: '',
    };
}

/**
 * 获取当前场景的唯一标识
 * 用于判断打开 AI 助手时是否需要新建会话
 * 场景相同则恢复上次会话，场景不同则新建会话
 *
 * @param {Object} store - Vuex store 实例
 * @param {Object} routeParams - 路由参数
 * @returns {string} 场景标识，格式如 "routeName/entityType:entityId"
 */
export function getSceneKey(store, routeParams = {}) {
    // 优先检测弹窗场景
    const taskId = store.state.taskId;
    if (taskId > 0) {
        return `modal-task/task:${taskId}`;
    }

    const dialogModalShow = store.state.dialogModalShow;
    const dialogId = store.state.dialogId;
    if (dialogModalShow && dialogId > 0) {
        return `modal-dialog/dialog:${dialogId}`;
    }

    const routeName = store.state.routeName;
    const parts = [routeName || 'unknown'];

    switch (routeName) {
        case 'manage-project': {
            const project = store.getters.projectData;
            if (project?.id) {
                parts.push(`project:${project.id}`);
            }
            break;
        }
        case 'manage-messenger': {
            const dialogId = store.state.dialogId;
            if (dialogId) {
                parts.push(`dialog:${dialogId}`);
            }
            break;
        }
        case 'single-task':
        case 'single-task-content': {
            if (routeParams.taskId) {
                parts.push(`task:${routeParams.taskId}`);
            }
            break;
        }
        case 'single-dialog': {
            if (routeParams.dialogId) {
                parts.push(`dialog:${routeParams.dialogId}`);
            }
            break;
        }
        case 'single-file': {
            if (routeParams.codeOrFileId) {
                parts.push(`file:${routeParams.codeOrFileId}`);
            }
            break;
        }
        case 'single-file-task': {
            if (routeParams.fileId) {
                parts.push(`file:${routeParams.fileId}`);
            }
            break;
        }
        case 'single-report-edit': {
            if (routeParams.reportEditId) {
                parts.push(`report:${routeParams.reportEditId}`);
            }
            break;
        }
        case 'single-report-detail': {
            if (routeParams.reportDetailId) {
                parts.push(`report:${routeParams.reportDetailId}`);
            }
            break;
        }
    }

    return parts.join('/');
}
