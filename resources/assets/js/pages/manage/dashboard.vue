<template>
    <div class="page-dashboard">
        <PageTitle :title="$L('仪表盘')"/>
        <Alert v-if="warningMsg" class="dashboard-warning" type="warning" show-icon>
            <span @click="goForward({name: 'manage-setting-license'})">{{warningMsg}}</span>
        </Alert>
        <div class="dashboard-wrapper" :style="wrapperStyle">
            <div class="dashboard-hello">{{$L('欢迎您，' + userInfo.nickname)}}</div>
            <div class="dashboard-desc">
                {{$L('以下是你当前的任务统计数据')}}
                <transition name="dashboard-load">
                    <div v-if="loadDashboardTasks" class="dashboard-load"><Loading/></div>
                </transition>
            </div>
            <ul class="dashboard-block">
                <li @click="scrollTo('today')">
                    <div class="block-title">{{getTitle('today')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{dashboardTask.today_count}}</div>
                        <i class="taskfont">&#xe6f4;</i>
                    </div>
                </li>
                <li @click="scrollTo('overdue')">
                    <div class="block-title">{{getTitle('overdue')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{dashboardTask.overdue_count}}</div>
                        <i class="taskfont">&#xe603;</i>
                    </div>
                </li>
                <li @click="scrollTo('all')">
                    <div class="block-title">{{getTitle('all')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{dashboardTask.all_count}}</div>
                        <i class="taskfont">&#xe6f9;</i>
                    </div>
                </li>
            </ul>
            <Scrollbar class="dashboard-list">
                <template
                    v-for="column in columns"
                    v-if="column.list.length > 0">
                    <div :ref="`type_${column.type}`" class="dashboard-ref"></div>
                    <div class="dashboard-title">{{column.title}}</div>
                    <ul class="dashboard-ul">
                        <li
                            v-for="(item, index) in column.list"
                            :key="index"
                            :class="{complete: item.complete_at}"
                            :style="item.color ? {backgroundColor: item.color} : {}"
                            @click="openTask(item)">
                            <em
                                v-if="item.p_name"
                                class="priority-color"
                                :style="{backgroundColor:item.p_color}"></em>
                            <div class="item-select" @click.stop="openMenu($event, item)">
                                <i class="taskfont" v-html="item.complete_at ? '&#xe627;' : '&#xe625;'"></i>
                            </div>
                            <div class="item-title">
                                <!--工作流状态-->
                                <span v-if="item.flow_item_name" :class="item.flow_item_status" @click.stop="openMenu($event, item)">{{item.flow_item_name}}</span>
                                <!--是否子任务-->
                                <span v-if="item.sub_top === true">{{$L('子任务')}}</span>
                                <!--有多少个子任务-->
                                <span v-if="item.sub_my && item.sub_my.length > 0">+{{item.sub_my.length}}</span>
                                <!--任务描述-->
                                {{item.name}}
                            </div>
                            <div v-if="item.desc" class="item-icon">
                                <i class="taskfont">&#xe71a;</i>
                            </div>
                            <div v-if="item.sub_num > 0" class="item-icon">
                                <i class="taskfont">&#xe71f;</i>
                                <em>{{item.sub_complete}}/{{item.sub_num}}</em>
                            </div>
                            <ETooltip v-if="item.end_at" :disabled="$isEEUiApp || windowTouch" :content="item.end_at" placement="right">
                                <div :class="['item-icon', item.today ? 'today' : '', item.overdue ? 'overdue' : '']">
                                    <i class="taskfont">&#xe71d;</i>
                                    <em>{{expiresFormat(item.end_at)}}</em>
                                </div>
                            </ETooltip>
                        </li>
                    </ul>
                </template>
            </Scrollbar>
        </div>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import TaskMenu from "./components/TaskMenu";

export default {
    components: {TaskMenu},
    data() {
        return {
            nowTime: $A.Time(),
            nowInter: null,

            licenseTimer: null,

            loadIng: 0,
            dashboard: 'today',

            warningMsg: '',
        }
    },

    activated() {
        this.$store.dispatch("getTaskForDashboard", 600);
        this.loadInterval(true);
        this.loadLicense(true);
    },

    deactivated() {
        this.$store.dispatch("forgetTaskCompleteTemp", true);
        this.loadInterval(false);
        this.loadLicense(false);
    },

    computed: {
        ...mapState(['userInfo', 'userIsAdmin', 'cacheTasks', 'taskCompleteTemps', 'loadDashboardTasks']),

        ...mapGetters(['dashboardTask', 'assistTask', 'transforTasks']),

        routeName() {
            return this.$route.name
        },

        columns() {
            const list = [];
            ['today', 'overdue', 'all'].some(type => {
                let data = this.transforTasks(this.dashboardTask[type]);
                list.push({
                    type,
                    title: this.getTitle(type),
                    list: data.sort((a, b) => {
                        return $A.Date(a.end_at || "2099-12-31 23:59:59") - $A.Date(b.end_at || "2099-12-31 23:59:59");
                    })
                })
            })
            list.push({
                type: 'assist',
                title: this.getTitle('assist'),
                list: this.assistTask.sort((a, b) => {
                    return $A.Date(a.end_at || "2099-12-31 23:59:59") - $A.Date(b.end_at || "2099-12-31 23:59:59");
                })
            })
            return list;
        },

        total() {
            const {dashboardTask} = this;
            return dashboardTask.today_count + dashboardTask.overdue_count + dashboardTask.all_count;
        },

        wrapperStyle({warningMsg}) {
            return warningMsg ? {
                'max-height': 'calc(100% - 50px)'
            } : null
        },
    },

    watch: {
        windowActive(active) {
            if (this.routeName !== 'manage-dashboard') {
                return
            }
            this.loadInterval(active)
            this.loadLicense(active)
            if (active) {
                this.$store.dispatch("getTaskForDashboard", 600)
            }
        }
    },

    methods: {
        getTitle(type) {
            switch (type) {
                case 'today':
                    return this.$L('今日到期');
                case 'overdue':
                    return this.$L('超期任务');
                case 'all':
                    return this.$L('待完成任务');
                case 'assist':
                    return this.$L('协助的任务');
                default:
                    return '';
            }
        },

        scrollTo(type) {
            let refs = this.$refs[`type_${type}`]
            if (refs) {
                $A.scrollToView(refs[0], {
                    behavior: 'smooth',
                    inline: 'end',
                });
            }
        },

        openTask(task) {
            this.$store.dispatch("openTask", task)
        },

        openMenu(event, task) {
            this.$store.state.taskOperation = {event, task}
        },

        expiresFormat(date) {
            return $A.countDownFormat(date, this.nowTime)
        },

        loadInterval(load) {
            if (this.nowInter) {
                clearInterval(this.nowInter)
                this.nowInter = null;
            }
            if (load === false) {
                return
            }
            this.nowInter = setInterval(_ => {
                this.nowTime = $A.Time()
            }, 1000)
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
                        type: 'get'
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
