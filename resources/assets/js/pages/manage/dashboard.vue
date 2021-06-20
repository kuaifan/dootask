<template>
    <div class="page-dashboard">
        <PageTitle>{{$L('仪表板')}}</PageTitle>
        <div class="dashboard-wrapper">
            <div class="dashboard-hello">{{$L('欢迎您，' + userInfo.nickname)}}</div>
            <div class="dashboard-desc">{{$L('以下是你当前的任务统计数据')}}</div>
            <ul class="dashboard-block">
                <li @click="dashboard='today'">
                    <div class="block-title">{{$L('今日待完成')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{projectStatistics.today || '...'}}</div>
                        <i class="iconfont">&#xe6f4;</i>
                    </div>
                </li>
                <li @click="dashboard='overdue'">
                    <div class="block-title">{{$L('超期未完成')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{projectStatistics.overdue || '...'}}</div>
                        <i class="iconfont">&#xe603;</i>
                    </div>
                </li>
                <li>
                    <div class="block-title">{{$L('参与的项目')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{projects.length}}</div>
                        <i class="iconfont">&#xe6f9;</i>
                    </div>
                </li>
            </ul>
            <div class="dashboard-title">{{title}}</div>
            <ul class="dashboard-list overlay-y">
                <li v-for="item in list" :key="item.id" @click="$store.dispatch('openTask', item.id)">
                    <i class="iconfont">&#xe625;</i>
                    <div class="item-title">{{item.name}}</div>
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
            active: false,
            dashboard: 'today',
        }
    },

    activated() {
        this.getTask();
        this.active = true;
        this.$store.dispatch("getProjectStatistics");
    },

    deactivated() {
        this.active = false;
    },

    computed: {
        ...mapState(['userInfo', 'projects', 'projectStatistics', 'tasks', 'taskId']),

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
            const {tasks, dashboard} = this;
            const todayStart = new Date($A.formatDate("Y-m-d 00:00:00")),
                todayEnd = new Date($A.formatDate("Y-m-d 23:59:59"));
            return tasks.filter((data) => {
                const start = new Date(data.start_at),
                    end = new Date(data.end_at);
                if (data.parent_id > 0) {
                    return false;
                }
                if (data.complete_at) {
                    return false;
                }
                switch (dashboard) {
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
        dashboard() {
            this.getTask();
        },

        taskId(id) {
            if (id == 0 && this.active) {
                this.$store.dispatch("getProjectStatistics");
            }
        }
    },

    methods: {
        getTask() {
            let data = {};
            switch (this.dashboard) {
                case 'today':
                    data = {
                        time: [
                            $A.formatDate("Y-m-d 00:00:00"),
                            $A.formatDate("Y-m-d 23:59:59")
                        ],
                    }
                    break;
                case 'overdue':
                    data = {
                        time_before: $A.formatDate("Y-m-d 00:00:00")
                    }
                    break;
                default:
                    return;
            }
            //
            this.loadIng++;
            this.$store.dispatch("getTasks", data).then(() => {
                this.loadIng--;
            }).catch(() => {
                this.loadIng--;
            })
        }
    }
}
</script>
