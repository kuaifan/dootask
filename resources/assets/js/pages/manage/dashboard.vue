<template>
    <div class="page-dashboard">
        <PageTitle :title="$L('仪表盘')"/>
        <div class="dashboard-wrapper">
            <div class="dashboard-hello">{{$L('欢迎您，' + userInfo.nickname)}}</div>
            <div class="dashboard-desc">{{$L('以下是你当前的任务统计数据')}}</div>
            <ul class="dashboard-block">
                <li @click="scrollTo('today')">
                    <div class="block-title">{{getTitle('today')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{dashboardTask.today.length}}</div>
                        <i class="taskfont">&#xe6f4;</i>
                    </div>
                </li>
                <li @click="scrollTo('overdue')">
                    <div class="block-title">{{getTitle('overdue')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{dashboardTask.overdue.length}}</div>
                        <i class="taskfont">&#xe603;</i>
                    </div>
                </li>
                <li @click="scrollTo('all')">
                    <div class="block-title">{{getTitle('all')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{dashboardTask.all.length}}</div>
                        <i class="taskfont">&#xe6f9;</i>
                    </div>
                </li>
            </ul>
            <div class="dashboard-list overlay-y">
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
                            <TaskMenu :ref="`taskMenu_${column.type}_${item.id}`" :task="item">
                                <div slot="icon" class="drop-icon" @click.stop="">
                                    <i class="taskfont" v-html="item.complete_at ? '&#xe627;' : '&#xe625;'"></i>
                                </div>
                            </TaskMenu>
                            <div class="item-title">
                                <!--工作流状态-->
                                <span v-if="item.flow_item_name" :class="item.flow_item_status" @click.stop="openMenu(column.type, item)">{{item.flow_item_name}}</span>
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
                            <ETooltip v-if="item.end_at" :content="item.end_at" placement="right">
                                <div :class="['item-icon', item.today ? 'today' : '', item.overdue ? 'overdue' : '']">
                                    <i class="taskfont">&#xe71d;</i>
                                    <em>{{expiresFormat(item.end_at)}}</em>
                                </div>
                            </ETooltip>
                        </li>
                    </ul>
                </template>
            </div>
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
            nowInterval: null,

            loadIng: 0,
            dashboard: 'today',
        }
    },

    mounted() {
        this.nowInterval = setInterval(() => {
            this.nowTime = $A.Time();
        }, 1000)
    },

    destroyed() {
        clearInterval(this.nowInterval)
    },

    activated() {
        this.$store.dispatch("getTaskForDashboard");
    },

    deactivated() {
        this.$store.dispatch("forgetTaskCompleteTemp", true);
    },

    computed: {
        ...mapState(['userInfo']),

        ...mapGetters(['dashboardTask', 'transforTasks']),

        columns() {
            let list = [];
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
            return list;
        },

        total() {
            const {dashboardTask} = this;
            return dashboardTask.today.length + dashboardTask.overdue.length + dashboardTask.all.length;
        },
    },

    methods: {
        getTitle(type) {
            switch (type) {
                case 'today':
                    return this.$L('今日任务');
                case 'overdue':
                    return this.$L('超期任务');
                case 'all':
                    return this.$L('待完成任务');
                default:
                    return '';
            }
        },

        scrollTo(type) {
            $A.scrollToView(this.$refs[`type_${type}`][0], {
                behavior: 'smooth',
                inline: 'end',
            });
        },

        openTask(task) {
            this.$store.dispatch("openTask", task)
        },

        openMenu(type, task) {
            const el = this.$refs[`taskMenu_${type}_${task.id}`];
            if (el) {
                el[0].handleClick()
            }
        },

        expiresFormat(date) {
            return $A.countDownFormat(date, this.nowTime)
        },
    }
}
</script>
