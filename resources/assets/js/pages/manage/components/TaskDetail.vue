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
            <span :class="taskDetail.flow_item_status" @click.stop="openMenu($event, taskDetail)">{{taskDetail.flow_item_name}}</span>
        </div>
        <div class="subtask-name">
            <Input
                v-model="taskDetail.name"
                ref="name"
                type="textarea"
                :rows="1"
                :autosize="{ minRows: 1, maxRows: 8 }"
                :maxlength="255"
                enterkeyhint="done"
                @on-blur="updateBlur('name')"
                @on-keydown="onNameKeydown"
            />
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
            @on-change="taskTimeChange"
            @on-clear="timeClear"
            @on-ok="timeOk"
            transfer>
            <div v-if="!taskDetail.complete_at && taskDetail.end_at && taskDetail.end_at != mainEndAt" @click="openTime" :class="['time', taskDetail.today ? 'today' : '', taskDetail.overdue ? 'overdue' : '']">
                {{expiresFormat(taskDetail.end_at)}}
            </div>
            <Icon v-else class="clock" type="ios-clock-outline" @click="openTime" />
        </DatePicker>
        <UserSelect
            class="subtask-avatar"
            v-model="ownerData.owner_userid"
            :multiple-max="10"
            :avatar-size="20"
            :title="$L('修改负责人')"
            :add-icon="false"
            :project-id="taskDetail.project_id"
            :before-submit="onOwner"/>
    </li>
    <!--主任务-->
    <div
        v-else-if="ready"
        :class="{'task-detail':true, 'open-dialog': hasOpenDialog, 'completed': taskDetail.complete_at}"
        :style="taskDetailStyle">
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
                    <span :class="taskDetail.flow_item_status" @click.stop="openMenu($event, taskDetail)">{{taskDetail.flow_item_name}}</span>
                </div>
                <div v-if="taskDetail.archived_at" class="flow">
                    <span class="archived" @click.stop="openMenu($event, taskDetail)">{{$L('已归档')}}</span>
                </div>
                <div class="nav">
                    <p v-if="projectName"><span>{{projectName}}</span></p>
                    <p v-if="columnName"><span>{{columnName}}</span></p>
                    <p v-if="taskDetail.id"><span>{{taskDetail.id}}</span></p>
                </div>
                <div class="function">
                    <ETooltip v-if="$Electron" :disabled="$isEEUiApp || windowTouch" :content="$L('新窗口打开')">
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
                            :show-load="false"
                            @on-update="getLogLists"/>
                    </div>
                </div>
            </div>
            <Scrollbar ref="scroller" class="scroller">
                <Alert v-if="getOwner.length === 0" class="receive-box" type="warning">
                    <span class="receive-text">{{$L('该任务尚未被领取，点击这里')}}</span>
                    <EPopover
                        v-model="receiveShow"
                        class="receive-button"
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
                                    :editable="false"
                                    @on-change="taskTimeChange"/>
                            </div>
                            <div class="receive-bottom">
                                <Button size="small" type="text" @click="receiveShow=false">{{$L('取消')}}</Button>
                                <Button :loading="ownerLoad > 0" size="small" type="primary" @click="onOwner(true)">{{$L('确定')}}</Button>
                            </div>
                        </div>
                        <Button slot="reference" :loading="ownerLoad > 0" size="small" type="primary">{{$L('领取任务')}}</Button>
                    </EPopover>
                </Alert>
                <div class="title">
                    <Input
                        v-model="taskDetail.name"
                        ref="name"
                        type="textarea"
                        :rows="1"
                        :autosize="{ minRows: 1, maxRows: 8 }"
                        :maxlength="255"
                        enterkeyhint="done"
                        @on-blur="updateBlur('name')"
                        @on-keydown="onNameKeydown"/>
                </div>
                <TEditorTask
                    ref="desc"
                    class="desc"
                    :value="taskContent"
                    :placeholder="$L('详细描述...')"
                    @on-history="onHistory"
                    @on-blur="updateBlur('content', $event)"/>
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
                        <UserSelect
                            class="item-content user"
                            v-model="ownerData.owner_userid"
                            :multiple-max="10"
                            :avatar-size="28"
                            :title="$L('修改负责人')"
                            :project-id="taskDetail.project_id"
                            :add-icon="false"
                            :before-submit="onOwner"/>
                    </FormItem>
                    <FormItem v-if="getAssist.length > 0 || assistForce">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe63f;</i>{{$L('协助人员')}}
                        </div>
                        <UserSelect
                            ref="assist"
                            class="item-content user"
                            v-model="assistData.assist_userid"
                            :multiple-max="10"
                            :avatar-size="28"
                            :title="$L(getAssist.length > 0 ? '修改协助人员' : '添加协助人员')"
                            :project-id="taskDetail.project_id"
                            :disabled-choice="assistData.disabled"
                            :add-icon="false"
                            :before-submit="onAssist"/>
                    </FormItem>
                    <FormItem v-if="taskDetail.visibility > 1 || visibleForce || visibleKeep">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe77b;</i>
                            <span class="visibility-text color" @click="showCisibleDropdown">{{$L('可见性')}} <i class="taskfont">&#xe740;</i></span>
                        </div>
                        <div class="item-content user">
                            <span v-if="taskDetail.visibility == 1 || taskDetail.visibility == 2" ref="visibilityText" class="visibility-text" @click="showCisibleDropdown">{{ taskDetail.visibility == 1 ? $L('项目人员可见') : $L('任务人员可见') }}</span>
                            <UserSelect v-else
                                ref="visibleUserSelectRef"
                                v-model="taskDetail.visibility_appointor"
                                :avatar-size="28"
                                :title="$L('选择指定人员')"
                                :project-id="taskDetail.project_id"
                                :add-icon="false"
                                @on-show-change="visibleUserSelectShowChange"/>
                        </div>
                    </FormItem>
                    <FormItem v-if="taskDetail.end_at || timeForce">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe6e8;</i>
                            <span v-if="!taskDetail.end_at" @click="timeOpen = true" class="visibility-text color">{{$L('截止时间')}}</span>
                            <span v-else class="visibility-text color" @click="showAtDropdown">{{$L('截止时间')}}</span>
                        </div>
                        <ul class="item-content">
                            <li>
                                <DatePicker
                                    disabled
                                    v-model="timeValue"
                                    :open="timeOpen"
                                    :options="timeOptions"
                                    format="yyyy/MM/dd HH:mm"
                                    type="datetimerange"
                                    @on-open-change="timeChange"
                                    @on-change="taskTimeChange"
                                    @on-clear="timeClear"
                                    @on-ok="timeOk"
                                    transfer>
                                    <div class="picker-time">
                                        <div v-if="!taskDetail.end_at" @click="timeOpen = true" class="time">{{taskDetail.end_at ? cutTime : '--'}}</div>
                                        <div v-else @click="showAtDropdown" class="time">{{taskDetail.end_at ? cutTime : '--'}}</div>
                                        <template v-if="!taskDetail.complete_at && taskDetail.end_at">
                                            <Tag v-if="within24Hours(taskDetail.end_at)" :color="tagColor(taskDetail)">
                                                <i class="taskfont">&#xe71d;</i>{{expiresFormat(taskDetail.end_at)}}
                                            </Tag>
                                            <Tag v-if="taskDetail.overdue" color="red">{{$L('超期未完成')}}</Tag>
                                        </template>
                                    </div>
                                </DatePicker>
                            </li>
                        </ul>

                    </FormItem>
                    <FormItem v-if="(taskDetail.loop && taskDetail.loop != 'never') || loopForce">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe93f;</i>{{$L('重复周期')}}
                        </div>
                        <ul class="item-content">
                            <li>
                                <EDropdown
                                    ref="loop"
                                    trigger="click"
                                    placement="bottom"
                                    @command="updateData('loop', $event)">
                                    <ETooltip :disabled="$isEEUiApp || windowTouch || !taskDetail.loop_at" :content="`${$L('下个周期')}: ${taskDetail.loop_at}`" placement="right">
                                        <span>{{$L(loopLabel(taskDetail.loop))}}</span>
                                    </ETooltip>
                                    <EDropdownMenu slot="dropdown" class="task-detail-loop">
                                        <EDropdownItem v-for="item in loops" :key="item.key" :command="item.key">
                                            {{$L(item.label)}}
                                        </EDropdownItem>
                                    </EDropdownMenu>
                                </EDropdown>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="fileList.length > 0">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe6e6;</i>{{$L('附件')}}
                        </div>
                        <ul class="item-content file">
                            <li v-if="taskDetail.file_num > 50" class="tip">{{$L(`共${taskDetail.file_num}个文件，仅显示最新50个`)}}</li>
                            <li v-for="file in fileList" @click="showFileDropdown(file, $event)">
                                <img v-if="file.id" class="file-ext" :src="file.thumb"/>
                                <Loading v-else class="file-load"/>
                                <div class="file-name">{{file.name}}</div>
                                <div class="file-size">{{$A.bytesToSize(file.size)}}</div>
                            </li>
                        </ul>
                        <ul class="item-content">
                            <li>
                                <div class="add-button" @click="onUploadClick(true)">
                                    <i class="taskfont">&#xe6f2;</i>
                                    <span>{{$L('添加附件')}}</span>
                                </div>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="subList.length > 0 || addsubForce" className="item-subtask">
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
                                    enterkeyhint="done"
                                    @on-blur="addsubChackClose"
                                    @on-keydown="addsubKeydown"/>
                                <div v-else class="add-button" @click="addsubOpen">
                                    <i class="taskfont">&#xe6f2;</i>
                                    <span>{{$L('添加子任务')}}</span>
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
                            <span>{{$L('添加')}}</span>
                            <em>{{menuText}}</em>
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
                <EDropdown ref="eDropdownRef" class="calculate-dropdown" trigger="click" placement="bottom" @command="dropVisible">
                    <div class="calculate-content"></div>
                    <EDropdownMenu slot="dropdown">
                        <EDropdownItem :command="1">
                            <div class="task-menu-icon">
                                <Icon v-if="taskDetail.visibility == 1" class="completed" :type="'md-checkmark-circle'"/>
                                <Icon v-else class="uncomplete" :type="'md-radio-button-off'"/>
                                {{$L('项目人员')}}
                            </div>
                        </EDropdownItem>
                        <EDropdownItem :command="2">
                            <div class="task-menu-icon">
                                <Icon v-if="taskDetail.visibility == 2" class="completed" :type="'md-checkmark-circle'"/>
                                <Icon v-else class="uncomplete" :type="'md-radio-button-off'"/>
                                {{$L('任务人员')}}
                            </div>
                        </EDropdownItem>
                        <EDropdownItem :command="3">
                            <div class="task-menu-icon">
                                <Icon v-if="taskDetail.visibility == 3" class="completed" :type="'md-checkmark-circle'"/>
                                <Icon v-else class="uncomplete" :type="'md-radio-button-off'"/>
                                {{$L('指定成员')}}
                            </div>
                        </EDropdownItem>
                    </EDropdownMenu>
                </EDropdown>
                <EDropdown ref="eDeadlineRef" class="calculate-dropdown" trigger="click" placement="bottom" @command="dropDeadline">
                    <div class="calculate-content"></div>
                    <EDropdownMenu slot="dropdown">
                        <EDropdownItem :command="1">
                            {{$L('任务延期')}}
                        </EDropdownItem>
                        <EDropdownItem :command="2">
                            {{$L('修改时间')}}
                        </EDropdownItem>
                        <EDropdownItem :command="3">
                            {{$L('清除时间')}}
                        </EDropdownItem>
                    </EDropdownMenu>
                </EDropdown>
                <EDropdown ref="eFileRef" class="calculate-dropdown" trigger="click" placement="bottom" @command="dropFile">
                    <div class="calculate-content"></div>
                    <EDropdownMenu slot="dropdown">
                        <EDropdownItem :command="1">
                            {{$L('查看附件')}}
                        </EDropdownItem>
                        <EDropdownItem :command="2">
                            {{$L('下载附件')}}
                        </EDropdownItem>
                        <EDropdownItem :command="3" class="task-calc-warn-text">
                            {{$L('删除附件')}}
                        </EDropdownItem>
                    </EDropdownMenu>
                </EDropdown>
            </Scrollbar>
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
                    <div class="menu">
                        <div v-if="navActive=='dialog' && taskDetail.msg_num > 0" class="menu-item" @click.stop="onSend('open')">
                            <div v-if="openLoad > 0" class="menu-load"><Loading/></div>
                            {{$L('任务聊天')}}
                            <em>({{taskDetail.msg_num > 999 ? '999+' : taskDetail.msg_num}})</em>
                            <i class="taskfont">&#xe703;</i>
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
                        <ChatInput
                            ref="chatInput"
                            :task-id="taskId"
                            v-model="msgText"
                            :loading="sendLoad > 0"
                            :maxlength="200000"
                            :placeholder="$L('输入消息...')"
                            :send-menu="false"
                            @on-more="onEventMore"
                            @on-file="onSelectFile"
                            @on-record="onRecord"
                            @on-send="onSend"/>
                    </div>
                    <div v-if="dialogDrag" class="drag-over" @click="dialogDrag=false">
                        <div class="drag-text">{{$L('拖动到这里发送')}}</div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="!taskDetail.id" class="task-load"><Loading/></div>
        <!-- 提示  -->
        <TaskExistTips ref="taskExistTipsRef" @onContinue="updateData('timesSave', updateParams)"/>
        <!--任务延期-->
        <Modal
            v-model="delayTaskShow"
            :title="$L('任务延期')"
            :mask-closable="false"
            :styles="{
                width: '90%',
                maxWidth: '450px'
            }">
            <Form
                ref="formDelayTaskRef"
                :model="delayTaskForm"
                :rules="delayTaskRule"
                v-bind="formOptions"
                @submit.native.prevent>
                <FormItem :label="$L('延期时长')" prop="time">
                    <Input type="number" v-model="delayTaskForm.time" :placeholder="$L('请输入时长')">
                        <template #append>
                            <Select v-model="delayTaskForm.type" style="width:auto">
                                <Option value="hour">{{$L('小时')}}</Option>
                                <Option value="day">{{$L('天')}}</Option>
                            </Select>
                        </template>
                    </Input>
                </FormItem>
                <FormItem :label="$L('延期备注')" prop="remark">
                    <Input type="textarea" v-model="delayTaskForm.remark" :placeholder="$L('请输入修改备注')"></Input>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button @click="delayTaskShow=false">{{$L('关闭')}}</Button>
                <Button type="primary" @click="onDelay">{{$L('确定')}}</Button>
            </div>
        </Modal>
        <!--任务描述历史记录-->
        <Modal
            v-model="historyShow"
            :title="$L('任务描述历史记录')"
            :mask-closable="false"
            :styles="{
                width: '90%',
                maxWidth: '700px'
            }">
            <TaskContentHistory v-if="historyShow" :task-id="taskDetail.id" :task-name="taskDetail.name"/>
            <div slot="footer">
                <Button @click="historyShow=false">{{$L('关闭')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import {mapState} from "vuex";
import TaskPriority from "./TaskPriority";
import TaskUpload from "./TaskUpload";
import DialogWrapper from "./DialogWrapper";
import ProjectLog from "./ProjectLog";
import {Store} from "le5le-store";
import TaskMenu from "./TaskMenu";
import ChatInput from "./ChatInput";
import UserSelect from "../../../components/UserSelect.vue";
import TaskExistTips from "./TaskExistTips.vue";
import TEditorTask from "../../../components/TEditorTask.vue";
import TaskContentHistory from "./TaskContentHistory.vue";

export default {
    name: "TaskDetail",
    components: {
        TaskContentHistory,
        TEditorTask,
        UserSelect,
        TaskExistTips,
        ChatInput,
        TaskMenu,
        ProjectLog,
        DialogWrapper,
        TaskUpload,
        TaskPriority,
    },
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
        // 是否Modal模式
        modalMode: {
            type: Boolean,
            default: false
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

            visibleForce: false,

            addsubForce: false,
            addsubShow: false,
            addsubName: "",
            addsubLoad: 0,

            timeForce: false,
            timeOpen: false,
            timeValue: [],
            timeOptions: {shortcuts: $A.timeOptionShortcuts()},

            loopForce: false,

            nowTime: $A.dayjs().unix(),
            nowInterval: null,

            msgText: '',
            msgFile: [],
            msgRecord: {},
            navActive: 'dialog',
            logLoadIng: false,

            sendLoad: 0,
            openLoad: 0,

            dialogDrag: false,
            imageAttachment: true,
            receiveTaskSubscribe: null,

            loops: [
                {key: 'never', label: '从不'},
                {key: 'day', label: '每天'},
                {key: 'weekdays', label: '工作日'},
                {key: 'week', label: '每周'},
                {key: 'twoweeks', label: '每两周'},
                {key: 'month', label: '每月'},
                {key: 'year', label: '每年'},
                {key: 'custom', label: '自定义'},
            ],

            updateParams: {},

            delayTaskShow: false,
            delayTaskForm: {
                type: "hour",
                time: "24",
                remark: ""
            },
            delayTaskRule: {
                time: [
                    { required: true,  message: this.$L('请输入时长'), trigger: 'blur' },
                ],
                remark: [
                    { required: true, message: this.$L('请输入备注'), trigger: 'blur' },
                ],
            },

            historyShow: false,
        }
    },

    created() {
        const navActive = $A.getObject(this.$route.query, 'navActive')
        if (['dialog', 'log'].includes(navActive)) {
            this.navActive = navActive;
        }
        $A.IDBJson('delayTaskForm').then(data => {
            data.type && this.$set(this.delayTaskForm, 'type', data.type);
            data.time && this.$set(this.delayTaskForm, 'time', data.time);
        });
    },

    mounted() {
        this.nowInterval = setInterval(() => {
            this.nowTime = $A.dayjs().unix();
        }, 1000);
        //
        this.receiveTaskSubscribe = Store.subscribe('receiveTask', () => {
            this.receiveShow = true;
        });
    },

    destroyed() {
        clearInterval(this.nowInterval);
        //
        if (this.receiveTaskSubscribe) {
            this.receiveTaskSubscribe.unsubscribe();
            this.receiveTaskSubscribe = null;
        }
    },

    computed: {
        ...mapState([
            'systemConfig',

            'cacheProjects',
            'cacheColumns',
            'cacheTasks',

            'taskContents',
            'taskFiles',
            'taskPriority',

            'dialogId',

            'formOptions'
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
            return this.taskDetail.dialog_id > 0 && this.windowLandscape;
        },

        dialogStyle() {
            const {windowHeight, hasOpenDialog} = this;
            const height = Math.min(1100, windowHeight)
            if (!height) {
                return {};
            }
            if (!hasOpenDialog) {
                return {};
            }
            const factor = height > 900 ? 200 : 70;
            return {
                minHeight: (height - factor - 48) + 'px'
            }
        },

        taskDetailStyle() {
            const {modalMode, windowHeight, hasOpenDialog} = this;
            const height = Math.min(1100, windowHeight)
            if (modalMode && hasOpenDialog) {
                const factor = height > 900 ? 200 : 70;
                return {
                    maxHeight: (height - factor - 30) + 'px'
                }
            }
            return {}
        },

        cutTime() {
            const {taskDetail} = this;
            let start_at = $A.dayjs(taskDetail.start_at);
            let end_at = $A.dayjs(taskDetail.end_at);
            let string = "";
            if (start_at.format('YYYY/MM/DD') == end_at.format('YYYY/MM/DD')) {
                string = start_at.format('YYYY/MM/DD HH:mm') + " ~ " + end_at.format('HH:mm')
            } else if (start_at.year() == end_at.year()) {
                string = start_at.format('YYYY/MM/DD HH:mm') + " ~ " + end_at.format('MM/DD HH:mm')
                string = string.replace(/( 00:00| 23:59)/g, "")
            } else {
                string = start_at.format('YYYY/MM/DD HH:mm') +end_at.format('YYYY/MM/DD HH:mm')
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
            return taskDetail.task_user.filter(({owner}) => owner === 0).sort((a, b) => {
                return a.id - b.id;
            });
        },

        menuList() {
            const {taskDetail} = this;
            const list = [];
            if (!taskDetail.p_name) {
                list.push({
                    command: 'priority',
                    icon: '&#xe6ec;',
                    name: '优先级',
                });
            }
            if (!($A.isArray(taskDetail.task_user) && taskDetail.task_user.find(({owner}) => owner === 0 ))) {
                list.push({
                    command: 'assist',
                    icon: '&#xe63f;',
                    name: '协助人员',
                });
            }
            if (taskDetail.visibility <= 1 && !this.visibleKeep) {
                list.push({
                    command: 'visible',
                    icon: '&#xe77b;',
                    name: '可见性',
                });
            }
            if (!taskDetail.end_at) {
                list.push({
                    command: 'times',
                    icon: '&#xe6e8;',
                    name: '截止时间',
                });
            }
            if (!taskDetail.loop || taskDetail.loop == 'never') {
                list.push({
                    command: 'loop',
                    icon: '&#xe93f;',
                    name: '重复周期',
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

        menuText() {
            const {menuList} = this
            let text = ''
            if (menuList.length > 0) {
                menuList.forEach((item, index) => {
                    if (index > 0) {
                        text += " / "
                    }
                    text += this.$L(item.name)
                })
            }
            return text
        },

        visibleKeep() {
            return this.systemConfig.task_visible === 'open'    // 可见性保持显示
        },
    },

    watch: {
        openTask: {
            handler(data) {
                this.taskDetail = $A.cloneJSON(data);
                this.__openTask && clearTimeout(this.__openTask);
                this.__openTask = setTimeout(_ => this.$refs.name?.resizeTextarea(), 100)
            },
            immediate: true,
            deep: true
        },
        taskId: {
            handler(id) {
                if (id > 0) {
                    this.ready = true;
                } else {
                    if (this.windowPortrait) {
                        $A.onBlur();
                    }
                    this.timeOpen = false;
                    this.timeForce = false;
                    this.loopForce = false;
                    this.assistForce = false;
                    this.visibleForce = false;
                    this.addsubForce = false;
                    this.receiveShow = false;
                    this.$refs.chatInput && this.$refs.chatInput.hidePopover();
                }
            },
            immediate: true
        },
        getOwner: {
            handler(arr) {
                const list = arr.map(({userid}) => userid)
                this.$set(this.taskDetail, 'owner_userid', list)
                this.$set(this.ownerData, 'owner_userid', list)
                this.$set(this.assistData, 'disabled', arr.map(({userid}) => userid).filter(userid => userid != this.userId))
            },
            immediate: true
        },
        getAssist: {
            handler(arr) {
                const list = arr.map(({userid}) => userid)
                this.$set(this.taskDetail, 'assist_userid', list)
                this.$set(this.assistData, 'assist_userid', list);
            },
            immediate: true
        },
        receiveShow(val) {
            if (val) {
                this.timeValue = this.taskDetail.end_at ? [this.taskDetail.start_at, this.taskDetail.end_at] : [];
            }
        },
        "taskDetail.visibility_appointor": {
            handler(arr) {
                if (arr?.filter(id=>id).length > 0) {
                    this.taskDetail.visibility = 3
                    this.updateVisible()
                }
            },
            immediate: true
        },
    },

    methods: {
        within24Hours(date) {
            return ($A.dayjs(date).unix() - this.nowTime) < 86400
        },

        expiresFormat(date) {
            return $A.countDownFormat(this.nowTime, date)
        },

        tagColor(taskDetail) {
            if (taskDetail.overdue) {
                return 'red';
            }
            if (taskDetail.today) {
                return 'orange';
            }
            return 'blue'
        },

        loopLabel(loop) {
            const item = this.loops.find(item => item.key === loop)
            if (item) {
                return item.label
            }
            return loop ? `每${loop}天` : '从不'
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

        onHistory() {
            this.historyShow = true;
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
                    // 没有开始时间，直接保存
                    if (!this.taskDetail.start_at) {
                        this.isExistTask(params).then(() => {
                            this.updateData("timesSave", params)
                        });
                        return;
                    }
                    // 时间变化未超过1分钟，不保存
                    if (Math.abs($A.dayjs(this.taskDetail.start_at).unix() - $A.dayjs(params.start_at).unix()) < 60 && Math.abs($A.dayjs(this.taskDetail.end_at).unix() - $A.dayjs(params.end_at).unix()) < 60) {
                        return;
                    }
                    // 已经有备注，直接保存
                    if (params.desc) {
                        this.isExistTask(params).then(() => {
                            this.updateData("timesSave", params)
                        });
                        return;
                    }
                    // 弹出修改备注
                    let isClear = !params.start_at || !params.end_at;
                    let title = `修改${this.taskDetail.parent_id > 0 ? '子任务' : '任务'}时间`
                    let placeholder = `请输入修改备注`
                    if (isClear) {
                        title = `清除${this.taskDetail.parent_id > 0 ? '子任务' : '任务'}时间`
                        placeholder = `请输入清除备注`
                    }
                    $A.modalInput({
                        title,
                        placeholder,
                        okText: "确定",
                        okType: isClear ? "warning" : "primary",
                        onOk: (desc) => {
                            if (!desc) {
                                return placeholder
                            }
                            params.desc = desc;
                            this.isExistTask(params).then(() => {
                                this.updateData("timesSave", params)
                            })
                            return false
                        },
                    })
                    return;

                case 'timesSave':
                    action = 'times';
                    this.$set(this.taskDetail, 'times', [params.start_at, params.end_at, params.desc])
                    break;

                case 'loop':
                    if (params === 'custom') {
                        this.customLoop()
                        return;
                    }
                    this.$set(this.taskDetail, 'loop', params)
                    break;

                case 'content':
                    const content = this.$refs.desc.getContent();
                    if (content == this.taskContent.replace(/\s+original-(width|height)="[^"]*"/g, "")) {
                        return;
                    }
                    if (!this.windowTouch || params === 'force') {
                        this.updateData("contentSave", {content})
                        return;
                    }
                    $A.modalConfirm({
                        title: '温馨提示',
                        content: '是否保存编辑内容？',
                        onOk: () => {
                            this.updateData("contentSave", {content})
                        },
                        onCancel: () => {
                            this.$refs.desc.updateContent(this.taskContent);
                        }
                    });
                    return;

                case 'contentSave':
                    this.$set(this.taskDetail, 'content', params.content)
                    action = 'content';
                    successCallback = () => {
                        this.$store.dispatch("saveTaskContent", {
                            task_id: this.taskId,
                            content: params.content
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

        isExistTask(params) {
            return new Promise(resolve => {
                if (!params.start_at || !params.end_at) {
                    resolve()
                    return
                }
                this.updateParams = Object.assign({}, params)
                const tipsRef = this.$refs.taskExistTipsRef
                if (!tipsRef) {
                    resolve()
                    return
                }
                tipsRef.isExistTask({
                    taskid: this.taskDetail.id,
                    userids: this.taskDetail.owner_userid,
                    timerange: [params.start_at, params.end_at]
                }, 600).then(res => {
                    !res && resolve()
                })
            })
        },

        customLoop() {
            let value = this.taskDetail.loop || 1
            $A.Modal.confirm({
                render: (h) => {
                    return h('div', [
                        h('div', {
                            style: {
                                fontSize: '16px',
                                fontWeight: '500',
                                marginBottom: '20px',
                            }
                        }, this.$L('重复周期')),
                        h('Input', {
                            style: {
                                width: '160px',
                                margin: '0 auto',
                            },
                            props: {
                                type: 'number',
                                value,
                                maxlength: 3
                            },
                            on: {
                                input: (val) => {
                                    value = $.runNum(val)
                                }
                            }
                        }, [
                            h('span', {slot: 'prepend'}, this.$L('每')),
                            h('span', {slot: 'append'}, this.$L('天'))
                        ])
                    ])
                },
                onOk: _ => {
                    this.$Modal.remove()
                    if (value > 0) {
                        this.updateData('loop', value)
                    }
                },
                loading: true,
                okText: this.$L('确定'),
                cancelText: this.$L('取消'),
            });
        },

        async taskTimeChange() {
            const times = $A.newDateString(this.timeValue, "YYYY-MM-DD HH:mm");
            if (/\s+(00:00|23:59)$/.test(times[0]) && /\s+(00:00|23:59)$/.test(times[1])) {
                this.timeValue = await this.$store.dispatch("taskDefaultTime", times)
            }
        },

        async onOwner(pick) {
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
                const times = $A.newDateString(this.timeValue, "YYYY-MM-DD HH:mm");
                if (!(times[0] && times[1])) {
                    $A.messageError("请设置计划时间");
                    return;
                }
                data.times = times;
                data.owner = this.ownerData.owner_userid = [this.userId];
            }
            if ($A.jsonStringify(this.taskDetail.owner_userid) === $A.jsonStringify(this.ownerData.owner_userid)) {
                return;
            }
            if ($A.count(data.owner) == 0) {
                data.owner = '';
            }
            //
            this.ownerLoad++;
            return new Promise((resolve, reject) => {
                this.$store.dispatch("taskUpdate", data).then(({msg}) => {
                    $A.messageSuccess(msg);
                    this.ownerLoad--;
                    this.receiveShow = false;
                    this.$store.dispatch("getTaskOne", this.taskDetail.id).catch(() => {})
                    resolve()
                }).catch(({msg}) => {
                    $A.modalError(msg);
                    this.ownerLoad--;
                    this.receiveShow = false;
                    reject()
                })
            })
        },

        onAssist() {
            if ($A.jsonStringify(this.taskDetail.assist_userid) === $A.jsonStringify(this.assistData.assist_userid)) {
                return;
            }
            return new Promise((resolve, reject) => {
                if (this.getOwner.find(({userid}) => userid === this.userId) && this.assistData.assist_userid.find(userid => userid === this.userId)) {
                    $A.modalConfirm({
                        content: '你当前是负责人，确定要转为协助人员吗？',
                        cancelText: '取消',
                        okText: '确定',
                        onOk: () => {
                            this.onAssistConfirm().then(resolve).catch(reject)
                        },
                        onCancel: () => {
                            reject()
                        }
                    })
                } else {
                    this.onAssistConfirm().then(resolve).catch(reject)
                }
            })
        },

        onAssistConfirm() {
            return new Promise((resolve, reject) => {
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
                    resolve()
                }).catch(({msg}) => {
                    $A.modalError(msg);
                    this.assistLoad--;
                    reject()
                })
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
            const times = $A.newDateString(this.timeValue, "YYYY-MM-DD HH:mm");
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
                    this.$nextTick(() => {
                        this.$refs.assist.onSelection();
                    });
                    break;

                case 'visible':
                    this.visibleForce = true;
                    this.$nextTick(() => {
                        this.showCisibleDropdown(null);
                    });
                    break;

                case 'times':
                    this.timeForce = true;
                    this.$nextTick(() => {
                        this.openTime()
                    })
                    break;

                case 'loop':
                    this.loopForce = true;
                    this.$nextTick(() => {
                        this.$refs.loop.show();
                    })
                    break;

                case 'file':
                    this.onUploadClick(true)
                    break;

                case 'subtask':
                    this.addsubForce = true;
                    this.$nextTick(() => {
                        this.addsubOpen();
                    });
                    break;
            }
        },

        onEventMore(e) {
            if (['image', 'file'].includes(e)) {
                this.onUploadClick(false)
            }
        },

        onUploadClick(attachment) {
            this.imageAttachment = !!attachment;
            this.$refs.upload.handleClick()
        },

        msgDialog(msgText = null, onlyOpen = false) {
            if (this.sendLoad > 0 || this.openLoad > 0) {
                return;
            }
            if (onlyOpen === true) {
                this.openLoad++;
            } else {
                this.sendLoad++;
            }
            //
            this.$store.dispatch("call", {
                url: 'project/task/dialog',
                data: {
                    task_id: this.taskDetail.id,
                },
            }).then(({data}) => {
                this.$store.dispatch("saveTask", {
                    id: data.id,
                    dialog_id: data.dialog_id,
                });
                this.$store.dispatch("saveDialog", data.dialog_data);
                //
                if ($A.isSubElectron) {
                    this.resizeDialog().then(() => {
                        this.sendDialogMsg(msgText);
                    });
                } else {
                    this.$nextTick(() => {
                        if (this.windowPortrait) {
                            $A.onBlur();
                            const transferData = {
                                time: $A.dayjs().unix() + 10,
                                msgRecord: this.msgRecord,
                                msgFile: this.msgFile,
                                msgText: typeof msgText === 'string' && msgText ? msgText : this.msgText,
                                dialogId: data.dialog_id,
                            };
                            this.msgRecord = {};
                            this.msgFile = [];
                            this.msgText = "";
                            this.$nextTick(_ => {
                                if (this.dialogId > 0) {
                                    this.$store.dispatch("openTask", 0)    // 如果当前打开着对话窗口则关闭任务窗口
                                }
                                this.$store.dispatch('openDialog', data.dialog_id).then(_ => {
                                    this.$store.state.dialogMsgTransfer = transferData
                                })
                            })
                        } else {
                            this.sendDialogMsg(msgText);
                        }
                    });
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                if (onlyOpen === true) {
                    this.openLoad--;
                } else {
                    this.sendLoad--;
                }
            });
        },

        sendDialogMsg(msgText = null) {
            if (this.msgFile.length > 0) {
                this.$refs.dialog.sendFileMsg(this.msgFile.map(file => Object.assign(file, {
                    ajaxExtraData: {
                        image_attachment: this.imageAttachment ? 1 : 0
                    }
                })));
            } else if (this.msgText) {
                this.$refs.dialog.sendMsg(this.msgText);
            } else if (typeof msgText === 'string' && msgText) {
                this.$refs.dialog.sendMsg(msgText);
            }
            this.msgFile = [];
            this.msgText = "";
        },

        taskPasteDrag(e, type) {
            this.dialogDrag = false;
            if ($A.dataHasFolder(type === 'drag' ? e.dataTransfer : e.clipboardData)) {
                e.preventDefault();
                $A.modalWarning(`暂不支持${type === 'drag' ? '拖拽' : '粘贴'}文件夹。`)
                return;
            }
            const files = type === 'drag' ? e.dataTransfer.files : e.clipboardData.files;
            this.msgFile = Array.prototype.slice.call(files);
            if (this.msgFile.length > 0) {
                e.preventDefault();
                this.msgDialog()
            }
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

        onSelectFile(row) {
            this.msgFile = $A.isArray(row) ? row : [row];
            this.msgDialog()
        },

        onRecord(row) {
            this.msgRecord = row;
            this.msgDialog()
        },

        onSend(msgText) {
            this.$refs.chatInput && this.$refs.chatInput.hidePopover();
            if (msgText === 'open') {
                this.msgDialog(null, true);
            } else {
                this.msgDialog(msgText);
            }
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

        openMenu(event, task) {
            const el = this.$refs[`taskMenu_${task.id}`];
            el && el.handleClick(event)
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
            this.$store.dispatch('openChildWindow', {
                name: `task-${this.taskDetail.id}`,
                path: `/single/task/${this.taskDetail.id}?navActive=${this.navActive}`,
                force: false,
                config
            });
            this.$store.dispatch('openTask', 0);
        },

        resizeDialog() {
            return new Promise(resolve => {
                this.$Electron.sendMessage('windowSize', {
                    width: Math.max(1100, this.windowWidth),
                    height: Math.max(720, this.windowHeight),
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
            if (['jpg', 'jpeg', 'webp', 'gif', 'png'].includes(file.ext)) {
                const list = this.fileList.filter(item => ['jpg', 'jpeg', 'webp', 'gif', 'png'].includes(item.ext))
                const index = list.findIndex(item => item.id === file.id);
                if (index > -1) {
                    this.$store.dispatch("previewImage", {
                        index,
                        list: list.map(item => {
                            return {
                                src: item.path,
                                width: item.width,
                                height: item.height,
                            }
                        })
                    })
                } else {
                    this.$store.dispatch("previewImage", {
                        index: 0,
                        list: [{
                            src: file.path,
                            width: file.width,
                            height: file.height,
                        }]
                    })
                }
                return
            }
            const path = `/single/file/task/${file.id}`;
            if (this.$Electron) {
                this.$store.dispatch('openChildWindow', {
                    name: `file-task-${file.id}`,
                    path: path,
                    userAgent: "/hideenOfficeTitle/",
                    force: false,
                    config: {
                        title: `${file.name} (${$A.bytesToSize(file.size)})`,
                        titleFixed: true,
                        parent: null,
                        width: Math.min(window.screen.availWidth, 1440),
                        height: Math.min(window.screen.availHeight, 900),
                    },
                    webPreferences: {
                        nodeIntegrationInSubFrames: file.ext === 'drawio'
                    },
                });
            } else if (this.$isEEUiApp) {
                this.$store.dispatch('openAppChildPage', {
                    pageType: 'app',
                    pageTitle: `${file.name} (${$A.bytesToSize(file.size)})`,
                    url: 'web.js',
                    params: {
                        titleFixed: true,
                        allowAccess: true,
                        url: $A.rightDelete(window.location.href, window.location.hash) + `#${path}`
                    },
                });
            } else {
                window.open($A.mainUrl(path.substring(1)))
            }
        },

        downFile(file) {
            $A.modalConfirm({
                language: false,
                title: this.$L('下载文件'),
                okText: this.$L('立即下载'),
                content: `${file.name} (${$A.bytesToSize(file.size)})`,
                onOk: () => {
                    this.$store.dispatch('downUrl', $A.apiUrl(`project/task/filedown?file_id=${file.id}`))
                }
            });
        },

        showDropdown(ref, eRect){
            const boxRect = this.$refs.scroller.$el.getBoundingClientRect()
            const refEl = ref.$el
            refEl.style.top = (eRect.top - boxRect.top) + 'px'
            refEl.style.left = (eRect.left - boxRect.left) + 'px'
            refEl.style.width = eRect.width + 'px'
            refEl.style.height = eRect.height + 'px'
            //
            if (ref.visible) {
                ref.hide()
            }
            setTimeout(() => {
                ref.show()
            }, 0)
        },

        showCisibleDropdown(e){
            let eRect = null
            if (e === null) {
                eRect = this.$refs.visibilityText?.getBoundingClientRect()
            } else {
                eRect = e.target.getBoundingClientRect()
            }
            if (eRect === null) {
                return
            }
            this.showDropdown(this.$refs.eDropdownRef, eRect)
        },

        showAtDropdown({target}){
            this.timeOpen = false
            this.showDropdown(this.$refs.eDeadlineRef, target.getBoundingClientRect())
        },

        visibleUserSelectShowChange(isShow){
            if (!isShow && this.taskDetail.visibility_appointor.filter(id => id).length == 0) {
                let old = this.taskDetail.old_visibility;
                this.taskDetail.visibility = old > 2 ? 1 : (old || 1);
                if (this.taskDetail.visibility < 3) {
                    this.updateVisible();
                }
            }
        },

        dropVisible(command) {
            switch (command) {
                case 1:
                case 2:
                    this.taskDetail.visibility = command
                    this.updateVisible();
                    break;
                case 3:
                    this.taskDetail.old_visibility = this.taskDetail.visibility
                    this.taskDetail.visibility = command
                    this.$nextTick(() => {
                        this.$refs.visibleUserSelectRef.onSelection()
                    });
                    break;
            }
        },

        dropDeadline(command) {
            switch (command) {
                case 1:
                    this.delayTaskShow = true;
                    break;
                case 2:
                    this.openTime()
                    break;
                case 3:
                    this.updateData('times', {start_at: false, end_at: false})
                    break;
            }
        },

        onDelay(){
            this.$refs.formDelayTaskRef.validate((valid) => {
                if (!valid) {
                    return
                }
                const endAt = $A.dayjs(this.taskDetail.end_at).add(this.delayTaskForm.time, this.delayTaskForm.type)
                this.updateData('times', {
                    start_at: this.taskDetail.start_at,
                    end_at: endAt.format('YYYY-MM-DD HH:mm:ss'),
                    desc: this.delayTaskForm.remark,
                })
                this.delayTaskShow = false
                this.delayTaskForm.remark = ''
                $A.IDBSet('delayTaskForm', this.delayTaskForm)
            })
        },

        showFileDropdown(file, {target}){
            this.operationFile = file
            this.showDropdown(this.$refs.eFileRef, target.getBoundingClientRect())
        },

        dropFile(command) {
            switch (command) {
                case 1:
                    this.viewFile(this.operationFile)
                    break;
                case 2:
                    this.downFile(this.operationFile)
                    break;
                case 3:
                    $A.modalConfirm({
                        title: '删除文件',
                        content: `你确定要删除文件【${this.operationFile.name}】吗？`,
                        onOk: () => {
                            this.deleteFile(this.operationFile)
                        }
                    });
                    break;
            }
        },

        updateVisible() {
            this.updateData(['visibility', 'visibility_appointor'])
        }
    }
}
</script>
