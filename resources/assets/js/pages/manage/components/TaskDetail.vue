<template>
    <div :class="{'task-detail':true, 'open-dialog': taskDetail._dialog || taskDetail._msgText, 'completed': taskDetail.complete_at}">
        <div class="task-info">
            <div class="head">
                <Icon v-if="taskDetail.complete_at" class="icon completed" type="md-checkmark-circle" @click="updateData('uncomplete')"/>
                <Icon v-else class="icon" type="md-radio-button-off" @click="updateData('complete')"/>
                <div class="nav">
                    <p v-if="taskDetail.project_name">{{taskDetail.project_name}}</p>
                    <p v-if="taskDetail.column_name">{{taskDetail.column_name}}</p>
                    <p v-if="taskDetail.id">{{taskDetail.id}}</p>
                </div>
                <Icon class="menu" type="ios-more"/>
            </div>
            <div class="scroller overlay-y" :style="scrollerStyle">
                <div class="title">
                    <Input
                        v-model="taskDetail.name"
                        type="textarea"
                        :rows="1"
                        :autosize="{ minRows: 1, maxRows: 8 }"
                        :maxlength="255"
                        @on-blur="updateData('name')"/>
                </div>
                <div class="desc">
                    <TEditor
                        v-model="taskDetail.content"
                        :plugins="taskPlugins"
                        :options="taskOptions"
                        :option-full="taskOptionFull"
                        :placeholder="$L('详细描述...')"
                        @on-blur="updateData('content')"
                        inline></TEditor>
                </div>
                <Form class="items" label-position="left" label-width="auto" @submit.native.prevent>
                    <FormItem v-if="taskDetail.p_name">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6ec;</i>{{$L('优先级')}}
                        </div>
                        <ul class="item-content">
                            <li>
                                <EDropdown
                                    trigger="click"
                                    placement="bottom-start"
                                    @command="updateData('priority', $event)">
                                    <TaskPriority :backgroundColor="taskDetail.p_color">{{taskDetail.p_name}}</TaskPriority>
                                    <EDropdownMenu slot="dropdown">
                                        <EDropdownItem v-for="(item, key) in taskPriority" :key="key" :command="item">
                                            <i
                                                class="iconfont"
                                                :style="{color:item.color}"
                                                v-html="taskDetail.p_name == item.name ? '&#xe61d;' : '&#xe61c;'"></i>
                                            {{item.name}}
                                        </EDropdownItem>
                                    </EDropdownMenu>
                                </EDropdown>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="getOwner()">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e4;</i>{{$L('负责人')}}
                        </div>
                        <ul class="item-content user">
                            <li @click="openTransfer"><UserAvatar :userid="getOwner().userid" :size="28"/></li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="getAssist.length > 0">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe63f;</i>{{$L('协助人员')}}
                        </div>
                        <ul class="item-content user">
                            <li v-for="item in getAssist" @click="openAssist"><UserAvatar :userid="item.userid" :size="28"/></li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="taskDetail.end_at">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e8;</i>{{$L('截止时间')}}
                        </div>
                        <ul class="item-content">
                            <li>{{taskDetail.end_at}}</li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="hasFile">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e6;</i>{{$L('附件')}}
                        </div>
                        <ul class="item-content file">
                            <li v-for="file in taskDetail.files">
                                <img class="file-ext" :src="file.thumb"/>
                                <div class="file-name">{{file.name}}</div>
                                <div class="file-size">{{$A.bytesToSize(file.size)}}</div>
                            </li>
                            <li>
                                <div class="add-button">
                                    <i class="iconfont">&#xe6f2;</i>{{$L('添加附件')}}
                                </div>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="hasSubtask">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6f0;</i>{{$L('子任务')}}
                        </div>
                        <ul class="item-content subtask">
                            <li v-for="task in taskDetail.sub_task">
                                <Icon class="subtask-icon" type="md-radio-button-off" />
                                <div class="subtask-name">
                                    <Input
                                        v-model="task.name"
                                        type="textarea"
                                        :rows="1"
                                        :autosize="{ minRows: 1, maxRows: 8 }"
                                        :maxlength="255"/>
                                </div>
                                <div
                                    v-if="task.end_at"
                                    :class="['subtask-time-avatar', task.today ? 'today' : '', task.overdue ? 'overdue' : '']">{{expiresFormat(task.end_at)}}</div>
                                <UserAvatar
                                    v-if="getOwner(task)"
                                    class="subtask-avatar"
                                    :userid="getOwner(task).userid"
                                    :size="20"/>
                            </li>
                            <li>
                                <div class="add-button">
                                    <i class="iconfont">&#xe6f2;</i>{{$L('添加子任务')}}
                                </div>
                            </li>
                        </ul>
                    </FormItem>
                </Form>
                <div class="add">
                    <EDropdown
                        trigger="click"
                        placement="bottom-start"
                        @command="">
                        <div class="add-button">
                            <i class="iconfont">&#xe6f2;</i>
                            {{$L('添加')}}
                            <em v-for="item in menuList">{{$L(item.name)}}</em>
                        </div>
                        <EDropdownMenu slot="dropdown">
                            <EDropdownItem v-for="(item, key) in menuList" :key="key" :command="item.command">
                                <div class="item">
                                    <i class="iconfont" v-html="item.icon"></i>{{$L(item.name)}}
                                </div>
                            </EDropdownItem>
                        </EDropdownMenu>
                    </EDropdown>
                </div>
            </div>
        </div>
        <div class="task-dialog" >
            <div class="head">
                <Icon class="icon" type="ios-chatbubbles-outline" />
                <div class="nav">
                    <p class="active">{{$L('聊天')}}</p>
                    <p>{{$L('动态')}}</p>
                </div>
            </div>
            <div class="no-dialog" :style="dialogStyle">
                <div class="no-tip">{{$L('暂无消息')}}</div>
                <div class="no-input">
                    <Input
                        ref="input"
                        class="dialog-input"
                        v-model="taskDetail._msgText"
                        type="textarea"
                        :rows="1"
                        :autosize="{ minRows: 1, maxRows: 3 }"
                        :maxlength="255"
                        :placeholder="$L('输入消息...')"/>
                </div>
            </div>
        </div>

        <!--修改负责人-->
        <Modal
            v-model="transferShow"
            :title="$L('修改负责人')"
            :mask-closable="false">
            <Form ref="addProject" :model="transferData" label-width="auto" @submit.native.prevent>
                <FormItem prop="owner_userid" :label="$L('任务负责人')">
                    <UserInput
                        v-if="transferShow"
                        v-model="transferData.owner_userid"
                        :multiple-max="1"
                        :placeholder="$L('选择任务负责人')"/>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="transferShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="transferLoad > 0" @click="onTransfer">{{$L('提交')}}</Button>
            </div>
        </Modal>

        <!--修改协助人员-->
        <Modal
            v-model="assistShow"
            :title="$L('修改协助人员')"
            :mask-closable="false">
            <Form ref="addProject" :model="assistData" label-width="auto" @submit.native.prevent>
                <FormItem prop="owner_userid" :label="$L('协助人员')">
                    <UserInput
                        v-if="assistShow"
                        v-model="assistData.assist_userid"
                        :multiple-max="1"
                        :disabled-choice="assistData.disabled"
                        :placeholder="$L('选择任务协助人员')"/>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="assistShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="assistLoad > 0" @click="onAssist">{{$L('提交')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import {mapState} from "vuex";
