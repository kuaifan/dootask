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
    // 重点/推进/优先级
    flag: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>',
    // 清单/行动项/纪要
    clipboard: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="16" y2="14"/><line x1="8" y1="18" x2="13" y2="18"/></svg>',
    // 链接/关联
    link: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 1 0-7l1-1a5 5 0 0 1 7 7l-1 1"/><path d="M14 11a5 5 0 0 1 0 7l-1 1a5 5 0 0 1-7-7l1-1"/></svg>',
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
 * 从数组中随机选择指定数量的元素
 * @param {Array} arr - 源数组
 * @param {number} count - 选择数量
 * @returns {Array} 随机选择的元素
 */
function getRandomItems(arr, count) {
    if (arr.length <= count) {
        return arr;
    }
    const shuffled = [...arr];
    // Fisher-Yates 洗牌算法（只洗前 count 个）
    for (let i = 0; i < count; i++) {
        const j = i + Math.floor(Math.random() * (shuffled.length - i));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }
    return shuffled.slice(0, count);
}

/**
 * 获取当前场景的快捷提示列表
 * @param {Object} store - Vuex store 实例
 * @param {Object} routeParams - 路由参数
 * @returns {Array} 快捷提示列表 [{ text, svg }]，随机显示 3-6 个
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

    // 随机选择 3-6 个提示词
    const displayCount = Math.floor(Math.random() * 4) + 3; // 3, 4, 5, 或 6
    const selectedPrompts = getRandomItems(rawPrompts, displayCount);

    // 转换文本为当前语言
    return selectedPrompts.map(item => ({
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
            text: { zh: `列出我的 ${overdueCount} 个逾期任务`, en: `List my ${overdueCount} overdue tasks` },
            svg: SVG_ICONS.alert,
        });
    }

    if (todayCount > 0) {
        prompts.push({
            text: { zh: '今天到期任务有哪些？', en: 'What tasks are due today?' },
            svg: SVG_ICONS.calendar,
        });
    }

    // 补充通用提示（项目管理视角：现状 -> 风险 -> 推进 -> 同步）
    prompts.push(
        {
            text: { zh: '按优先级排今天任务', en: 'Prioritize my tasks for today' },
            svg: SVG_ICONS.flag,
        },
        {
            text: { zh: '汇总逾期任务并给方案', en: 'Summarize overdue tasks with actions' },
            svg: SVG_ICONS.alert,
        },
        {
            text: { zh: '本周到期任务有哪些？', en: 'What tasks are due this week?' },
            svg: SVG_ICONS.list,
        },
        {
            text: { zh: '找出最近停滞的任务', en: 'Find recently stalled tasks' },
            svg: SVG_ICONS.alert,
        },
        {
            text: { zh: '哪些任务缺负责人或截止？', en: 'Which tasks lack owner or due date?' },
            svg: SVG_ICONS.edit,
        },
        {
            text: { zh: '我需要协助的任务清单', en: 'Tasks that need my assistance' },
            svg: SVG_ICONS.user,
        },
        {
            text: { zh: '生成今日工作同步文案', en: 'Draft today status update' },
            svg: SVG_ICONS.message,
        },
        {
            text: { zh: '生成本周周报草稿', en: 'Generate weekly report draft' },
            svg: SVG_ICONS.document,
        },
    );

    return prompts.slice(0, 10);
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
                text: { zh: '我参与的项目有哪些？', en: 'Which projects am I involved in?' },
                svg: SVG_ICONS.folder,
            },
            {
                text: { zh: '按关键词搜索项目', en: 'Search projects by keyword' },
                svg: SVG_ICONS.search,
            },
            {
                text: { zh: '哪些项目逾期最严重？', en: 'Which projects have the most overdue tasks?' },
                svg: SVG_ICONS.alert,
            },
            {
                text: { zh: '对比项目健康度', en: 'Compare project health' },
                svg: SVG_ICONS.chart,
            },
            {
                text: { zh: '生成项目概览简报', en: 'Generate a project overview brief' },
                svg: SVG_ICONS.document,
            },
            {
                text: { zh: '帮我创建一个新项目', en: 'Help me create a new project' },
                svg: SVG_ICONS.plus,
            },
        ];
    }

    // 项目详情页 - 提供具体操作
    return [
        {
            text: {
                zh: '项目未完成任务清单',
                en: 'List incomplete tasks in this project',
            },
            svg: SVG_ICONS.list,
        },
        {
            text: {
                zh: '项目逾期任务与原因概览',
                en: 'Overdue tasks & reasons overview',
            },
            svg: SVG_ICONS.alert,
        },
        {
            text: {
                zh: '查看项目看板列配置',
                en: 'View board columns configuration',
            },
            svg: SVG_ICONS.list,
        },
        {
            text: {
                zh: '成员负载与分配建议',
                en: 'Member workload & assignment suggestions',
            },
            svg: SVG_ICONS.chart,
        },
        {
            text: {
                zh: '创建任务并指派负责人',
                en: 'Create a task and assign an owner',
            },
            svg: SVG_ICONS.plus,
        },
        {
            text: {
                zh: '把需求拆成可执行任务',
                en: 'Break a requirement into tasks',
            },
            svg: SVG_ICONS.edit,
        },
        {
            text: {
                zh: '汇总项目风险与阻塞项',
                en: 'Summarize risks and blockers',
            },
            svg: SVG_ICONS.alert,
        },
        {
            text: {
                zh: '生成本周项目推进简报',
                en: 'Generate this week project update',
            },
            svg: SVG_ICONS.document,
        },
        {
            text: {
                zh: '搜索项目相关资料',
                en: 'Search project related docs',
            },
            svg: SVG_ICONS.search,
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

    // 从私聊列表中随机获取一个用户名（用于个性化提示）
    const userDialogs = dialogs.filter(d => d.type === 'user' && d.name && !d.bot);
    const userName = userDialogs.length > 0 ? userDialogs[Math.floor(Math.random() * userDialogs.length)].name : null;

    if (!dialog) {
        // 消息列表页
        return [
            {
                text: { zh: '按名称找对话', en: 'Find a chat by name' },
                svg: SVG_ICONS.search,
            },
            {
                text: { zh: '按名字/邮箱找人', en: 'Find a person by name/email' },
                svg: SVG_ICONS.user,
            },
            {
                text: userName
                    ? { zh: `给 ${userName} 发送一条消息`, en: `Send a message to ${userName}` }
                    : { zh: '给某人发送一条消息', en: 'Send a message to someone' },
                svg: SVG_ICONS.send,
            },
            {
                text: { zh: '搜索关键词消息', en: 'Search messages by keyword' },
                svg: SVG_ICONS.search,
            },
            {
                text: { zh: '生成礼貌催办话术', en: 'Draft a polite follow-up message' },
                svg: SVG_ICONS.edit,
            },
            {
                text: { zh: '生成进度同步文案', en: 'Draft a progress update message' },
                svg: SVG_ICONS.document,
            },
        ];
    }

    // 对话详情页
    return [
        {
            text: {
                zh: '总结近期对话要点与结论',
                en: 'Summarize recent chat highlights',
            },
            svg: SVG_ICONS.document,
        },
        {
            text: {
                zh: '提取对话待办并建任务',
                en: 'Extract to-dos and create tasks',
            },
            svg: SVG_ICONS.task,
        },
        {
            text: {
                zh: '列出对话里的文件与链接',
                en: 'List files and links in this chat',
            },
            svg: SVG_ICONS.link,
        },
        {
            text: {
                zh: '搜索对话关键词并摘要',
                en: 'Search chat keyword with context',
            },
            svg: SVG_ICONS.search,
        },
        {
            text: {
                zh: '给我三种推进回复版本',
                en: 'Give 3 reply options to move forward',
            },
            svg: SVG_ICONS.message,
        },
        {
            text: {
                zh: '生成会议纪要（可直接发）',
                en: 'Generate meeting notes to send',
            },
            svg: SVG_ICONS.clipboard,
        },
    ];
}

/**
 * 日历页提示 - 聚焦时间维度的任务查看
 */
