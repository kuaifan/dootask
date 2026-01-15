/**
 * AI 助手欢迎界面快捷提示配置
 *
 * 根据不同页面场景显示相关的快捷提示，帮助用户快速开始对话
 * 提示内容基于 DooTask MCP 工具的实际能力设计
 */

import {languageName} from "../../language";

// SVG 图标定义
const SVG_ICONS = {
    // 任务/待办
    task: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
    // 列表/概览
    list: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
    // 搜索
    search: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>',
    // 日历/时间
    calendar: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
    // 文档/报告
    document: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
    // 添加/新建
    plus: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
    // 消息/对话
    message: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    // 分析/图表
    chart: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
    // 警告/逾期
    alert: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    // 文件夹
    folder: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
    // 编辑/优化
    edit: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>',
    // 用户/团队
    user: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    // 发送
    send: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>',
    // 时钟/截止
    clock: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    // 完成/勾选
    check: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
};

/**
 * 根据当前语言获取文本
 */
function getText(textObj) {
    if (typeof textObj === 'string') {
        return textObj;
    }
    const isZh = languageName && languageName.includes('zh');
    return isZh ? textObj.zh : textObj.en;
}

/**
 * 获取当前场景的快捷提示列表
 * @param {Object} store - Vuex store 实例
 * @param {Object} routeParams - 路由参数
 * @returns {Array} 快捷提示列表 [{ text, svg }]
 */
export function getWelcomePrompts(store, routeParams = {}) {
    const routeName = store.state.routeName;

    const promptsMap = {
        // 主要管理页面
        'manage-dashboard': getDashboardPrompts,
        'manage-project': getProjectPrompts,
        'manage-messenger': getMessengerPrompts,
        'manage-calendar': getCalendarPrompts,
        'manage-file': getFilePrompts,
        // 独立页面
        'single-task': getSingleTaskPrompts,
        'single-task-content': getSingleTaskPrompts,
        'single-dialog': getSingleDialogPrompts,
        'single-file': getSingleFilePrompts,
        'single-file-task': getSingleFileTaskPrompts,
        'single-report-edit': getSingleReportEditPrompts,
        'single-report-detail': getSingleReportDetailPrompts,
    };

    const getPrompts = promptsMap[routeName];
    const rawPrompts = getPrompts ? getPrompts(store, routeParams) : getDefaultPrompts();

    // 转换文本为当前语言
    return rawPrompts.map(item => ({
        text: getText(item.text),
        svg: item.svg,
    }));
}

/**
 * 仪表盘提示 - 聚焦任务管理和工作安排
 */
function getDashboardPrompts(store) {
    const dashboardTask = store.getters.dashboardTask || {};
    const prompts = [];

    const overdueCount = dashboardTask.overdue_count || 0;
    const todayCount = dashboardTask.today_count || 0;

    // 根据实际数据动态调整提示
    if (overdueCount > 0) {
        prompts.push({
            text: {
                zh: `列出我的 ${overdueCount} 个逾期任务`,
                en: `List my ${overdueCount} overdue tasks`,
            },
            svg: SVG_ICONS.alert,
        });
    }

    if (todayCount > 0) {
        prompts.push({
            text: {
                zh: `今天要完成哪些任务？`,
                en: `What tasks are due today?`,
            },
            svg: SVG_ICONS.calendar,
        });
    }

    // 补充通用提示
    prompts.push(
        {
            text: { zh: '我本周有哪些任务？', en: 'What are my tasks this week?' },
            svg: SVG_ICONS.list,
        },
        {
            text: { zh: '哪些任务需要我协助？', en: 'Which tasks need my assistance?' },
            svg: SVG_ICONS.user,
        },
    );

    return prompts.slice(0, 4);
}

/**
 * 项目页提示 - 聚焦项目任务管理
 */
function getProjectPrompts(store) {
    const project = store.getters.projectData || {};

    if (!project.id) {
        // 项目列表页
        return [
            {
                text: { zh: '我参与了哪些项目？', en: 'Which projects am I involved in?' },
                svg: SVG_ICONS.folder,
            },
            {
                text: { zh: '哪个项目有逾期任务？', en: 'Which project has overdue tasks?' },
                svg: SVG_ICONS.alert,
            },
        ];
    }

    // 项目详情页 - 提供具体操作
    const projectName = project.name || '';
    return [
        {
            text: {
                zh: '这个项目还有多少未完成的任务？',
                en: 'How many incomplete tasks in this project?',
            },
            svg: SVG_ICONS.list,
        },
        {
            text: {
                zh: '帮我在这个项目创建一个任务',
                en: 'Help me create a task in this project',
            },
            svg: SVG_ICONS.plus,
        },
        {
            text: {
                zh: '这个项目有哪些逾期任务？',
                en: 'What tasks are overdue in this project?',
            },
            svg: SVG_ICONS.alert,
        },
        {
            text: {
                zh: '查看项目成员的任务分配',
                en: 'View task assignments by member',
            },
            svg: SVG_ICONS.user,
        },
    ];
}

/**
 * 消息页提示 - 聚焦沟通和消息查找
 */
