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
                <li @click="dashboard='all'">
                    <div class="block-title">{{$L('待完成任务')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{dashboardTask.all.length}}</div>
                        <i class="taskfont">&#xe6f9;</i>
                    </div>
                </li>
            </ul>
            <Tabs v-model="dashboard" style="margin-top: 50px;">
                <TabPane  :label="$L('今日待完成')" name="today" @click="dashboard='today'">
                    <TaskDashboard :list="list" />
                </TabPane>
                <TabPane :label="$L('超期未完成')" name="overdue" @click="dashboard='overdue'">
                    <TaskDashboard :list="list" />
                </TabPane>
                <TabPane :label="$L('待完成任务')" name="all" @click="dashboard='all'">
                    <TaskDashboard :list="list" />
                </TabPane>
            </Tabs>
        </div>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import TaskDashboard from "./components/TaskDashboard";

export default {
    components: {TaskDashboard},
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
                case 'all':
                    return this.$L('待完成任务');
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
                case 'all':
                    data = this.transforTasks(this.dashboardTask.all);
                    break
            }
            return data.sort((a, b) => {
                return $A.Date(a.end_at || "2099-12-31 23:59:59") - $A.Date(b.end_at || "2099-12-31 23:59:59");
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
