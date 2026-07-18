<template>
    <div class="dashboard-team">
        <!--核心指标-->
        <ul class="dashboard-cards">
            <li @click="onBlock('uncompleted')">
                <div class="card-label">{{$L('未完成')}}</div>
                <div class="card-data">
                    <span class="card-num">{{blocks.uncompleted || 0}}</span>
                    <span class="card-sub">{{$L('项')}}</span>
                </div>
                <div class="card-link">{{$L('查看任务')}} →</div>
            </li>
            <li @click="onBlock('overdue')">
                <div class="card-label">{{$L('已超期')}}</div>
                <div class="card-data">
                    <span class="card-num num-red">{{blocks.overdue || 0}}</span>
                    <span class="card-sub">{{$L('项')}}<template v-if="blocks.overdue_owner_count"> · {{$L('涉及 (*) 人', blocks.overdue_owner_count)}}</template></span>
                </div>
                <div class="card-link link-red">{{$L('查看任务')}} →</div>
            </li>
            <li @click="onBlock('soon')">
                <div class="card-label">{{$L('3 天内到期')}}</div>
                <div class="card-data">
                    <span class="card-num num-orange">{{blocks.due_soon || 0}}</span>
                    <span class="card-sub">{{$L('项')}}</span>
                </div>
                <div class="card-link link-orange">{{$L('查看任务')}} →</div>
            </li>
            <li class="card-static">
                <div class="card-label">{{$L('本周完成')}}</div>
                <div class="card-data">
                    <span class="card-num num-green">{{blocks.week_completed || 0}}</span>
                    <span class="card-sub">{{$L('项')}}<template v-if="weekDiffText"> · {{weekDiffText}}</template></span>
                </div>
            </li>
        </ul>
        <!--成员任务分配 + 优先级分布-->
        <div class="team-duo">
            <div class="dashboard-card team-members">
                <div class="card-head">
                    <span class="head-title">{{$L('成员任务分配')}}</span>
                    <span class="head-legend">
                        <span><i style="background:#1e9e55"></i>{{$L('进行中')}}</span>
                        <span><i style="background:#bfe3cd"></i>{{$L('待处理')}}</span>
                        <span v-if="hasTestSegment"><i style="background:#6ba7d8"></i>{{$L('待测试')}}</span>
                        <span><i style="background:#d94f46"></i>{{$L('已超期')}}</span>
                    </span>
                </div>
                <ul class="member-list">
                    <li v-for="member in visibleMembers" :key="member.nickname" class="member-row" @click="onMember(member)">
                        <span class="mock-avatar" :style="{backgroundColor: member.color}">{{member.nickname.substring(0, 1)}}</span>
                        <span class="member-name">{{member.nickname}}</span>
                        <span class="member-track">
                            <span class="member-bar" :style="{width: memberBarWidth(member)}">
                                <i v-if="member.segments.progress > 0" :style="{flex: member.segments.progress, background: '#1e9e55'}"></i>
                                <i v-if="member.segments.start > 0" :style="{flex: member.segments.start, background: '#bfe3cd'}"></i>
                                <i v-if="member.segments.test > 0" :style="{flex: member.segments.test, background: '#6ba7d8'}"></i>
                                <i v-if="member.overdue > 0" :style="{flex: member.overdue, background: '#d94f46'}"></i>
                            </span>
                        </span>
                        <span class="member-num">
                            <em v-if="member.overdue > 0" class="num-overdue">{{member.overdue}} {{$L('超期')}} · </em>{{$L('(*) 项', member.total)}}
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
            </div>
            <div class="dashboard-card team-priority">
                <div class="card-head">
                    <span class="head-title">{{$L('优先级分布')}}</span>
                    <span class="head-note">{{$L('未完成 (*) 项', blocks.uncompleted || 0)}}</span>
                </div>
                <div class="priority-list">
                    <div v-for="item in priorityList" :key="item.level" class="priority-row" @click="onPriority(item)">
                        <div class="priority-head">
                            <span class="priority-name">{{item.name}}</span>
                            <span class="priority-num">{{$L('(*) 项', item.num)}} · {{item.percent}}%</span>
                        </div>
                        <div class="priority-bar">
                            <i :style="{width: item.percent + '%', background: item.color}"></i>
                        </div>
                    </div>
                    <div class="priority-tip">{{$L('点击任一档位可下钻到任务列表')}}</div>
                </div>
            </div>
        </div>
        <!--重点关注任务-->
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
                            <span class="mock-avatar" :style="{backgroundColor: item.owner.color}">{{item.owner.nickname.substring(0, 1)}}</span>
                            <span class="owner-name">{{item.owner.nickname}}</span>
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
                <div v-if="currentList.loading" class="focus-loading"><Loading/></div>
                <div v-else-if="currentList.list.length === 0" class="focus-empty">{{$L('暂无任务')}}</div>
                <div v-else-if="currentList.hasMore" class="card-more" @click="loadMore">{{$L('加载更多')}}（{{$L('还有 (*) 项', currentList.total - currentList.list.length)}}）</div>
                <div v-else-if="currentList.total > pageSize" class="card-more more-done">{{$L('全部显示完毕')}}</div>
            </div>
        </div>
    </div>
