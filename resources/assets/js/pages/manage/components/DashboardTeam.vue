<template>
    <div class="dashboard-team">
        <!--核心指标-->
        <ul class="dashboard-cards">
            <li @click="onBlock('uncompleted')">
                <div class="card-label">{{$L('未完成')}}</div>
                <div class="card-data">
                    <span class="card-num">{{blocks.uncompleted || 0}}</span>
                    <span class="card-sub">{{$L('[task_unit].项')}}</span>
                </div>
                <div class="card-link">{{$L('查看任务')}} →</div>
            </li>
            <li @click="onBlock('overdue')">
                <div class="card-label">{{$L('已超期')}}</div>
                <div class="card-data">
                    <span class="card-num num-red">{{blocks.overdue || 0}}</span>
                    <span class="card-sub">{{$L('[task_unit].项')}}<template v-if="blocks.overdue_owner_count"> · {{$L('涉及 (*) 人', blocks.overdue_owner_count)}}</template></span>
                </div>
                <div class="card-link link-red">{{$L('查看任务')}} →</div>
            </li>
            <li @click="onBlock('soon')">
                <div class="card-label">{{$L('(*) 天内到期', 3)}}</div>
                <div class="card-data">
                    <span class="card-num num-orange">{{blocks.due_soon || 0}}</span>
                    <span class="card-sub">{{$L('[task_unit].项')}}</span>
                </div>
                <div class="card-link link-orange">{{$L('查看任务')}} →</div>
            </li>
            <li class="card-static">
                <div class="card-label">{{$L('本周完成')}}</div>
                <div class="card-data">
                    <span class="card-num num-green">{{blocks.week_completed || 0}}</span>
                    <span class="card-sub">{{$L('[task_unit].项')}}<template v-if="weekDiffText"> · {{weekDiffText}}</template></span>
                </div>
            </li>
        </ul>
        <!--成员任务分配 + 优先级分布-->
        <div class="team-duo">
            <div class="dashboard-card team-members">
                <div class="card-head">
                    <span class="head-title">{{$L('成员任务分配')}}</span>
                    <span class="head-legend">
                        <span><i style="background:#bfe3cd"></i>{{$L('待处理')}}</span>
                        <span><i style="background:#1e9e55"></i>{{$L('进行中')}}</span>
                        <span><i style="background:#6ba7d8"></i>{{$L('验收/测试')}}</span>
                    </span>
                </div>
                <div v-if="showStatsLoading" class="team-stats-loading"><Loading/></div>
                <template v-else>
                    <ul class="member-list">
                        <li v-for="member in visibleMembers" :key="member.userid" class="member-row" @click="onMember(member)">
                            <UserAvatar class="team-avatar" :userid="member.userid" :size="26"/>
                            <span class="member-name">{{member.nickname}}</span>
                            <ETooltip
                                :disabled="$isEEUIApp || $store.state.windowTouch || member.total === 0"
                                :open-delay="300"
                                effect="light"
                                placement="top"
                                popper-class="dashboard-member-progress-popper">
                                <span class="member-track">
                                    <span class="member-bar" :style="{width: memberBarWidth(member)}">
                                        <i v-if="member.segments.start > 0" :style="{flex: member.segments.start, background: '#bfe3cd'}"></i>
                                        <i v-if="member.segments.progress > 0" :style="{flex: member.segments.progress, background: '#1e9e55'}"></i>
                                        <i v-if="member.segments.test > 0" :style="{flex: member.segments.test, background: '#6ba7d8'}"></i>
                                    </span>
                                </span>
                                <div slot="content" class="member-progress-tooltip">
                                    <span class="progress-tooltip-title">{{$L('任务概况')}}</span>
                                    <div class="progress-tooltip-row">
                                        <span class="progress-tooltip-label">{{$L('未完成总数')}}</span>
                                        <strong>{{$L('(*) 项', member.total)}}</strong>
                                    </div>
                                    <div class="progress-tooltip-row row-overdue">
                                        <span class="progress-tooltip-label">{{$L('已超期')}}</span>
                                        <strong>{{$L('(*) 项', member.overdue)}} · {{memberOverduePercent(member)}}%</strong>
                                    </div>
                                    <span class="progress-tooltip-title title-stage">{{$L('流程阶段')}}</span>
                                    <div v-for="segment in memberProgressDetails(member)" :key="segment.type" class="progress-tooltip-row">
                                        <span class="progress-tooltip-label"><i :style="{background: segment.color}"></i>{{segment.label}}</span>
                                        <strong>{{$L('(*) 项', segment.count)}} · {{segment.percent}}%</strong>
                                    </div>
                                </div>
                            </ETooltip>
                            <span class="member-num" :title="memberSummary(member)">
                                <em v-if="member.overdue > 0" class="num-overdue">{{$L('(*) 超期', member.overdue)}} · </em>{{$L('(*) 项', member.total)}}
                            </span>
                        </li>
                        <li v-if="blocks.no_owner > 0" class="member-row member-noowner" @click="onBlock('noowner')">
                            <span class="mock-avatar avatar-none">?</span>
                            <span class="member-name">{{$L('未分配')}}</span>
                            <span class="member-track">
                                <span class="member-bar bar-hatched" :style="{width: memberBarWidth({total: blocks.no_owner})}"></span>
                            </span>
                            <span class="member-num num-noowner">{{$L('(*) 项', blocks.no_owner)}}</span>
                        </li>
                    </ul>
                    <div v-if="members.length > memberLimit" class="card-more" @click="memberExpand = !memberExpand">
                        {{memberExpand ? $L('收起') : $L('查看全部 (*) 人', members.length) + ' →'}}
                    </div>
                </template>
            </div>
            <div class="dashboard-card team-priority">
                <div class="card-head">
                    <span class="head-title">{{$L('优先级分布')}}</span>
                    <span v-if="!showStatsLoading" class="head-note">{{$L('未完成 (*) 项', blocks.uncompleted || 0)}}</span>
                </div>
                <div v-if="showStatsLoading" class="team-stats-loading"><Loading/></div>
                <div v-else class="priority-list">
                    <div v-for="item in visiblePriorityList" :key="item.level" class="priority-row" @click="onPriority(item)">
                        <div class="priority-head">
                            <span class="priority-name">{{item.name}}</span>
                            <span class="priority-num">{{$L('(*) 项', item.num)}} · {{item.percent}}%</span>
                        </div>
                        <div class="priority-bar">
                            <i :style="{width: item.percent + '%', background: item.color}"></i>
                        </div>
                    </div>
                    <div
                        v-if="priorityList.length > priorityLimit"
                        class="priority-tip priority-more"
                        @click="priorityExpand = !priorityExpand">
                        {{priorityExpand ? $L('收起') : $L('查看全部 (*) 个优先级', priorityList.length) + ' →'}}
                    </div>
                    <div v-else class="priority-tip">{{$L('点击任一档位可下钻到任务列表')}}</div>
                </div>
            </div>
        </div>
        <!--重点关注任务-->
        <div class="team-focus-section" :class="{'focus-scroll-reserve': focusScrollReserved}">
            <div ref="focusCard" class="dashboard-card team-focus">
                <div class="card-head focus-head">
                    <span class="head-title">{{$L('重点关注任务')}}</span>
                    <span
                        v-for="chip in chips"
                        :key="chip.type"
                        class="focus-chip"
                        :class="[`chip-${chip.type}`, {'chip-active': !extraFilter && focus === chip.type}]"
                        @click="setFocus(chip.type)">{{chip.label}} {{chip.num}}</span>
                    <span v-if="extraFilter" class="focus-chip chip-extra chip-active" @click="clearFilter">
                        {{extraFilter.label}}<i>×</i>
                    </span>
                </div>
                <div class="focus-table">
                    <div class="focus-row focus-thead">
                        <span>{{$L('任务')}}</span>
                        <span class="f-project">{{$L('项目')}}</span>
                        <span class="f-owner">{{$L('负责人')}}</span>
                        <span class="f-priority">{{$L('优先级')}}</span>
                        <span class="f-status">{{$L('状态')}}</span>
                        <span class="f-end">{{$L('截止时间')}}</span>
                    </div>
                    <div v-for="item in currentList.list" :key="item.id" class="focus-row" @click="onTask(item)">
                        <span class="f-name">{{item.name}}</span>
                        <span class="f-project">{{item.project_name}}</span>
                        <span class="f-owner">
                            <template v-if="item.owner">
                                <UserAvatar class="team-avatar" :userid="item.owner.userid" :size="20"/>
                                <span class="owner-name">{{item.owner.nickname}}</span>
                                <em v-if="item.owners.length > 1" class="owner-more">+{{item.owners.length - 1}}</em>
                            </template>
                            <template v-else>
                                <span class="mock-avatar avatar-none">?</span><em class="owner-none">{{$L('待分配')}}</em>
                            </template>
                        </span>
                        <span class="f-priority">
                            <template v-if="item.p_name">
                                <i class="priority-dot" :style="{backgroundColor: item.p_color}"></i>
                                <span>{{item.p_name}}</span>
                            </template>
                            <template v-else>—</template>
                        </span>
                        <span class="f-status"><em class="status-pill" :class="item.flow_item_name ? item.flow_item_status : ''">{{item.flow_item_name || $L('未完成')}}</em></span>
                        <span class="f-end" :class="deadlineClass(item.end_at)">{{deadlineText(item.end_at) || '—'}}</span>
                    </div>
                    <div v-if="currentList.loading && !currentList.silent" class="focus-loading"><Loading/></div>
                    <div v-else-if="currentList.list.length === 0" class="focus-empty">
                        <span class="focus-empty-icon"><Icon type="md-checkmark"/></span>
                        <strong>{{focusEmptyTitle}}</strong>
                        <span>{{$L('可以切换上方条件查看其他任务')}}</span>
                    </div>
                    <div v-else-if="currentList.hasMore" class="card-more" @click="loadMore">{{$L('加载更多')}}（{{$L('还有 (*) 项', currentList.total - currentList.list.length)}}）</div>
                    <div v-else-if="currentList.total > pageSize" class="card-more more-done">{{$L('全部显示完毕')}}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import dashboardTimeMixin from "./dashboard-time-mixin";

