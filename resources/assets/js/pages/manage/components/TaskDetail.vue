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
                    <EDropdownItem command="times">
                        <div class="item">
                            <Icon type="md-time" />{{$L('时间')}}
                        </div>
                    </EDropdownItem>
                    <EDropdownItem command="remove">
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
            <div @click="openTime" :class="['time', taskDetail.today ? 'today' : '', taskDetail.overdue ? 'overdue' : '']">
                {{taskDetail.end_at ? expiresFormat(taskDetail.end_at) : ' '}}
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
                    :project-id="taskDetail.project_id"
                    :placeholder="$L('选择任务负责人')"/>
                <div class="task-detail-avatar-buttons">
                    <Button size="small" type="primary" @click="$refs.owner.ok()">{{$L('确定')}}</Button>
                </div>
            </div>
            <template v-if="getOwner.length > 0">
                <UserAvatar v-for="item in getOwner" :key="item.userid" :userid="item.userid" :size="20" tooltip-disabled/>
            </template>
            <div v-else>--</div>
        </Poptip>
    </li>
    <!--主任务-->
    <div v-else v-show="taskDetail.id > 0" :class="{'task-detail':true, 'open-dialog': taskDetail.dialog_id, 'completed': taskDetail.complete_at}">
        <div class="task-info">
            <div class="head">
                <Icon v-if="taskDetail.complete_at" class="icon completed" type="md-checkmark-circle" @click="updateData('uncomplete')"/>
                <Icon v-else class="icon" type="md-radio-button-off" @click="updateData('complete')"/>
                <div class="nav">
                    <p v-if="projectName">{{projectName}}</p>
                    <p v-if="columnName">{{columnName}}</p>
                    <p v-if="taskDetail.id">{{taskDetail.id}}</p>
                </div>
                <Poptip
                    v-if="getOwner.length === 0"
                    confirm
                    ref="owner"
                    class="pick"
                    :title="$L('你确认领取任务吗？')"
                    placement="bottom"
                    @on-ok="onOwner(true)"
                    transfer>
                    <Button type="primary">{{$L('我要领取任务')}}</Button>
                </Poptip>
                <EDropdown
                    trigger="click"
                    placement="bottom"
                    @command="dropTask">
                    <Icon class="menu" type="ios-more"/>
                    <EDropdownMenu slot="dropdown">
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
                        <EDropdownItem command="archived">
                            <div class="item">
                                <Icon type="ios-filing" />{{$L('归档')}}
                            </div>
                        </EDropdownItem>
                        <EDropdownItem command="remove">
                            <div class="item">
                                <Icon type="md-trash" />{{$L('删除')}}
                            </div>
                        </EDropdownItem>
                    </EDropdownMenu>
                </EDropdown>
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
                        ref="desc"
                        v-if="loadEditor"
                        :value="taskContent"
                        :plugins="taskPlugins"
                        :options="taskOptions"
                        :option-full="taskOptionFull"
                        :placeholder="$L('详细描述...')"
                        @on-blur="updateData('content')"
                        inline/>
                </div>
                <Form class="items" label-position="left" label-width="auto" @submit.native.prevent>
                    <FormItem v-if="taskDetail.p_name">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6ec;</i>{{$L('优先级')}}
                        </div>
                        <ul class="item-content">
                            <li>
                                <EDropdown
                                    ref="priority"
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
                    <FormItem v-if="getOwner.length > 0">
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
                                    :multiple-max="10"
                                    :project-id="taskDetail.project_id"
                                    :placeholder="$L('选择任务负责人')"/>
                                <div class="task-detail-avatar-buttons">
                                    <Button size="small" type="primary" @click="$refs.owner.ok()">{{$L('确定')}}</Button>
                                </div>
                            </div>
                            <div class="user-list">
                                <UserAvatar v-for="item in getOwner" :key="item.userid" :userid="item.userid" :size="28" tooltip-disabled/>
                            </div>
                        </Poptip>
                    </FormItem>
                    <FormItem v-if="getAssist.length > 0 || assistForce">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe63f;</i>{{$L('协助人员')}}
                        </div>
                        <Poptip
                            ref="assist"
                            :title="$L(getAssist.length > 0 ? '修改协助人员' : '添加协助人员')"
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
                                    :project-id="taskDetail.project_id"
                                    :disabled-choice="assistData.disabled"
                                    :placeholder="$L('选择任务协助人员')"/>
                                <div class="task-detail-avatar-buttons">
                                    <Button size="small" type="primary" @click="$refs.assist.ok()">{{$L('确定')}}</Button>
                                </div>
                            </div>
                            <div v-if="getAssist.length > 0" class="user-list">
                                <UserAvatar v-for="item in getAssist" :key="item.userid" :userid="item.userid" :size="28"/>
                            </div>
                            <div v-else>--</div>
                        </Poptip>
                    </FormItem>
                    <FormItem v-if="taskDetail.end_at || timeForce">
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
                                        <div @click="openTime" class="time">{{taskDetail.end_at ? cutTime : '--'}}</div>
                                        <Tag v-if="!taskDetail.complete_at && taskDetail.today" color="blue"><i class="iconfont">&#xe71d;</i>{{expiresFormat(taskDetail.end_at)}}</Tag>
                                        <Tag v-if="!taskDetail.complete_at && taskDetail.overdue" color="red">{{$L('超期未完成')}}</Tag>
                                    </div>
                                </DatePicker>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="fileList.length > 0">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6e6;</i>{{$L('附件')}}
                        </div>
                        <ul class="item-content file">
                            <li v-for="file in fileList">
                                <img v-if="file.id" class="file-ext" :src="file.thumb"/>
                                <Loading v-else class="file-load"/>
                                <a class="file-name" :href="file.path||'javascript:;'" target="_blank">{{file.name}}</a>
                                <div class="file-size">{{$A.bytesToSize(file.size)}}</div>
                                <EPopover v-model="file._deling" class="file-delete">
                                    <div class="task-detail-delete-file-popover">
                                        <p>{{$L('你确定要删除这个文件吗？')}}</p>
                                        <div class="buttons">
                                            <Button size="small" type="text" @click="file._deling=false">取消</Button>
                                            <Button size="small" type="primary" @click="deleteFile(file)">确定</Button>
                                        </div>
                                    </div>
                                    <i slot="reference" :class="['iconfont', file._deling ? 'deling' : '']">&#xe6ea;</i>
                                </EPopover>
                            </li>
                        </ul>
                        <ul class="item-content">
                            <li>
                                <div class="add-button" @click="$refs.upload.handleClick()">
                                    <i class="iconfont">&#xe6f2;</i>{{$L('添加附件')}}
                                </div>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="subList.length > 0 || addsubForce">
                        <div class="item-label" slot="label">
                            <i class="iconfont">&#xe6f0;</i>{{$L('子任务')}}
                        </div>
                        <ul class="item-content subtask">
                            <TaskDetail v-for="(task, key) in subList" :key="key" :open-task="task"/>
                        </ul>
                        <ul :class="['item-content', subList.length === 0 ? 'nosub' : '']">
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
                <div v-if="menuList.length > 0" class="add">
                    <EDropdown
                        trigger="click"
                        placement="bottom"
                        @command="dropAdd">
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
        <div class="task-dialog" :style="dialogStyle">
            <template v-if="taskDetail.dialog_id > 0">
                <DialogWrapper v-if="taskId > 0" ref="dialog" :dialog-id="taskDetail.dialog_id">
                    <div slot="head" class="head">
                        <Icon class="icon" type="ios-chatbubbles-outline" />
                        <div class="nav">
                            <p :class="{active:navActive=='dialog'}" @click="setNavActive('dialog')">{{$L('聊天')}}</p>
                            <p :class="{active:navActive=='log'}" @click="setNavActive('log')">{{$L('动态')}}</p>
                        </div>
                    </div>
                </DialogWrapper>
                <ProjectLog v-if="navActive=='log'" ref="log" :task-id="taskDetail.id"/>
            </template>
            <div v-else>
                <div class="head">
                    <Icon class="icon" type="ios-chatbubbles-outline" />
                    <div class="nav">
                        <p :class="{active:navActive=='dialog'}" @click="setNavActive('dialog')">{{$L('聊天')}}</p>
                        <p :class="{active:navActive=='log'}" @click="setNavActive('log')">{{$L('动态')}}</p>
                    </div>
                </div>
                <ProjectLog v-if="navActive=='log'" ref="log" :task-id="taskDetail.id"/>
                <div v-else class="no-dialog">
                    <div class="no-tip">{{$L('暂无消息')}}</div>
                    <div class="no-input">
                        <Input
                            class="dialog-input"
                            v-model="msgText"
                            type="textarea"
                            :rows="1"
                            :autosize="{ minRows: 1, maxRows: 3 }"
                            :maxlength="255"
                            :placeholder="$L('输入消息...')"
                            @on-keydown="msgKeydown"/>
                    </div>
                </div>
            </div>
            <Input ref="input" v-show="false"/>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import TEditor from "../../../components/TEditor";