</template>

<script>
import {fetchTeamStats, fetchTeamTasks} from "./dashboard-team-mock";
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

            focus: ['overdue', 'soon', 'hi', 'noowner'].includes(this.initialFocus) ? this.initialFocus : 'overdue',
            extraFilter: null,

            lists: {},
            pageSize: 20,

            memberLimit: 6,
            memberExpand: false,
        }
    },

    created() {
        this.loadStats()
        this.loadList()
    },

    activated() {
        this.loadInterval(true)
    },

    deactivated() {
        this.loadInterval(false)
    },

    beforeDestroy() {
        this.loadInterval(false)
    },

    computed: {
        blocks({stats}) {
            return stats.blocks || {}
        },

        members({stats}) {
            return stats.members || []
        },

        visibleMembers({members, memberExpand, memberLimit}) {
            return memberExpand ? members : members.slice(0, memberLimit)
        },

        hasTestSegment({members}) {
            return members.some(member => (member.segments?.test || 0) > 0)
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
                {type: 'soon', label: this.$L('3 天内到期'), num: blocks.due_soon || 0},
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

        currentKey({focus, extraFilter}) {
            if (extraFilter) {
                return `${extraFilter.kind}:${extraFilter.value}`
            }
            return `type:${focus}`
        },

        currentList({lists, currentKey}) {
            return lists[currentKey] || {list: [], page: 1, total: 0, hasMore: false, loading: true}
        },
    },

    methods: {
        loadStats() {
            fetchTeamStats().then(stats => {
                this.stats = stats
                this.$emit('stats', stats)
            })
        },

        listParams(page) {
            const params = {page}
            if (this.extraFilter?.kind === 'member') {
                params.member = this.extraFilter.value
            } else if (this.extraFilter?.kind === 'level') {
                params.level = this.extraFilter.value
            } else {
                params.type = this.focus
            }
            return params
        },

        loadList(page = 1) {
            const key = this.currentKey
            if (page === 1 && this.lists[key] && !this.lists[key].loading) {
                return
            }
            const prev = page > 1 ? this.lists[key] : null
            this.$set(this.lists, key, {
                list: prev ? prev.list : [],
                page,
                total: prev ? prev.total : 0,
                hasMore: false,
                loading: true,
            })
            fetchTeamTasks(this.listParams(page)).then(data => {
                this.$set(this.lists, key, {
                    list: prev ? [...prev.list, ...data.list] : data.list,
                    page: data.page,
                    total: data.total,
                    hasMore: data.hasMore,
                    loading: false,
                })
            })
        },

        loadMore() {
            this.loadList(this.currentList.page + 1)
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
        },

        onBlock(type) {
            if (type === 'uncompleted') {
                this.scrollToFocus()
                return
            }
            this.setFocus(type)
            this.scrollToFocus()
        },

        onMember(member) {
            this.setFilter('member', member.nickname, `${this.$L('成员')}: ${member.nickname}`)
        },

        onPriority(item) {
            this.setFilter('level', item.level, `${this.$L('优先级')}: ${item.name}`)
        },

        onTask() {
            $A.messageInfo("演示数据，接口对接后可打开任务详情")
        },

        scrollToFocus() {
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
    }
}
</script>
