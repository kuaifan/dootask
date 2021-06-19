<template>
    <div class="page-dashboard">
        <PageTitle>{{$L('仪表板')}}</PageTitle>
        <div class="dashboard-wrapper">
            <div class="dashboard-hello">{{$L('欢迎您，' + userInfo.nickname)}}</div>
            <div class="dashboard-desc">{{$L('以下是你当前的任务统计数据')}}</div>
            <ul class="dashboard-block">
                <li @click="active='today'">
                    <div class="block-title">{{$L('今日待完成')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{projectTaskStatistics.today || '...'}}</div>
                        <i class="iconfont">&#xe6f4;</i>
                    </div>
                </li>
                <li @click="active='overdue'">
                    <div class="block-title">{{$L('超期未完成')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{projectTaskStatistics.overdue || '...'}}</div>
                        <i class="iconfont">&#xe603;</i>
                    </div>
                </li>
                <li>
                    <div class="block-title">{{$L('参与的项目')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{projectList.length}}</div>
                        <i class="iconfont">&#xe6f9;</i>
                    </div>
                </li>
            </ul>
            <div class="dashboard-title">{{getTitle}}</div>
            <ul class="dashboard-list overlay-y">
                <li v-for="item in taskList">
                    <i class="iconfont">&#xe625;</i>
                    <div class="item-title">{{item.title}}</div>
                    <div class="item-time"></div>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    data() {
        return {
            loadIng: 0,

            active: 'today',
        }
    },

    activated() {
        this.$store.dispatch("getTaskStatistics");
        this.getDashboardTask();
    },

    computed: {
        ...mapState(['userInfo', 'projectList', 'projectTaskStatistics', 'calendarTask']),

        getTitle() {
            const {active} = this;
            switch (active) {
                case 'today':
                    return this.$L('今日任务');
                case 'overdue':
                    return this.$L('超期任务');
                default:
                    return '';
            }
        },

        taskList() {
            const {calendarTask, active} = this;
            const todayStart = new Date($A.formatDate("Y-m-d 00:00:00")),
                todayEnd = new Date($A.formatDate("Y-m-d 23:59:59"));
            return calendarTask.filter((item) => {
                const start = new Date(item.start);
                const end = new Date(item.end);
                switch (active) {
                    case 'today':
                        return (start >= todayStart && start <= todayEnd) || (end >= todayStart && end <= todayEnd);
                    case 'overdue':
                        return end < todayStart;
                    default:
                        return false;
                }
            });
        }
    },

    watch: {
        active() {
            this.getDashboardTask();
        }
    },

    methods: {
        getDashboardTask() {
            let payload = {};
            switch (this.active) {
                case 'today':
                    payload = {
                        time: [
                            $A.formatDate("Y-m-d 00:00:00"),
                            $A.formatDate("Y-m-d 23:59:59")
                        ],
                    }
                    break;
                case 'overdue':
                    payload = {
                        time_before: $A.formatDate("Y-m-d 00:00:00")
                    }
                    break;
                default:
                    return;
            }
            //
            this.loadIng++;
            this.$store.dispatch("getTaskList", payload).then(({data}) => {
                this.loadIng--;
                this.$store.dispatch("saveCalendarTask", data.data)
            }).catch(() => {
                this.loadIng--;
            })
        }
    }
}
</script>