function getCalendarPrompts() {
    return [
        {
            text: { zh: '今天到期任务清单', en: 'Tasks due today' },
            svg: SVG_ICONS.calendar,
        },
        {
            text: { zh: '本周到期任务清单', en: 'Tasks due this week' },
            svg: SVG_ICONS.list,
        },
        {
            text: { zh: '下周任务安排建议', en: 'Suggestions for next week plan' },
            svg: SVG_ICONS.clock,
        },
        {
            text: { zh: '截止日期冲突排查', en: 'Check due-date conflicts' },
            svg: SVG_ICONS.alert,
        },
        {
            text: { zh: '逾期任务怎么重排期', en: 'Reschedule overdue tasks' },
            svg: SVG_ICONS.calendar,
        },
        {
            text: { zh: '未来两周风险预警点', en: 'Risks in the next two weeks' },
            svg: SVG_ICONS.alert,
        },
        {
            text: { zh: '生成下周工作计划草案', en: 'Draft next week work plan' },
            svg: SVG_ICONS.document,
        },
    ];
}

/**
 * 文件页提示 - 聚焦文件查找
 */
function getFilePrompts(store) {
    // 从今日任务中随机获取一个任务名称（用于个性化提示）
    const dashboardTask = store.getters.dashboardTask || {};
    const todayTasks = (dashboardTask.today || []).filter(t => t.name);
    const taskName = todayTasks.length > 0 ? todayTasks[Math.floor(Math.random() * todayTasks.length)].name : null;

    return [
        {
            text: { zh: '搜索文件（名称/关键词）', en: 'Search files by name/keyword' },
            svg: SVG_ICONS.search,
        },
        {
            text: { zh: '查看共享文件列表', en: 'View shared files' },
            svg: SVG_ICONS.folder,
        },
        {
            text: taskName
                ? { zh: `查找「${taskName}」的附件`, en: `Find attachments of "${taskName}"` }
                : { zh: '查找某个任务的附件', en: 'Find attachments of a task' },
            svg: SVG_ICONS.task,
        },
        {
            text: { zh: '阅读文件并总结要点', en: 'Read a file and summarize' },
            svg: SVG_ICONS.document,
        },
        {
            text: { zh: '从文件提取行动项清单', en: 'Extract action items from files' },
            svg: SVG_ICONS.clipboard,
        },
        {
            text: { zh: '生成文件转发说明文案', en: 'Draft a file sharing note' },
            svg: SVG_ICONS.send,
        },
        {
            text: { zh: '搜索项目相关资料', en: 'Search project related docs' },
            svg: SVG_ICONS.search,
        },
    ];
}

