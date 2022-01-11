<template>
    <div class="page-dashboard">
        <PageTitle :title="$L('仪表盘')"/>
        <div class="dashboard-wrapper">
            <div class="dashboard-hello">{{$L('欢迎您，' + userInfo.nickname)}}</div>
            <div class="dashboard-desc">{{$L('以下是你当前的任务统计数据')}}</div>
            <ul class="dashboard-block">
                <li @click="dashboard='today'">
                    <div class="block-title">{{$L('今日待完成')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{dashboardTask.today.length}}</div>
                        <i class="taskfont">&#xe6f4;</i>
                    </div>
                </li>
                <li @click="dashboard='overdue'">
                    <div class="block-title">{{$L('超期未完成')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{dashboardTask.overdue.length}}</div>
                        <i class="taskfont">&#xe603;</i>
                    </div>
                </li>
                <li>
                    <div class="block-title">{{$L('参与的项目')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{cacheProjects.length}}</div>
                        <i class="taskfont">&#xe6f9;</i>
                    </div>
                </li>
            </ul>
            <template v-if="list.length > 0">
                <div class="dashboard-title">{{title}}</div>
                <ul class="dashboard-list overlay-y">
                    <li
                        v-for="(item, index) in list"
                        :key="index"
                        :class="{complete: item.complete_at}"
                        :style="item.color ? {backgroundColor: item.color} : {}"
                        @click="openTask(item)">
                        <em
                            v-if="item.p_name"
                            class="priority-color"
                            :style="{backgroundColor:item.p_color}"></em>
                        <TaskMenu :ref="`taskMenu_${item.id}`" :task="item">
                            <div slot="icon" class="drop-icon" @click.stop="">
                                <i class="taskfont" v-html="item.complete_at ? '&#xe627;' : '&#xe625;'"></i>
                            </div>
                        </TaskMenu>
                        <div class="item-title">
                            <!--工作流状态-->
                            <span v-if="item.flow_item_name" :class="item.flow_item_status" @click.stop="openMenu(item)">{{item.flow_item_name}}</span>
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
                        <div :class="['item-icon', item.today ? 'today' : '', item.overdue ? 'overdue' : '']">
                            <i class="taskfont">&#xe71d;</i>
                            <em>{{expiresFormat(item.end_at)}}</em>
                        </div>
                    </li>
                </ul>
            </template>
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
        ...mapState(['userInfo', 'cacheProjects', 'taskId']),

        ...mapGetters(['dashboardTask', 'transforTasks']),

        title() {
            const {dashboard} = this;
            switch (dashboard) {
                case 'today':
                    return this.$L('今日任务');
                case 'overdue':
                    return this.$L('超期任务');
                default:
                    return '';
            }
        },

        list() {
            const {dashboard} = this;
            let data = [];
            switch (dashboard) {
                case 'today':
                    data = this.transforTasks(this.dashboardTask.today);
                    break
                case 'overdue':
                    data = this.transforTasks(this.dashboardTask.overdue);
                    break
            }
            return data.sort((a, b) => {
                return $A.Date(a.end_at) - $A.Date(b.end_at);
            });
        },
    },

    methods: {
        openTask(task) {
            this.$store.dispatch("openTask", task)
        },

        openMenu(task) {
            const el = this.$refs[`taskMenu_${task.id}`];
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
