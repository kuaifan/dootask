/**
 * 团队概览 Mock 数据（仅接口对接前使用，确认交互后整体删除本文件）
 *
 * 结构与规划中的接口返回保持一致，便于无缝替换：
 * - fetchTeamStats()  → api/dashboard/team/stats
 * - fetchTeamTasks()  → api/dashboard/team/tasks?type=xxx&page=N（20条/页）
 *
 * 口径说明（与后端实现约定一致）：
 * - members[].segments 按任务表 flow_item_name 的状态前缀（start/progress/test）聚合，
 *   已超期的任务不论状态归入 overdue 段
 * - priority 按系统优先级设置动态分档，未设置优先级的任务归入「未设置」
 */

const PAGE_SIZE = 20;

// 成员池（nickname/color 用于 Mock 字母头像，真实接口返回 userid 后换 UserAvatar）
const MEMBERS = [
    {userid: 0, nickname: "林晓雨", color: "#3a7bd5", total: 14, overdue: 2, segments: {progress: 7, start: 4, test: 1}},
    {userid: 0, nickname: "王志强", color: "#8a63d2", total: 13, overdue: 3, segments: {progress: 5, start: 4, test: 1}},
    {userid: 0, nickname: "陈嘉明", color: "#1e9e55", total: 11, overdue: 3, segments: {progress: 4, start: 3, test: 1}},
    {userid: 0, nickname: "赵倩", color: "#d9822b", total: 9, overdue: 0, segments: {progress: 5, start: 4, test: 0}},
    {userid: 0, nickname: "孙浩然", color: "#5f9ea0", total: 7, overdue: 2, segments: {progress: 3, start: 2, test: 0}},
    {userid: 0, nickname: "李雪", color: "#c2554f", total: 6, overdue: 1, segments: {progress: 2, start: 3, test: 0}},
    {userid: 0, nickname: "周明", color: "#4f7a5b", total: 5, overdue: 0, segments: {progress: 2, start: 3, test: 0}},
    {userid: 0, nickname: "吴婷", color: "#9a6ec2", total: 4, overdue: 1, segments: {progress: 1, start: 2, test: 0}},
    {userid: 0, nickname: "郑凯", color: "#3f8fa8", total: 3, overdue: 0, segments: {progress: 1, start: 2, test: 0}},
    {userid: 0, nickname: "冯丽", color: "#b8863d", total: 3, overdue: 0, segments: {progress: 2, start: 1, test: 0}},
    {userid: 0, nickname: "韩磊", color: "#6b7ac2", total: 2, overdue: 0, segments: {progress: 1, start: 1, test: 0}},
    {userid: 0, nickname: "杨帆", color: "#52a06e", total: 1, overdue: 0, segments: {progress: 0, start: 1, test: 0}},
];

const PROJECTS = ["DooTask v2", "点餐系统", "TTPOS-MENU", "财务管理", "项目收支"];

const PRIORITIES = [
    {level: 1, name: "紧急", color: "#d94f46"},
    {level: 2, name: "高", color: "#d9822b"},
    {level: 3, name: "中", color: "#3a7bd5"},
    {level: 4, name: "低", color: "#9aa096"},
];

const FLOWS = [
    {status: "start", name: "待处理"},
    {status: "progress", name: "进行中"},
    {status: "test", name: "审核中"},
];

const TASK_NAMES = [
    "建议放开部门群组的自定义头像权限", "【收款】佳博 WiFi 打印机联调", "点餐系统会员余额对账异常排查",
    "电子菜单需开通 Stripe 账户的申请", "公开分享链接的选项没有做初始化记录", "建议增加任务外链访问功能",
    "客户端登录页视觉走查", "周报数据汇总脚本修复", "移动端消息推送延迟优化方案评估",
    "建议将任务模板和任务描述的内容编辑框保持一致", "仪表盘统计口径梳理与确认", "客户反馈工单归类规则更新",
    "灰度发布检查清单补充", "新版工作流状态配置文档评审", "财务管理报表导出字段确认",
    "点餐系统打印模板多语言适配", "API 文档站点信息架构调整", "财务管理季度结算流程自动化",
    "项目收支月度盘点模板整理", "移动端首页白屏问题排查", "消息中心已读状态同步异常",
    "新员工入职引导流程梳理", "数据备份策略评审与演练", "第三方登录接口升级适配",
    "文件预览服务内存占用优化", "会议室预定模块需求评审", "客户端自动更新失败重试机制",
    "通知推送模板文案统一", "任务导出 Excel 字段缺失修复", "项目归档流程权限确认",
    "服务端日志切割脚本调整", "看板拖拽卡顿性能分析", "多语言翻译缺失项补齐",
    "接口限流阈值评估与调整", "运营后台数据大盘需求确认", "旧版附件迁移方案评审",
    "扫码登录二维码过期时间调整", "子任务批量操作交互优化", "网盘分享链接权限梳理",
    "审批流转节点通知缺失排查", "版本发布公告文案准备", "工时统计报表口径确认",
];

// —— 以下为确定性生成逻辑（不用随机数，保证每次渲染一致）——
// 时间一律基于 $A.daytz()（服务器时区），与真实接口返回口径一致

function fmt(day) {
    return day.format("YYYY-MM-DD HH:mm:00");
}

function dayOffset(days, hour) {
    return $A.daytz().add(days, "day").hour(hour).minute(0).second(0);
}

/**
 * 生成未完成任务池：84 条 = 成员 78 条 + 未分配 6 条
 * 超期 12、3 日内到期 18、紧急/高 29、未分配 6，与 stats 数字咬合
 */