function getMessengerPrompts(store) {
    const dialogId = store.state.dialogId;
    const dialogs = store.state.cacheDialogs || [];
    const dialog = dialogs.find(d => d.id === dialogId);

    if (!dialog) {
        // 消息列表页
        return [
            {
                text: { zh: '给某人发送一条消息', en: 'Send a message to someone' },
                svg: SVG_ICONS.send,
            },
            {
                text: { zh: '搜索包含关键词的聊天', en: 'Search chats containing keyword' },
                svg: SVG_ICONS.search,
            },
        ];
    }

    // 对话详情页
    const dialogName = dialog.name || '';
    return [
        {
            text: {
                zh: '查看这个对话最近的消息',
                en: 'View recent messages in this chat',
            },
            svg: SVG_ICONS.message,
        },
        {
            text: {
                zh: '搜索对话中的文件',
                en: 'Search files in this chat',
            },
            svg: SVG_ICONS.search,
        },
        {
            text: {
                zh: '给对方发一条消息',
                en: 'Send a message',
            },
            svg: SVG_ICONS.send,
        },
    ];
}

/**
 * 日历页提示 - 聚焦时间维度的任务查看
 */
function getCalendarPrompts() {
    return [
        {
            text: { zh: '今天有哪些任务到期？', en: 'What tasks are due today?' },
            svg: SVG_ICONS.calendar,
        },
        {
            text: { zh: '本周有哪些任务要完成？', en: 'What tasks are due this week?' },
            svg: SVG_ICONS.list,
        },
        {
            text: { zh: '下周的任务安排', en: 'Tasks scheduled for next week' },
            svg: SVG_ICONS.clock,
        },
    ];
}

/**
 * 文件页提示 - 聚焦文件查找
 */
function getFilePrompts() {
    return [
        {
            text: { zh: '搜索文件名包含...', en: 'Search files named...' },
            svg: SVG_ICONS.search,
        },
        {
            text: { zh: '查看我最近的文件', en: 'View my recent files' },
            svg: SVG_ICONS.folder,
        },
    ];
}

/**
 * 任务详情提示 - 聚焦当前任务的操作
 */
function getSingleTaskPrompts() {
    return [
        {
            text: { zh: '帮我添加一个子任务', en: 'Help me add a subtask' },
            svg: SVG_ICONS.plus,
        },
        {
            text: { zh: '修改这个任务的截止时间', en: 'Change the due date of this task' },
            svg: SVG_ICONS.clock,
        },
        {
            text: { zh: '将这个任务标记为完成', en: 'Mark this task as complete' },
            svg: SVG_ICONS.check,
        },
        {
            text: { zh: '查看这个任务的附件', en: 'View attachments of this task' },
            svg: SVG_ICONS.folder,
        },
    ];
}

/**
 * 对话页提示
 */
function getSingleDialogPrompts() {
    return [
        {
            text: { zh: '查看最近的消息记录', en: 'View recent messages' },
            svg: SVG_ICONS.message,
        },
        {
            text: { zh: '发送一条消息', en: 'Send a message' },
            svg: SVG_ICONS.send,
        },
    ];
}

/**
 * 文件预览提示
 */
function getSingleFilePrompts() {
    return [
        {
            text: { zh: '查看这个文件的详情', en: 'View file details' },
            svg: SVG_ICONS.document,
        },
        {
            text: { zh: '搜索类似的文件', en: 'Search similar files' },
            svg: SVG_ICONS.search,
        },
    ];
}

/**
 * 任务附件提示
 */
function getSingleFileTaskPrompts() {
    return [
        {
            text: { zh: '查看这个任务的所有附件', en: 'View all attachments of this task' },
            svg: SVG_ICONS.folder,
        },
        {
            text: { zh: '查看任务详情', en: 'View task details' },
            svg: SVG_ICONS.task,
        },
    ];
}

/**
 * 汇报编辑提示 - 聚焦汇报生成
 */
function getSingleReportEditPrompts() {
    return [
        {
            text: { zh: '根据本周任务生成周报', en: 'Generate weekly report from tasks' },
            svg: SVG_ICONS.document,
        },
        {
            text: { zh: '根据今天任务生成日报', en: 'Generate daily report from tasks' },
            svg: SVG_ICONS.calendar,
        },
        {
            text: { zh: '查看我上周的汇报', en: 'View my last week\'s report' },
            svg: SVG_ICONS.search,
        },
    ];
}

/**
 * 汇报详情提示
 */
function getSingleReportDetailPrompts() {
    return [
        {
            text: { zh: '标记为已读', en: 'Mark as read' },
            svg: SVG_ICONS.check,
        },
        {
            text: { zh: '查看这个人的其他汇报', en: 'View other reports from this person' },
            svg: SVG_ICONS.list,
        },
    ];
}

/**
 * 默认提示 - 通用场景
 */
function getDefaultPrompts() {
    return [
        {
            text: { zh: '我有哪些未完成的任务？', en: 'What tasks do I have pending?' },
            svg: SVG_ICONS.task,
        },
        {
            text: { zh: '搜索任务或项目', en: 'Search tasks or projects' },
            svg: SVG_ICONS.search,
        },
        {
            text: { zh: '帮我写一份工作汇报', en: 'Help me write a work report' },
            svg: SVG_ICONS.document,
        },
    ];
}