export default {
    name: "DashboardTeam",
    mixins: [dashboardTimeMixin],
    props: {
        initialFocus: {
            type: String,
            default: 'overdue'
        },
    },
    data() {
        return {
            stats: {
                member_count: 0,
                blocks: {},
                priority: [],
                members: [],
            },
            statsLoading: false,
            statsLoadedAt: 0,
            statsToken: 0,

            focus: ['overdue', 'soon', 'hi', 'noowner'].includes(this.initialFocus) ? this.initialFocus : 'overdue',
            extraFilter: null,

            lists: {},
            listTokens: {},
            pageSize: 20,
            refreshSeconds: 60,

            memberLimit: 6,
            memberExpand: false,
            priorityLimit: 6,
            priorityExpand: false,
            focusScrollReserved: false,
        }
    },

    created() {
        this.loadAll()
    },

    activated() {
        this.loadInterval(true)
        if (this.statsLoadedAt > 0 && $A.dayjs().unix() - this.statsLoadedAt >= this.refreshSeconds) {
            this.loadAll(true)
        }
    },

    deactivated() {
        this.loadInterval(false)
        this.focusScrollReserved = false
    },

    beforeDestroy() {
        this.loadInterval(false)
    },

    computed: {
        ...mapState(['cacheDepartmentOwnerIds']),

        scopeKey({cacheDepartmentOwnerIds}) {
            const ids = (cacheDepartmentOwnerIds || []).map(id => parseInt(id)).filter(id => id > 0).sort((a, b) => a - b)
            return ids.length > 0 ? ids.join(',') : 'all'
        },

        blocks({stats}) {
            return stats.blocks || {}
        },

        members({stats}) {
            return stats.members || []
        },

        visibleMembers({members, memberExpand, memberLimit}) {
            return memberExpand ? members : members.slice(0, memberLimit)
        },

        showStatsLoading({statsLoading, statsLoadedAt}) {
            return statsLoading && statsLoadedAt === 0
        },

        memberMax({members, blocks}) {
            return Math.max(...members.map(item => item.total), blocks.no_owner || 0, 1)
        },

        priorityList({stats, blocks}) {
            const total = Math.max(blocks.uncompleted || 0, 1)
            return (stats.priority || []).filter(item => item.num > 0).map(item => {
                return {
                    ...item,
                    name: item.name || this.$L('未设置'),
                    percent: Math.round(item.num / total * 100),
                }
            })
        },

        visiblePriorityList({priorityList, priorityExpand, priorityLimit}) {
            return priorityExpand ? priorityList : priorityList.slice(0, priorityLimit)
        },

        highPriorityChip({stats, blocks}) {
            // 与后端口径一致：系统优先级前 2 档视为高优先级
            const tops = (stats.priority || []).filter(item => item.level > 0).slice(0, 2)
            return {
                label: tops.map(item => item.name).join(' / ') || this.$L('高优先级'),
                num: tops.reduce((sum, item) => sum + item.num, 0),
            }
        },

        chips({blocks, highPriorityChip}) {
            return [
                {type: 'overdue', label: this.$L('已超期'), num: blocks.overdue || 0},
                {type: 'soon', label: this.$L('(*) 天内到期', 3), num: blocks.due_soon || 0},
                {type: 'hi', label: highPriorityChip.label, num: highPriorityChip.num},
                {type: 'noowner', label: this.$L('未分配负责人'), num: blocks.no_owner || 0},
            ]
        },

        weekDiffText({blocks}) {
            if (!blocks.week_completed && !blocks.last_week_completed) {
                return ''
            }
            const diff = (blocks.week_completed || 0) - (blocks.last_week_completed || 0)
            if (diff > 0) {
                return this.$L('较上周') + ` +${diff}`
            }
            if (diff < 0) {
                return this.$L('较上周') + ` ${diff}`
            }
            return this.$L('与上周持平')
        },

        currentKey({scopeKey, focus, extraFilter}) {
            if (extraFilter) {
                return `${scopeKey}|${extraFilter.kind}:${extraFilter.value}`
            }
            return `${scopeKey}|type:${focus}`
        },

        currentList({lists, currentKey}) {
            return lists[currentKey] || {list: [], page: 1, total: 0, hasMore: false, loading: true, silent: false}
        },

        focusEmptyTitle({extraFilter, focus}) {
            if (extraFilter) {
                return this.$L('当前筛选条件下暂无任务')
            }
            switch (focus) {
                case 'overdue':
                    return this.$L('暂无已超期任务')
                case 'soon':
                    return this.$L('未来 (*) 天内暂无到期任务', 3)
                case 'hi':
                    return this.$L('暂无高优先级任务')
                case 'noowner':
                    return this.$L('所有任务均已分配负责人')
                default:
                    return this.$L('暂无任务')
            }
        },
    },

    watch: {
        scopeKey() {
            this.statsToken++
            this.statsLoading = false
            this.$emit('stats-loading', false)
            this.stats = {member_count: 0, blocks: {}, priority: [], members: []}
            this.statsLoadedAt = 0
            this.lists = {}
            this.listTokens = {}
            this.extraFilter = null
            this.memberExpand = false
            this.priorityExpand = false
            this.$emit('stats', this.stats)
            this.loadAll(true)
        },
    },

    methods: {
        deptParams() {
            const ids = (this.cacheDepartmentOwnerIds || []).map(id => parseInt(id)).filter(id => id > 0)
            return ids.length > 0 ? {department_owner_ids: ids.join(',')} : {}
        },

        loadAll(force = false) {
            const statsRequest = this.loadStats(force)
            this.loadList(1, force)
            return statsRequest
        },

        loadStats(force = false) {
            if (this.statsLoading) {
                return Promise.resolve(false)
            }
            if (!force && this.statsLoadedAt > 0 && $A.dayjs().unix() - this.statsLoadedAt < this.refreshSeconds) {
                return Promise.resolve(true)
            }
            const scopeKey = this.scopeKey
            const token = ++this.statsToken
            this.statsLoading = true
            this.$emit('stats-loading', true)
            return this.$store.dispatch("call", {
                url: 'dashboard/team/stats',
                data: Object.assign(this.deptParams(), force ? {refresh: 1} : {}),
            }).then(({data}) => {
                if (scopeKey !== this.scopeKey || token !== this.statsToken) {
                    return false
                }
                this.stats = Object.assign({member_count: 0, blocks: {}, priority: [], members: []}, data)
                this.statsLoadedAt = $A.dayjs().unix()
                this.$emit('stats', this.stats)
                return true
            }).catch(({msg}) => {
                if (scopeKey === this.scopeKey && token === this.statsToken) {
                    this.statsLoadedAt = 0
                    msg && $A.messageWarning(msg)
                }
                return false
            }).finally(() => {
                if (token === this.statsToken) {
                    this.statsLoading = false
                    this.$emit('stats-loading', false)
                }
            })
        },

        refreshAll() {
            return this.loadAll(true)
        },

        listParams(page) {
            const params = Object.assign(this.deptParams(), {page, pagesize: this.pageSize})
            if (this.extraFilter?.kind === 'member') {
                params.member_id = this.extraFilter.value
            } else if (this.extraFilter?.kind === 'level') {
                params.level = this.extraFilter.value
            } else if (this.extraFilter?.kind === 'type') {
                params.type = this.extraFilter.value
            } else {
                params.type = this.focus
            }
            return params
        },

        loadList(page = 1, force = false) {
            const key = this.currentKey
            const current = this.lists[key]
            if (current?.loading) {
                return
            }
            if (page === 1 && !force && current) {
                return
            }
            const prev = current || null
            const previousList = page > 1 || (force && prev) ? (prev?.list || []) : []
            const scopeKey = this.scopeKey
            const token = (this.listTokens[key] || 0) + 1
            this.$set(this.listTokens, key, token)
            this.$set(this.lists, key, {
                list: previousList,
                page,
                total: prev ? prev.total : 0,
                hasMore: false,
                loading: true,
                silent: force && previousList.length > 0,
                loadedAt: prev ? prev.loadedAt : 0,
            })
            this.$store.dispatch("call", {
                url: 'dashboard/team/tasks',
                data: this.listParams(page),
            }).then(({data}) => {
                if (scopeKey !== this.scopeKey || token !== this.listTokens[key]) {
                    return
                }
                const rows = data.data || []
                this.$set(this.lists, key, {
                    list: page > 1 ? [...previousList, ...rows] : rows,
                    page: data.current_page || page,
                    total: data.total || 0,
                    hasMore: (data.current_page || page) < (data.last_page || 1),
                    loading: false,
                    silent: false,
                    loadedAt: $A.dayjs().unix(),
                })
            }).catch(({msg}) => {
                if (scopeKey === this.scopeKey && token === this.listTokens[key]) {
                    this.$set(this.lists, key, {
                        list: previousList,
                        page: prev ? prev.page : 1,
                        total: prev ? prev.total : 0,
                        hasMore: prev ? prev.hasMore : false,
                        loading: false,
                        silent: false,
                        loadedAt: prev ? prev.loadedAt : 0,
                    })
                    msg && previousList.length === 0 && $A.messageWarning(msg)
                }
            })
        },

        loadMore() {
            if (!this.currentList.loading && this.currentList.hasMore) {
                this.loadList(this.currentList.page + 1)
            }
        },

        setFocus(type) {
            this.extraFilter = null
            this.focus = type
            $A.IDBSave("dashboardTeamFocus", type)
            this.$nextTick(() => this.loadList())
        },

        setFilter(kind, value, label) {
            this.extraFilter = {kind, value, label}
            this.$nextTick(() => {
                this.loadList()
                this.scrollToFocus()
            })
        },

        clearFilter() {
            this.extraFilter = null
            this.$nextTick(() => this.loadList())
        },

        onBlock(type) {
            if (type === 'uncompleted') {
                this.setFilter('type', 'uncompleted', this.$L('未完成'))
                return
            }
            this.setFocus(type)
            this.scrollToFocus()
        },

        onMember(member) {
            this.setFilter('member', member.userid, `${this.$L('成员')}: ${member.nickname}`)
        },

        onPriority(item) {
            this.setFilter('level', item.level, `${this.$L('优先级')}: ${item.name}`)
        },

        onTask(task) {
            this.$store.dispatch("openTask", task)
        },

        scrollToFocus() {
            this.focusScrollReserved = true
            this.$nextTick(() => {
                $A.scrollToView(this.$refs.focusCard, {
                    behavior: 'smooth',
                    block: 'start',
                })
            })
        },

        memberBarWidth(member) {
            return `${Math.max(Math.round(member.total / this.memberMax * 100), 4)}%`
        },

        memberProgressDetails(member) {
            const total = Math.max(member.total || 0, 1)
            return [
                {type: 'start', label: this.$L('待处理'), color: '#bfe3cd', count: member.segments.start || 0},
                {type: 'progress', label: this.$L('进行中'), color: '#1e9e55', count: member.segments.progress || 0},
                {type: 'test', label: this.$L('验收/测试'), color: '#6ba7d8', count: member.segments.test || 0},
            ].filter(item => item.count > 0).map(item => {
                return {
                    ...item,
                    percent: Math.round(item.count / total * 1000) / 10,
                }
            })
        },

        memberOverduePercent(member) {
            return member.total > 0 ? Math.round(member.overdue / member.total * 1000) / 10 : 0
        },

        memberSummary(member) {
            const total = this.$L('(*) 项', member.total)
            return member.overdue > 0 ? `${this.$L('(*) 超期', member.overdue)} · ${total}` : total
        },
    }
}
</script>
