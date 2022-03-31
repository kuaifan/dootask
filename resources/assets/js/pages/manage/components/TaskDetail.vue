<template>
    <!--子任务-->
    <li v-if="ready && taskDetail.parent_id > 0">
        <div class="subtask-icon">
            <TaskMenu
                :ref="`taskMenu_${taskDetail.id}`"
                :disabled="taskId === 0"
                :task="taskDetail"
                :load-status="taskDetail.loading === true"
                @on-update="getLogLists"/>
        </div>
        <div v-if="taskDetail.flow_item_name" class="subtask-flow">
            <span :class="taskDetail.flow_item_status" @click.stop="openMenu(taskDetail)">{{taskDetail.flow_item_name}}</span>
        </div>
        <div class="subtask-name">
            <Input
                v-model="taskDetail.name"
                ref="name"
                type="textarea"
                :rows="1"
                :autosize="{ minRows: 1, maxRows: 8 }"
                :maxlength="255"
                @on-blur="updateBlur('name')"
                @on-keydown="onNameKeydown"/>
        </div>
        <DatePicker
            v-model="timeValue"
            :open="timeOpen"
            :options="timeOptions"
            format="yyyy/MM/dd HH:mm"
            type="datetimerange"
            class="subtask-time"
            placement="bottom-end"
            @on-open-change="timeChange"
            @on-clear="timeClear"
            @on-ok="timeOk"
            transfer>
            <div v-if="!taskDetail.complete_at && taskDetail.end_at && taskDetail.end_at != mainEndAt" @click="openTime" :class="['time', taskDetail.today ? 'today' : '', taskDetail.overdue ? 'overdue' : '']">
                {{expiresFormat(taskDetail.end_at)}}
            </div>
            <Icon v-else class="clock" type="ios-clock-outline" @click="openTime" />
        </DatePicker>
        <Poptip
            ref="owner"
            class="subtask-avatar"
            popper-class="task-detail-user-popper"
            :title="$L('修改负责人')"
            :width="240"
            placement="bottom"
            @on-popper-show="openOwner"
            @on-ok="onOwner"
            transfer>
            <div slot="content">
                <UserInput
                    v-model="ownerData.owner_userid"
                    :multiple-max="1"
                    :project-id="taskDetail.project_id"
                    :placeholder="$L('选择任务负责人')"
                    :transfer="false"/>
                <div class="task-detail-avatar-buttons">
                    <Button size="small" type="primary" @click="$refs.owner.ok()">{{$L('确定')}}</Button>
                </div>
            </div>
            <template v-if="getOwner.length > 0">
                <UserAvatar v-for="item in getOwner" :key="item.userid" :userid="item.userid" :size="20" tooltipDisabled/>
            </template>
            <div v-else>--</div>
        </Poptip>
    </li>
    <!--主任务-->
    <div
        v-else-if="ready"
        :class="{'task-detail':true, 'open-dialog': hasOpenDialog, 'completed': taskDetail.complete_at}">
        <div v-show="taskDetail.id > 0" class="task-info">
            <div class="head">
                <TaskMenu
                    :ref="`taskMenu_${taskDetail.id}`"
                    :disabled="taskId === 0"
                    :task="taskDetail"
                    class="icon"
                    size="medium"
                    :color-show="false"
                    @on-update="getLogLists"/>
                <div v-if="taskDetail.flow_item_name" class="flow">
                    <span :class="taskDetail.flow_item_status" @click.stop="openMenu(taskDetail)">{{taskDetail.flow_item_name}}</span>
                </div>
                <div v-if="taskDetail.archived_at" class="flow">
                    <span class="archived" @click.stop="openMenu(taskDetail)">{{$L('已归档')}}</span>
                </div>
                <div class="nav">
                    <p v-if="projectName"><span>{{projectName}}</span></p>
                    <p v-if="columnName"><span>{{columnName}}</span></p>
                    <p v-if="taskDetail.id"><span>{{taskDetail.id}}</span></p>
                </div>
                <div class="function">
                    <EPopover
                        v-if="getOwner.length === 0"
                        v-model="receiveShow"
                        placement="bottom">
                        <div class="task-detail-receive">
                            <div class="receive-title">
                                <Icon type="ios-help-circle"/>
                                {{$L('确认计划时间领取任务')}}
                            </div>
                            <div class="receive-time">
                                <DatePicker
                                    v-model="timeValue"
                                    :options="timeOptions"
                                    format="yyyy/MM/dd HH:mm"
                                    type="datetimerange"
                                    :placeholder="$L('请设置计划时间')"
                                    :clearable="false"
                                    :editable="false"/>
                            </div>
                            <div class="receive-bottom">
                                <Button size="small" type="text" @click="receiveShow=false">取消</Button>
                                <Button :loading="ownerLoad > 0" size="small" type="primary" @click="onOwner(true)">确定</Button>
                            </div>
                        </div>
                        <Button slot="reference" :loading="ownerLoad > 0" class="pick" type="primary">{{$L('我要领取任务')}}</Button>
                    </EPopover>
                    <ETooltip v-if="$Electron" :content="$L('新窗口打开')">
                        <i class="taskfont open" @click="openNewWin">&#xe776;</i>
                    </ETooltip>
                    <div class="menu">
                        <TaskMenu
                            :disabled="taskId === 0"
                            :task="taskDetail"
                            icon="ios-more"
                            completed-icon="ios-more"
                            size="medium"
                            :color-show="false"
                            @on-update="getLogLists"/>
                    </div>
                </div>
            </div>
            <div class="scroller overlay-y">
                <div class="title">
                    <Input
                        v-model="taskDetail.name"
                        ref="name"
                        type="textarea"
                        :rows="1"
                        :autosize="{ minRows: 1, maxRows: 8 }"
                        :maxlength="255"
                        @on-blur="updateBlur('name')"
                        @on-keydown="onNameKeydown"/>
                </div>
                <div class="desc">
                    <TEditor
                        ref="desc"
                        :value="taskContent"
                        :plugins="taskPlugins"
                        :options="taskOptions"
                        :option-full="taskOptionFull"
                        :placeholder="$L('详细描述...')"
                        @on-blur="updateBlur('content')"
                        inline/>
                </div>
                <Form class="items" label-position="left" label-width="auto" @submit.native.prevent>
                    <FormItem v-if="taskDetail.p_name">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe6ec;</i>{{$L('优先级')}}
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
                                                class="taskfont"
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
                            <i class="taskfont">&#xe6e4;</i>{{$L('负责人')}}
                        </div>
                        <Poptip
                            ref="owner"
                            :title="$L('修改负责人')"
                            :width="240"
                            class="item-content user"
                            popper-class="task-detail-user-popper"
                            placement="bottom"
                            @on-popper-show="openOwner"
                            @on-ok="onOwner"
                            transfer>
                            <div slot="content">
                                <UserInput
                                    v-model="ownerData.owner_userid"
                                    :multiple-max="10"
                                    :project-id="taskDetail.project_id"
                                    :placeholder="$L('选择任务负责人')"
                                    :transfer="false"/>
                                <div class="task-detail-avatar-buttons">
                                    <Button size="small" type="primary" @click="$refs.owner.ok()">{{$L('确定')}}</Button>
                                </div>
                            </div>
                            <div class="user-list">
                                <UserAvatar v-for="item in getOwner" :key="item.userid" :userid="item.userid" :size="28" :showName="getOwner.length === 1" tooltipDisabled/>
                            </div>
                        </Poptip>
                    </FormItem>
                    <FormItem v-if="getAssist.length > 0 || assistForce">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe63f;</i>{{$L('协助人员')}}
                        </div>
                        <Poptip
                            ref="assist"
                            :title="$L(getAssist.length > 0 ? '修改协助人员' : '添加协助人员')"
                            :width="280"
                            class="item-content user"
                            popper-class="task-detail-user-popper"
                            placement="bottom"
                            @on-popper-show="openAssist"
                            @on-ok="onAssist"
                            transfer>
                            <div slot="content">
                                <UserInput
                                    v-model="assistData.assist_userid"
                                    :multiple-max="10"
                                    :project-id="taskDetail.project_id"
                                    :disabled-choice="assistData.disabled"
                                    :placeholder="$L('选择任务协助人员')"
                                    :transfer="false"/>
                                <div class="task-detail-avatar-buttons">
                                    <Button size="small" type="primary" @click="$refs.assist.ok()">{{$L('确定')}}</Button>
                                </div>
                            </div>
                            <div v-if="getAssist.length > 0" class="user-list">
                                <UserAvatar v-for="item in getAssist" :key="item.userid" :userid="item.userid" :size="28" :showName="getAssist.length === 1" tooltipDisabled/>
                            </div>
                            <div v-else>--</div>
                        </Poptip>
                    </FormItem>
                    <FormItem v-if="taskDetail.end_at || timeForce">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe6e8;</i>{{$L('截止时间')}}
                        </div>
                        <ul class="item-content">
                            <li>
                                <DatePicker
                                    v-model="timeValue"
                                    :open="timeOpen"
                                    :options="timeOptions"
                                    format="yyyy/MM/dd HH:mm"
                                    type="datetimerange"
                                    @on-open-change="timeChange"
                                    @on-clear="timeClear"
                                    @on-ok="timeOk"
                                    transfer>
                                    <div class="picker-time">
                                        <div @click="openTime" class="time">{{taskDetail.end_at ? cutTime : '--'}}</div>
                                        <template v-if="!taskDetail.complete_at && taskDetail.end_at">
                                            <Tag v-if="within24Hours(taskDetail.end_at)" color="blue"><i class="taskfont">&#xe71d;</i>{{expiresFormat(taskDetail.end_at)}}</Tag>
                                            <Tag v-if="isOverdue(taskDetail)" color="red">{{$L('超期未完成')}}</Tag>
                                        </template>
                                    </div>
                                </DatePicker>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="fileList.length > 0">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe6e6;</i>{{$L('附件')}}
                        </div>
                        <ul class="item-content file">
                            <li v-if="taskDetail.file_num > 50" class="tip">{{$L(`共${taskDetail.file_num}个文件，仅显示最新50个`)}}</li>
                            <li v-for="file in fileList">
                                <img v-if="file.id" class="file-ext" :src="file.thumb"/>
                                <Loading v-else class="file-load"/>
                                <div class="file-name">{{file.name}}</div>
                                <div class="file-size">{{$A.bytesToSize(file.size)}}</div>
                                <div class="file-menu" :class="{show:file._show_menu}">
                                    <Icon @click="viewFile(file)" type="md-eye" />
                                    <Icon @click="downFile(file)" type="md-arrow-round-down" />
                                    <EPopover v-model="file._show_menu" class="file-delete">
                                        <div class="task-detail-delete-file-popover">
                                            <p>{{$L('你确定要删除这个文件吗？')}}</p>
                                            <div class="buttons">
                                                <Button size="small" type="text" @click="file._show_menu=false">{{$L('取消')}}</Button>
                                                <Button size="small" type="primary" @click="deleteFile(file)">{{$L('确定')}}</Button>
                                            </div>
                                        </div>
                                        <i slot="reference" class="taskfont del">&#xe6ea;</i>
                                    </EPopover>
                                </div>
                            </li>
                        </ul>
                        <ul class="item-content">
                            <li>
                                <div class="add-button" @click="$refs.upload.handleClick()">
                                    <i class="taskfont">&#xe6f2;</i>{{$L('添加附件')}}
                                </div>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="subList.length > 0 || addsubForce">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe6f0;</i>{{$L('子任务')}}
                        </div>
                        <ul class="item-content subtask">
                            <TaskDetail
                                v-for="(task, key) in subList"
                                :ref="`subTask_${task.id}`"
                                :key="key"
                                :task-id="task.id"
                                :open-task="task"
                                :main-end-at="taskDetail.end_at"
                                :can-update-blur="canUpdateBlur"/>
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
                                    :class="{loading: addsubLoad > 0}"
                                    @on-blur="addsubChackClose"
                                    @on-keydown="addsubKeydown"/>
                                <div v-else class="add-button" @click="addsubOpen">
                                    <i class="taskfont">&#xe6f2;</i>{{$L('添加子任务')}}
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
                            <i class="taskfont">&#xe6f2;</i>
                            {{$L('添加')}}
                            <em v-for="item in menuList">{{$L(item.name)}}</em>
                        </div>
                        <EDropdownMenu slot="dropdown">
                            <EDropdownItem v-for="(item, key) in menuList" :key="key" :command="item.command">
                                <div class="item">
                                    <i class="taskfont" v-html="item.icon"></i>{{$L(item.name)}}
                                </div>
                            </EDropdownItem>
                        </EDropdownMenu>
                    </EDropdown>
                </div>
            </div>
            <TaskUpload ref="upload" class="upload" @on-select-file="onSelectFile"/>
        </div>
        <div v-show="taskDetail.id > 0" class="task-dialog" :style="dialogStyle">
            <template v-if="hasOpenDialog">
                <DialogWrapper v-if="taskId > 0" ref="dialog" :dialog-id="taskDetail.dialog_id">
                    <div slot="head" class="head">
                        <Icon class="icon" type="ios-chatbubbles-outline" />
                        <div class="nav">
                            <p :class="{active:navActive=='dialog'}" @click="navActive='dialog'">{{$L('聊天')}}</p>
                            <p :class="{active:navActive=='log'}" @click="navActive='log'">{{$L('动态')}}</p>
                            <div v-if="navActive=='log'" class="refresh">
                                <Loading v-if="logLoadIng"/>
                                <Icon v-else type="ios-refresh" @click="getLogLists"></Icon>
                            </div>
                        </div>
                    </div>
                </DialogWrapper>
                <ProjectLog v-if="navActive=='log' && taskId > 0" ref="log" :task-id="taskDetail.id" @on-load-change="logLoadChange"/>
            </template>
            <div v-else>
                <div class="head">
                    <Icon class="icon" type="ios-chatbubbles-outline" />
                    <div class="nav">
                        <p :class="{active:navActive=='dialog'}" @click="navActive='dialog'">{{$L('聊天')}}</p>
                        <p :class="{active:navActive=='log'}" @click="navActive='log'">{{$L('动态')}}</p>
                        <div v-if="navActive=='log'" class="refresh">
                            <Loading v-if="logLoadIng"/>
                            <Icon v-else type="ios-refresh" @click="getLogLists"></Icon>
                        </div>
                    </div>
                </div>
                <ProjectLog v-if="navActive=='log' && taskId > 0" ref="log" :task-id="taskDetail.id" :show-load="false" @on-load-change="logLoadChange"/>
                <div v-else class="no-dialog"
                     @drop.prevent="taskPasteDrag($event, 'drag')"
                     @dragover.prevent="taskDragOver(true, $event)"
                     @dragleave.prevent="taskDragOver(false, $event)">
                    <div class="no-tip">{{$L('暂无消息')}}</div>
                    <div class="no-input">
                        <DragInput
                            class="dialog-input"
                            v-model="msgText"
                            type="textarea"
                            :disabled="sendLoad > 0"
                            :rows="1"
                            :autosize="{ minRows: 1, maxRows: 3 }"
                            :maxlength="20000"
                            :placeholder="$L('输入消息...')"
                            @on-keydown="msgKeydown"
                            @on-input-paste="msgPasteDrag"/>
                        <div class="no-send" @click="msgDialog">
                            <Loading v-if="sendLoad > 0"/>
                            <template v-else>
                                <Badge :count="taskDetail.msg_num"/>
                                <Icon type="md-send" />
                            </template>
                        </div>
                    </div>
                    <div v-if="dialogDrag" class="drag-over" @click="dialogDrag=false">
                        <div class="drag-text">{{$L('拖动到这里发送')}}</div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="!taskDetail.id" class="task-load"><Loading/></div>
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
import {Store} from "le5le-store";
import TaskMenu from "./TaskMenu";
import DragInput from "../../../components/DragInput";