import TEditor from "../../../components/TEditor";
import TaskPriority from "./TaskPriority";
import UserInput from "../../../components/UserInput";

export default {
    name: "TaskDetail",
    components: {UserInput, TaskPriority, TEditor},
    data() {
        return {
            taskDetail: {},

            transferShow: false,
            transferData: {},
            transferLoad: 0,

            assistShow: false,
            assistData: {},
            assistLoad: 0,

            nowTime: Math.round(new Date().getTime() / 1000),
            nowInterval: null,

            innerHeight: window.innerHeight,

            taskPlugins: [
                'advlist autolink lists link image charmap print preview hr anchor pagebreak imagetools',
                'searchreplace visualblocks visualchars code',
                'insertdatetime media nonbreaking save table contextmenu directionality',
                'emoticons paste textcolor colorpicker imagetools codesample',
                'autoresize'
            ],
            taskOptions: {
                statusbar: false,
                menubar: false,
                forced_root_block : false,
                remove_trailing_brs: false,
                autoresize_bottom_margin: 2,
                min_height: 200,
                max_height: 380,
                valid_elements : 'a[href|target=_blank],em,strong/b,div[align],span[style],a,br,img[src|alt|witdh|height],pre[class],code',
                toolbar: 'uploadImages | uploadFiles | bold italic underline forecolor backcolor | codesample | preview screenload'
            },
            taskOptionFull: {
                menubar: 'file edit view',
                forced_root_block : false,
                remove_trailing_brs: false,
                valid_elements : 'a[href|target=_blank],em,strong/b,div[align],span[style],a,br,img[src|alt|witdh|height],pre[class],code',
                toolbar: 'uploadImages | uploadFiles | bold italic underline forecolor backcolor | codesample | preview screenload'
            },
        }
    },

    mounted() {
        this.$store.dispatch('taskPriority');
        this.nowInterval = setInterval(() => {
            this.nowTime = Math.round(new Date().getTime() / 1000);
        }, 1000);
        window.addEventListener('resize', this.innerHeightListener);
    },

    destroyed() {
        clearInterval(this.nowInterval);
        window.removeEventListener('resize', this.innerHeightListener);
    },

    computed: {
        ...mapState(['userId', 'projectOpenTask', 'taskPriority']),

        scrollerStyle() {
            const {innerHeight, taskDetail} = this;
            if (!innerHeight) {
                return {};
            }
            if (!taskDetail._dialog) {
                return {};
            }
            return {
                maxHeight: (innerHeight - 70 - 66 - 30) + 'px'
            }
        },

        dialogStyle() {
            const {innerHeight, taskDetail} = this;
            if (!innerHeight) {
                return {};
            }
            if (taskDetail._dialog || taskDetail._msgText) {
                return {
                    minHeight: (innerHeight - 70 - 66 - 30) + 'px'
                }
            } else {
                return {};
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

        hasFile() {
            const {taskDetail} = this;
            return $A.isArray(taskDetail.files) && taskDetail.files.length > 0;
        },

        hasSubtask() {
            const {taskDetail} = this;
            return $A.isArray(taskDetail.sub_task) && taskDetail.sub_task.length > 0;
        },

        getOwner() {
            return function (task) {
                if (task === undefined) {
                    task = this.taskDetail;
                }
                if (!$A.isArray(task.task_user)) {
                    return null;
                }
                return task.task_user.find(({owner}) => owner === 1);
            }
        },

        getAssist() {
            const {taskDetail} = this;
            if (!$A.isArray(taskDetail.task_user)) {
                return [];
            }
            return taskDetail.task_user.filter(({owner}) => owner !== 1);
        },

        menuList() {
            const {taskDetail} = this;
            let list = [];
            if (!taskDetail.p_name) {
                list.push({
                    command: 'priority',
                    icon: '&#xe6ec;',
                    name: '优先级',
                });
            }
            if (!($A.isArray(taskDetail.task_user) && taskDetail.task_user.find(({owner}) => owner === 1))) {
                list.push({
                    command: 'owner',
                    icon: '&#xe6e4;',
                    name: '负责人',
                });
            }
            if (!($A.isArray(taskDetail.task_user) && taskDetail.task_user.find(({owner}) => owner !== 1))) {
                list.push({
                    command: 'owner',
                    icon: '&#xe63f;',
                    name: '协助人员',
                });
            }
            if (!taskDetail.end_at) {
                list.push({
                    command: 'times',
                    icon: '&#xe6e8;',
                    name: '截止时间',
                });
            }
            if (!($A.isArray(taskDetail.files) && taskDetail.files.length > 0)) {
                list.push({
                    command: 'file',
                    icon: '&#xe6e6;',
                    name: '附件',
                });
            }
            if (!($A.isArray(taskDetail.sub_task) && taskDetail.sub_task.length > 0)) {
                list.push({
                    command: 'subtask',
                    icon: '&#xe6f0;',
                    name: '子任务',
                });
            }
            return list;
        },
    },

    watch: {
        projectOpenTask(data) {
            this.taskDetail = $A.cloneJSON(data);
            if (data._show) this.$nextTick(this.$refs.input.focus)
        },
    },

    methods: {
        innerHeightListener() {
            this.innerHeight = window.innerHeight;
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

        updateData(action, params) {
            switch (action) {
                case 'complete':
                    this.$set(this.taskDetail, 'complete_at', $A.formatDate());
                    action = 'complete_at';
                    break;
                case 'uncomplete':
                    this.$set(this.taskDetail, 'complete_at', false);
                    action = 'complete_at';
                    break;
                case 'priority':
                    this.$set(this.taskDetail, 'p_level', params.priority)
                    this.$set(this.taskDetail, 'p_name', params.name)
                    this.$set(this.taskDetail, 'p_color', params.color)
                    action = ['p_level', 'p_name', 'p_color'];
                    break;
            }
            //
            let dataJson = {task_id: this.taskDetail.id};
            ($A.isArray(action) ? action : [action]).forEach(key => {
                let newData = this.taskDetail[key];
                let originalData = this.projectOpenTask[key];
                if ($A.jsonStringify(newData) != $A.jsonStringify(originalData)) {
                    dataJson[key] = newData;
                }
            })
            if (Object.keys(dataJson).length <= 1) return;
            //
            this.$store.dispatch("taskUpdate", dataJson).then(() => {
                // 更新成功
            }).catch(() => {
                // 更新失败
            })
        },

        openTransfer() {
            this.$set(this.taskDetail, 'owner_userid', [this.getOwner().userid])
            this.$set(this.transferData, 'owner_userid', [this.getOwner().userid]);
            this.transferShow = true;
        },

        onTransfer() {
            if ($A.jsonStringify(this.taskDetail.owner_userid) === $A.jsonStringify(this.transferData.owner_userid)) {
                return;
            }
            this.transferLoad++;
            this.$store.dispatch("taskUpdate", {
                task_id: this.taskDetail.id,
                owner: this.transferData.owner_userid
            }).then(() => {
                this.transferLoad--;
                this.transferShow = false;
                this.$store.dispatch("taskOne", this.taskDetail.id);
            }).catch(({msg}) => {
                this.transferLoad--;
                this.transferShow = false;
                $A.modalError(msg);
            })
        },

        openAssist() {
            const list = this.getAssist.map(({userid}) => userid)
            this.$set(this.taskDetail, 'assist_userid', list)
            this.$set(this.assistData, 'assist_userid', list);
            this.$set(this.assistData, 'disabled', [this.getOwner().userid]);
            this.assistShow = true;
        },

        onAssist() {
            if ($A.jsonStringify(this.taskDetail.assist_userid) === $A.jsonStringify(this.assistData.assist_userid)) {
                return;
            }
            let assist = this.assistData.assist_userid;
            if (assist.length === 0) assist = false;
            this.assistLoad++;
            this.$store.dispatch("taskUpdate", {
                task_id: this.taskDetail.id,
                assist,
            }).then(() => {
                this.assistLoad--;
                this.assistShow = false;
                this.$store.dispatch("taskOne", this.taskDetail.id);
            }).catch(({msg}) => {
                this.assistLoad--;
                this.assistShow = false;
                $A.modalError(msg);
            })
        }
    }
}
</script>
