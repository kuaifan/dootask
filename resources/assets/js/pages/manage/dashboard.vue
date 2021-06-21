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
                        <div class="block-num">{{projectStatistics.today || 0}}</div>
                        <i class="iconfont">&#xe6f4;</i>
                    </div>
                </li>
                <li @click="dashboard='overdue'">
                    <div class="block-title">{{$L('超期未完成')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{projectStatistics.overdue || 0}}</div>
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
            <template v-if="list.length > 0">
                <div class="dashboard-title">{{title}}</div>
                <ul class="dashboard-list overlay-y">
                    <li v-for="item in list" :key="item.id" :style="item.color ? {backgroundColor: item.color} : {}">
                        <EDropdown
                            trigger="click"
                            size="small"
                            placement="bottom"
                            @command="dropTask(item, $event)">
                            <div class="drop-icon">
                                <i class="iconfont">&#xe625;</i>
                            </div>
                            <EDropdownMenu slot="dropdown" class="project-list-more-dropdown-menu">
                                <EDropdownItem v-if="item.complete_at" command="uncomplete">
                                    <div class="item red">
                                        <Icon type="md-checkmark-circle-outline" />{{$L('标记未完成')}}
                                    </div>
                                </EDropdownItem>
                                <EDropdownItem v-else command="complete">
                                    <div class="item">
                                        <Icon type="md-radio-button-off" />{{$L('完成')}}
                                    </div>
                                </EDropdownItem>
                                <EDropdownItem v-if="item.parent_id === 0" command="archived">
                                    <div class="item">
                                        <Icon type="ios-filing" />{{$L('归档')}}
                                    </div>
                                </EDropdownItem>
                                <EDropdownItem command="remove">
                                    <div class="item">
                                        <Icon type="md-trash" />{{$L('删除')}}
                                    </div>
                                </EDropdownItem>
                                <template v-if="item.parent_id === 0">
                                    <EDropdownItem v-if="item.parent_id === 0" divided disabled>{{$L('背景色')}}</EDropdownItem>
                                    <EDropdownItem v-for="(c, k) in $store.state.taskColorList" :key="k" :command="c">
                                        <div class="item">
                                            <i class="iconfont" :style="{color:c.color||'#f9f9f9'}" v-html="c.color == item.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                        </div>
                                    </EDropdownItem>
                                </template>
                            </EDropdownMenu>
                        </EDropdown>
                        <div class="item-title" @click="$store.dispatch('openTask', item.id)">{{item.name}}</div>
                        <div :class="['item-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']">
                            <Icon type="ios-time-outline"/>{{expiresFormat(item.end_at)}}
                        </div>
                    </li>
                </ul>
            </template>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    data() {
        return {
            nowTime: Math.round(new Date().getTime() / 1000),
            nowInterval: null,

            loadIng: 0,
            active: false,
            dashboard: 'today',

            taskLoad: {}
        }
    },

    mounted() {
        this.nowInterval = setInterval(() => {
            this.nowTime = Math.round(new Date().getTime() / 1000);
        }, 1000)
    },

    destroyed() {
        clearInterval(this.nowInterval)
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
            const {dashboard} = this;
            const todayStart = new Date($A.formatDate("Y-m-d 00:00:00")),
                todayEnd = new Date($A.formatDate("Y-m-d 23:59:59"));
            let datas = $A.cloneJSON(this.tasks);
            datas = datas.filter((data) => {
                if (data.parent_id > 0) {
                    return false;
                }
                if (data.complete_at) {
                    return false;
                }
                if (!data.end_at) {
                    return false;
                }
                if (!data.owner) {
                    return false;
                }
                const start = new Date(data.start_at),
                    end = new Date(data.end_at);
                data._start_time = start;
                data._end_time = end;
                switch (dashboard) {
                    case 'today':
                        return (start >= todayStart && start <= todayEnd) || (end >= todayStart && end <= todayEnd);
                    case 'overdue':
                        return end <= todayStart;
                    default:
                        return false;
                }
            })
            return datas.sort((a, b) => {
                if (a._end_time != b._end_time) {
                    return a._end_time - b._end_time;
                }
                return a.id - b.id;
            });
        },

        expiresFormat() {
            const {nowTime} = this;
            return function (date) {
                let time = Math.round(new Date(date).getTime() / 1000) - nowTime;
                if (time < 86400 * 4 && time > 0 ) {
                    return this.formatSeconds(time);
                } else if (time <= 0) {
                    return '-' + this.formatSeconds(time * -1);
                }
                return this.formatTime(date)
            }
        },
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
        },

        dropTask(task, command) {
            switch (command) {
                case 'complete':
                    if (task.complete_at) return;
                    this.updateTask(task, {
                        complete_at: $A.formatDate("Y-m-d H:i:s")
                    })
                    break;
                case 'uncomplete':
                    if (!task.complete_at) return;
                    this.updateTask(task, {
                        complete_at: false
                    })
                    break;
                case 'archived':
                case 'remove':
                    this.archivedOrRemoveTask(task, command);
                    break;
                default:
                    if (command.name) {
                        this.updateTask(task, {
                            color: command.color
                        })
                    }
                    break;
            }
        },

        updateTask(task, updata) {
            if (this.taskLoad[task.id] === true) {
                return;
            }
            this.$set(this.taskLoad, task.id, true);
            //
            Object.keys(updata).forEach(key => this.$set(task, key, updata[key]));
            //
            this.$store.dispatch("taskUpdate", Object.assign(updata, {
                task_id: task.id,
            })).then(() => {
                this.$set(this.taskLoad, task.id, false);
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.$set(this.taskLoad, task.id, false);
                this.$store.dispatch("getTaskOne", task.id);
            });
        },

        archivedOrRemoveTask(task, type) {
            let typeDispatch = type == 'remove' ? 'removeTask' : 'archivedTask';
            let typeName = type == 'remove' ? '删除' : '归档';
            let typeTask = task.parent_id > 0 ? '子任务' : '任务';
            $A.modalConfirm({
                title: typeName + typeTask,
                content: '你确定要' + typeName + typeTask + '【' + task.name + '】吗？',
                loading: true,
                onOk: () => {
                    if (this.taskLoad[task.id] === true) {
                        this.$Modal.remove();
                        return;
                    }
                    this.$set(this.taskLoad, task.id, true);
                    this.$store.dispatch(typeDispatch, task.id).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                        this.$set(this.taskLoad, task.id, false);
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$Modal.remove();
                        this.$set(this.taskLoad, task.id, false);
                    });
                }
            });
        },

        formatTime(date) {
            let time = Math.round(new Date(date).getTime() / 1000),
                string = '';
            if ($A.formatDate('Ymd') === $A.formatDate('Ymd', time)) {
                string = $A.formatDate('H:i', time)
            } else if ($A.formatDate('Y') === $A.formatDate('Y', time)) {
                string = $A.formatDate('m-d', time)
            } else {
                string = $A.formatDate('Y-m-d', time)
            }
            return string || '';
        },

        formatBit(val) {
            val = +val
            return val > 9 ? val : '0' + val
        },

        formatSeconds(second) {
            let duration
            let days = Math.floor(second / 86400);
            let hours = Math.floor((second % 86400) / 3600);
            let minutes = Math.floor(((second % 86400) % 3600) / 60);
            let seconds = Math.floor(((second % 86400) % 3600) % 60);
            if (days > 0) {
                if (hours > 0) duration = days + "d," + this.formatBit(hours) + "h";
                else if (minutes > 0) duration = days + "d," + this.formatBit(minutes) + "min";
                else if (seconds > 0) duration = days + "d," + this.formatBit(seconds) + "s";
                else duration = days + "d";
            }
            else if (hours > 0) duration = this.formatBit(hours) + ":" + this.formatBit(minutes) + ":" + this.formatBit(seconds);
            else if (minutes > 0) duration = this.formatBit(minutes) + ":" + this.formatBit(seconds);
            else if (seconds > 0) duration = this.formatBit(seconds) + "s";
            return duration;
        },
    }
}
</script>