import TaskPriority from "./TaskPriority";
import UserInput from "../../../components/UserInput";
import TaskUpload from "./TaskUpload";
import DialogWrapper from "./DialogWrapper";
import ProjectLog from "./ProjectLog";

export default {
    name: "TaskDetail",
    components: {ProjectLog, DialogWrapper, TaskUpload, UserInput, TaskPriority, TEditor},
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
            loadEditor: false,

            ownerShow: false,
            ownerData: {},
            ownerLoad: 0,

            assistForce: false,
            assistShow: false,
            assistData: {},
            assistLoad: 0,

            addsubForce: false,
            addsubShow: false,
            addsubName: "",
            addsubLoad: 0,

            timeForce: false,
            timeOpen: false,
            timeValue: [],
            timeOptions: {
                shortcuts: []
            },

            nowTime: Math.round(new Date().getTime() / 1000),
            nowInterval: null,

            innerHeight: window.innerHeight,

            msgText: '',
            navActive: 'dialog',

            taskPlugins: [
                'advlist autolink lists link image charmap print preview hr anchor pagebreak imagetools',
                'searchreplace visualblocks visualchars code',
                'insertdatetime media nonbreaking save table directionality',
                'emoticons paste imagetools codesample',
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
        ...mapState([
            'userId',
            'projects',
            'columns',
            'taskId',
            'taskSubs',
            'taskContents',
            'taskFiles',
            'taskPriority',
        ]),

        projectName() {
            if (!this.taskDetail.project_id) {
                return ''
            }
            if (this.taskDetail.project_name) {
                return this.taskDetail.project_name;
            }
            const project = this.projects.find(({id}) => id == this.taskDetail.project_id)
            return project ? project.name : '';
        },

        columnName() {
            if (!this.taskDetail.column_id) {
                return ''
            }
            if (this.taskDetail.column_name) {
                return this.taskDetail.column_name;
            }
            const column = this.columns.find(({id}) => id == this.taskDetail.column_id)
            return column ? column.name : '';
        },

        taskContent() {
            if (!this.taskId) {
                return "";
            }
            let content = this.taskContents.find(({task_id}) => task_id == this.taskId)
            return content ? content.content : ''
        },

        fileList() {
            if (!this.taskId) {
                return [];
            }
            return this.taskFiles.filter(({task_id}) => {
                return task_id == this.taskId
            }).sort((a, b) => {
                return a.id - b.id;
            });
        },

        subList() {
            if (!this.taskId) {
                return [];
            }
            return this.taskSubs.filter(({parent_id}) => {
                return parent_id == this.taskId
            }).sort((a, b) => {
                return a.id - b.id;
            });
        },

        scrollerStyle() {
            const {innerHeight, taskDetail} = this;
            if (!innerHeight) {
                return {};
            }
            if (!taskDetail.dialog_id) {
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
            if (taskDetail.dialog_id) {
                return {
                    minHeight: (innerHeight - 70 - 48) + 'px'
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

        getOwner() {
            const {taskDetail} = this;
            if (!$A.isArray(taskDetail.task_user)) {
                return [];
            }
            return taskDetail.task_user.filter(({owner}) => owner === 1).sort((a, b) => {
                return a.id - b.id;
            });
        },

        getAssist() {
            const {taskDetail} = this;
            if (!$A.isArray(taskDetail.task_user)) {
                return [];
            }
            return taskDetail.task_user.filter(({owner}) => owner !== 1).sort((a, b) => {
                return a.id - b.id;
            });
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
            if (!($A.isArray(taskDetail.task_user) && taskDetail.task_user.find(({owner}) => owner !== 1))) {
                list.push({
                    command: 'assist',
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
            if (this.fileList.length == 0) {
                list.push({
                    command: 'file',
                    icon: '&#xe6e6;',
                    name: '附件',
                });
            }
            if (this.subList.length == 0) {
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
        taskId (id) {
            if (id > 0) {
                this.$nextTick(() => {
                    this.loadEditor = true;
                    this.$refs.input.focus()
                });
            } else {
                this.timeOpen = false;
                this.timeForce = false;
                this.assistForce = false;
                this.addsubForce = false;
                this.$refs.owner && this.$refs.owner.handleClose();
                this.$refs.assist && this.$refs.assist.handleClose();
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
            switch (command) {
                case 'complete':
                    this.updateData('complete')
                    break;
                case 'uncomplete':
                    this.updateData('uncomplete')
                    break;
                case 'times':
                    this.openTime()
                    break;
                case 'archived':
                case 'remove':
                    this.archivedOrRemoveTask(command);
                    break;
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
                case 'content':
                    if (this.$refs.desc.getContent() == this.taskContent) {
                        return;
                    }
                    this.$set(this.taskDetail, 'content', this.$refs.desc.getContent())
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
            let typeDispatch = type == 'remove' ? 'removeTask' : 'archivedTask';
            let typeName = type == 'remove' ? '删除' : '归档';
            let typeTask = this.taskDetail.parent_id > 0 ? '子任务' : '任务';
            $A.modalConfirm({
                title: typeName + typeTask,
                content: '你确定要' + typeName + typeTask + '【' + this.taskDetail.name + '】吗？',
                loading: true,
                onOk: () => {
                    if (this.taskDetail.loading === true) {
                        this.$Modal.remove();
                        return;
                    }
                    this.$set(this.taskDetail, 'loading', true);
                    this.$store.dispatch(typeDispatch, this.taskDetail.id).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$Modal.remove();
                    });
                }
            });
        },

        openOwner() {
            const list = this.getOwner.map(({userid}) => userid)
            this.$set(this.taskDetail, 'owner_userid', list)
            this.$set(this.ownerData, 'owner_userid', list)
            this.ownerShow = true;
        },

        onOwner(pick) {
            if (pick === true && this.getOwner.length === 0) {
                this.ownerData.owner_userid = [this.userId];
            }
            if ($A.jsonStringify(this.taskDetail.owner_userid) === $A.jsonStringify(this.ownerData.owner_userid)) {
                return;
            }
            let owner = this.ownerData.owner_userid;
            if ($A.count(owner) == 0) owner = '';
            this.ownerLoad++;
            this.$store.dispatch("taskUpdate", {
                task_id: this.taskDetail.id,
                owner: owner,
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.ownerLoad--;
                this.ownerShow = false;
                this.$store.dispatch("getTaskOne", this.taskDetail.id);
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.ownerLoad--;
                this.ownerShow = false;
            })
        },

        openAssist() {
            const list = this.getAssist.map(({userid}) => userid)
            this.$set(this.taskDetail, 'assist_userid', list)
            this.$set(this.assistData, 'assist_userid', list);
            this.$set(this.assistData, 'disabled', this.getOwner.map(({userid}) => userid))
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
                $A.messageSuccess(msg);
                this.assistLoad--;
                this.assistShow = false;
                this.$store.dispatch("getTaskOne", this.taskDetail.id);
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.assistLoad--;
                this.assistShow = false;
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
                $A.messageSuccess(msg);
                this.addsubLoad--;
                this.addsubName = "";
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.addsubLoad--;
            });
        },

        setNavActive(act) {
            if (act == 'log' && this.navActive == act) {
                this.$refs.log.getLists(true);
            }
            this.navActive = act;
        },

        dropAdd(command) {
            switch (command) {
                case 'priority':
                    this.$set(this.taskDetail, 'p_name', this.$L('未设置'));
                    this.$nextTick(() => {
                        this.$refs.priority.show();
                    })
                    break;

                case 'assist':
                    this.assistForce = true;
                    this.openAssist();
                    this.$nextTick(() => {
                        this.$refs.assist.handleClick();
                    });
                    break;

                case 'times':
                    this.timeForce = true;
                    this.$nextTick(() => {
                        this.openTime()
                    })
                    break;

                case 'file':
                    this.$refs.upload.handleClick();
                    break;

                case 'subtask':
                    this.addsubForce = true;
                    this.$nextTick(() => {
                        this.addsubOpen();
                    });
                    break;
            }
        },

        msgKeydown(e) {
            if (e.keyCode === 13) {
                if (e.shiftKey) {
                    return;
                }
                e.preventDefault();
                this.msgDialog();
            }
        },

        msgDialog() {
            if (!this.msgText) {
                return;
            }
            this.$store.dispatch("call", {
                url: 'project/task/dialog',
                data: {
                    task_id: this.taskDetail.id,
                },
            }).then(({data}) => {
                this.$store.dispatch("saveTask", data);
                this.$store.dispatch("getDialogOne", data.dialog_id);
                this.$nextTick(() => {
                    this.$refs.dialog.sendMsg(this.msgText);
                    this.msgText = "";
                });
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        deleteFile(file) {
            this.$set(file, '_deling', false);
            this.$store.dispatch("forgetTaskFile", file.id)
            //
            this.$store.dispatch("call", {
                url: 'project/task/filedelete',
                data: {
                    file_id: file.id,
                },
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.$store.dispatch("getTaskFiles", this.taskDetail.id)
            });
        }
    }
}
</script>