function buildPool() {
    const pool = [];
    const owners = [];
    MEMBERS.forEach(member => {
        for (let i = 0; i < member.total; i++) {
            owners.push({member, isOverdue: i < member.overdue});
        }
    });
    for (let i = 0; i < 6; i++) {
        owners.push({member: null, isOverdue: false});
    }
    // 优先级配额：紧急 8、高 21、中 31、低 16、未设置 8（合计 84，紧急+高=29）
    // 用互质数打散分配顺序，避免同一成员的任务优先级扎堆
    const priorityRank = owners.map((_, i) => i).sort((a, b) => ((a * 53) % owners.length) - ((b * 53) % owners.length));
    const priorityMap = {};
    priorityRank.forEach((ownerIndex, rank) => {
        if (rank < 8) {
            priorityMap[ownerIndex] = PRIORITIES[0];
        } else if (rank < 29) {
            priorityMap[ownerIndex] = PRIORITIES[1];
        } else if (rank < 60) {
            priorityMap[ownerIndex] = PRIORITIES[2];
        } else if (rank < 76) {
            priorityMap[ownerIndex] = PRIORITIES[3];
        } else {
            priorityMap[ownerIndex] = null;
        }
    });
    // 非超期任务里：18 条 3 日内到期（时间保证在未来）、30 条较远截止、其余无截止
    const nonOverdue = owners.map((item, i) => item.isOverdue ? -1 : i).filter(i => i >= 0);
    const soonRank = [...nonOverdue].sort((a, b) => ((a * 37) % owners.length) - ((b * 37) % owners.length));
    const soonSet = new Set(soonRank.slice(0, 18));
    const farSet = new Set(soonRank.slice(18, 48));
    owners.forEach((item, index) => {
        const priority = priorityMap[index];
        //
        let endAt = null;
        if (item.isOverdue) {
            endAt = dayOffset(-((index % 6) + 1), 10 + index % 8);
        } else if (soonSet.has(index)) {
            // 现在起 2 小时 ~ 2.5 天内，保证不会因当天时间已过而变成逾期
            endAt = $A.daytz().add(2 + (index % 3) * 20, "hour").minute(0).second(0);
        } else if (farSet.has(index)) {
            endAt = dayOffset(4 + index % 20, 9 + index % 9);
        }
        const flow = FLOWS[index % FLOWS.length];
        const name = TASK_NAMES[index % TASK_NAMES.length] + (index >= TASK_NAMES.length * 2 ? "（二期）" : index >= TASK_NAMES.length ? "（跟进）" : "");
        pool.push({
            id: 900000 + index,
            name,
            project_name: PROJECTS[index % PROJECTS.length],
            owner: item.member ? {userid: item.member.userid, nickname: item.member.nickname, color: item.member.color} : null,
            p_level: priority ? priority.level : 0,
            p_name: priority ? priority.name : "",
            p_color: priority ? priority.color : "",
            flow_item_status: flow.status,
            flow_item_name: flow.name,
            end_at: endAt ? fmt(endAt) : "",
        });
    });
    return pool;
}

let POOL = null;

function getPool() {
    if (POOL === null) {
        POOL = buildPool();
    }
    return POOL;
}

function isOverdue(task) {
    return task.end_at && $A.dayjs(task.end_at) <= $A.daytz();
}

function isSoon(task) {
    if (!task.end_at || isOverdue(task)) {
        return false;
    }
    return $A.dayjs(task.end_at) <= $A.daytz().add(3, "day").endOf("day");
}

function filterPool({type, member, level}) {
    let list = getPool();
    if (member) {
        list = list.filter(task => task.owner && task.owner.nickname === member);
    } else if (level > 0) {
        list = list.filter(task => task.p_level === level);
    } else if (level === -1) {
        list = list.filter(task => task.p_level === 0);
    } else {
        switch (type) {
            case "overdue":
                list = list.filter(task => isOverdue(task));
                break;
            case "soon":
                list = list.filter(task => isSoon(task));
                break;
            case "hi":
                list = list.filter(task => task.p_level > 0 && task.p_level <= 2);
                break;
            case "noowner":
                list = list.filter(task => !task.owner);
                break;
        }
    }
    // 排序：超期最久优先，其次截止时间近的优先，无截止最后
    return list.slice().sort((a, b) => {
        if (!a.end_at && !b.end_at) return 0;
        if (!a.end_at) return 1;
        if (!b.end_at) return -1;
        return a.end_at.localeCompare(b.end_at);
    });
}

/**
 * 团队统计（对应 api/dashboard/team/stats）
 */
export function fetchTeamStats() {
    return new Promise(resolve => {
        setTimeout(() => {
            resolve({
                member_count: MEMBERS.length,
                blocks: {
                    uncompleted: 84,
                    overdue: 12,
                    overdue_owner_count: 5,
                    due_soon: 18,
                    week_completed: 27,
                    last_week_completed: 21,
                    no_owner: 6,
                },
                priority: [
                    {level: 1, name: "紧急", color: "#d94f46", num: 8},
                    {level: 2, name: "高", color: "#d9822b", num: 21},
                    {level: 3, name: "中", color: "#3a7bd5", num: 31},
                    {level: 4, name: "低", color: "#9aa096", num: 16},
                    {level: -1, name: "", color: "#c5c8ce", num: 8},
                ],
                members: MEMBERS,
            });
        }, 300);
    });
}

/**
 * 团队任务列表（对应 api/dashboard/team/tasks）
 * @param {Object} params {type, page, member, level}
 */
export function fetchTeamTasks(params) {
    return new Promise(resolve => {
        setTimeout(() => {
            const page = Math.max(1, params.page || 1);
            const list = filterPool(params);
            resolve({
                page,
                total: list.length,
                list: list.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE),
                hasMore: page * PAGE_SIZE < list.length,
            });
        }, 300);
    });
}
