<template>
    <!--子任务-->
    <li v-if="taskDetail.parent_id > 0">
        <div class="subtask-icon">
            <div v-if="taskDetail.loading === true" class="loading"><Loading /></div>
            <EDropdown
                v-else
                trigger="click"
                placement="bottom"
                size="small"
                @command="dropTask">
                <div>
                    <Icon v-if="taskDetail.complete_at" class="completed" type="md-checkmark-circle" />
                    <Icon v-else type="md-radio-button-off" />
                </div>
                <EDropdownMenu slot="dropdown" class="project-list-more-dropdown-menu">
                    <EDropdownItem v-if="taskDetail.complete_at" command="uncomplete">
                        <div class="item red">
                            <Icon type="md-checkmark-circle-outline" />{{$L('标记未完成')}}
                        </div>
                    </EDropdownItem>
                    <EDropdownItem v-else command="complete">
                        <div class="item">
                            <Icon type="md-radio-button-off" />{{$L('完成')}}
                        </div>
                    </EDropdownItem>
                    <EDropdownItem command="delete">
                        <div class="item">
                            <Icon type="md-trash" />{{$L('删除')}}
                        </div>
                    </EDropdownItem>
                </EDropdownMenu>
            </EDropdown>
        </div>
        <div class="subtask-name">
            <Input
                v-model="taskDetail.name"
                type="textarea"
                :rows="1"
                :autosize="{ minRows: 1, maxRows: 8 }"
                :maxlength="255"
                @on-blur="updateData('name')"
                @on-keydown="onNameKeydown"/>
        </div>
        <DatePicker
            v-model="timeValue"
            :open="timeOpen"
            :options="timeOptions"
            format="yyyy-MM-dd HH:mm"
            type="datetimerange"
            class="subtask-time"
            @on-open-change="timeChange"
            @on-clear="timeClear"
            @on-ok="timeOk"
            transfer>
            <div
                @click="openTime"
                :class="['time', taskDetail.today ? 'today' : '', taskDetail.overdue ? 'overdue' : '']">
                {{taskDetail.end_at ? expiresFormat(taskDetail.end_at) : '--'}}
            </div>
        </DatePicker>
        <Poptip
            ref="owner"
            class="subtask-avatar"
            :title="$L('修改负责人')"
            :width="240"
            placement="bottom"
            @on-popper-show="openOwner"
            @on-popper-hide="ownerShow=false"
            @on-ok="onOwner"
            transfer>
            <div slot="content">
                <UserInput
                    v-if="ownerShow"
                    v-model="ownerData.owner_userid"
                    :multiple-max="1"
                    :placeholder="$L('选择任务负责人')"/>
                <div class="task-detail-avatar-buttons">
                    <Button size="small" type="primary" @click="$refs.owner.ok()">{{$L('确定')}}</Button>
                </div>
            </div>
            <UserAvatar v-if="getOwner" :userid="getOwner.userid" :size="20" hide-icon-menu/>
            <div v-else>--</div>
        </Poptip>
    </li>
    <!--主任务-->
    <div v-else v-show="taskDetail.id > 0" :class="{'task-detail':true, 'open-dialog': taskDetail._dialog || taskDetail._msgText, 'completed': taskDetail.complete_at}">
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
                        @on-blur="updateData('name')"
                        @on-keydown="onNameKeydown"/>
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
                                    placement="bottom"
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
                    <FormItem>
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e4;</i>{{$L('负责人')}}
                        </div>
                        <Poptip
                            ref="owner"
                            :title="$L('修改负责人')"
                            :width="240"
                            class="item-content user"
                            placement="bottom"
                            @on-popper-show="openOwner"
                            @on-popper-hide="ownerShow=false"
                            @on-ok="onOwner"
                            transfer>
                            <div slot="content">
                                <UserInput
                                    v-if="ownerShow"
                                    v-model="ownerData.owner_userid"
                                    :multiple-max="1"
                                    :placeholder="$L('选择任务负责人')"/>
                                <div class="task-detail-avatar-buttons">
                                    <Button size="small" type="primary" @click="$refs.owner.ok()">{{$L('确定')}}</Button>
                                </div>
                            </div>
                            <div v-if="getOwner" class="user-list">
                                <UserAvatar :userid="getOwner.userid" :size="28" hide-icon-menu/>
                            </div>
                            <div v-else>--</div>
                        </Poptip>
                    </FormItem>
                    <FormItem v-if="getAssist.length > 0">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe63f;</i>{{$L('协助人员')}}
                        </div>
                        <Poptip
                            ref="assist"
                            :title="$L('修改协助人员')"
                            :width="280"
                            class="item-content user"
                            placement="bottom"
                            @on-popper-show="openAssist"
                            @on-popper-hide="assistShow=false"
                            @on-ok="onAssist"
                            transfer>
                            <div slot="content">
                                <UserInput
                                    v-if="assistShow"
                                    v-model="assistData.assist_userid"
                                    :multiple-max="10"
                                    :disabled-choice="assistData.disabled"
                                    :placeholder="$L('选择任务协助人员')"/>
                                <div class="task-detail-avatar-buttons">
                                    <Button size="small" type="primary" @click="$refs.assist.ok()">{{$L('确定')}}</Button>
                                </div>
                            </div>
                            <div class="user-list">
                                <UserAvatar v-for="item in getAssist" :key="item.userid" :userid="item.userid" :size="28" hide-icon-menu/>
                            </div>
                        </Poptip>
                    </FormItem>
                    <FormItem v-if="taskDetail.end_at">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e8;</i>{{$L('截止时间')}}
                        </div>
                        <ul class="item-content">
                            <li>
                                <DatePicker
                                    v-model="timeValue"
                                    :open="timeOpen"
                                    :options="timeOptions"
                                    format="yyyy-MM-dd HH:mm"
                                    type="datetimerange"
                                    @on-open-change="timeChange"
                                    @on-clear="timeClear"
                                    @on-ok="timeOk"
                                    transfer>
                                    <div class="picker-time">
                                        <div @click="openTime" class="time">{{cutTime}}</div>
                                        <Tag v-if="!taskDetail.complete_at && taskDetail.today" color="blue"><Icon type="ios-time-outline"/>{{expiresFormat(taskDetail.end_at)}}</Tag>
                                        <Tag v-if="!taskDetail.complete_at && taskDetail.overdue" color="red">{{$L('超期未完成')}}</Tag>
                                    </div>
                                </DatePicker>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="hasFile">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e6;</i>{{$L('附件')}}
                        </div>
                        <ul class="item-content file">
                            <li v-for="file in taskDetail.files">
                                <img v-if="file.id" class="file-ext" :src="file.thumb"/>
                                <Loading v-else class="file-load"/>
                                <div class="file-name">{{file.name}}</div>
                                <div class="file-size">{{$A.bytesToSize(file.size)}}</div>
                            </li>
                            <li>
                                <div class="add-button" @click="$refs.upload.handleClick()">
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
                            <TaskDetail v-for="(task, key) in taskDetail.sub_task" :key="key" :open-task="task"/>
                            <li>
                                <Input
                                    v-if="addsubShow"
                                    v-model="addsubName"
                                    ref="addsub"
                                    class="add-input"
                                    :placeholder="$L('+ 输入子任务，回车添加子任务')"
                                    :icon="addsubLoad > 0 ? 'ios-loading' : ''"
                                    @on-blur="addsubChackClose"
                                    @on-keydown="addsubKeydown"/>
                                <div v-else class="add-button" @click="addsubOpen">
                                    <i class="iconfont">&#xe6f2;</i>{{$L('添加子任务')}}
                                </div>
                            </li>
                        </ul>
                    </FormItem>
                </Form>
                <div class="add">
                    <EDropdown
                        trigger="click"
                        placement="bottom"
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
            <TaskUpload ref="upload" class="upload"/>
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
    </div>
