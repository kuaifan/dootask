<template>
    <div class="task-rows">
        <div v-for="(item, key) in list" :key="key">
            <Row class="task-row" :style="item.color ? {backgroundColor: item.color, borderBottomColor: item.color} : {}">
                <em v-if="item.p_name && item.parent_id === 0" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                <Col span="12" :class="['row-name', item.complete_at ? 'complete' : '']">
                    <Icon
                        v-if="item.sub_num > 0 || (item.parent_id===0 && fastAddTask)"
                        :class="['sub-icon', taskOpen[item.id] ? 'active' : '']"
                        type="ios-arrow-forward"
                        @click="getSublist(item)"/>
                    <EDropdown
                        trigger="click"
                        size="small"
                        @command="dropTask(item, $event)">
                        <div class="drop-icon">
                            <Icon v-if="item.complete_at" class="completed" type="md-checkmark-circle" />
                            <Icon v-else type="md-radio-button-off" />
                            <div v-if="taskLoad[item.id] === true" class="loading"><Loading /></div>
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
                                <EDropdownItem v-for="(c, k) in colorList" :key="k" :command="c">
                                    <div class="item">
                                        <i class="iconfont" :style="{color:c.color||'#f9f9f9'}" v-html="c.color == item.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                    </div>
                                </EDropdownItem>
                            </template>
                        </EDropdownMenu>
                    </EDropdown>
                    <div class="item-title" @click="openTask(item)">{{item.name}}</div>
                    <div class="item-icons" @click="openTask(item)">
                        <div v-if="item.file_num > 0" class="item-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                        <div v-if="item.msg_num > 0" class="item-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                    </div>
                    <div v-if="item.sub_num > 0" class="item-sub-num" @click="getSublist(item)">
                        <Icon type="md-git-merge" />
                        {{item.sub_complete}}/{{item.sub_num}}
                    </div>
                </Col>
                <Col span="3" class="row-column">
                    <div v-if="item.parent_id === 0" class="task-column">{{item.column_name}}</div>
                </Col>
                <Col span="3" class="row-priority">
                    <TaskPriority v-if="item.p_name && item.parent_id === 0" :backgroundColor="item.p_color">{{item.p_name}}</TaskPriority>
                </Col>
                <Col span="3" class="row-user">
                    <ul>
                        <li v-for="(user, keyu) in item.task_user" :key="keyu" v-if="keyu < 3">
                            <UserAvatar :userid="user.userid" size="32" :borderWitdh="2" :borderColor="item.color"/>
                        </li>
                    </ul>
                </Col>
                <Col span="3" class="row-time">
                    <ETooltip
                        v-if="!item.complete_at && item.end_at"
                        :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                        :open-delay="600"
                        :content="item.end_at">
                        <div>{{expiresFormat(item.end_at)}}</div>
                    </ETooltip>
                </Col>
            </Row>
            <TaskRow
                v-if="taskOpen[item.id]===true"
                :list="subTask(item.id)"
                :parent-id="item.id"
                :fast-add-task="item.parent_id===0 && fastAddTask"
                :color-list="colorList"
                :open-key="openKey"
                @command="dropTask"/>
        </div>
        <TaskAddSimple v-if="fastAddTask" :parent-id="parentId" row-mode/>
    </div>
</template>

<script>
import TaskPriority from "./TaskPriority";
import TaskAddSimple from "./TaskAddSimple";
import {mapState} from "vuex";

export default {
    name: "TaskRow",
    components: {TaskAddSimple, TaskPriority},
    props: {
        list: {
            type: Array,
            default: () => {
                return [];
            }
        },
        colorList: {
            type: Array,
            default: () => {
                return [];
            }
        },
        parentId: {
            type: Number,
            default: 0
        },
        fastAddTask: {
            type: Boolean,
            default: false
        },
        openKey: {
            type: String,
            default: 'default'
        },
    },
    data() {
        return {
            nowTime: Math.round(new Date().getTime() / 1000),
            nowInterval: null,

            taskLoad: {},
            taskOpen: {}
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

    computed: {
        ...mapState(['tasks']),

        subTask() {
            return function(task_id) {
                return this.tasks.filter(({parent_id}) => parent_id == task_id);
            }
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
    methods: {
        dropTask(task, command) {
            this.$emit("command", task, command)
        },

        getSublist(task) {
            if (this.taskOpen[task.id] === true) {
                this.$set(this.taskOpen, task.id, false);
                return;
            }
            if (this.taskLoad[task.id] === true) {
                return;
            }
            this.$set(this.taskLoad, task.id, true);
            //
            this.$store.dispatch("getTasks", {
                parent_id: task.id
            }).then(() => {
                this.$set(this.taskLoad, task.id, false);
                this.$set(this.taskOpen, task.id, true);
            }).catch(({msg}) => {
                this.$set(this.taskLoad, task.id, false);
                $A.modalError(msg);
            });
        },

        openTask(task) {
            if (task.parent_id > 0) {
                this.$store.dispatch("openTask", task.parent_id)
            } else {
                this.$store.dispatch("openTask", task.id)
            }
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
        }
    }
}
</script>
