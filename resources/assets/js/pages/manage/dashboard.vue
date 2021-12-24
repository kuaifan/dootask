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
                        <div class="block-num">{{dashboardData.today.length}}</div>
                        <i class="taskfont">&#xe6f4;</i>
                    </div>
                </li>
                <li @click="dashboard='overdue'">
                    <div class="block-title">{{$L('超期未完成')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{dashboardData.overdue.length}}</div>
                        <i class="taskfont">&#xe603;</i>
                    </div>
                </li>
                <li>
                    <div class="block-title">{{$L('参与的项目')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{projects.length}}</div>
                        <i class="taskfont">&#xe6f9;</i>
                    </div>
                </li>
            </ul>
            <template v-if="list.length > 0">
                <div class="dashboard-title">{{title}}</div>
                <ul class="dashboard-list overlay-y">
                    <li
                        v-for="item in list"
                        :key="item.id"
                        :class="{complete: item.complete_at}"
                        :style="item.color ? {backgroundColor: item.color} : {}"
                        @click="$store.dispatch('openTask', item.id)">
                        <em
                            v-if="item.p_name && item.parent_id === 0"
                            class="priority-color"
                            :style="{backgroundColor:item.p_color}"></em>
                        <EDropdown
                            trigger="click"
                            size="small"
                            placement="bottom"
                            @command="dropTask(item, $event)">
                            <div class="drop-icon" @click.stop="">
                                <i class="taskfont" v-html="item.complete_at ? '&#xe627;' : '&#xe625;'"></i>
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
                                            <i class="taskfont" :style="{color:c.color||'#f9f9f9'}" v-html="c.color == item.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                        </div>
                                    </EDropdownItem>
                                </template>
                            </EDropdownMenu>
                        </EDropdown>
                        <div class="item-title">{{item.name}}</div>
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
        <AppDown/>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import AppDown from "../../components/AppDown";

export default {
    components: {AppDown},
    data() {
        return {
            nowTime: $A.Time(),
            nowInterval: null,

            loadIng: 0,
            dashboard: 'today',

            taskLoad: {},

            completeTask: [],
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
        this.$store.dispatch("getDashboardTasks");
    },

    computed: {
        ...mapState(['userInfo', 'projects', 'tasks', 'taskId']),

        ...mapGetters(['dashboardData']),

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
            const {dashboard, completeTask} = this;
            let data = [];
            switch (dashboard) {
                case 'today':
                    data = $A.cloneJSON(this.dashboardData.today);
                    break
                case 'overdue':
                    data = $A.cloneJSON(this.dashboardData.overdue);
                    break
            }
            if (completeTask.length > 0) {
                completeTask.forEach(task => {
                    if (!data.find(({id}) => id == task.id)) {
                        data.push(task);
                    }
                })
            }
            return data.sort((a, b) => {
                return $A.Date(a.end_at) - $A.Date(b.end_at);
            });
        },

        expiresFormat() {
            const {nowTime} = this;
            return function (date) {
                let time = Math.round($A.Date(date).getTime() / 1000) - nowTime;
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
        '$route'() {
            this.completeTask = [];
        },
        dashboard() {
            this.completeTask = [];
        }
    },

    methods: {
        dropTask(task, command) {
            switch (command) {
                case 'complete':
                    if (task.complete_at) return;
                    this.updateTask(task, {
                        complete_at: $A.formatDate("Y-m-d H:i:s")
                    }).then(() => {
                        this.completeTask.push(task)
                    })
                    break;
                case 'uncomplete':
                    if (!task.complete_at) return;
                    this.updateTask(task, {
                        complete_at: false
                    }).then(() => {
                        let index = this.completeTask.findIndex(({id}) => id == task.id)
                        if (index > -1) {
                            this.completeTask.splice(index, 1)
                        }
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
            return new Promise((resolve, reject) => {
                if (this.taskLoad[task.id] === true) {
                    reject()
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
                    resolve()
                }).catch(({msg}) => {
                    $A.modalError(msg);
                    this.$set(this.taskLoad, task.id, false);
                    this.$store.dispatch("getTaskOne", task.id);
                    reject();
                });
            })
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
            let time = Math.round($A.Date(date).getTime() / 1000),
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