</template>

<script>
import {mapState} from "vuex";
import TEditor from "../../../components/TEditor";
import TaskPriority from "./TaskPriority";
import UserInput from "../../../components/UserInput";
import TaskUpload from "./TaskUpload";

export default {
    name: "TaskDetail",
    components: {TaskUpload, UserInput, TaskPriority, TEditor},
    props: {
        openTask: {
            type: Object,
            default: () => {
                return {};
            }
        },
    },
    data() {
        return {
            taskDetail: {},

            ownerShow: false,
            ownerData: {},
            ownerLoad: 0,

            assistShow: false,
            assistData: {},
            assistLoad: 0,

            addsubShow: false,
            addsubName: "",
            addsubLoad: 0,

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

            timeOpen: false,
            timeValue: [],
            timeOptions: {
                shortcuts: []
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
        ...mapState(['userId', 'taskPriority']),

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

        cutTime() {
            const {nowTime, taskDetail} = this;
            let string = "";
            let start_at = Math.round(new Date(taskDetail.start_at).getTime() / 1000);
            if (start_at > nowTime) {
                string = $A.formatDate('Y/m/d H:i', start_at) + " ~ "
            }
            let end_at = Math.round(new Date(taskDetail.end_at).getTime() / 1000);
            string+= $A.formatDate('Y/m/d H:i', end_at);
            return string;
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
            const {taskDetail} = this;
            if (!$A.isArray(taskDetail.task_user)) {
                return null;
            }
            return taskDetail.task_user.find(({owner}) => owner === 1);
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
        openTask: {
            handler(data) {
                this.taskDetail = $A.cloneJSON(data);
            },
            immediate: true,
            deep: true
        },
        'openTask._show' (v) {
            if (v) {
                this.$nextTick(this.$refs.input.focus)
            } else {
                this.timeOpen = false;
            }
        }
    },

    methods: {
        initLanguage() {
            const lastSecond = (e) => {
                return new Date($A.formatDate("Y-m-d 23:59:29", Math.round(e / 1000)))
            };
            this.timeOptions = {
                shortcuts: [{
                    text: this.$L('今天'),
                    value() {
                        return [new Date(), lastSecond(new Date().getTime())];
                    }
                }, {
                    text: this.$L('明天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 1);
                        return [new Date(), lastSecond(e.getTime())];
                    }
                }, {
                    text: this.$L('本周'),
                    value() {
                        return [$A.getData('今天', true), lastSecond($A.getData('本周结束2', true))];
                    }
                }, {
                    text: this.$L('本月'),
                    value() {
                        return [$A.getData('今天', true), lastSecond($A.getData('本月结束', true))];
                    }
                }, {
                    text: this.$L('3天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 3);
                        return [new Date(), lastSecond(e.getTime())];
                    }
                }, {
                    text: this.$L('5天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 5);
                        return [new Date(), lastSecond(e.getTime())];
                    }
                }, {
                    text: this.$L('7天'),
                    value() {
                        let e = new Date();
                        e.setDate(e.getDate() + 7);
                        return [new Date(), lastSecond(e.getTime())];
                    }
                }]
            };
        },

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

        onNameKeydown(e) {
            if (e.keyCode === 13) {
                if (e.shiftKey) {
                    return;
                }
                e.preventDefault();
                this.updateData('name');
            }
        },

        dropTask(command) {
            if (command === 'complete') {
                this.updateData('complete')
            }
            else if (command === 'uncomplete') {
                this.updateData('uncomplete')
            }
            else if (command === 'delete') {
                this.archivedOrRemoveTask('delete');
            }
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
                case 'times':
                    this.$set(this.taskDetail, 'times', [params.start_at, params.end_at])
                    break;
            }
            //
            let dataJson = {task_id: this.taskDetail.id};
            ($A.isArray(action) ? action : [action]).forEach(key => {
                let newData = this.taskDetail[key];
                let originalData = this.openTask[key];
                if ($A.jsonStringify(newData) != $A.jsonStringify(originalData)) {
                    dataJson[key] = newData;
                }
            })
            if (Object.keys(dataJson).length <= 1) return;
            //
            this.$store.dispatch("taskUpdate", dataJson).then(({msg}) => {
                // 更新成功
                $A.messageSuccess(msg);
            }).catch(({msg}) => {
                // 更新失败
                $A.modalError(msg);
            })
        },

        archivedOrRemoveTask(type) {
            let typeTitle = this.taskDetail.parent_id > 0 ? '子任务' : '任务';
            $A.modalConfirm({
                title: '删除' + typeTitle,
                content: '你确定要删除' + typeTitle + '【' + this.taskDetail.name + '】吗？',
                loading: true,
                onOk: () => {
                    if (this.taskDetail.loading === true) {
                        return;
                    }
                    this.$set(this.taskDetail, 'loading', true);
                    this.$store.dispatch("taskArchivedOrRemove", {
                        task_id: this.taskDetail.id,
                        type: type,
                    }).then(({msg}) => {
                        this.$Modal.remove();
                        $A.messageSuccess(msg);
                    }).catch(({msg}) => {
                        this.$Modal.remove();
                        $A.modalError(msg, 301);
                    });
                }
            });
        },

        openOwner() {
            this.$set(this.taskDetail, 'owner_userid', [this.getOwner.userid])
            this.$set(this.ownerData, 'owner_userid', [this.getOwner.userid]);
            this.ownerShow = true;
        },

        onOwner() {
            if ($A.jsonStringify(this.taskDetail.owner_userid) === $A.jsonStringify(this.ownerData.owner_userid)) {
                return;
            }
            this.ownerLoad++;
            this.$store.dispatch("taskUpdate", {
                task_id: this.taskDetail.id,
                owner: this.ownerData.owner_userid
            }).then(({msg}) => {
                this.ownerLoad--;
                this.ownerShow = false;
                this.$store.dispatch("taskOne", this.taskDetail.id);
                $A.messageSuccess(msg);
            }).catch(({msg}) => {
                this.ownerLoad--;
                this.ownerShow = false;
                $A.modalError(msg);
            })
        },

        openAssist() {
            const list = this.getAssist.map(({userid}) => userid)
            this.$set(this.taskDetail, 'assist_userid', list)
            this.$set(this.assistData, 'assist_userid', list);
            this.$set(this.assistData, 'disabled', [this.getOwner.userid]);
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
            }).then(({msg}) => {
                this.assistLoad--;
                this.assistShow = false;
                this.$store.dispatch("taskOne", this.taskDetail.id);
                $A.messageSuccess(msg);
            }).catch(({msg}) => {
                this.assistLoad--;
                this.assistShow = false;
                $A.modalError(msg);
            })
        },

        openTime() {
            this.timeOpen = !this.timeOpen;
            if (this.timeOpen) {
                this.timeValue = this.taskDetail.end_at ? [this.taskDetail.start_at, this.taskDetail.end_at] : [];
            }
        },

        timeChange(open) {
            if (!open) {
                this.timeOpen = false;
            }
        },

        timeClear() {
            $A.modalConfirm({
                content: '你确定要取消任务时间吗？',
                cancelText: '不是',
                onOk: () => {
                    this.updateData('times', {
                        start_at: false,
                        end_at: false,
                    });
                    this.timeOpen = false;
                }
            });
        },

        timeOk() {
            let times = $A.date2string(this.timeValue, "Y-m-d H:i");
            if (times[0] && times[1]) {
                if ($A.rightExists(times[0], '00:00') && $A.rightExists(times[1], '00:00')) {
                    times[1] = times[1].replace("00:00", "23:59");
                }
            }
            this.updateData('times', {
                start_at: times[0],
                end_at: times[1],
            });
            this.timeOpen = false;
        },

        addsubOpen() {
            this.addsubShow = true;
            this.$nextTick(() => {
                this.$refs.addsub.focus()
            });
        },

        addsubChackClose() {
            if (this.addsubName == '') {
                this.addsubShow = false;
            }
        },

        addsubKeydown(e) {
            if (e.keyCode === 13) {
                if (e.shiftKey) {
                    return;
                }
                e.preventDefault();
                this.onAddsub();

            }
        },

        onAddsub() {
            if (this.addsubName == '') {
                $A.messageSuccess('任务描述不能为空');
                return;
            }
            this.addsubLoad++;
            this.$store.dispatch("taskAddSub", {
                task_id: this.taskDetail.id,
                name: this.addsubName,
            }).then(({msg}) => {
                this.addsubLoad--;
                this.addsubName = "";
                $A.messageSuccess(msg);
            }).catch(({msg}) => {
                this.addsubLoad--;
                $A.modalError(msg);
            });
        }
    }
}
</script>