/**
 * 任务详情提示 - 聚焦当前任务的操作
 */
function getSingleTaskPrompts() {
    return [
        {
            text: { zh: '补全任务信息与验收标准', en: 'Fill task details and acceptance criteria' },
            svg: SVG_ICONS.edit,
        },
        {
            text: { zh: '把任务拆成子任务清单', en: 'Break down into subtasks' },
            svg: SVG_ICONS.list,
        },
        {
            text: { zh: '立刻添加一个子任务', en: 'Add a subtask now' },
            svg: SVG_ICONS.plus,
        },
        {
            text: { zh: '调整负责人/协助人', en: 'Adjust owner/assignees' },
            svg: SVG_ICONS.user,
        },
        {
            text: { zh: '修改开始与截止时间', en: 'Change start and due dates' },
            svg: SVG_ICONS.clock,
        },
        {
            text: { zh: '把任务移动到看板列', en: 'Move task to a board column' },
            svg: SVG_ICONS.list,
        },
        {
            text: { zh: '将任务标记为完成', en: 'Mark this task as complete' },
            svg: SVG_ICONS.check,
        },
        {
            text: { zh: '生成任务进展同步文案', en: 'Draft a task progress update' },
            svg: SVG_ICONS.message,
        },
        {
            text: { zh: '查看这个任务的附件列表', en: 'View task attachments' },
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
            text: { zh: '总结对话重点与待办', en: 'Summarize highlights and to-dos' },
            svg: SVG_ICONS.document,
        },
        {
            text: { zh: '提取待办并创建任务', en: 'Extract to-dos and create tasks' },
            svg: SVG_ICONS.task,
        },
        {
            text: { zh: '列出对话中的文件', en: 'List files in this chat' },
            svg: SVG_ICONS.folder,
        },
        {
            text: { zh: '帮我写推进回复', en: 'Draft a reply to move forward' },
            svg: SVG_ICONS.message,
        },
        {
            text: { zh: '生成对话纪要摘要', en: 'Generate chat summary notes' },
            svg: SVG_ICONS.clipboard,
        },
        {
            text: { zh: '搜索对话关键词并结论', en: 'Search keyword and summarize findings' },
            svg: SVG_ICONS.search,
        },
    ];
}

/**
 * 文件预览提示
 */
function getSingleFilePrompts() {
    return [
        {
            text: { zh: '总结文件要点与结论', en: 'Summarize key points and conclusions' },
            svg: SVG_ICONS.document,
        },
        {
            text: { zh: '从文件提取任务清单', en: 'Extract tasks from this file' },
            svg: SVG_ICONS.task,
        },
        {
            text: { zh: '生成文件分享说明', en: 'Draft a file sharing note' },
            svg: SVG_ICONS.send,
        },
        {
            text: { zh: '搜索相似文件并对比', en: 'Find similar files and compare' },
            svg: SVG_ICONS.search,
        },
        {
            text: { zh: '查找关联的任务/项目', en: 'Find related tasks/projects' },
            svg: SVG_ICONS.list,
        },
        {
            text: { zh: '列出需要确认的问题', en: 'List questions to clarify' },
            svg: SVG_ICONS.alert,
        },
    ];
}

