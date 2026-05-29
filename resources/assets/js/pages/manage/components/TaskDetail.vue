<template>
    <!--子任务-->
    <li v-if="ready && isSubTask">
        <div v-if="!isDepartmentReadonly" class="subtask-icon">
            <TaskMenu
                :ref="`taskMenu_${taskDetail.id}`"
                :disabled="taskId === 0"
                :task="taskDetail"
                :load-status="taskDetail.loading === true"
                @on-update="getLogLists"/>
        </div>
        <div
            v-if="taskDetail.flow_item_name"
            class="subtask-flow"
            :style="$A.generateColorVarStyle(taskDetail.flow_item_color, [10], 'flow-item-custom-color')">
            <span :class="taskDetail.flow_item_status" @click.stop="!isDepartmentReadonly && openMenu($event, taskDetail)">{{taskDetail.flow_item_name}}</span>
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
                :readonly="isDepartmentReadonly"
                @on-blur="updateBlur('name')"
                @on-keydown="onNameKeydown"
            />
        </div>
        <DatePicker
            v-if="!isDepartmentReadonly"
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
            <div v-if="showSubTime" @click="openTime" :class="['time', taskDetail.today ? 'today' : '', taskDetail.overdue ? 'overdue' : '']">
                {{expiresFormat(taskDetail.end_at)}}
            </div>
            <Icon v-else class="clock" type="ios-clock-outline" @click="openTime" />
        </DatePicker>
        <div v-else-if="showSubTime" :class="['subtask-time readonly-time', taskDetail.today ? 'today' : '', taskDetail.overdue ? 'overdue' : '']">
            {{expiresFormat(taskDetail.end_at)}}
        </div>
        <UserSelect
            v-if="!isDepartmentReadonly || (ownerData.owner_userid && ownerData.owner_userid.length > 0)"
            class="subtask-avatar"
            v-model="ownerData.owner_userid"
            :multiple-max="10"
            :avatar-size="20"
            :title="$L('修改负责人')"
            :add-icon="false"
            :project-id="taskDetail.project_id"
            :disabled="isDepartmentReadonly"
            :before-submit="onOwner"/>
    </li>
    <!--主任务-->
    <div
        v-else-if="ready"
        class="task-detail"
        :class="taskDetailClass"
        :style="taskDetailStyle">
        <div v-show="taskDetail.id > 0" class="task-info" v-resize-observer="scrollIntoInput">
            <div class="head">
                <TaskMenu
                    v-if="!isDepartmentReadonly"
                    :ref="`taskMenu_${taskDetail.id}`"
                    :disabled="taskId === 0"
                    :task="taskDetail"
                    class="icon"
                    size="medium"
                    :color-show="false"
                    @on-update="getLogLists"/>
                <div
                    v-if="taskDetail.flow_item_name"
                    class="flow"
                    :style="$A.generateColorVarStyle(taskDetail.flow_item_color, [10], 'flow-item-custom-color')">
                    <span :class="taskDetail.flow_item_status" @click.stop="!isDepartmentReadonly && openMenu($event, taskDetail)">{{taskDetail.flow_item_name}}</span>
                </div>
                <div v-if="taskDetail.archived_at" class="flow">
                    <span class="archived" @click.stop="!isDepartmentReadonly && openMenu($event, taskDetail)">{{$L('已归档')}}</span>
                </div>
                <div class="nav user-select-auto">
                    <p v-if="projectName"><span>{{projectName}}</span></p>
                    <p v-if="columnName"><span>{{columnName}}</span></p>
                    <p v-if="taskDetail.id"><span>{{taskDetail.id}}</span></p>
                </div>
                <div class="function">
                    <ETooltip v-if="$Electron" :disabled="$isEEUIApp || windowTouch" :content="$L('独立窗口显示')">
                        <i class="taskfont open" @click="openNewWin">&#xe776;</i>
                    </ETooltip>
                    <div v-if="!isDepartmentReadonly" class="menu">
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
            <Scrollbar ref="scroller" class="scroller" :touch-content-blur="false">
                <Alert v-if="taskDetail.department_readonly" class="task-readonly-alert" type="info" show-icon>
                    {{$L('当前为负责人视角，并参与讨论，但不能编辑任务。')}}
                </Alert>
                <Alert v-if="!isDepartmentReadonly && taskDetail.task_user !== undefined && getOwner.length === 0" class="receive-box" type="warning">
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
                        :readonly="isDepartmentReadonly"
                        @on-blur="updateBlur('name')"
                        @on-keydown="onNameKeydown"/>
                </div>
                <TEditorTask
                    ref="desc"
                    class="desc"
                    :value="taskContent"
                    :placeholder="$L('详细描述...')"
                    :readonly="isDepartmentReadonly"
                    @on-history="onHistory"
                    @on-blur="updateBlur('content', $event)"/>
                <Form class="items" label-position="left" label-width="auto" @submit.native.prevent>
                    <FormItem v-if="getTag.length > 0 || tagForce">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe61e;</i>{{$L('标签')}}
                        </div>
                        <div class="item-content tags">
                            <TaskTag v-if="isDepartmentReadonly" :tags="getTag"/>
                            <EPopover v-else v-model="tagShow" class="tags-select" placement="bottom">
                                <TaskTagSelect
                                    ref="tagSelect"
                                    v-model="tagValue"
                                    :data-sources="tagData"
                                    :loading="tagLoad > 0"
                                    :max="10"
                                    @add="onTagAdd"/>
                                <div slot="reference">
                                    <TaskTag :tags="getTag">
                                        <li v-if="getTag.length === 0" slot="end" class="add-icon"></li>
                                    </TaskTag>
                                </div>
                            </EPopover>
                        </div>
                    </FormItem>
                    <FormItem v-if="taskDetail.p_name">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe6ec;</i>{{$L('优先级')}}
                        </div>
                        <ul class="item-content priority">
                            <li>
                                <TaskPriority :backgroundColor="taskDetail.p_color"><span ref="priorityText" @click="!isDepartmentReadonly && onPriority($event)">{{taskDetail.p_name}}</span></TaskPriority>
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
                            :disabled="isDepartmentReadonly"
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
                            :disabled="isDepartmentReadonly"
                            :before-submit="onAssist"/>
                    </FormItem>
                    <FormItem v-if="taskDetail.visibility > 1 || visibleForce || visibleKeep">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe77b;</i>
                            <span class="visibility-text color" @click="!isDepartmentReadonly && showCisibleDropdown($event)">{{$L('可见性')}} <i class="taskfont">&#xe740;</i></span>
                        </div>
                        <div class="item-content user">
                            <span v-if="taskDetail.visibility == 1 || taskDetail.visibility == 2" ref="visibilityText" class="visibility-text" @click="!isDepartmentReadonly && showCisibleDropdown($event)">{{ taskDetail.visibility == 1 ? $L('项目人员可见') : $L('任务人员可见') }}</span>
                            <UserSelect v-else
                                ref="visibleUserSelectRef"
                                v-model="taskDetail.visibility_appointor"
                                :avatar-size="28"
                                :title="$L('选择指定人员')"
                                :project-id="taskDetail.project_id"
                                :add-icon="false"
                                :disabled="isDepartmentReadonly"
                                @on-show-change="visibleUserSelectShowChange"/>
                        </div>
                    </FormItem>
                    <FormItem v-if="taskDetail.end_at || timeForce">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe6e8;</i>
                            <span v-if="!taskDetail.end_at" @click="!isDepartmentReadonly && (timeOpen = true)" class="visibility-text color">{{$L('截止时间')}}</span>
                            <span v-else class="visibility-text color" @click="!isDepartmentReadonly && showAtDropdown($event)">{{$L('截止时间')}}</span>
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
                                        <div v-if="!taskDetail.end_at" @click="!isDepartmentReadonly && (timeOpen = true)" class="time">{{taskDetail.end_at ? cutTime : '--'}}</div>
                                        <div v-else @click="!isDepartmentReadonly && showAtDropdown($event)" class="time">{{taskDetail.end_at ? cutTime : '--'}}</div>
                                        <template v-if="!taskDetail.complete_at && taskDetail.end_at">
                                            <Tag v-if="within24Hours(taskDetail.end_at)" :color="tagColor(taskDetail)" @on-click="!isDepartmentReadonly && showAtDropdown($event)">
                                                <i class="taskfont">&#xe71d;</i>{{expiresFormat(taskDetail.end_at)}}
                                            </Tag>
                                            <Tag v-if="taskDetail.overdue" color="red" @on-click="!isDepartmentReadonly && showAtDropdown($event)">{{$L('超期未完成')}}</Tag>
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
                        <ul class="item-content loop">
                            <li>
                                <ETooltip :disabled="$isEEUIApp || windowTouch || !taskDetail.loop_at" :content="`${$L('下个周期')}: ${taskDetail.loop_at}`" placement="right">
                                    <span ref="loopText" @click="!isDepartmentReadonly && onLoop($event)">{{$L(loopLabel(taskDetail.loop))}}</span>
                                </ETooltip>
                            </li>
                        </ul>
                    </FormItem>
                    <FormItem v-if="fileList.length > 0">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe6e6;</i>{{$L('附件')}}
                        </div>
                        <ul class="item-content file">
                            <li v-if="taskDetail.file_num > 50" class="tip">{{$L(`共${taskDetail.file_num}个文件，仅显示最新50个`)}}</li>
                            <li v-for="(file, index) in fileList" :key="index" @click="showFileDropdown(file, $event)">
                                <img v-if="file.id" class="file-ext" :src="file.thumb"/>
                                <Loading v-else class="file-load"/>
                                <div class="file-name">{{file.name}}</div>
                                <div class="file-size">{{$A.bytesToSize(file.size)}}</div>
                            </li>
                        </ul>
                        <ul v-if="!isDepartmentReadonly" class="item-content file-up">
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
                        <ul v-if="subList.length > 0" class="item-content subtask">
                            <TaskDetail
                                v-for="(task, key) in subList"
                                :ref="`subTask_${task.id}`"
                                :key="key"
                                :task-id="task.id"
                                :open-task="task"
                                :main-end-at="taskDetail.end_at"
                                :can-update-blur="canUpdateBlur"/>
                        </ul>
                        <ul v-if="!isDepartmentReadonly" class="item-content subtask-add">
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
                    <FormItem v-if="relatedTasks.length > 0" className="item-related-task">
                        <div class="item-label" slot="label">
                            <i class="taskfont">&#xe7d6;</i>{{$L('关联任务')}}
                        </div>
                        <ul class="item-content related-task">
                            <li
                                v-for="item in relatedTasks"
                                :key="item.related_task_id"
                                class="related-item"
                                @click="openRelatedTask(item)">
                                <span class="related-direction" :class="{
                                    inbound: item.mentioned_by,
                                    outbound: item.mention,
                                    mutual: item.mention && item.mentioned_by
                                }">
                                    <Icon v-if="item.mention && item.mentioned_by" type="md-swap"/>
                                    <Icon v-else-if="item.mentioned_by" type="md-arrow-round-back"/>
                                    <Icon v-else type="md-arrow-round-forward"/>
                                </span>
                                <span class="related-main">
                                    <span class="related-id">#{{item.related_task_id}}</span>
                                    <span class="related-title">{{item.task.name}}</span>
                                </span>
                                <span v-if="item.task.project_name && item.task.project_id != taskDetail.project_id" class="related-project">{{item.task.project_name}}</span>
                                <span v-if="item.task.column_name" class="related-column">{{item.task.column_name}}</span>
                                <span
                                    v-if="item.task.flow_item_name"
                                    class="related-status"
                                    :class="item.task.flow_item_status"
                                    :style="$A.generateColorVarStyle(item.task.flow_item_color, [10], 'flow-item-custom-color')">
                                    {{item.task.flow_item_name}}
                                </span>
                                <span
                                    v-else-if="item.task.complete_at"
                                    class="related-status end">
                                    {{$L('已完成')}}
                                </span>
                                <span
                                    v-else-if="item.task.archived_at"
                                    class="related-status archived">
                                    {{$L('已归档')}}
                                </span>
                                <Icon
                                    v-if="!isDepartmentReadonly"
                                    type="md-close"
                                    class="related-remove"
                                    @click.native.stop="removeRelatedTask(item)"/>
                            </li>
                        </ul>
                    </FormItem>
                </Form>
                <div v-if="!isDepartmentReadonly && menuList.length > 0" class="add">
                    <div class="add-wrap">
                        <div class="add-button" @click="onAddItem">
                            <i class="taskfont">&#xe6f2;</i>
                            <span>{{$L('添加')}}</span>
                            <em>{{menuText}}</em>
                        </div>
                    </div>
                </div>
            </Scrollbar>
            <TaskUpload ref="upload" class="upload" @on-select-file="onSelectFile"/>
        </div>
        <div
            v-show="taskDetail.id > 0"
            ref="taskDialog"
            class="task-dialog"
            :style="dialogStyle">
            <template v-if="hasOpenDialog">
                <ResizeLine
                    class="task-resize"
                    placement="right"
                    v-model="taskDialogWidth"
                    :min="300"
                    :max="900"
                    :reverse="true"/>
                <DialogWrapper
                    v-if="taskId > 0"
                    ref="dialog"
                    :dialog-id="taskDetail.dialog_id"
                    @on-type-change="onTypeChange">
                    <div slot="head" class="head">
                        <Icon class="icon" type="ios-chatbubbles-outline" />
                        <div class="nav">
                            <div class="nav-item nav-chat" :class="{active:navActive=='dialog'}" @click="navActive='dialog'">
                                {{$L('讨论')}}
                                <span v-if="msgTypes.length > 1" class="msg-type" @click.stop="openTypeClick">
                                    <i class="taskfont">&#xe740;</i>
                                    <em v-if="msgType">{{getTypeLabel(msgType)}}</em>
                                </span>
                            </div>
                            <div class="nav-item" :class="{active:navActive=='log'}" @click="navActive='log'">{{$L('动态')}}</div>
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
                        <div class="nav-item" :class="{active:navActive=='dialog'}" @click="navActive='dialog'">{{$L('讨论')}}</div>
                        <div class="nav-item" :class="{active:navActive=='log'}" @click="navActive='log'">{{$L('动态')}}</div>
                        <div v-if="navActive=='log'" class="refresh">
                            <Loading v-if="logLoadIng"/>
                            <Icon v-else type="ios-refresh" @click="getLogLists"></Icon>
                        </div>
                    </div>
                    <div class="menu">
                        <div v-if="navActive=='dialog' && taskDetail.msg_num > 0" class="menu-item" @click.stop="onOpen">
                            <div v-if="openLoad > 0" class="menu-load"><Loading/></div>
                            {{$L('任务讨论')}}
                            <em>({{taskDetail.msg_num > 999 ? '999+' : taskDetail.msg_num}})</em>
                            <i class="taskfont">&#xe703;</i>
                        </div>
                    </div>
                </div>
                <ProjectLog
                    v-if="navActive=='log' && taskId > 0"
                    ref="log"
                    :task-id="taskDetail.id"
                    :show-load="false"
                    @on-load-change="logLoadChange"/>
                <div
                    v-else
                    class="no-dialog"
                    @drop.prevent="taskPasteDrag($event, 'drag')"
                    @dragover.prevent="taskDragOver(true, $event)"
                    @dragleave.prevent="taskDragOver(false, $event)">
                    <div class="no-input">
                        <ChatInput
                            ref="chatInput"
                            :task-id="taskId"
                            v-model="msgText"
                            :loading="sendLoad > 0"
                            :maxlength="200000"
                            :placeholder="$L('输入消息...')"
                            @on-focus="onFocus"
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
            width="450px">
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
                    <div class="form-tip form-quick-select">
                        <span>{{$L('快捷选择')}}:</span>
                        <em v-for="(item, index) in delayTaskQuicks" :key="index" @click="onTaskQuick(item.time, item.type)">{{$L(item.name)}}</em>
                    </div>
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
        <!--标签添加-->
        <TaskTagAdd ref="addTag" :project-id="taskDetail.project_id" @on-save="onTagAddSave"/>
    </div>
