/**
 * AI 助手页面上下文配置
 *
 * 根据当前路由和应用状态，为 AI 助手提供针对性的系统提示词
 * 原则：诚实描述 AI 能力边界，不承诺做不到的事情
 */

/**
 * 获取当前页面的 AI 上下文
 * @param {Object} store - Vuex store 实例
 * @returns {Object} { systemPrompt, title }
 */
export function getPageContext(store) {
    const routeName = store.state.routeName;

    const contextMap = {
        'manage-dashboard': getDashboardContext,
        'manage-project': getProjectContext,
        'manage-messenger': getMessengerContext,
        'manage-calendar': getCalendarContext,
        'manage-file': getFileContext,
    };

    const getContext = contextMap[routeName];
    if (getContext) {
        return getContext(store);
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

    const lines = [
        '你是一个工作效率助手。用户正在查看仪表盘。',
        '',
        '当前任务概况：',
    ];

    if (overdueCount > 0 || todayCount > 0 || todoCount > 0 || assistCount > 0) {
        if (overdueCount > 0) lines.push(`- 逾期任务：${overdueCount} 个`);
        if (todayCount > 0) lines.push(`- 今日到期：${todayCount} 个`);
        if (todoCount > 0) lines.push(`- 待办任务：${todoCount} 个`);
        if (assistCount > 0) lines.push(`- 协助任务：${assistCount} 个`);
    } else {
        lines.push('- 暂无待办任务');
    }

    lines.push(
        '',
        '你可以帮助用户：',
        '- 回答任务管理和时间规划相关问题',
        '- 根据用户描述的任务情况给出优先级建议',
        '- 协助用户整理和规划工作思路',
    );

    return {
        title: '工作助手',
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

    const lines = [
        '你是一个项目管理助手。用户正在查看项目详情页面。',
    ];

    if (project.id) {
        // 项目基本信息
        lines.push('', '当前项目：');
        lines.push(`- project_id：${project.id}`);
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
    }

    lines.push(
        '',
        '你可以帮助用户：',
        '- 协助拆解用户描述的需求为具体任务',
        '- 回答项目管理方法和最佳实践问题',
        '- 根据用户提供的信息给出排期建议',
    );

    return {
        title: '项目助手',
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

    const lines = [
        '你是一个沟通协作助手。用户正在使用消息功能。',
    ];

    if (dialog) {
        const dialogType = dialog.type === 'group' ? '群聊' : '私聊';
        lines.push('', '当前对话：');
        lines.push(`- dialog_id：${dialog.id}`);
        lines.push(`- 类型：${dialogType}`);
        if (dialog.name) {
            lines.push(`- 名称：${dialog.name}`);
        }
    }

    lines.push(
        '',
        '你可以帮助用户：',
        '- 润色和优化用户输入的消息内容',
        '- 根据用户描述的场景生成得体的回复',
        '- 翻译消息内容',
        '',
        '请保持回复简洁、专业、得体。',
    );

    return {
        title: '沟通助手',
        systemPrompt: lines.join('\n'),
    };
}

/**
 * 日历上下文
 */
function getCalendarContext() {
    const lines = [
        '你是一个时间管理助手。用户正在查看日历。',
        '',
        '你可以帮助用户：',
        '- 回答时间管理和日程规划相关问题',
        '- 根据用户描述的安排给出优化建议',
        '- 协助用户合理安排会议和任务时间',
    ];

    return {
        title: '日程助手',
        systemPrompt: lines.join('\n'),
    };
}

/**
 * 文件管理上下文
 */
function getFileContext() {
    const lines = [
        '你是一个文件管理助手。用户正在查看文件管理页面。',
        '',
        '你可以帮助用户：',
        '- 回答文件整理和分类相关问题',
        '- 根据用户描述建议文件命名和组织方式',
    ];

    return {
        title: '文件助手',
        systemPrompt: lines.join('\n'),
    };
}

/**
 * 默认上下文（通用）
 */
function getDefaultContext() {
    const lines = [
        '你是 DooTask 的 AI 助手。',
        '',
        '你可以帮助用户：',
        '- 回答任务管理和项目协作相关问题',
        '- 提供工作效率和方法论建议',
        '- 协助处理各种工作事务',
    ];

    return {
        title: 'AI 助手',
        systemPrompt: lines.join('\n'),
    };
}
