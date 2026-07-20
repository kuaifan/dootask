<template>
    <div class="page-dashboard">
        <PageTitle :title="$L('仪表盘')"/>
        <Alert v-if="warningMsg" class="dashboard-warning" type="warning" show-icon>
            <span @click="goForward({name: 'manage-setting-license'})">{{warningMsg}}</span>
        </Alert>
        <Scrollbar class="dashboard-scroller">
            <div class="dashboard-body">
                <!--头部-->
                <div class="dashboard-header">
                    <div class="header-left">
                        <template v-if="currentView === 'my'">
                            <h2 class="header-hello">{{dashboardHello}}</h2>
                            <div class="header-sub">
                                <span>{{todayText}} · {{mySummary}}</span>
                                <transition name="dashboard-load">
                                    <div v-if="loadDashboardTasks" class="dashboard-load"><Loading/></div>
                                </transition>
                            </div>
                        </template>
                        <template v-else>
                            <h2 class="header-hello">{{$L('部门任务总览')}}</h2>
                            <div class="header-sub">
                                <span>
                                    {{todayText}}<template v-if="teamSummary"> · {{teamSummary}}</template>
                                <EPopover
                                    v-model="teamCachePopoverShow"
                                    placement="bottom-start"
                                        popper-class="dashboard-cache-popper"
                                        trigger="click">
                                        <div class="dashboard-cache-popover">
                                            <strong>{{$L('数据更新说明')}}</strong>
                                            <p>{{$L('页面统计数据在 (*) 秒内复用，重点关注任务列表除外。', 60)}}</p>
                                            <span>{{$L('上次更新：(*)', teamStatsUpdatedTime)}}</span>
                                            <Button
                                                type="primary"
                                                size="small"
                                                :loading="teamStatsLoading"
                                                @click="refreshTeamDashboard">{{$L('立即刷新')}}</Button>
                                        </div>
                                        <Icon slot="reference" class="team-cache-help" type="ios-information-circle-outline"/>
                                    </EPopover>
                                </span>
                            </div>
                        </template>
                        <div v-if="systemConfig.timezoneDifference" class="header-servertime">
                            <span>{{$L('服务器时间')}}:</span>
                            <span>{{$A.daytz().format('YYYY-MM-DD HH:mm:ss')}}</span>
                        </div>
                    </div>
                    <div class="header-right">
                        <div class="header-search" :class="{'min-search': windowPortrait}" @click="openSearch">
                            <Icon type="ios-search"/>
                            <span>{{$L('搜索')}} ({{mateName}}+F)</span>
                        </div>
                        <div v-if="currentView === 'my'" class="header-seg">
                            <span :class="{'seg-active': layout === 'list'}" @click="setLayout('list')">{{$L('列表')}}</span>
                            <span :class="{'seg-active': layout === 'quad'}" @click="setLayout('quad')">{{$L('四象限')}}</span>
                        </div>
                        <div v-else class="header-dept" @click="deptViewShow = true">
                            <Icon type="md-people"/>
                            <span>{{deptLabel}}</span>
                            <em v-if="teamStats.member_count" class="dept-badge">{{teamStats.member_count}}</em>
                        </div>
                        <div v-if="teamEnabled" class="header-seg">
                            <span :class="{'seg-active': currentView === 'my'}" @click="setView('my')">{{$L('个人视角')}}</span>
                            <span :class="{'seg-active': currentView === 'team'}" @click="setView('team')">{{$L('部门负责人')}}</span>
                        </div>
                    </div>
                </div>
                <!--个人视角-->
                <template v-if="currentView === 'my'">
                    <!--列表布局-->
                    <template v-if="layout === 'list'">
                        <ul class="dashboard-cards personal-panel">
                            <li :class="{'card-off': dashboardTask.overdue_count === 0}" @click="scrollTo('overdue')">
                                <div class="card-label">{{getTitle('overdue')}}</div>
                                <div class="card-data">
                                    <span class="card-num num-red">{{dashboardTask.overdue_count}}</span>
                                    <span class="card-sub">{{$L('[task_unit].项')}}<template v-if="overdueMaxDays > 0"> · {{$L('最久 (*) 天', overdueMaxDays)}}</template></span>
                                </div>
                            </li>
                            <li :class="{'card-off': dashboardTask.today_count === 0}" @click="scrollTo('today')">
                                <div class="card-label">{{getTitle('today')}}</div>
                                <div class="card-data">
                                    <span class="card-num num-orange">{{dashboardTask.today_count}}</span>
                                    <span class="card-sub">{{$L('[task_unit].项')}}<template v-if="todayNearest"> · {{$L('最近')}} {{todayNearest}}</template></span>
                                </div>
                            </li>
                            <li :class="{'card-off': dashboardTask.todo_count === 0}" @click="scrollTo('todo')">
                                <div class="card-label">{{getTitle('todo')}}</div>
                                <div class="card-data">
                                    <span class="card-num">{{dashboardTask.todo_count}}</span>
                                    <span class="card-sub">{{$L('[task_unit].项')}}</span>
                                </div>
                                <div v-if="upcomingTask.count > 0" class="duo-upcoming" @click.stop="scrollTo('upcoming')">
                                    <div class="side-num">{{upcomingTask.count}}</div>
                                    <div class="side-label">{{$L('待开始')}}</div>
                                </div>
                            </li>
                            <li :class="{'card-off': assistTask.length === 0}" @click="scrollTo('assist')">
                                <div class="card-label">{{getTitle('assist')}}</div>
                                <div class="card-data">
                                    <span class="card-num">{{assistTask.length}}</span>
                                    <span class="card-sub">{{$L('[task_unit].项')}}<template v-if="assistTodayCount > 0"> · {{$L('今天 (*) 项到期', assistTodayCount)}}</template></span>
                                </div>
                            </li>
                        </ul>
                        <div class="dashboard-card my-table">
                            <div v-if="listColumns.length === 0" class="table-all-empty">
                                <div v-if="loadDashboardTasks !== false && cacheTasks.length === 0" class="empty-state-loading"><Loading/></div>
                                <div v-else class="empty-state-content">
                                    <span class="empty-icon" :class="`empty-icon-${emptyState}`">
                                        <Icon :type="emptyState === 'new' ? 'md-hand' : 'md-checkmark'"/>
                                    </span>
                                    <strong class="empty-state-title">{{emptyStateTitle}}</strong>
                                    <span class="empty-state-summary">{{emptyStateSummary}}</span>
                                    <div v-if="emptyState === 'new'" class="empty-state-actions">
                                        <Button type="primary" icon="md-add" @click="createProject">{{$L('创建第一个项目')}}</Button>
                                    </div>
                                    <div v-else-if="emptyState === 'idle'" class="empty-state-actions">
                                        <Button icon="md-add" @click="createTask">{{$L('新建任务')}}</Button>
                                    </div>
                                    <span v-if="emptyState === 'new'" class="empty-state-hint">{{$L('已有团队？请同事把你加入项目即可')}}</span>
                                    <div v-if="emptyState === 'completed'" class="recent-completed-list">
                                        <div class="recent-completed-head">
                                            <span>{{$L('近期完成')}}</span>
                                            <em>{{$L('本周 (*) 项', weeklyCompletedCount)}}</em>
                                        </div>
                                        <div
                                            v-for="task in recentCompleted"
                                            :key="`recent-${task.id}`"
                                            class="recent-completed-row"
                                            @click="openTask(task)">
                                            <Icon class="recent-completed-check" type="md-checkmark-circle"/>
                                            <span class="recent-completed-name">{{task.name}}</span>
                                            <span class="recent-completed-project">{{projectName(task)}}</span>
                                            <span class="recent-completed-time">{{completedText(task.complete_at)}}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="table-row table-thead">
                                <span></span>
                                <span>{{$L('任务')}}</span>
                                <span class="t-project">{{$L('项目')}}</span>
                                <span class="t-status">{{$L('状态')}}</span>
                                <span class="t-priority">{{$L('优先级')}}</span>
                                <span class="t-sub">{{$L('子任务')}}</span>
                                <span class="t-end">{{$L('截止时间')}}</span>
                            </div>
                            <template v-for="group in listColumns">
                                <div
                                    :key="`head-${group.type}`"
                                    :ref="`group_${group.type}`"
                                    class="table-group"
                                    :class="[`group-${group.type}`, {'group-flash': flashType === group.type}]"
                                    @click="toggleGroup(group.type)">
                                    <i class="group-dot"></i>
                                    <span class="group-title">{{group.title}} · {{group.count}}</span>
                                    <i class="group-chevron taskfont" :class="{'chevron-close': group.hidden}">&#xe702;</i>
                                </div>
                                <template v-if="!group.hidden">
                                    <div v-if="group.list.length === 0" :key="`empty-${group.type}`" class="table-empty-group">
                                        <span class="empty-icon"><Icon type="md-checkmark"/></span>
                                        <span>{{listEmptyText(group.type)}}</span>
                                    </div>
                                    <div class="table-body" :key="`list-${group.type}`">
                                        <div
                                            v-for="item in groupList(group, listLimit(group))"
                                            :key="`${group.type}-${item.id}`"
                                            class="table-row"
                                            :class="{complete: item.complete_at}"
                                            :style="$A.generateColorVarStyle(item.flow_item_color, [10], 'flow-item-custom-color', item.color ? {backgroundColor: item.color} : {})"
                                            @click="openTask(item)">
                                            <span class="cell-check" @click.stop>
                                                <UserAvatar v-if="group.type === 'assist' && ownerUserid(item)" :userid="ownerUserid(item)" :size="22"/>
                                                <TaskMenu v-else :task="item"/>
                                            </span>
                                            <span class="cell-name">
                                                <em
                                                    class="status-pill name-status"
                                                    :class="item.flow_item_name ? item.flow_item_status : {end: !!item.complete_at}"
                                                    @click.stop="openMenu($event, item)">{{item.flow_item_name || (item.complete_at ? $L('已完成') : $L('未完成'))}}</em>
                                                <em v-if="item.sub_top === true" class="name-tag">{{$L('子任务')}}</em>
                                                <em v-if="item.sub_my && item.sub_my.length > 0" class="name-tag">+{{item.sub_my.length}}</em>
                                                {{item.name}}
                                            </span>
                                            <span class="t-project">{{projectName(item)}}</span>
                                            <span class="t-status">
                                                <em
                                                    class="status-pill"
                                                    :class="item.flow_item_name ? item.flow_item_status : {end: !!item.complete_at}"
                                                    @click.stop="openMenu($event, item)">{{item.flow_item_name || (item.complete_at ? $L('已完成') : $L('未完成'))}}</em>
                                            </span>
                                            <span class="t-priority">
                                                <template v-if="item.p_name">
                                                    <i class="priority-dot" :style="{backgroundColor: item.p_color}"></i>
                                                    <span>{{item.p_name}}</span>
                                                </template>
                                                <template v-else>—</template>
                                            </span>
                                            <span class="t-sub">
                                                <template v-if="item.sub_num > 0">
                                                    <i class="sub-bar"><b :style="{width: `${Math.min(item.sub_complete / item.sub_num * 100, 100)}%`}"></b></i>
                                                    <em>{{item.sub_complete}}/{{item.sub_num}}</em>
                                                </template>
                                                <template v-else>—</template>
                                            </span>
                                            <span
                                                class="t-end"
                                                :class="deadlineClass(item.end_at)"
                                                :title="item.end_at">{{deadlineText(item.end_at) || '—'}}</span>
                                        </div>
                                        <div
                                            v-if="group.list.length > listLimit(group) && !expandedGroups.includes(group.type)"
                                            :key="`more-${group.type}`"
                                            class="card-more"
                                            @click="expandedGroups.push(group.type)">{{$L('还有 (*) 项', group.list.length - listLimit(group))}} →</div>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </template>
                    <!--四象限布局-->
                    <div v-else class="dashboard-quad">
                        <div
                            v-for="group in columns"
                            :key="group.type"
                            class="dashboard-card quad-card"
                            :class="[`group-${group.type}`]">
                            <div class="quad-head">
                                <i class="group-dot"></i>
                                <span class="group-title">{{group.title}}</span>
                                <span class="quad-num" :class="{'num-zero': group.count === 0}">{{group.count}}</span>
                            </div>
                            <div
                                v-for="item in groupList(group, quadLimit)"
                                :key="item.id"
                                class="quad-row"
                                :class="{complete: item.complete_at}"
                                :style="$A.generateColorVarStyle(item.flow_item_color, [10], 'flow-item-custom-color', item.color ? {backgroundColor: item.color} : {})"
                                @click="openTask(item)">
                                <span class="cell-check" @click.stop>
                                    <UserAvatar v-if="group.type === 'assist' && ownerUserid(item)" :userid="ownerUserid(item)" :size="22"/>
                                    <TaskMenu v-else :task="item"/>
                                </span>
                                <span class="cell-name">{{item.name}}</span>
                                <em
                                    class="status-pill"
                                    :class="item.flow_item_name ? item.flow_item_status : {end: !!item.complete_at}"
                                    @click.stop="openMenu($event, item)">{{item.flow_item_name || (item.complete_at ? $L('已完成') : $L('未完成'))}}</em>
                                <span v-if="item.p_name" class="quad-priority"><i class="priority-dot" :style="{backgroundColor: item.p_color}"></i></span>
                                <span v-if="item.sub_num > 0" class="quad-sub">{{item.sub_complete}}/{{item.sub_num}}</span>
                                <span v-if="item.end_at" class="t-end" :class="deadlineClass(item.end_at)">{{deadlineText(item.end_at)}}</span>
                            </div>
                            <div v-if="group.list.length === 0" class="quad-empty">
                                <span class="empty-icon"><Icon type="md-checkmark"/></span>
                                <span>{{quadEmptyText(group.type)}}</span>
                            </div>
                            <div v-else-if="group.list.length <= quadLimit" class="quad-filler">
                                {{group.type === 'today' && group.list.length === 1 ? $L('今天只有这一项，处理完就轻松了') : $L('全部显示完毕')}}
                            </div>
                            <div
                                v-else
                                class="card-more"
                                @click="toggleExpand(group.type)">
                                {{expandedGroups.includes(group.type) ? $L('收起') : $L('数量较多，仅显示最近 (*) 项，展开其余 (*) 项', quadLimit, group.list.length - quadLimit) + ' →'}}
                            </div>
                        </div>
                    </div>
                </template>
                <!--部门负责人视角-->
                <keep-alive>
                    <DashboardTeam
                        v-if="currentView === 'team'"
                        ref="dashboardTeam"
                        :initial-focus="teamFocusPref"
                        @stats="teamStats = $event"
                        @stats-loading="teamStatsLoading = $event"/>
                </keep-alive>
            </div>
        </Scrollbar>
        <DepartmentOwnerView v-model="deptViewShow" scope-only/>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import TaskMenu from "./components/TaskMenu";
