<template>
    <div class="project-list">
        <PageTitle :title="projectData.name"/>
        <div class="project-head">
            <div class="project-titbox">
                <div class="project-title">
                    <h1>{{projectData.name}}</h1>
                    <div v-if="projectLoad > 0" class="project-load"><Loading/></div>
                </div>
                <ul class="project-icons">
                    <li>
                        <UserAvatar :userid="projectData.owner_userid" :size="36">
                            <p>{{$L('项目负责人')}}</p>
                        </UserAvatar>
                    </li>
                    <li class="project-icon" @click="addTaskOpen(0)">
                        <Icon class="menu-icon" type="md-add" />
                    </li>
                    <li :class="['project-icon', searchText!='' ? 'active' : '']">
                        <Tooltip :always="searchText!=''" theme="light">
                            <Icon class="menu-icon" type="ios-search" />
                            <div slot="content">
                                <Input v-model="searchText" :placeholder="$L('名称、描述...')" class="search-input" clearable autofocus/>
                            </div>
                        </Tooltip>
                    </li>
                    <li :class="['project-icon', projectParameters('chat') ? 'active' : '']" @click="$store.dispatch('toggleProjectParameters', 'chat')">
                        <Icon class="menu-icon" type="ios-chatbubbles" />
                        <Badge class="menu-badge" :count="msgUnread"></Badge>
                    </li>
                    <li class="project-icon">
                        <EDropdown @command="projectDropdown" trigger="click" transfer>
                            <Icon class="menu-icon" type="ios-more" />
                            <EDropdownMenu v-if="projectData.owner_userid === userId" slot="dropdown">
                                <EDropdownItem command="setting">{{$L('项目设置')}}</EDropdownItem>
                                <EDropdownItem command="user">{{$L('成员管理')}}</EDropdownItem>
                                <EDropdownItem command="invite">{{$L('邀请链接')}}</EDropdownItem>
                                <EDropdownItem command="log" divided>{{$L('项目动态')}}</EDropdownItem>
                                <EDropdownItem command="archived_task">{{$L('已归档任务')}}</EDropdownItem>
                                <EDropdownItem command="transfer" divided>{{$L('移交项目')}}</EDropdownItem>
                                <EDropdownItem command="archived">{{$L('归档项目')}}</EDropdownItem>
                                <EDropdownItem command="delete" style="color:#f40">{{$L('删除项目')}}</EDropdownItem>
                            </EDropdownMenu>
                            <EDropdownMenu v-else slot="dropdown">
                                <EDropdownItem command="log">{{$L('项目动态')}}</EDropdownItem>
                                <EDropdownItem command="archived_task">{{$L('已归档任务')}}</EDropdownItem>
                                <EDropdownItem command="exit" divided style="color:#f40">{{$L('退出项目')}}</EDropdownItem>
                            </EDropdownMenu>
                        </EDropdown>
                    </li>
                </ul>
            </div>
            <div v-if="projectData.desc" class="project-subtitle">{{projectData.desc}}</div>
            <div class="project-switch">
                <div v-if="completedCount > 0" class="project-checkbox">
                    <Checkbox :value="projectParameters('completedTask')" @on-change="toggleCompleted">{{$L('显示已完成')}}</Checkbox>
                </div>
                <div :class="['project-switch-button', !projectParameters('card') ? 'menu' : '']" @click="$store.dispatch('toggleProjectParameters', 'card')">
                    <div><i class="taskfont">&#xe60c;</i></div>
                    <div><i class="taskfont">&#xe66a;</i></div>
                </div>
            </div>
        </div>
        <div v-if="projectParameters('card')" class="project-column">
            <Draggable
                :list="columnList"
                :animation="150"
                :disabled="sortDisabled || $store.state.windowMax768"
                class="column-list"
                tag="ul"
                draggable=".column-item"
                @sort="sortUpdate(true)">
                <li v-for="column in columnList" class="column-item">
                    <div
                        :class="['column-head', column.color ? 'custom-color' : '']"
                        :style="column.color ? {backgroundColor: column.color} : {}">
                        <div class="column-head-title">
                            <AutoTip>{{column.name}}</AutoTip>
                            <em>({{panelTask(column.tasks).length}})</em>
                        </div>
                        <div class="column-head-icon">
                            <div v-if="columnLoad[column.id] === true" class="loading"><Loading /></div>
                            <EDropdown
                                v-else
                                trigger="click"
                                size="small"
                                @command="dropColumn(column, $event)">
                                <Icon type="ios-more" />
                                <EDropdownMenu slot="dropdown" class="project-list-more-dropdown-menu">
                                    <EDropdownItem command="title">
                                        <div class="item">
                                            <Icon type="md-create" />{{$L('修改')}}
                                        </div>
                                    </EDropdownItem>
                                    <EDropdownItem command="remove">
                                        <div class="item">
                                            <Icon type="md-trash" />{{$L('删除')}}
                                        </div>
                                    </EDropdownItem>
                                    <EDropdownItem divided disabled>{{$L('颜色')}}</EDropdownItem>
                                    <EDropdownItem v-for="(c, k) in $store.state.columnColorList" :key="k" :command="c">
                                        <div class="item">
                                            <i class="taskfont" :style="{color:c.color}" v-html="c.color == column.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                        </div>
                                    </EDropdownItem>
                                </EDropdownMenu>
                            </EDropdown>
                            <Icon class="last" type="md-add" @click="addTopShow(column.id, true)" />
                        </div>
                    </div>
                    <div :ref="'column_' + column.id" class="column-task overlay-y">
                        <div v-if="!!columnTopShow[column.id]" class="task-item additem">
                            <TaskAddSimple
                                :column-id="column.id"
                                :project-id="projectId"
                                :add-top="true"
                                @on-close="addTopShow(column.id, false)"
                                @on-priority="addTaskOpen"
                                auto-active/>
                        </div>
                        <Draggable
                            :list="column.tasks"
                            :animation="150"
                            :disabled="sortDisabled || $store.state.windowMax768"
                            class="task-list"
                            draggable=".task-draggable"
                            group="task"
                            @sort="sortUpdate"
                            @remove="sortUpdate">
                            <div
                                v-for="item in column.tasks"
                                :class="['task-item task-draggable', item.complete_at ? 'complete' : '', taskIsHidden(item) ? 'hidden' : '']"
                                :style="item.color ? {backgroundColor: item.color} : {}"
                                @click="openTask(item)">
                                <div :class="['task-head', item.desc ? 'has-desc' : '']">
                                    <div class="task-title"><pre>{{item.name}}</pre></div>
                                    <div class="task-menu" @click.stop="">
                                        <div v-if="taskLoad[item.id] === true" class="loading"><Loading /></div>
                                        <EDropdown
                                            v-else
                                            trigger="click"
                                            size="small"
                                            @command="dropTask(item, $event)">
                                            <Icon type="ios-more" />
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
                                                <EDropdownItem divided disabled>{{$L('背景色')}}</EDropdownItem>
                                                <EDropdownItem v-for="(c, k) in $store.state.taskColorList" :key="k" :command="c">
                                                    <div class="item">
                                                        <i class="taskfont" :style="{color:c.color||'#f9f9f9'}" v-html="c.color == item.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                                    </div>
                                                </EDropdownItem>
                                            </EDropdownMenu>
                                        </EDropdown>
                                    </div>
                                </div>
                                <div v-if="item.desc" class="task-desc"><pre v-html="item.desc"></pre></div>
                                <div v-if="item.task_tag.length > 0" class="task-tags">
                                    <Tag v-for="(tag, keyt) in item.task_tag" :key="keyt" :color="tag.color">{{tag.name}}</Tag>
                                </div>
                                <div class="task-users">
                                    <ul>
                                        <li v-for="(user, keyu) in ownerUser(item.task_user)" :key="keyu">
                                            <UserAvatar :userid="user.userid" size="32" :borderWitdh="2" :borderColor="item.color"/>
                                        </li>
                                        <li v-if="ownerUser(item.task_user).length === 0" class="no-owner">
                                            <Button type="primary" size="small" ghost @click.stop="openTask(item, true)">{{$L('领取任务')}}</Button>
                                        </li>
                                    </ul>
                                    <div v-if="item.file_num > 0" class="task-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                                    <div v-if="item.msg_num > 0" class="task-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                                </div>
                                <div class="task-progress">
                                    <div v-if="item.sub_num > 0" class="task-sub-num">{{item.sub_complete}}/{{item.sub_num}}</div>
                                    <Progress :percent="item.percent" :stroke-width="6" />
                                    <ETooltip
                                        v-if="item.end_at"
                                        :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                                        :open-delay="600"
                                        :content="item.end_at">
                                        <div v-if="!item.complete_at"><i class="taskfont">&#xe71d;</i>{{ expiresFormat(item.end_at) }}</div>
                                    </ETooltip>
                                </div>
                                <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                            </div>
                            <div class="task-item additem">
                                <TaskAddSimple
                                    :column-id="column.id"
                                    :project-id="projectId"
                                    @on-priority="addTaskOpen"/>
                            </div>
                        </Draggable>
                    </div>
                </li>
                <li :class="['add-column', addColumnShow ? 'show-input' : '']">
                    <div class="add-column-text" @click="addColumnOpen">
                        <Icon type="md-add" />{{$L('添加列表')}}
                    </div>
                    <div class="add-column-input">
                        <Input
                            ref="addColumnName"
                            v-model="addColumnName"
                            @on-blur="addColumnBlur"
                            @on-enter="addColumnSubmit"
                            @on-clear="addColumnShow=false"
                            :placeholder="$L('列表名称，回车创建')"
                            clearable/>
                    </div>
                </li>
            </Draggable>
        </div>
        <div v-else class="project-table overlay-y">
            <div class="project-table-head">
                <Row class="task-row">
                    <Col span="12"># {{$L('任务名称')}}</Col>
                    <Col span="3">{{$L('列表')}}</Col>
                    <Col span="3">
                        <div class="sort" @click="onSort('level')">
                            {{$L('优先级')}}
                            <div class="task-sort">
                                <Icon :class="{on:sortField=='level' && sortType=='asc'}" type="md-arrow-dropup" />
                                <Icon :class="{on:sortField=='level' && sortType=='desc'}" type="md-arrow-dropdown" />
                            </div>
                        </div>
                    </Col>
                    <Col span="3">{{$L('负责人')}}</Col>
                    <Col span="3">
                        <div class="sort" @click="onSort('end_at')">
                            {{$L('到期时间')}}
                            <div class="task-sort">
                                <Icon :class="{on:sortField=='end_at' && sortType=='asc'}" type="md-arrow-dropup" />
                                <Icon :class="{on:sortField=='end_at' && sortType=='desc'}" type="md-arrow-dropdown" />
                            </div>
                        </div>
                    </Col>
                </Row>
            </div>
            <!--我的任务-->
            <div :class="['project-table-body', !projectParameters('showMy') ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="taskfont" @click="$store.dispatch('toggleProjectParameters', 'showMy')">&#xe689;</i>
                        <div class="row-h1">{{$L('我的任务')}}</div>
                        <div class="row-num">({{myList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow v-if="projectParameters('showMy')" :list="transforTasks(myList)" open-key="my" @command="dropTask" @on-priority="addTaskOpen" fast-add-task/>
            </div>
            <!--协助的任务-->
            <div v-if="helpList.length" :class="['project-table-body', !projectParameters('showHelp') ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="taskfont" @click="$store.dispatch('toggleProjectParameters', 'showHelp')">&#xe689;</i>
                        <div class="row-h1">{{$L('协助的任务')}}</div>
                        <div class="row-num">({{helpList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow v-if="projectParameters('showHelp')" :list="helpList" open-key="help" @command="dropTask" @on-priority="addTaskOpen"/>
            </div>
            <!--未完成任务-->
            <div v-if="projectData.task_num > 0" :class="['project-table-body', !projectParameters('showUndone') ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="taskfont" @click="$store.dispatch('toggleProjectParameters', 'showUndone')">&#xe689;</i>
                        <div class="row-h1">{{$L('未完成任务')}}</div>
                        <div class="row-num">({{unList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow v-if="projectParameters('showUndone')" :list="unList" open-key="undone" @command="dropTask" @on-priority="addTaskOpen"/>
            </div>
            <!--已完成任务-->
            <div v-if="projectData.task_num > 0" :class="['project-table-body', !projectParameters('showCompleted') ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="taskfont" @click="$store.dispatch('toggleProjectParameters', 'showCompleted')">&#xe689;</i>
                        <div class="row-h1">{{$L('已完成任务')}}</div>
                        <div class="row-num">({{completedList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow v-if="projectParameters('showCompleted')" :list="completedList" open-key="completed" @command="dropTask" @on-priority="addTaskOpen"/>
            </div>
        </div>

        <!--项目设置-->
        <Modal
            v-model="settingShow"
            :title="$L('项目设置')"
            :mask-closable="false">
            <Form :model="settingData" label-width="auto" @submit.native.prevent>
                <FormItem prop="name" :label="$L('项目名称')">
                    <Input ref="projectName" type="text" v-model="settingData.name" :maxlength="32" :placeholder="$L('必填')"></Input>
                </FormItem>
                <FormItem prop="desc" :label="$L('项目介绍')">
                    <Input type="textarea" :autosize="{ minRows: 3, maxRows: 5 }" v-model="settingData.desc" :maxlength="255" :placeholder="$L('选填')"></Input>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="settingShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="settingLoad > 0" @click="onSetting">{{$L('修改')}}</Button>
            </div>
        </Modal>

        <!--成员管理-->
        <Modal
            v-model="userShow"
            :title="$L('成员管理')"
            :mask-closable="false">
            <Form :model="userData" label-width="auto" @submit.native.prevent>
                <FormItem prop="userids" :label="$L('项目成员')">
                    <UserInput v-if="userShow" v-model="userData.userids" :uncancelable="userData.uncancelable" :multiple-max="100" :placeholder="$L('选择项目成员')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="userShow=false">{{$L('取消')}}</Button>
                <Poptip
                    v-if="userWaitRemove.length > 0"
                    confirm
                    placement="bottom"
                    style="margin-left:8px"
                    @on-ok="onUser"
                    transfer>
                    <div slot="title">
                        <p><strong>{{$L('移除成员负责的任务将变成无负责人，')}}</strong></p>
                        <p>{{$L('注意此操作不可逆！')}}</p>
                        <ul class="project-list-wait-remove">
                            <li>{{$L('即将移除')}}：</li>
                            <li v-for="id in userWaitRemove" :key="id">
                                <UserAvatar :userid="id" :size="20" showName tooltipDisabled/>
                            </li>
                        </ul>
                    </div>
                    <Button type="primary" :loading="userLoad > 0">{{$L('保存')}}</Button>
                </Poptip>
                <Button v-else type="primary" :loading="userLoad > 0" @click="onUser">{{$L('保存')}}</Button>
            </div>
        </Modal>

        <!--邀请链接-->
        <Modal
            v-model="inviteShow"
            :title="$L('邀请链接')"
            :mask-closable="false">
            <Form :model="inviteData" label-width="auto" @submit.native.prevent>
                <FormItem :label="$L('链接地址')">
                    <Input ref="inviteInput" v-model="inviteData.url" type="textarea" :rows="3" @on-focus="inviteFocus" readonly/>
                    <div class="form-tip">{{$L('可通过此链接直接加入项目。')}}</div>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="inviteShow=false">{{$L('取消')}}</Button>
                <Poptip
                    confirm
                    placement="bottom"
                    style="margin-left:8px"
                    @on-ok="inviteGet(true)"
                    transfer>
                    <div slot="title">
                        <p><strong>{{$L('注意：刷新将导致原来的邀请链接失效！')}}</strong></p>
                    </div>
                    <Button type="primary" :loading="inviteLoad > 0">{{$L('刷新')}}</Button>
                </Poptip>
            </div>
        </Modal>

        <!--移交项目-->
        <Modal
            v-model="transferShow"
            :title="$L('移交项目')"
            :mask-closable="false">
            <Form :model="transferData" label-width="auto" @submit.native.prevent>
                <FormItem prop="owner_userid" :label="$L('项目负责人')">
                    <UserInput v-if="transferShow" v-model="transferData.owner_userid" :multiple-max="1" :placeholder="$L('选择项目负责人')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="transferShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="transferLoad > 0" @click="onTransfer">{{$L('移交')}}</Button>
            </div>
        </Modal>

        <!--查看项目动态-->
        <DrawerOverlay
            v-model="logShow"
            placement="right"
            :size="768">
            <ProjectLog v-if="logShow" :project-id="projectId"/>
        </DrawerOverlay>

        <!--查看归档任务-->
        <DrawerOverlay
            v-model="archivedTaskShow"
            placement="right"
            :size="768">
            <TaskArchived v-if="archivedTaskShow" :project-id="projectId"/>
        </DrawerOverlay>
    </div>
</template>

<script>
import Vue from 'vue'
import VueClipboard from 'vue-clipboard2'
Vue.use(VueClipboard)

import Draggable from 'vuedraggable'
import TaskPriority from "./TaskPriority";
import TaskAdd from "./TaskAdd";
import {mapGetters, mapState} from "vuex";
import {Store} from 'le5le-store';
import UserInput from "../../../components/UserInput";
import TaskAddSimple from "./TaskAddSimple";
import TaskRow from "./TaskRow";
import TaskArchived from "./TaskArchived";
import ProjectLog from "./ProjectLog";
import DrawerOverlay from "../../../components/DrawerOverlay";

export default {
    name: "ProjectList",
    components: {
        DrawerOverlay,
        ProjectLog, TaskArchived, TaskRow, Draggable, TaskAddSimple, UserInput, TaskAdd, TaskPriority},
    data() {
        return {
            nowTime: $A.Time(),
            nowInterval: null,

            columnLoad: {},
            columnTopShow: {},
            taskLoad: {},

            completeTask: [],

            sortField: 'end_at',
            sortType: 'desc',

            searchText: '',

            addColumnShow: false,
            addColumnName: '',

            sortData: [],
            sortDisabled: false,

            settingShow: false,
            settingData: {},
            settingLoad: 0,

            userShow: false,
            userData: {},
            userLoad: 0,

            inviteShow: false,
            inviteData: {},
            inviteLoad: 0,

            transferShow: false,
            transferData: {},
            transferLoad: 0,

            logShow: false,
            archivedTaskShow: false,

            projectDialogSubscribe: null,
        }
    },

    mounted() {
        this.nowInterval = setInterval(() => {
            this.nowTime = $A.Time();
        }, 1000);
        //
        this.projectDialogSubscribe = Store.subscribe('onProjectDialogBack', () => {
            this.$store.dispatch('toggleProjectParameters', 'chat');
        });
    },

    destroyed() {
        clearInterval(this.nowInterval);
        //
        if (this.projectDialogSubscribe) {
            this.projectDialogSubscribe.unsubscribe();
            this.projectDialogSubscribe = null;
        }
    },

    computed: {
        ...mapState([
            'userId',
            'dialogs',

            'taskPriority',

            'projectId',
            'projectLoad',
            'tasks',
            'columns',
        ]),

        ...mapGetters(['projectData', 'projectParameters', 'myTasks', 'transforTasks']),

        userWaitRemove() {
            const {userids, useridbak} = this.userData;
            if (!userids) {
                return [];
            }
            let wait = [];
            useridbak.some(id => {
                if (!userids.includes(id)) {
                    wait.push(id)
                }
            })
            return wait;
        },

        msgUnread() {
            const {dialogs, projectData} = this;
            const dialog = dialogs.find(({id}) => id === projectData.dialog_id);
            return dialog ? dialog.unread : 0;
        },

        panelTask() {
            const {searchText} = this;
            return function (list) {
                if (!this.projectParameters('completedTask')) {
                    list = list.filter(({complete_at}) => {
                        return !complete_at;
                    });
                }
                if (searchText) {
                    list = list.filter(({name, desc}) => {
                        return $A.strExists(name, searchText) || $A.strExists(desc, searchText);
                    });
                }
                return list;
            }
        },

        columnList() {
            const {projectId, columns, tasks} = this;
            const list = columns.filter(({project_id}) => {
                return project_id == projectId
            }).sort((a, b) => {
                if (a.sort != b.sort) {
                    return a.sort - b.sort;
                }
                return a.id - b.id;
            });
            list.forEach((column) => {
                column.tasks = this.transforTasks(tasks.filter((task) => {
                    return task.column_id == column.id;
                })).sort((a, b) => {
                    if (a.sort != b.sort) {
                        return a.sort - b.sort;
                    }
                    return a.id - b.id;
                });
            })
            return list;
        },

        myList() {
            const {projectId, myTasks, searchText, completeTask, sortField, sortType} = this;
            const array = myTasks.filter((task) => {
                if (task.project_id != projectId) {
                    return false;
                }
                if (!this.projectParameters('completedTask')) {
                    if (task.complete_at && !completeTask.find(id => id == task.id)) {
                        return false;
                    }
                }
                if (searchText) {
                    if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                        return false;
                    }
                }
                return true;
            });
            return array.sort((a, b) => {
                if (sortType == 'asc') {
                    [a, b] = [b, a];
                }
                if (sortField == 'level') {
                    return a.p_level - b.p_level;
                } else if (sortField == 'end_at') {
                    if (a.end_at == b.end_at) {
                        return a.p_level - b.p_level;
                    }
                    return $A.Date(a.end_at || "2099-12-31 23:59:59") - $A.Date(b.end_at || "2099-12-31 23:59:59");
                }
            });
        },

        helpList() {
            const {projectId, tasks, searchText, userId, completeTask, sortField, sortType} = this;
            const array = tasks.filter((task) => {
                if (task.project_id != projectId || task.parent_id > 0) {
                    return false;
                }
                if (!this.projectParameters('completedTask')) {
                    if (task.complete_at && !completeTask.find(id => id == task.id)) {
                        return false;
                    }
                }
                if (searchText) {
                    if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                        return false;
                    }
                }
                return task.task_user && task.task_user.find(({userid, owner}) => userid == userId && owner == 0);
            });
            return array.sort((a, b) => {
                if (sortType == 'asc') {
                    [a, b] = [b, a];
                }
                if (sortField == 'level') {
                    return a.p_level - b.p_level;
                } else if (sortField == 'end_at') {
                    if (a.end_at == b.end_at) {
                        return a.p_level - b.p_level;
                    }
                    return $A.Date(a.end_at || "2099-12-31 23:59:59") - $A.Date(b.end_at || "2099-12-31 23:59:59");
                }
            });
        },

        unList() {
            const {projectId, tasks, searchText, completeTask, sortField, sortType} = this;
            const array = tasks.filter((task) => {
                if (task.project_id != projectId || task.parent_id > 0) {
                    return false;
                }
                if (!this.projectParameters('completedTask')) {
                    if (task.complete_at && !completeTask.find(id => id == task.id)) {
                        return false;
                    }
                }
                if (searchText) {
                    if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                        return false;
                    }
                }
                return !task.complete_at || completeTask.find(id => id == task.id);
            });
            return array.sort((a, b) => {
                if (sortType == 'asc') {
                    [a, b] = [b, a];
                }
                if (sortField == 'level') {
                    return a.p_level - b.p_level;
                } else if (sortField == 'end_at') {
                    if (a.end_at == b.end_at) {
                        return a.p_level - b.p_level;
                    }
                    return $A.Date(a.end_at || "2099-12-31 23:59:59") - $A.Date(b.end_at || "2099-12-31 23:59:59");
                }
            });
        },

        completedList() {
            const {projectId, tasks, searchText} = this;
            const array = tasks.filter((task) => {
                if (task.project_id != projectId || task.parent_id > 0) {
                    return false;
                }
                if (searchText) {
                    if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                        return false;
                    }
                }
                return task.complete_at;
            });
            return array.sort((a, b) => {
                let at1 = $A.Date(a.complete_at),
                    at2 = $A.Date(b.complete_at);
                return at2 - at1;
            });
        },

        completedCount() {
            const {projectId, tasks} = this;
            return tasks.filter((task) => {
                if (task.project_id != projectId || task.parent_id > 0) {
                    return false;
                }
                return task.complete_at;
            }).length;
        },
    },

    watch: {
        projectData() {
            this.sortData = this.getSort();
        },
        '$route'() {
            this.completeTask = [];
        }
    },

    methods: {
        getSort() {
            const sortData = [];
            this.columnList.forEach((column) => {
                sortData.push({
                    id: column.id,
                    task: column.tasks.map(({id}) => id)
                });
            })
            return sortData;
        },

        sortUpdate(only_column) {
            const oldSort = this.sortData;
            const newSort = this.getSort();
            if (JSON.stringify(oldSort) === JSON.stringify(newSort)) {
                return;
            }
            this.sortData = newSort;
            //
            const data = {
                project_id: this.projectId,
                sort: this.sortData,
                only_column: only_column === true ? 1 : 0
            };
            this.sortDisabled = true;
            this.$store.dispatch("call", {
                url: 'project/sort',
                data,
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.sortDisabled = false;
                //
                if (!data.only_column) {
                    let sort,
                        upTask = [];
                    data.sort.forEach((item) => {
                        sort = -1;
                        upTask.push(...item.task.map(id => {
                            sort++;
                            upTask.push(...this.tasks.filter(({parent_id}) => parent_id == id).map(({id}) => {
                                return {
                                    id,
                                    sort,
                                    column_id: item.id,
                                }
                            }))
                            return {
                                id,
                                sort,
                                column_id: item.id,
                            }
                        }))
                    })
                    this.$store.dispatch("saveTask", upTask)
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.sortDisabled = false;
                this.$store.dispatch("getTasks", {project_id: this.projectId})
            });
        },

        addTopShow(id, show) {
            this.$set(this.columnTopShow, id, show);
            if (show) {
                this.$refs['column_' + id][0].scrollTop = 0;
            }
        },

        addTaskOpen(column_id) {
            Store.set('addTask', column_id);
        },

        addColumnOpen() {
            this.addColumnShow = true;
            this.$nextTick(() => {
                this.$refs.addColumnName.focus();
            })
        },

        addColumnBlur() {
            if (this.addColumnName === '') {
                this.addColumnShow = false
            }
        },

        addColumnSubmit() {
            let name = this.addColumnName.trim();
            if (name === '') {
                return;
            }
            this.$store.dispatch("call", {
                url: 'project/column/add',
                data: {
                    project_id: this.projectId,
                    name: name,
                },
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.addColumnName = '';
                this.$store.dispatch("saveColumn", data);
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        dropColumn(column, command) {
            if (command === 'title') {
                this.titleColumn(column);
            }
            else if (command === 'remove') {
                this.removeColumn(column);
            }
            else if (command.name) {
                this.updateColumn(column, {
                    color: command.color
                });
            }
        },

        titleColumn(column) {
            $A.modalInput({
                value: column.name,
                title: "修改列表",
                placeholder: "输入列表名称",
                onOk: (value) => {
                    if (value) {
                        this.updateColumn(column, {
                            name: value
                        });
                    }
                    return true;
                }
            });
        },

        updateColumn(column, updata) {
            if (this.columnLoad[column.id] === true) {
                return;
            }
            this.$set(this.columnLoad, column.id, true);
            //
            Object.keys(updata).forEach(key => this.$set(column, key, updata[key]));
            //
            this.$store.dispatch("call", {
                url: 'project/column/update',
                data: Object.assign(updata, {
                    column_id: column.id,
                }),
            }).then(({data}) => {
                this.$set(this.columnLoad, column.id, false);
                this.$store.dispatch("saveColumn", data);
            }).catch(({msg}) => {
                this.$set(this.columnLoad, column.id, false);
                this.$store.dispatch("getColumns", this.projectId)
                $A.modalError(msg);
            });
        },

        removeColumn(column) {
            $A.modalConfirm({
                title: '删除列表',
                content: '你确定要删除列表【' + column.name + '】及列表内的任务吗？',
                loading: true,
                onOk: () => {
                    if (this.columnLoad[column.id] === true) {
                        return;
                    }
                    this.$set(this.columnLoad, column.id, true);
                    //
                    this.$store.dispatch("removeColumn", column.id).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        this.$set(this.columnLoad, column.id, false);
                        this.$Modal.remove();
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$set(this.columnLoad, column.id, false);
                        this.$Modal.remove();
                    });
                }
            });
        },

        dropTask(task, command) {
            if ($A.isJson(command)) {
                if (command.name) {
                    // 修改背景色
                    this.updateTask(task, {
                        color: command.color
                    })
                }
                return;
            }
            if ($A.leftExists(command, 'column::')) {
                // 修改列表
                this.updateTask(task, {
                    column_id: $A.leftDelete(command, 'column::')
                })
                return;
            }
            if ($A.leftExists(command, 'priority::')) {
                // 修改优先级
                let data = this.taskPriority[parseInt($A.leftDelete(command, 'priority::'))];
                if (data) {
                    this.updateTask(task, {
                        p_level: data.priority,
                        p_name: data.name,
                        p_color: data.color,
                    })
                }
                return;
            }
            switch (command) {
                case 'complete':
                    if (task.complete_at) return;
                    this.updateTask(task, {
                        complete_at: $A.formatDate("Y-m-d H:i:s")
                    })
                    this.completeTask.push(task.id)
                    break;

                case 'uncomplete':
                    if (!task.complete_at) return;
                    this.updateTask(task, {
                        complete_at: false
                    })
                    let index = this.completeTask.findIndex(id => id == task.id)
                    if (index > -1) {
                        this.completeTask.splice(index, 1)
                    }
                    break;

                case 'archived':
                case 'remove':
                    this.archivedOrRemoveTask(task, command);
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
                    reject()
                });
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

        onSort(field) {
            this.sortField = field;
            this.sortType = this.sortType == 'desc' ? 'asc' : 'desc';
        },

        onSetting() {
            this.settingLoad++;
            this.$store.dispatch("call", {
                url: 'project/update',
                data: Object.assign(this.settingData, {
                    project_id: this.projectId
                }),
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.settingLoad--;
                this.settingShow = false;
                this.$store.dispatch("saveProject", data)
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.settingLoad--;
            });
        },

        onUser() {
            this.userLoad++;
            this.$store.dispatch("call", {
                url: 'project/user',
                data: {
                    project_id: this.projectId,
                    userid: this.userData.userids,
                },
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.userLoad--;
                this.userShow = false;
                this.$store.dispatch("getProjectOne", this.projectId);
                this.$store.dispatch("getTasks", {project_id: this.projectId})
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.userLoad--;
            });
        },

        onTransfer() {
            this.transferLoad++;
            this.$store.dispatch("call", {
                url: 'project/transfer',
                data: {
                    project_id: this.projectId,
                    owner_userid: this.transferData.owner_userid[0],
                },
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.transferLoad--;
                this.transferShow = false;
                this.$store.dispatch("getProjectOne", this.projectId);
                this.$store.dispatch("getTasks", {project_id: this.projectId})
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.transferLoad--;
            });
        },

        onArchived() {
            $A.modalConfirm({
                title: '归档项目',
                content: '你确定要归档项目【' + this.projectData.name + '】吗？',
                loading: true,
                onOk: () => {
                    this.$store.dispatch("archivedProject", this.projectId).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$Modal.remove();
                    });
                }
            });
        },

        onDelete() {
            $A.modalConfirm({
                title: '删除项目',
                content: '你确定要删除项目【' + this.projectData.name + '】吗？',
                loading: true,
                onOk: () => {
                    this.$store.dispatch("removeProject", this.projectId).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$Modal.remove();
                    });
                }
            });
        },

        onExit() {
            $A.modalConfirm({
                title: '退出项目',
                content: '你确定要退出项目【' + this.projectData.name + '】吗？',
                loading: true,
                onOk: () => {
                    this.$store.dispatch("exitProject", this.projectId).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$Modal.remove();
                    });
                }
            });
        },

        projectDropdown(name) {
            switch (name) {
                case "setting":
                    this.$set(this.settingData, 'name', this.projectData.name);
                    this.$set(this.settingData, 'desc', this.projectData.desc);
                    this.settingShow = true;
                    this.$nextTick(() => {
                        this.$refs.projectName.focus();
                    });
                    break;

                case "user":
                    const userids = this.projectData.project_user.map(({userid}) => userid);
                    this.$set(this.userData, 'userids', userids);
                    this.$set(this.userData, 'useridbak', userids);
                    this.$set(this.userData, 'uncancelable', [this.projectData.owner_userid]);
                    this.userShow = true;
                    break;

                case "invite":
                    this.inviteData = {};
                    this.inviteShow = true;
                    this.inviteGet()
                    break;

                case "log":
                    this.logShow = true;
                    break;

                case "archived_task":
                    this.archivedTaskShow = true;
                    break;

                case "transfer":
                    this.$set(this.transferData, 'owner_userid', [this.projectData.owner_userid]);
                    this.transferShow = true;
                    break;

                case "archived":
                    this.onArchived();
                    break;

                case "delete":
                    this.onDelete();
                    break;

                case "exit":
                    this.onExit();
                    break;
            }
        },

        openTask(task, receive) {
            this.$store.dispatch("openTask", task)
            if (receive === true) {
                // 向任务窗口发送领取任务请求
                setTimeout(() => {
                    Store.set('receiveTask', true);
                }, 300)
            }
        },

        taskIsHidden(task) {
            const {name, desc, complete_at} = task;
            const {searchText} = this;
            if (!this.projectParameters('completedTask')) {
                if (complete_at) {
                    return true;
                }
            }
            if (searchText) {
                if (!($A.strExists(name, searchText) || $A.strExists(desc, searchText))) {
                    return true;
                }
            }
            return false;
        },

        ownerUser(list) {
            return list.filter(({owner}) => owner == 1).sort((a, b) => {
                return a.id - b.id;
            });
        },

        inviteGet(refresh) {
            this.inviteLoad++;
            this.$store.dispatch("call", {
                url: 'project/invite',
                data: {
                    project_id: this.projectId,
                    refresh: refresh === true ? 'yes' : 'no'
                },
            }).then(({data}) => {
                this.inviteLoad--;
                this.inviteData = data;
                this.inviteCopy();
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.inviteLoad--;
            });
        },

        inviteCopy() {
            if (!this.inviteData.url) {
                return;
            }
            this.$copyText(this.inviteData.url).then(() => {
                $A.messageSuccess(this.$L('复制成功！'));
            }, () => {
                $A.messageError(this.$L('复制失败！'));
            });
        },

        inviteFocus() {
            this.$refs.inviteInput.focus({cursor:'all'});
        },

        toggleCompleted() {
            this.$store.dispatch('toggleProjectParameters', 'completedTask');
            this.completeTask = [];
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

        expiresFormat(date) {
            let time = Math.round($A.Date(date).getTime() / 1000) - this.nowTime;
            if (time < 86400 * 7 && time > 0 ) {
                return this.formatSeconds(time);
            } else if (time <= 0) {
                return '-' + this.formatSeconds(time * -1);
            }
            return this.formatTime(date)
        }
    }
}
</script>