/**
 * 任务附件提示
 */
function getSingleFileTaskPrompts() {
    return [
        {
            text: { zh: '查看该任务全部附件', en: 'View all attachments' },
            svg: SVG_ICONS.folder,
        },
        {
            text: { zh: '总结附件要点与风险', en: 'Summarize attachment highlights and risks' },
            svg: SVG_ICONS.document,
        },
        {
            text: { zh: '从附件提取行动项', en: 'Extract action items from attachments' },
            svg: SVG_ICONS.clipboard,
        },
        {
            text: { zh: '用附件内容完善任务描述', en: 'Improve task description from attachments' },
            svg: SVG_ICONS.edit,
        },
        {
            text: { zh: '生成验收检查清单', en: 'Generate acceptance checklist' },
            svg: SVG_ICONS.check,
        },
        {
            text: { zh: '基于附件拆分子任务', en: 'Create subtasks based on attachments' },
            svg: SVG_ICONS.plus,
        },
    ];
}

/**
 * 汇报编辑提示 - 聚焦汇报生成
 */
function getSingleReportEditPrompts() {
    return [
        {
            text: { zh: '基于本周任务生成周报', en: 'Generate weekly report from tasks' },
            svg: SVG_ICONS.document,
        },
        {
            text: { zh: '基于今天任务生成日报', en: 'Generate daily report from tasks' },
            svg: SVG_ICONS.calendar,
        },
        {
            text: { zh: '查看我上周的汇报', en: 'View my last week\'s report' },
            svg: SVG_ICONS.search,
        },
        {
            text: { zh: '汇总本周已完成事项', en: 'Summarize completed items this week' },
            svg: SVG_ICONS.check,
        },
        {
            text: { zh: '汇总本周未完成与原因', en: 'Summarize unfinished items and reasons' },
            svg: SVG_ICONS.alert,
        },
        {
            text: { zh: '补充风险与需要支持', en: 'Add risks and needed support' },
            svg: SVG_ICONS.alert,
        },
        {
            text: { zh: '生成下周计划与重点', en: 'Generate next week plan and focus' },
            svg: SVG_ICONS.list,
        },
        {
            text: { zh: '润色为更专业表达', en: 'Polish for more professional tone' },
            svg: SVG_ICONS.edit,
        },
        {
            text: { zh: '提交汇报给指定同事', en: 'Submit report to specified people' },
            svg: SVG_ICONS.send,
        },
    ];
}

/**
 * 汇报详情提示
 */
function getSingleReportDetailPrompts() {
    return [
        {
            text: { zh: '总结这份汇报关键点', en: 'Summarize key points of this report' },
            svg: SVG_ICONS.document,
        },
        {
            text: { zh: '提取汇报里的待办事项', en: 'Extract action items from this report' },
            svg: SVG_ICONS.clipboard,
        },
        {
            text: { zh: '找出汇报提到的任务', en: 'Find tasks mentioned in this report' },
            svg: SVG_ICONS.task,
        },
        {
            text: { zh: '生成一条回复评论文案', en: 'Draft a reply/comment' },
            svg: SVG_ICONS.message,
        },
        {
            text: { zh: '标记为已读', en: 'Mark as read' },
            svg: SVG_ICONS.check,
        },
        {
            text: { zh: '标记为未读', en: 'Mark as unread' },
            svg: SVG_ICONS.alert,
        },
        {
            text: { zh: '查看TA的其他汇报', en: 'View other reports from this person' },
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
            text: { zh: '我有哪些未完成任务？', en: 'What tasks do I have pending?' },
            svg: SVG_ICONS.task,
        },
        {
            text: { zh: '全局智能搜索（任务/项目/文件）', en: 'Global smart search (tasks/projects/files)' },
            svg: SVG_ICONS.search,
        },
        {
            text: { zh: '帮我创建一个任务', en: 'Help me create a task' },
            svg: SVG_ICONS.plus,
        },
        {
            text: { zh: '查看我的项目列表', en: 'View my projects' },
            svg: SVG_ICONS.folder,
        },
        {
            text: { zh: '帮我写一份工作汇报', en: 'Help me write a work report' },
            svg: SVG_ICONS.document,
        },
        {
            text: { zh: '给某人发消息并说明背景', en: 'Send a message with context' },
            svg: SVG_ICONS.send,
        },
    ];
}