</template>

<script>
import {mapState} from "vuex";
import TaskPriority from "./TaskPriority";
import TaskUpload from "./TaskUpload";
import DialogWrapper from "./DialogWrapper";
import ProjectLog from "./ProjectLog";
import TaskMenu from "./TaskMenu";
import ChatInput from "./ChatInput";
import UserSelect from "../../../components/UserSelect.vue";
import TaskTag from "./ProjectTaskTag/tags.vue";
import TaskTagSelect from "./ProjectTaskTag/select.vue";
import TaskExistTips from "./TaskExistTips.vue";
import TEditorTask from "../../../components/TEditorTask.vue";
import ResizeLine from "../../../components/ResizeLine.vue";
import TaskContentHistory from "./TaskContentHistory.vue";
import TaskTagAdd from "./ProjectTaskTag/add.vue";
import emitter from "../../../store/events";
import resizeObserver from "../../../directives/resize-observer";

export default {
    name: "TaskDetail",
    components: {
        ResizeLine,
        TaskTagAdd,
        TaskContentHistory,
        TEditorTask,
        UserSelect,
        TaskTag,
        TaskTagSelect,
        TaskExistTips,
        ChatInput,
        TaskMenu,
        ProjectLog,
        DialogWrapper,
        TaskUpload,
        TaskPriority,
    },
    directives: {resizeObserver},
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
            taskDialogWidth: $A.getStorageInt('task.dialogWidth', -1),

            ownerData: {},
            ownerLoad: 0,

            receiveShow: false,

            tagForce: false,
            tagShow: false,
            tagValue: [],
            tagBakValue: [],
            tagData: [],
            tagLoad: 0,

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

            relatedTasks: [],
            relatedRequestKey: 0,

            keepInterval: null,
            keepIntoTimer: null,
            keepUnix: $A.dayjs().unix(),

            msgText: '',
            msgFile: [],
            msgRecord: {},
            msgType: '',
            navActive: 'dialog',
            logLoadIng: false,

            sendLoad: 0,
            openLoad: 0,

            dialogDrag: false,
            imageAttachment: true,

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
            delayTaskQuicks: [],
            delayTaskForm: {
                type: "hour",
                time: 24,
                remark: ""
            },
            delayTaskRule: {
                time: [
                    { required: true,  message: this.$L('请输入时长'), trigger: 'blur', pattern: /^\d+(\.\d+)?$/ },
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
            data.time && this.$set(this.delayTaskForm, 'time', Math.round(data.time * 100) / 100);
            data.type && this.$set(this.delayTaskForm, 'type', data.type);
        });
        if (this.taskDialogWidth === -1) {
            this.taskDialogWidth = Math.min(450, Math.min(1200, this.windowWidth * 0.9) * 0.4)
        }
    },

    mounted() {
        this.keepInterval = setInterval(() => {
            this.keepUnix = $A.dayjs().unix();
            this.keepIntoInput();
        }, 1000);
        //
        emitter.on('receiveTask', this.onReceiveShow);
        emitter.on('taskRelationUpdate', this.onTaskRelationUpdate);
    },

    destroyed() {
        clearInterval(this.keepInterval);
        //
        emitter.off('receiveTask', this.onReceiveShow);
        emitter.off('taskRelationUpdate', this.onTaskRelationUpdate);
    },

    computed: {
        ...mapState([
            'systemConfig',

            'cacheProjects',
            'cacheColumns',
            'cacheTasks',
            'cacheDialogs',

            'taskContents',
            'taskFiles',
            'taskPriority',

            'formOptions',
            'keyboardShow'
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
            const content = this.taskContents.find(({task_id}) => task_id == this.taskId)
            return content?.content || ''
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
            const {windowHeight, taskDialogWidth, hasOpenDialog} = this;
            const height = Math.min(1100, windowHeight)
            if (!height) {
                return {};
            }
            if (!hasOpenDialog) {
                return {};
            }
            const factor = height > 900 ? 200 : 70;
            return {
                minHeight: (height - factor - 48) + 'px',
                width: taskDialogWidth + 'px',
            }
        },

        taskDetailClass() {
            const {taskDetail, hasOpenDialog} = this;
            return {
                'open-dialog': hasOpenDialog,
                'completed': taskDetail.complete_at
            }
        },

        taskDetailStyle() {
            const {modalMode, keyboardShow, windowHeight, hasOpenDialog} = this;
            const style = {}
            if (modalMode) {
                if (hasOpenDialog) {
                    style.maxHeight = `${Math.min(1100, windowHeight) - (windowHeight > 900 ? 200 : 70) - 30}px`;
                }
                if (keyboardShow && $A.isIos()) {
                    style.overflow = 'hidden'
                }
            }
            return style
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
                string = start_at.format('YYYY/MM/DD HH:mm') + " ~ " + end_at.format('YYYY/MM/DD HH:mm')
                string = string.replace(/( 00:00| 23:59)/g, "")
            }
            return string
        },

        getTag() {
            const {taskDetail} = this;
            if (!$A.isArray(taskDetail.task_tag)) {
                return [];
            }
            return taskDetail.task_tag;
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
            if (this.isDepartmentReadonly) {
                return [];
            }
            const {taskDetail} = this;
            const list = [];
            if ($A.arrayLength(taskDetail.task_tag) === 0) {
                list.push({
                    command: 'tag',
                    icon: '&#xe61e;',
                    name: '标签',
                });
            }
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

        isDepartmentReadonly() {
            return !!this.taskDetail?.department_readonly;
        },

        isSubTask({taskDetail}) {
            return taskDetail.parent_id > 0
        },

        showSubTime({taskDetail, mainEndAt}) {
            return taskDetail.parent_id > 0
                && !taskDetail.complete_at
                && taskDetail.end_at
                && taskDetail.end_at != mainEndAt
        },

        dialogData({taskDetail}) {
            if (!taskDetail.dialog_id) {
                return {}
            }
            return this.cacheDialogs.find(({id}) => id == taskDetail.dialog_id) || {}
        },

        msgTypes({dialogData}) {
            const array = [
                {value: '', label: this.$L('全部')},
            ];
            if (dialogData.has_tag) {
                array.push({value: 'tag', label: this.$L('标注')})
            }
            if (dialogData.has_todo) {
                array.push({value: 'todo', label: this.$L('事项')})
            }
            if (dialogData.has_image) {
                array.push({value: 'image', label: this.$L('图片')})
            }
            if (dialogData.has_file) {
                array.push({value: 'file', label: this.$L('文件')})
            }
            if (dialogData.has_link) {
                array.push({value: 'link', label: this.$L('链接')})
            }
            return array
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
                    this.loadRelatedTasks();
                } else {
                    $A.eeuiAppKeyboardHide()
                    this.timeOpen = false;
                    this.timeForce = false;
                    this.loopForce = false;
                    this.tagForce = false;
                    this.assistForce = false;
                    this.visibleForce = false;
                    this.addsubForce = false;
                    this.receiveShow = false;
                    this.$refs.chatInput?.hidePopover();
                    this.relatedRequestKey++;
                    this.relatedTasks = [];
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
        tagShow(val) {
            if (val) {
                this.tagValue = this.getTag;
                this.tagBakValue = $A.cloneJSON(this.tagValue);
                //
                const isLoad = this.tagValue.length === 0 && this.tagData.length === 0;
                isLoad && this.tagLoad++;
                this.$store.dispatch("call", {
                    url: "project/tag/list",
                    data: {
                        project_id: this.taskDetail.project_id
                    }
                }).then(res => {
                    this.tagData = res.data;
                }).finally(_ => {
                    isLoad && this.tagLoad--;
                })
            } else {
                const isChanged = (() => {
                    if (this.tagValue.length !== this.tagBakValue.length) return true;
                    const sortValue = arr => [...arr].map(({name, color}) => ({name, color})).sort((a, b) => a.name.localeCompare(b.name));
                    const sortedValue = sortValue(this.tagValue);
                    const sortedBakValue = sortValue(this.tagBakValue);
                    return JSON.stringify(sortedValue) !== JSON.stringify(sortedBakValue);
                })();
                if (isChanged) {
                    this.updateData('tag', this.tagValue);
                }
            }
        },
        taskDialogWidth(w, o) {
            if (o === -1) {
                return;
            }
            $A.setStorage('task.dialogWidth', w);
        }
    },

    methods: {
        onReceiveShow() {
            this.receiveShow = true;
        },

        within24Hours(date) {
            return ($A.dayjs(date).unix() - this.keepUnix) < 86400
        },

        expiresFormat(date) {
            return $A.countDownFormat(this.keepUnix, date)
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
            if (this.isDepartmentReadonly) {
                return;
            }
            if (e.keyCode === 13) {
                if (!e.shiftKey) {
                    e.preventDefault();
                    this.updateData('name');
                }
            }
        },

        checkUpdate(action) {
            if (this.isDepartmentReadonly) {
                return false;
            }
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
            if (this.isDepartmentReadonly) {
                return;
            }

            if (this.canUpdateBlur) {
                this.updateData(action, params)
            }
        },

        updateData(action, params) {
            if (this.isDepartmentReadonly) {
                return;
            }

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
                    // 子任务修改时间，如果之前跟主任务的时间相同，直接保存
                    if (this.isSubTask && !this.showSubTime) {
                        this.isExistTask(params).then(() => {
                            this.updateData("timesSave", params)
                        });
                        return;
                    }
                    // 弹出修改备注
                    let isClear = !params.start_at || !params.end_at;
                    let title = `修改${this.isSubTask ? '子任务' : '任务'}时间`
                    let placeholder = `请输入修改备注`
                    if (isClear) {
                        title = `清除${this.isSubTask ? '子任务' : '任务'}时间`
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
                            this.$refs.desc.updateTouchContent();
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

                case 'tag':
                    this.$set(this.taskDetail, 'task_tag', params)
                    action = 'task_tag'
                    break;
            }
            //
            const dataJson = {task_id: this.taskDetail.id};
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
            this.timeValue = await this.$store.dispatch("taskDefaultTime", $A.newDateString(this.timeValue, "YYYY-MM-DD HH:mm"))
        },

        async onOwner(pick) {
            if (this.isDepartmentReadonly) {
                return;
            }

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
            if (this.isDepartmentReadonly) {
                return;
            }

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
            if (this.isDepartmentReadonly) {
                return;
            }

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
            if (this.isDepartmentReadonly) {
                return;
            }

            this.updateData('times', {
                start_at: false,
                end_at: false,
            });
            this.timeOpen = false;
        },

        timeOk() {
            if (this.isDepartmentReadonly) {
                return;
            }

            const times = $A.newDateString(this.timeValue, "YYYY-MM-DD HH:mm");
            this.updateData('times', {
                start_at: times[0],
                end_at: times[1],
            });
            this.timeOpen = false;
        },

        addsubOpen() {
            if (this.isDepartmentReadonly) {
                return;
            }

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
            if (this.isDepartmentReadonly) {
                return;
            }

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

        async loadRelatedTasks() {
            if (!this.taskId) {
                this.relatedTasks = [];
                return;
            }
            if (this.isSubTask) {
                this.relatedTasks = [];
                return;
            }
            const cacheMap = this.$store.state.taskRelatedCache || {};
            const cached = cacheMap[this.taskId];
            if (cached?.list) {
                this.relatedTasks = cached.list;
            }
            const requestKey = ++this.relatedRequestKey;
            try {
                const data = await this.$store.dispatch('getTaskRelated', this.taskId);
                if (requestKey !== this.relatedRequestKey) {
                    return;
                }
                this.relatedTasks = data;
            } catch (e) {
                if (requestKey === this.relatedRequestKey) {
                    this.relatedTasks = [];
                }
                console.warn(e);
            }
        },

        openRelatedTask(item) {
            if (!item || !item.related_task_id) {
                return;
            }
            if (item.related_task_id === this.taskId) {
                return;
            }
            this.$store.dispatch('openTask', item.related_task_id);
        },

        removeRelatedTask(item) {
            if (this.isDepartmentReadonly) {
                return;
            }

            if (!item || !item.related_task_id) {
                return;
            }
            $A.modalConfirm({
                title: '温馨提示',
                content: '确定要解除与任务 #' + item.related_task_id + ' 的关联吗？',
                onOk: () => {
                    this.$store.dispatch('deleteTaskRelated', {
                        taskId: this.taskId,
                        relatedTaskId: item.related_task_id,
                    }).then(() => {
                        this.loadRelatedTasks();
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    });
                },
            });
        },

        onTaskRelationUpdate(taskId) {
            if (!taskId || taskId !== this.taskId) {
                return;
            }
            if (this.isSubTask) {
                return;
            }
            this.loadRelatedTasks();
        },

        logLoadChange(load) {
            this.logLoadIng = load
        },

        onPriority(event) {
            if (this.isDepartmentReadonly) {
                return;
            }

            const list = this.taskPriority.map(item => {
                return {
                    label: item.name,
                    value: item,
                    prefix: `<i class="taskfont" style="color:${item.color};font-size:18px">${this.taskDetail.p_name == item.name ? '&#xe61d;' : '&#xe61c;'}</i>`,
                }
            });
            this.$store.commit('menu/operation', {
                event,
                list,
                size: 'large',
                language: false,
                onUpdate: (value) => {
                    this.updateData('priority', value)
                }
            })
        },

        onLoop(event) {
            if (this.isDepartmentReadonly) {
                return;
            }

            const list = this.loops.map(item => {
                return {
                    label: item.label,
                    value: item.key,
                }
            });
            this.$store.commit('menu/operation', {
                event,
                list,
                size: 'large',
                onUpdate: (newLoop) => {
                    const currentLoop = this.taskDetail.loop || 'never';
                    const loopTip = currentLoop == 'never' && newLoop != 'never' && this.subList.length > 0
                    if (loopTip) {
                        $A.modalConfirm({
                            language: false,
                            content: this.$L('周期任务的子任务时间将被重置，是否继续？'),
                            onOk: () => {
                                this.updateData('loop', newLoop)
                            }
                        });
                    } else {
                        this.updateData('loop', newLoop)
                    }
                }
            })
        },

        onAddItem(event) {
            if (this.isDepartmentReadonly) {
                return;
            }

            const list = this.menuList.map(item => {
                return {
                    label: item.name,
                    value: item.command,
                    prefix: `<i class="taskfont">${item.icon}</i>`,
                }
            });
            this.$store.commit('menu/operation', {
                event,
                list,
                size: 'large',
                onUpdate: (value) => {
                    this.dropAddItem(value)
                }
            })
        },

        dropAddItem(command) {
            if (this.isDepartmentReadonly) {
                return;
            }

            switch (command) {
                case 'tag':
                    this.tagForce = true;
                    this.$nextTick(() => {
                        this.tagShow = true;
                    });
                    break;

                case 'priority':
                    this.$set(this.taskDetail, 'p_name', this.$L('未设置'));
                    this.$nextTick(() => {
                        this.onPriority({target: this.$refs.priorityText})
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
                        this.showCisibleDropdown({
                            target: this.$refs.visibilityText
                        });
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
                        this.onLoop({target: this.$refs.loopText})
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

        onFocus() {
            this.scrollIntoInput()
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

        msgDialog(sendType = null) {

            if (this.sendLoad > 0 || this.openLoad > 0) {
                return;
            }
            //
            if (this.taskDetail.dialog_id) {
                this.openDialogBefore(this.taskDetail.dialog_id, sendType)
                return;
            }
            //
            if (sendType === true) {
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
            }).then(async ({data}) => {
                await this.$store.dispatch("saveTask", {id: data.id, dialog_id: data.dialog_id});
                await this.$store.dispatch("saveDialog", data.dialog_data);
                this.openDialogBefore(data.dialog_id, sendType)
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                if (sendType === true) {
                    this.openLoad--;
                } else {
                    this.sendLoad--;
                }
            });
        },

        openDialogBefore(dialogId, sendType) {
            if (sendType !== true) {
                this.$store.state.dialogMsgTransfer = {
                    time: $A.dayjs().unix() + 10,
                    msgRecord: this.msgRecord,
                    msgFile: this.msgFile,
                    msgText: sendType === 'md' ? this.$refs.chatInput?.getText() : this.msgText,
                    sendType,
                    dialogId,
                };
                this.msgRecord = {};
                this.msgFile = [];
                this.msgText = "";
                this.$store.dispatch("saveDialogDraft", {id: `t_${this.taskId}`, content: ""})
            }

            if ($A.isSubElectron) {
                this.resizeDialog()
                return
            }

            if (this.windowPortrait) {
                this.$store.dispatch('openDialog', dialogId).catch(({msg}) => {
                    $A.modalError(msg);
                })
                $A.eeuiAppKeyboardHide();
            }
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

        onOpen() {
            this.$refs.chatInput?.hidePopover();
            this.msgDialog(true);
        },

        onSend(text, type) {
            this.$refs.chatInput?.hidePopover();
            if (typeof text === "string" && text) {
                this.msgText = text;
            }
            this.msgDialog(type);
        },

        deleteFile(file) {
            if (this.isDepartmentReadonly) {
                return;
            }

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
            if (this.isDepartmentReadonly) {
                return;
            }
            const el = this.$refs[`taskMenu_${task.id}`];
            el && el.handleClick(event)
        },

        openNewWin() {
            this.$store.dispatch('openWindow', {
                name: `task-${this.taskDetail.id}`,
                path: `/single/task/${this.taskDetail.id}?navActive=${this.navActive}`,
                mode: 'window',
                title: this.taskDetail.name,
                titleFixed: true,
                width: Math.min(window.screen.availWidth * 0.8, this.$el.clientWidth + 72),
                height: Math.min(window.screen.availHeight * 0.8, this.$el.clientHeight + 72),
                minWidth: this.hasOpenDialog ? 800 : 600,
                minHeight: this.hasOpenDialog ? 600 : 450,
            });
            this.$store.dispatch('openTask', 0);
        },

        resizeDialog() {
            return new Promise(resolve => {
                const width = Math.max(1100, this.windowWidth);
                const height = Math.max(720, Math.min(width * 0.8, this.windowHeight));
                this.$Electron.sendMessage('windowSize', {
                    width,
                    height,
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
                this.$store.dispatch('openWindow', {
                    name: `file-task-${file.id}`,
                    path: path,
                    title: `${file.name} (${$A.bytesToSize(file.size)})`,
                    titleFixed: true,
                });
            } else if (this.$isEEUIApp) {
                this.$store.dispatch('openAppChildPage', {
                    pageType: 'app',
                    pageTitle: `${file.name} (${$A.bytesToSize(file.size)})`,
                    url: 'web.js',
                    params: {
                        titleFixed: true,
                        url: $A.urlReplaceHash(path)
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
                    const departmentOwnerIds = (this.$store.state.cacheDepartmentOwnerIds || []).join(',')
                    const url = $A.urlAddParams(`project/task/filedown?file_id=${file.id}`, departmentOwnerIds ? {department_owner_ids: departmentOwnerIds} : {})
                    this.$store.dispatch('downUrl', $A.apiUrl(url))
                }
            });
        },

        showCisibleDropdown(event){
            if (this.isDepartmentReadonly) {
                return;
            }

            const list = [
                {label: '项目人员', value: 1},
                {label: '任务人员', value: 2},
                {label: '指定成员', value: 3},
            ];
            this.$store.commit('menu/operation', {
                event,
                list,
                size: 'large',
                active: this.taskDetail.visibility,
                onUpdate: (value) => {
                    this.dropVisible(value)
                }
            })
        },

        showAtDropdown(event){
            if (this.isDepartmentReadonly) {
                return;
            }

            this.timeOpen = false
            const list = [
                {label: '任务延期', value: 1},
                {label: '修改时间', value: 2},
                {label: '清除时间', value: 3},
            ];
            this.$store.commit('menu/operation', {
                event,
                list,
                size: 'large',
                onUpdate: (value) => {
                    this.dropDeadline(value)
                }
            })
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
            if (this.isDepartmentReadonly) {
                return;
            }

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
            if (this.isDepartmentReadonly) {
                return;
            }

            switch (command) {
                case 1:
                    this.delayTaskQuicks = [
                        {time: 1, type: 'day', name: '1天'},
                        {time: 2, type: 'day', name: '2天'},
                        {time: 3, type: 'day', name: '3天'},
                        {time: 5, type: 'day', name: '5天'},
                    ];
                    const offDuty = $A.dayjs(`${$A.dayjs().format('YYYY-MM-DD')} ${this.systemConfig.task_default_time[1]}`)
                    const diffEnd = offDuty.diff($A.dayjs(this.taskDetail.end_at), 'hour', true).toFixed(2)
                    const diffEnd2 = offDuty.diff($A.dayjs(this.taskDetail.end_at).subtract(1, 'day'), 'day', true).toFixed(2)
                    const quickEnd = {time: diffEnd, type: 'hour', name: `今天下班前`}
                    const quickEnd2 = {time: diffEnd2, type: 'day', name: `明天下班前`}
                    if (quickEnd.time >= 24) {
                        quickEnd.type = 'day'
                        quickEnd.time = (quickEnd.time / 24).toFixed(2)
                    }
                    quickEnd2.time > 0 && this.delayTaskQuicks.unshift(quickEnd2)
                    quickEnd.time > 0 && this.delayTaskQuicks.unshift(quickEnd)
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
            if (this.isDepartmentReadonly) {
                return;
            }

            this.$refs.formDelayTaskRef.validate((valid) => {
                if (!valid) {
                    return
                }
                let {type, time} = this.delayTaskForm
                if (type === 'day') {
                    type = 'minute'
                    time = time * 24 * 60
                } else if (type === 'hour') {
                    type = 'minute'
                    time = time * 60
                }
                const endAt = $A.dayjs(this.taskDetail.end_at).add(time, type)
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

        showFileDropdown(file, event){
            this.operationFile = file
            const list = [
                {label: '查看附件', value: 1},
                {label: '下载附件', value: 2},
            ];
            if (!this.isDepartmentReadonly) {
                list.push({label: '删除附件', value: 3, style: {color:'#FF7070'}});
            }
            this.$store.commit('menu/operation', {
                event,
                list,
                size: 'large',
                onUpdate: (value) => {
                    this.dropFile(value)
                }
            })
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
        },

        onTaskQuick(time, type) {
            this.$set(this.delayTaskForm, 'time', Math.round(time * 100) / 100)
            this.$set(this.delayTaskForm, 'type', type)
        },

        onTagAdd(tagName) {
            if (this.isDepartmentReadonly) {
                return;
            }

            // 避免关闭选择框时触发更新
            this.tagValue = this.getTag;
            this.tagBakValue = $A.cloneJSON(this.tagValue);
            // 隐藏选择框并打开添加框
            this.tagShow = false
            this.$refs.addTag.onOpen(tagName ? {name: tagName} : null)
        },

        onTagAddSave(result) {
            if (this.isDepartmentReadonly) {
                return;
            }

            const current = this.tagValue;
            const addData = result.filter(({data}) => data && data.id > 0).map(({data}) => data);
            // 合并数组，如果有重名标签则使用新添加的标签数据
            const mergedTags = [
                ...addData,
                ...current.filter(tag => !addData.some(newTag => newTag.name === tag.name))
            ];
            // 触发更新
            this.updateData('tag', mergedTags);
            this.$refs.tagSelect?.clearSearch();
        },

        getTypeLabel(type) {
            this.msgTypes.some(item => {
                if (item.value === type) {
                    type = item.label
                    return true
                }
            })
            return type
        },

        onTypeChange(type) {
            this.msgType = type
        },

        openTypeClick(event) {
            if (this.msgTypes.length === 0) {
                return
            }
            this.$store.commit('menu/operation', {
                event,
                list: this.msgTypes,
                active: this.msgType,
                activeClick: true,
                language: false,
                onUpdate: (type) => {
                    this.navActive = 'dialog'
                    this.$refs.dialog?.onMsgType(type)
                }
            })
        },

        autoScrollInto() {
            return this.$isEEUIApp
                && this.windowPortrait
                && this.$refs.chatInput?.isFocus
        },

        scrollIntoInput() {
            if (!this.autoScrollInto()) {
                return;
            }
            this.$refs.taskDialog?.scrollIntoView({
                block: "end"
            })
        },

        keepIntoInput() {
            if (!this.autoScrollInto()) {
                return;
            }
            this.keepIntoTimer && clearTimeout(this.keepIntoTimer)
            this.keepIntoTimer = setTimeout(_ => {
                if (!this.autoScrollInto()) {
                    return;
                }
                this.$store.dispatch("scrollBottom", this.$refs.taskDialog)
            }, 500)
        }
    }
}
</script>