export default {
    name: "TaskDetail",
    components: {DragInput, TaskMenu, ProjectLog, DialogWrapper, TaskUpload, UserInput, TaskPriority, TEditor},
    props: {
        taskId: {
            type: Number,
            default: 0
        },
        openTask: {
            type: Object,
            default: () => {
                return {};
            }
        },
        mainEndAt: {
            default: null
        },
        // 允许失去焦点更新
        canUpdateBlur: {
            type: Boolean,
            default: true
        },
    },
    data() {
        return {
            ready: false,

            taskDetail: {},

            ownerData: {},
            ownerLoad: 0,

            receiveShow: false,

            assistForce: false,
            assistData: {},
            assistLoad: 0,

            addsubForce: false,
            addsubShow: false,
            addsubName: "",
            addsubLoad: 0,

            timeForce: false,
            timeOpen: false,
            timeValue: [],
            timeOptions: {shortcuts:$A.timeOptionShortcuts()},

            nowTime: $A.Time(),
            nowInterval: null,

            innerHeight: Math.min(1100, window.innerHeight),

            msgText: '',
            msgFile: [],
            navActive: 'dialog',
            logLoadIng: false,

            sendLoad: 0,

            taskPlugins: [
                'advlist autolink lists link image charmap print preview hr anchor pagebreak',
                'searchreplace visualblocks visualchars code',
                'insertdatetime media nonbreaking save table directionality',
                'emoticons paste codesample',
                'autoresize'
            ],
            taskOptions: {
                statusbar: false,
                menubar: false,
                autoresize_bottom_margin: 2,
                min_height: 200,
                max_height: 380,
                contextmenu: 'bold italic underline forecolor backcolor | codesample | uploadImages imagePreview | preview screenload',
                valid_elements : 'a[href|target=_blank],em,strong/b,div[align],span[style],a,br,p,img[src|alt|witdh|height],pre[class],code',
                toolbar: false
            },
            taskOptionFull: {
                menubar: 'file edit view',
                valid_elements : 'a[href|target=_blank],em,strong/b,div[align],span[style],a,br,p,img[src|alt|witdh|height],pre[class],code',
                toolbar: 'uploadImages | bold italic underline forecolor backcolor | codesample | preview screenload'
            },

            dialogDrag: false,
            receiveTaskSubscribe: null,
        }
    },

    mounted() {
        this.nowInterval = setInterval(() => {
            this.nowTime = $A.Time();
        }, 1000);
        window.addEventListener('resize', this.innerHeightListener);
        //
        this.receiveTaskSubscribe = Store.subscribe('receiveTask', () => {
            this.receiveShow = true;
        });
    },

    destroyed() {
        clearInterval(this.nowInterval);
        window.removeEventListener('resize', this.innerHeightListener);
        //
        if (this.receiveTaskSubscribe) {
            this.receiveTaskSubscribe.unsubscribe();
            this.receiveTaskSubscribe = null;
        }
    },

    computed: {
        ...mapState([
            'userId',
            'userToken',
            'cacheProjects',
            'cacheColumns',
            'cacheTasks',
            'taskContents',
            'taskFiles',
            'taskPriority',

            'windowMax768'
        ]),

        projectName() {
            if (!this.taskDetail.project_id) {
                return ''
            }
            if (this.taskDetail.project_name) {
                return this.taskDetail.project_name;
            }
            const project = this.cacheProjects.find(({id}) => id == this.taskDetail.project_id)
            return project ? project.name : '';
        },

        columnName() {
            if (!this.taskDetail.column_id) {
                return ''
            }
            if (this.taskDetail.column_name) {
                return this.taskDetail.column_name;
            }
            const column = this.cacheColumns.find(({id}) => id == this.taskDetail.column_id)
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
                return b.id - a.id;
            });
        },

        subList() {
            if (!this.taskId) {
                return [];
            }
            return this.cacheTasks.filter(task => {
                return task.parent_id == this.taskId
            }).sort((a, b) => {
                return a.id - b.id;
            });
        },

        hasOpenDialog() {
            return this.taskDetail.dialog_id > 0 && !this.windowMax768;
        },

        dialogStyle() {
            const {innerHeight, hasOpenDialog} = this;
            if (!innerHeight) {
                return {};
            }
            if (!hasOpenDialog) {
                return {};
            }
            return {
                minHeight: (innerHeight - (innerHeight > 900 ? 200 : 70) - 48) + 'px'
            }
        },

        cutTime() {
            const {taskDetail} = this;
            let start_at = $A.Date(taskDetail.start_at, true);
            let end_at = $A.Date(taskDetail.end_at, true);
            let string = "";
            if ($A.formatDate('Y/m/d', start_at) == $A.formatDate('Y/m/d', end_at)) {
                string = $A.formatDate('Y/m/d H:i', start_at) + " ~ " + $A.formatDate('H:i', end_at)
            } else if ($A.formatDate('Y', start_at) == $A.formatDate('Y', end_at)) {
                string = $A.formatDate('Y/m/d H:i', start_at) + " ~ " + $A.formatDate('m/d H:i', end_at)
                string = string.replace(/( 00:00| 23:59)/g, "")
            } else {
                string = $A.formatDate('Y/m/d H:i', start_at) + " ~ " + $A.formatDate('Y/m/d H:i', end_at)
                string = string.replace(/( 00:00| 23:59)/g, "")
            }
            return string
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
                this.$nextTick(() => {
                    this.$refs.name && this.$refs.name.resizeTextarea();
                })
            },
            immediate: true,
            deep: true
        },
        taskId: {
            handler(id) {
                if (id > 0) {
                    this.ready = true;
                } else {
                    this.timeOpen = false;
                    this.timeForce = false;
                    this.assistForce = false;
                    this.addsubForce = false;
                    this.receiveShow = false;
                    this.$refs.owner && this.$refs.owner.handleClose();
                    this.$refs.assist && this.$refs.assist.handleClose();
                }
            },
            immediate: true
        },
        receiveShow(val) {
            if (val) {
                this.timeValue = this.taskDetail.end_at ? [this.taskDetail.start_at, this.taskDetail.end_at] : [];
            }
        }
    },

    methods: {
        innerHeightListener() {
            this.innerHeight = Math.min(1100, window.innerHeight);
        },

        within24Hours(date) {
            return $A.Date(date, true) - this.nowTime < 86400
        },

        expiresFormat(date) {
            return $A.countDownFormat(date, this.nowTime)
        },

        isOverdue(taskDetail) {
            if (taskDetail.overdue) {
                return true;
            }
            return $A.Date(taskDetail.end_at, true) < this.nowTime;
        },

        onNameKeydown(e) {
            if (e.keyCode === 13) {
                if (!e.shiftKey) {
                    e.preventDefault();
                    this.updateData('name');
                }
            }
        },

        checkUpdate(action) {
            let isModify = false;
            if (this.openTask.name != this.taskDetail.name) {
                isModify = true;
                if (action === true) {
                    this.updateData('name');
                } else {
                    action === false && this.$refs.name.focus();
                    return true
                }
            }
            if (this.$refs.desc && this.$refs.desc.getContent() != this.taskContent) {
                isModify = true;
                if (action === true) {
                    this.updateData('content');
                } else {
                    action === false && this.$refs.desc.focus();
                    return true
                }
            }
            if (this.addsubShow && this.addsubName) {
                isModify = true;
                if (action === true) {
                    this.onAddsub();
                } else {
                    action === false && this.$refs.addsub.focus();
                    return true
                }
            }
            this.subList.some(({id}) => {
                if (this.$refs[`subTask_${id}`][0].checkUpdate(action)) {
                    isModify = true;
                }
            })
            return isModify;
        },

        updateBlur(action, params) {
            if (this.canUpdateBlur) {
                this.updateData(action, params)
            }
        },

        updateData(action, params) {
            let successCallback = null;
            switch (action) {
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
                    const content = this.$refs.desc.getContent();
                    if (content == this.taskContent) {
                        return;
                    }
                    this.$set(this.taskDetail, 'content', content)
                    successCallback = () => {
                        this.$store.dispatch("saveTaskContent", {
                            task_id: this.taskId,
                            content
                        })
                    }
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
                $A.messageSuccess(msg);
                if (typeof successCallback === "function") successCallback();
            }).catch(({msg}) => {
                $A.modalError(msg);
            })
        },

        openOwner() {
            const list = this.getOwner.map(({userid}) => userid)
            this.$set(this.taskDetail, 'owner_userid', list)
            this.$set(this.ownerData, 'owner_userid', list)
        },

        onOwner(pick) {
            let data = {
                task_id: this.taskDetail.id,
                owner: this.ownerData.owner_userid
            }
            //
            if (pick === true) {
                if (this.getOwner.length > 0) {
                    this.receiveShow = false;
                    $A.messageError("任务已被领取");
                    return;
                }
                let times = $A.date2string(this.timeValue, "Y-m-d H:i");
                if (times[0] && times[1]) {
                    if ($A.rightExists(times[0], '00:00') && $A.rightExists(times[1], '00:00')) {
                        times[1] = times[1].replace("00:00", "23:59");
                    }
                } else {
                    $A.messageError("请设置计划时间");
                    return;
                }
                data.times = times;
                data.owner = this.ownerData.owner_userid = [this.userId];
            }
            if ($A.jsonStringify(this.taskDetail.owner_userid) === $A.jsonStringify(this.ownerData.owner_userid)) {
                return;
            }
            //
            if ($A.count(data.owner) == 0) data.owner = '';
            this.ownerLoad++;
            this.$store.dispatch("taskUpdate", data).then(({msg}) => {
                $A.messageSuccess(msg);
                this.ownerLoad--;
                this.receiveShow = false;
                this.$store.dispatch("getTaskOne", this.taskDetail.id).catch(() => {})
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.ownerLoad--;
                this.receiveShow = false;
            })
        },

        openAssist() {
            const list = this.getAssist.map(({userid}) => userid)
            this.$set(this.taskDetail, 'assist_userid', list)
            this.$set(this.assistData, 'assist_userid', list);
            this.$set(this.assistData, 'disabled', this.getOwner.map(({userid}) => userid))
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
                this.$store.dispatch("getTaskOne", this.taskDetail.id).catch(() => {})
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.assistLoad--;
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
            this.updateData('times', {
                start_at: false,
                end_at: false,
            });
            this.timeOpen = false;
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
                if (e.shiftKey || this.addsubLoad > 0) {
                    return;
                }
                e.preventDefault();
                this.onAddsub();
            }
        },

        onAddsub() {
            if (this.addsubName == '') {
                $A.messageError('任务描述不能为空');
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

        getLogLists() {
            if (this.navActive != 'log') {
                return;
            }
            this.$refs.log.getLists(true);
        },

        logLoadChange(load) {
            this.logLoadIng = load
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
                if (this.msgText) {
                    this.msgDialog();
                }
            }
        },

        msgDialog() {
            if (this.sendLoad > 0) {
                return;
            }
            this.sendLoad++;
            //
            this.$store.dispatch("call", {
                url: 'project/task/dialog',
                data: {
                    task_id: this.taskDetail.id,
                },
            }).then(({data}) => {
                this.$store.dispatch("saveTask", data);
                this.$store.dispatch("getDialogOne", data.dialog_id).then(() => {
                    this.sendLoad--;
                    if ($A.isSubElectron) {
                        this.resizeDialog().then(() => {
                            this.sendDialogMsg();
                        });
                    } else {
                        this.$nextTick(() => {
                            if (this.windowMax768) {
                                window.__sendDialogMsg = {
                                    time: $A.Time() + 10,
                                    msgText: this.msgText,
                                    msgFile: this.msgFile
                                };
                                this.msgFile = [];
                                this.msgText = "";
                                this.goForward({name: 'manage-messenger', query: {_: $A.randomString(6)}});
                                $A.setStorage("messenger::dialogId", data.dialog_id)
                                this.$store.state.dialogOpenId = data.dialog_id;
                                this.$store.dispatch('openTask', 0);
                            } else {
                                this.sendDialogMsg();
                            }
                        });
                    }
                }).catch(({msg}) => {
                    this.sendLoad--;
                    $A.modalError(msg);
                });
            }).catch(({msg}) => {
                this.sendLoad--;
                $A.modalError(msg);
            });
        },

        sendDialogMsg() {
            if (this.msgFile.length > 0) {
                this.$refs.dialog.sendFileMsg(this.msgFile);
            } else if (this.msgText) {
                this.$refs.dialog.sendMsg(this.msgText);
            }
            this.msgFile = [];
            this.msgText = "";
        },

        msgPasteDrag(e, type) {
            const files = type === 'drag' ? e.dataTransfer.files : e.clipboardData.files;
            this.msgFile = Array.prototype.slice.call(files);
            if (this.msgFile.length > 0) {
                e.preventDefault();
                this.msgDialog()
            }
        },

        taskPasteDrag(e, type) {
            this.dialogDrag = false;
            this.msgPasteDrag(e, type);
        },

        taskDragOver(show, e) {
            let random = (this.__dialogDrag = $A.randomString(8));
            if (!show) {
                setTimeout(() => {
                    if (random === this.__dialogDrag) {
                        this.dialogDrag = show;
                    }
                }, 150);
            } else {
                if (e.dataTransfer.effectAllowed === 'move') {
                    return;
                }
                this.dialogDrag = true;
            }
        },

        onSelectFile(file) {
            this.msgFile = [file];
            this.msgDialog()
        },

        deleteFile(file) {
            this.$set(file, '_show_menu', false);
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
        },

        openMenu(task) {
            const el = this.$refs[`taskMenu_${task.id}`];
            el && el.handleClick()
        },

        openNewWin() {
            let config = {
                title: this.taskDetail.name,
                titleFixed: true,
                parent: null,
                width: Math.min(window.screen.availWidth, this.$el.clientWidth + 72),
                height: Math.min(window.screen.availHeight, this.$el.clientHeight + 72),
                minWidth: 600,
                minHeight: 450,
            };
            if (this.hasOpenDialog) {
                config.minWidth = 800;
                config.minHeight = 600;
            }
            this.$Electron.sendMessage('windowRouter', {
                name: 'task-' + this.taskDetail.id,
                path: "/single/task/" + this.taskDetail.id,
                force: false,
                config
            });
            this.$store.dispatch('openTask', 0);
        },

        resizeDialog() {
            return new Promise(resolve => {
                this.$Electron.sendSyncMessage('windowSize', {
                    width: Math.max(1100, window.innerWidth),
                    height: Math.max(720, window.innerHeight),
                    minWidth: 800,
                    minHeight: 600,
                    autoZoom: true,
                });
                let num = 0;
                let interval = setInterval(() => {
                    num++;
                    if (this.$refs.dialog || num > 20) {
                        clearInterval(interval);
                        if (this.$refs.dialog) {
                            resolve()
                        }
                    }
                }, 100);
            })
        },

        viewFile(file) {
            if (['jpg', 'jpeg', 'gif', 'png'].includes(file.ext)) {
                const list = this.fileList.filter(item => ['jpg', 'jpeg', 'gif', 'png'].includes(item.ext))
                const index = list.findIndex(item => item.id === file.id);
                if (index > -1) {
                    this.$store.state.previewImageIndex = index;
                    this.$store.state.previewImageList = list.map(({path}) => path);
                } else {
                    this.$store.state.previewImageIndex = 0;
                    this.$store.state.previewImageList = [file.path];
                }
                return
            }
            if (this.$Electron) {
                this.$Electron.sendMessage('windowRouter', {
                    name: 'file-task-' + file.id,
                    path: "/single/file/task/" + file.id,
                    userAgent: "/hideenOfficeTitle/",
                    force: false,
                    config: {
                        title: `${file.name} (${$A.bytesToSize(file.size)})`,
                        titleFixed: true,
                        parent: null,
                        width: Math.min(window.screen.availWidth, 1440),
                        height: Math.min(window.screen.availHeight, 900),
                    }
                });
            } else {
                window.open($A.apiUrl(`../single/file/task/${file.id}`))
            }
        },

        downFile(file) {
            $A.modalConfirm({
                title: '下载文件',
                content: `${file.name} (${$A.bytesToSize(file.size)})`,
                okText: '立即下载',
                onOk: () => {
                    this.$store.dispatch('downUrl', $A.apiUrl(`project/task/filedown?file_id=${file.id}`))
                }
            });
        }
    }
}
</script>