import DashboardTeam from "./components/DashboardTeam";
import DepartmentOwnerView from "./components/DepartmentOwnerView";
import dashboardTimeMixin from "./components/dashboard-time-mixin";
import emitter from "../../store/events";

const prefsCache = {
    view: 'my',
    layout: 'list',
    overrides: {},
    focus: 'overdue',
}

export default {
    components: {TaskMenu, DashboardTeam, DepartmentOwnerView},
    mixins: [dashboardTimeMixin],
    data() {
        return {
            licenseTimer: null,

            view: prefsCache.view,
            layout: prefsCache.layout,
            // 手动展开/收起的当天记忆：{type: 'open'|'close'}，未记录的组走策略快照
            collapsedOverrides: prefsCache.overrides,
            strategySnapshot: null,
            teamFocusPref: prefsCache.focus,

            expandedGroups: [],
            groupLimit: 10,
            completedLimit: 5,
            quadLimit: 5,
            flashType: '',
            flashTimer: null,

            deptViewShow: false,
            teamStats: {member_count: 0, blocks: {}},
            teamStatsLoading: false,
            teamCachePopoverShow: false,

            mateName: /macintosh|mac os x/i.test(navigator.userAgent) ? '⌘' : 'Ctrl',

            warningMsg: '',
        }
    },

    async beforeRouteEnter(to, from, next) {
        prefsCache.view = await $A.IDBString("dashboardView", "my")
        prefsCache.layout = await $A.IDBString("dashboardLayout", "list")
        prefsCache.focus = await $A.IDBString("dashboardTeamFocus", "overdue")
        // 手动展开/收起记忆只在当天有效，次日回到策略默认
        const collapsed = $A.jsonParse(await $A.IDBString("dashboardCollapsed", ""), {})
        prefsCache.overrides = collapsed.date === $A.daytz().format("YYYY-MM-DD") && $A.isJson(collapsed.overrides)
            ? collapsed.overrides
            : {}
        next(vm => {
            vm.view = ['my', 'team'].includes(prefsCache.view) ? prefsCache.view : 'my'
            vm.layout = ['list', 'quad'].includes(prefsCache.layout) ? prefsCache.layout : 'list'
            vm.collapsedOverrides = prefsCache.overrides
            vm.teamFocusPref = prefsCache.focus
        })
    },

    activated() {
        this.loadInterval(true);
        this.loadLicense(true);
        this.takeSnapshot();
    },

    deactivated() {
        this.$store.dispatch("forgetTaskCompleteTemp", true);
        this.loadInterval(false);
        this.loadLicense(false);
    },

    computed: {
        ...mapState(['systemConfig', 'userInfo', 'userIsAdmin', 'cacheProjects', 'cacheTasks', 'cacheDepartmentOwnerIds', 'taskCompleteTemps', 'loadDashboardTasks']),
        ...mapGetters(['dashboardTask', 'assistTask', 'transforTasks']),

        teamEnabled({systemConfig, userInfo}) {
            return systemConfig.department_owner_project_view === 'open' && (userInfo.managed_departments || []).length > 0;
        },

        currentView({view, teamEnabled}) {
            return teamEnabled && view === 'team' ? 'team' : 'my';
        },

        // 待开始：还没到开始时间的任务（数据已在 cacheTasks，dashboardTask getter 按开始时间过滤掉的部分）
        upcomingTask({cacheTasks, taskCompleteTemps}) {
            const now = $A.daytz();
            const filterTask = (task, checkCompleted = true) => {
                if (task.archived_at) {
                    return false;
                }
                if (task.complete_at && checkCompleted === true) {
                    return false;
                }
                if (!task.start_at || $A.dayjs(task.start_at) <= now) {
                    return false;
                }
                return task.owner == 1;
            };
            let array = cacheTasks.filter(task => filterTask(task));
            const count = array.length;
            if (taskCompleteTemps.length > 0) {
                const tmps = cacheTasks.filter(task => taskCompleteTemps.includes(task.id) && filterTask(task, false));
                if (tmps.length > 0) {
                    array = array.concat(tmps);
                }
            }
            return {count, list: array};
        },

        columns({dashboardTask, assistTask}) {
            const list = [];
            ['overdue', 'today', 'todo'].some(type => {
                let data = this.transforTasks(dashboardTask[type]);
                list.push({
                    type,
                    title: this.getTitle(type),
                    hidden: this.isCollapsed(type),
                    count: dashboardTask[`${type}_count`],
                    list: data.sort((a, b) => {
                        return $A.sortDay(a.end_at || "2099-12-31 23:59:59", b.end_at || "2099-12-31 23:59:59");
                    })
                })
            })
            list.push({
                type: 'assist',
                title: this.getTitle('assist'),
                hidden: this.isCollapsed('assist'),
                count: assistTask.length,
                list: assistTask.sort((a, b) => {
                    return $A.sortDay(a.end_at || "2099-12-31 23:59:59", b.end_at || "2099-12-31 23:59:59");
                })
            })
            return list;
        },

        // 列表布局的全部分组：比四象限多一个「待开始」，插在待完成之后、协助之前
        listAllColumns({columns, upcomingTask}) {
            const list = [...columns];
            list.splice(3, 0, {
                type: 'upcoming',
                title: this.getTitle('upcoming'),
                hidden: this.isCollapsed('upcoming'),
                count: upcomingTask.count,
                list: this.transforTasks(upcomingTask.list).sort((a, b) => {
                    return $A.sortDay(a.start_at, b.start_at);
                })
            });
            return list;
        },

        // 当前任务分组按实际列表内容显示；临时完成的划线任务仍留在原分组中
        currentListColumns({listAllColumns}) {
            return listAllColumns.filter(group => group.list.length > 0);
        },

        // 本周已完成任务：本周一至下周一，排除仍显示在原分组中的临时完成任务
        weeklyCompleted({cacheTasks, taskCompleteTemps, nowTime}) {
            const current = $A.daytz(nowTime);
            const start = current.clone().startOf('day').subtract((current.day() + 6) % 7, 'day');
            const next = start.clone().add(7, 'day');
            return cacheTasks.filter(task => {
                if (task.archived_at || !task.complete_at || task.owner != 1 || taskCompleteTemps.includes(task.id)) {
                    return false;
                }
                const complete = $A.dayjs(task.complete_at);
                return complete.valueOf() >= start.valueOf() && complete.valueOf() < next.valueOf();
            }).sort((a, b) => $A.sortDay(b.complete_at, a.complete_at));
        },

        weeklyCompletedCount({weeklyCompleted}) {
            return weeklyCompleted.length;
        },

        recentCompleted({weeklyCompleted, completedLimit}) {
            return weeklyCompleted.slice(0, completedLimit);
        },

        completedColumn({weeklyCompleted, weeklyCompletedCount}) {
            return {
                type: 'completed',
                title: this.getTitle('completed'),
                hidden: this.isCollapsed('completed'),
                count: weeklyCompletedCount,
                list: weeklyCompleted,
            };
        },

        // 只要还有当前任务分组，本周完成就作为同款任务分组附加在末尾
        listColumns({currentListColumns, completedColumn}) {
            if (currentListColumns.length === 0) {
                return [];
            }
            return completedColumn.count > 0 ? [...currentListColumns, completedColumn] : currentListColumns;
        },

        emptyState({weeklyCompletedCount, cacheProjects, cacheTasks}) {
            if (weeklyCompletedCount > 0) {
                return 'completed';
            }
            const hasVisibleTask = cacheTasks.some(task => !task.archived_at);
            return cacheProjects.length === 0 && !hasVisibleTask ? 'new' : 'idle';
        },

        emptyStateTitle({emptyState}) {
            if (emptyState === 'completed') {
                return this.$L('太棒了，任务全部清空');
            }
            if (emptyState === 'new') {
                return this.$L('欢迎使用 DooTask');
            }
            return this.$L('当前没有待处理任务');
        },

        emptyStateSummary({emptyState, weeklyCompletedCount}) {
            if (emptyState === 'completed') {
                return this.$L('本周完成了 (*) 项任务', weeklyCompletedCount);
            }
            if (emptyState === 'new') {
                return this.$L('项目是任务与协作的起点，创建一个开始');
            }
            return this.$L('可以新建一项任务，或等待新的工作安排');
        },

        overdueMaxDays({dashboardTask, nowTime}) {
            const now = $A.daytz(nowTime).startOf('day');
            return (dashboardTask.overdue || []).reduce((max, task) => {
                if (!task.end_at) {
                    return max;
                }
                return Math.max(max, now.diff($A.dayjs(task.end_at).startOf('day'), 'day'));
            }, 0);
        },

        todayNearest({dashboardTask}) {
            const list = (dashboardTask.today || []).filter(task => task.end_at);
            if (list.length === 0) {
                return '';
            }
            const nearest = list.reduce((min, task) => min === null || task.end_at < min ? task.end_at : min, null);
            return $A.dayjs(nearest).format('HH:mm');
        },

        assistTodayCount({assistTask, nowTime}) {
            const today = $A.daytz(nowTime).format('YYYY-MM-DD');
            return assistTask.filter(task => task.end_at && $A.dayjs(task.end_at).format('YYYY-MM-DD') === today).length;
        },

        todayText({nowTime}) {
            const now = $A.daytz(nowTime);
            const weeks = [this.$L('周日'), this.$L('周一'), this.$L('周二'), this.$L('周三'), this.$L('周四'), this.$L('周五'), this.$L('周六')];
            return `${this.$L('(*)月(*)日', now.month() + 1, now.date())} ${weeks[now.day()]}`;
        },

        mySummary({dashboardTask}) {
            if (dashboardTask.overdue_count > 0) {
                return this.$L('有 (*) 项已超期，建议先处理', dashboardTask.overdue_count);
            }
            if (dashboardTask.today_count > 0) {
                return this.$L('有 (*) 项今日到期', dashboardTask.today_count);
            }
            return this.$L('今日无到期任务');
        },

        teamSummary({teamStats}) {
            const blocks = teamStats.blocks || {};
            if (blocks.overdue > 0) {
                if (blocks.overdue_owner_count > 0) {
                    return this.$L('团队有 (*) 项已超期，涉及 (*) 人', blocks.overdue, blocks.overdue_owner_count);
                }
                return this.$L('团队有 (*) 项已超期', blocks.overdue);
            }
            if (blocks.week_completed > 0) {
                return this.$L('团队本周已完成 (*) 项', blocks.week_completed);
            }
            return '';
        },

        teamStatsUpdatedTime({teamStats}) {
            return teamStats.generated_at ? $A.dayjs(teamStats.generated_at).format('HH:mm:ss') : '--'
        },

        deptLabel({userInfo, cacheDepartmentOwnerIds}) {
            const managed = (userInfo.managed_departments || []).map(item => ({...item, id: parseInt(item.id)}));
            const ids = (cacheDepartmentOwnerIds || []).map(id => parseInt(id));
            const selected = ids.length > 0 ? managed.filter(item => ids.includes(item.id)) : managed;
            if (selected.length === 0 || selected.length === managed.length) {
                return managed.length === 1 ? managed[0].name : this.$L('我的部门');
            }
            if (selected.length === 1) {
                return selected[0].name;
            }
            return `${selected[0].name} +${selected.length - 1}`;
        },

        dashboardHello({systemConfig, userInfo, nowTime}) {
            if (systemConfig.system_welcome) {
                return this.$L(systemConfig.system_welcome).replace(/\{username}/g, userInfo.nickname);
            }
            const hour = $A.daytz(nowTime).hour();
            if (hour < 5) {
                return this.$L('夜深了，(*)', userInfo.nickname);
            } else if (hour < 11) {
                return this.$L('早上好，(*)', userInfo.nickname);
            } else if (hour < 14) {
                return this.$L('中午好，(*)', userInfo.nickname);
            } else if (hour < 18) {
                return this.$L('下午好，(*)', userInfo.nickname);
            }
            return this.$L('晚上好，(*)', userInfo.nickname);
        }
    },

    watch: {
        windowActive(active) {
            if (this.routeName !== 'manage-dashboard') {
                return
            }
            this.loadInterval(active)
            this.loadLicense(active)
        }
    },

    methods: {
        getTitle(type) {
            switch (type) {
                case 'today':
                    return this.$L('今日到期');
                case 'overdue':
                    return this.$L('已超期');
                case 'todo':
                    return this.$L('待完成');
                case 'upcoming':
                    return this.$L('待开始');
                case 'assist':
                    return this.$L('我协助的');
                case 'completed':
                    return this.$L('本周完成');
                default:
                    return '';
            }
        },

        setView(view) {
            this.view = view;
            if (view !== 'team') {
                this.teamCachePopoverShow = false;
            }
            prefsCache.view = view;
            $A.IDBSave("dashboardView", view);
        },

        setLayout(layout) {
            this.layout = layout;
            prefsCache.layout = layout;
            $A.IDBSave("dashboardLayout", layout);
        },

        /**
         * 分组是否收起：手动记忆（当天）优先，其余走策略快照
         */
        isCollapsed(type) {
            const override = this.collapsedOverrides[type];
            if (override) {
                return override === 'close';
            }
            return (this.strategySnapshot || []).includes(type);
        },

        /**
         * 策略快照（行动优先）：
         * - 显示的组默认展开；超期或今日到期有数据时，「待开始」默认收起
         */
        takeSnapshot() {
            const counts = {
                overdue: this.dashboardTask.overdue_count,
                today: this.dashboardTask.today_count,
                upcoming: this.upcomingTask.count,
                assist: this.assistTask.length,
            };
            const collapsed = [];
            if (counts.upcoming > 0 && (counts.overdue > 0 || counts.today > 0)) {
                collapsed.push('upcoming');
            }
            this.strategySnapshot = collapsed;
        },

        toggleGroup(type) {
            this.$set(this.collapsedOverrides, type, this.isCollapsed(type) ? 'open' : 'close');
            prefsCache.overrides = this.collapsedOverrides;
            $A.IDBSave("dashboardCollapsed", JSON.stringify({
                date: $A.daytz().format("YYYY-MM-DD"),
                overrides: this.collapsedOverrides,
            }));
        },

        toggleExpand(type) {
            if (this.expandedGroups.includes(type)) {
                this.expandedGroups = this.expandedGroups.filter(item => item !== type);
            } else {
                this.expandedGroups.push(type);
            }
        },

        groupList(group, limit) {
            if (this.expandedGroups.includes(group.type)) {
                return group.list;
            }
            return group.list.slice(0, limit);
        },

        listLimit(group) {
            return group.type === 'completed' ? this.completedLimit : this.groupLimit;
        },

        listEmptyText(type) {
            switch (type) {
                case 'overdue':
                    return this.$L('暂无已超期任务');
                case 'today':
                    return this.$L('暂无今日到期任务');
                case 'todo':
                    return this.$L('暂无待完成任务');
                case 'upcoming':
                    return this.$L('暂无待开始任务');
                case 'assist':
                    return this.$L('暂无协助的任务');
                default:
                    return this.$L('暂无任务');
            }
        },

        quadEmptyText(type) {
            switch (type) {
                case 'overdue':
                    return this.$L('没有超期任务，保持住');
                case 'today':
                    return this.$L('今日无到期任务');
                case 'todo':
                    return this.$L('暂无待完成任务');
                case 'assist':
                    return this.$L('暂无协助的任务');
                default:
                    return this.$L('暂无任务');
            }
        },

        scrollTo(type) {
            const column = this.listAllColumns.find(group => group.type === type);
            if (!column || column.count === 0) {
                return;
            }
            if (this.isCollapsed(type)) {
                this.toggleGroup(type);
            }
            this.$nextTick(_ => {
                const refs = this.$refs[`group_${type}`];
                if (refs && refs[0]) {
                    $A.scrollToView(refs[0], {
                        behavior: 'smooth',
                        block: 'start',
                    });
                    this.flashType = type;
                    this.flashTimer && clearTimeout(this.flashTimer);
                    this.flashTimer = setTimeout(_ => this.flashType = '', 1600);
                }
            })
        },

        refreshTeamDashboard() {
            this.$refs.dashboardTeam?.refreshAll().then(success => {
                if (success) {
                    this.teamCachePopoverShow = false
                    $A.messageSuccess("刷新成功")
                }
            })
        },

        projectName(task) {
            const project = this.cacheProjects.find(({id}) => id == task.project_id);
            return project ? project.name : '';
        },

        ownerUserid(task) {
            const owner = (task.task_user || []).find(user => user.owner === 1);
            return owner ? owner.userid : 0;
        },

        openSearch() {
            emitter.emit('openSearch', null);
        },

        openTask(task) {
            this.$store.dispatch("openTask", task)
        },

        createTask() {
            this.$emit('on-click', 'addTask');
        },

        createProject() {
            this.$emit('on-click', 'addProject');
        },

        completedText(completeAt) {
            if (!completeAt) {
                return '';
            }
            const date = $A.dayjs(completeAt);
            const now = $A.daytz(this.nowTime);
            if (date.format('YYYY-MM-DD') === now.format('YYYY-MM-DD')) {
                return `${this.$L('今天')} ${date.format('HH:mm')}`;
            }
            if (date.format('YYYY-MM-DD') === now.clone().subtract(1, 'day').format('YYYY-MM-DD')) {
                return `${this.$L('昨天')} ${date.format('HH:mm')}`;
            }
            return `${this.$L('(*)月(*)日', date.month() + 1, date.date())} ${date.format('HH:mm')}`;
        },

        openMenu(event, task) {
            this.$store.state.taskOperation = {event, task}
        },

        loadLicense(load) {
            if (this.licenseTimer) {
                clearTimeout(this.licenseTimer)
                this.licenseTimer = null;
            }
            if (load === false || !this.userIsAdmin) {
                return
            }
            this.licenseTimer = setTimeout(_ => {
                this.$store.dispatch("call", {
                    url: 'system/license',
                    data: {
                        type: 'error',
                    }
                }).then(({data}) => {
                    this.warningMsg = data.error.length > 0 ? data.error[0] : '';
                }).catch(_ => {
                    this.warningMsg = '';
                })
            }, 1500)
        }
    }
}
</script>
