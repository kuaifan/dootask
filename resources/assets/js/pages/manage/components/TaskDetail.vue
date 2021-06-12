<template>
    <div :class="['task-detail', projectOpenTask._dialog || projectOpenTask._msgText ? 'open-dialog' : '']">
        <div class="task-info">
            <div class="head">
                <Icon class="icon" type="md-radio-button-off"/>
                <div class="nav">
                    <p v-if="projectOpenTask.project_name">{{projectOpenTask.project_name}}</p>
                    <p v-if="projectOpenTask.column_name">{{projectOpenTask.column_name}}</p>
                    <p v-if="projectOpenTask.id">{{projectOpenTask.id}}</p>
                </div>
                <Icon class="menu" type="ios-more"/>
            </div>
            <div class="scroller overlay-y" :style="scrollerStyle">
                <div class="title">
                    <Input
                        v-model="projectOpenTask.name"
                        type="textarea"
                        :rows="1"
                        :autosize="{ minRows: 1, maxRows: 8 }"
                        :maxlength="255"/>
                </div>
                <div v-if="hasContent" class="desc">
                    <TEditor
                        v-model="projectOpenTask.content.content"
                        :plugins="taskPlugins"
                        :options="taskOptions"
                        :option-full="taskOptionFull"
                        :placeholder="$L('详细描述...')"
                        inline></TEditor>
                </div>
                <Form class="items" label-position="left" label-width="auto" @submit.native.prevent>
                    <FormItem v-if="projectOpenTask.p_name">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6ec;</i>{{$L('优先级')}}
                        </div>
                        <ul class="item-content">
                            <li>
                                <TaskPriority :backgroundColor="projectOpenTask.p_color">{{projectOpenTask.p_name}}</TaskPriority>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="getOwner()">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e4;</i>{{$L('负责人')}}
                        </div>
                        <ul class="item-content user">
                            <li><UserAvatar :userid="getOwner().userid" :size="28"/></li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="projectOpenTask.end_at">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e8;</i>{{$L('截止时间')}}
                        </div>
                        <ul class="item-content">
                            <li>{{projectOpenTask.end_at}}</li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="hasFile">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e6;</i>{{$L('附件')}}
                        </div>
                        <ul class="item-content file">
                            <li v-for="file in projectOpenTask.files">
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
                            <li v-for="task in projectOpenTask.sub_task">
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
                    <Input class="dialog-input" v-model="projectOpenTask._msgText" type="textarea" :rows="1" :autosize="{ minRows: 1, maxRows: 3 }" :maxlength="255" :placeholder="$L('输入消息...')" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import TEditor from "../../../components/TEditor";
import TaskPriority from "./TaskPriority";

export default {
    name: "TaskDetail",
    components: {TaskPriority, TEditor},
    data() {
        return {
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
        ...mapState(['userId', 'projectOpenTask']),

        scrollerStyle() {
            const {innerHeight, projectOpenTask} = this;
            if (!innerHeight) {
                return {};
            }
            if (!projectOpenTask._dialog) {
                return {};
            }
            return {
                maxHeight: (innerHeight - 70 - 66 - 30) + 'px'
            }
        },

        dialogStyle() {
            const {innerHeight, projectOpenTask} = this;
            if (!innerHeight) {
                return {};
            }
            if (projectOpenTask._dialog || projectOpenTask._msgText) {
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

        hasContent() {
            const {projectOpenTask} = this;
            return $A.isJson(projectOpenTask.content);
        },

        hasFile() {
            const {projectOpenTask} = this;
            return $A.isArray(projectOpenTask.files) && projectOpenTask.files.length > 0;
        },

        hasSubtask() {
            const {projectOpenTask} = this;
            return $A.isArray(projectOpenTask.sub_task) && projectOpenTask.sub_task.length > 0;
        },

        getOwner() {
            return function (task) {
                if (task === undefined) {
                    task = this.projectOpenTask;
                }
                if (!$A.isArray(task.task_user)) {
                    return null;
                }
                return task.task_user.find(({owner}) => owner === 1);
            }
        },

        menuList() {
            const {projectOpenTask} = this;
            let list = [];
            if (!projectOpenTask.p_name) {
                list.push({
                    command: 'priority',
                    icon: '&#xe6ec;',
                    name: '优先级',
                });
            }
            if (!($A.isArray(projectOpenTask.task_user) && projectOpenTask.task_user.find(({owner}) => owner === 1))) {
                list.push({
                    command: 'owner',
                    icon: '&#xe6e4;',
                    name: '负责人',
                });
            }
            if (!projectOpenTask.end_at) {
                list.push({
                    command: 'times',
                    icon: '&#xe6e8;',
                    name: '截止时间',
                });
            }
            if (!($A.isArray(projectOpenTask.files) && projectOpenTask.files.length > 0)) {
                list.push({
                    command: 'file',
                    icon: '&#xe6e6;',
                    name: '附件',
                });
            }
            if (!($A.isArray(projectOpenTask.sub_task) && projectOpenTask.sub_task.length > 0)) {
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
    }
}
</script>
