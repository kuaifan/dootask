<template>
    <div class="project-list">
        <div class="project-head">
            <div class="project-titbox">
                <div class="project-title">
                    <h1>{{projectDetail.name}}</h1>
                    <div v-if="projectLoad > 0" class="project-load"><Loading/></div>
                </div>
                <div v-if="projectDetail.desc" class="project-subtitle">{{projectDetail.desc}}</div>
            </div>
            <div class="project-icobox">
                <ul class="project-icons">
                    <li>
                        <UserAvatar :userid="projectDetail.owner_userid" :size="36"/>
                    </li>
                    <li class="project-icon" @click="addTaskOpen(0)">
                        <Icon type="md-add" />
                    </li>
                    <li class="project-icon">
                        <Tooltip theme="light" :always="searchText!=''" transfer>
                            <Icon type="ios-search" />
                            <div slot="content">
                                <Input v-model="searchText" :placeholder="$L('名称、描述...')" clearable autofocus/>
                            </div>
                        </Tooltip>
                    </li>
                    <li :class="['project-icon', projectChatShow ? 'active' : '']" @click="toggleBoolean('projectChatShow')">
                        <Icon type="ios-chatbubbles" />
                        <Badge :count="msgUnread"></Badge>
                    </li>
                    <li class="project-icon">
                        <Dropdown @on-click="projectDropdown" trigger="click" transfer>
                            <Icon type="ios-more" />
                            <DropdownMenu v-if="projectDetail.owner_userid === userId" slot="list">
                                <DropdownItem name="setting">{{$L('项目设置')}}</DropdownItem>
                                <DropdownItem name="user">{{$L('成员管理')}}</DropdownItem>
                                <DropdownItem name="transfer" divided>{{$L('移交项目')}}</DropdownItem>
                                <DropdownItem name="delete" style="color:#f40">{{$L('删除项目')}}</DropdownItem>
                            </DropdownMenu>
                            <DropdownMenu v-else slot="list">
                                <DropdownItem name="exit">{{$L('退出项目')}}</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>

                    </li>
                </ul>
                <div class="project-switch">
                    <div :class="['project-switch-button', !projectListPanel ? 'menu' : '']" @click="toggleBoolean('projectListPanel')">
                        <div><i class="iconfont">&#xe60c;</i></div>
                        <div><i class="iconfont">&#xe66a;</i></div>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="projectListPanel" class="project-column">
            <Draggable
                :list="projectDetail.project_column"
                :animation="150"
                :disabled="sortDisabled"
                class="column-list"
                tag="ul"
                draggable=".column-item"
                @sort="sortUpdate(true)">
                <li v-for="column in projectDetail.project_column" class="column-item">
                    <div
                        :class="['column-head', column.color ? 'custom-color' : '']"
                        :style="column.color ? {backgroundColor: column.color}:null">
                        <div class="column-head-title">
                            <span><AutoTip>{{column.name}}</AutoTip></span>
                            <em>({{column.project_task.length}})</em>
                        </div>
                        <div class="column-head-icon">
                            <Poptip>
                                <Icon type="ios-more" />
                                <div class="more-content" slot="content">
                                    <ul>
                                        <li @click="modifyColumn(column)"><Icon type="md-create" />{{$L('修改')}}</li>
                                        <li @click="removeColumn(column)"><Icon type="md-trash" />{{$L('删除')}}</li>
                                        <li class="divided"></li>
                                        <li class="title">{{$L('颜色')}}</li>
                                        <li v-for="(c, k) in colorList" :key="k" @click="saveColumn(column, column.name, c.color)">
                                            <i class="iconfont" :style="{color:c.color}" v-html="c.color == column.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                        </li>
                                    </ul>
                                </div>
                            </Poptip>
                            <Icon type="md-add" @click="addTopShow(column)" />
                        </div>
                    </div>
                    <div :ref="'column_' + column.id" class="column-task overlay-y">
                        <div v-if="column.addTopShow===true" class="task-item">
                            <TaskAddSimple
                                :column-id="column.id"
                                :project-id="projectDetail.id"
                                :add-top="true"
                                @on-close="column.addTopShow=false"
                                @on-priority="addTaskOpen"
                                @on-success="addTaskSuccess"
                                auto-active/>
                        </div>
                        <Draggable
                            :list="column.project_task"
                            :animation="150"
                            :disabled="sortDisabled"
                            class="task-list"
                            draggable=".task-draggable"
                            group="task"
                            @sort="sortUpdate"
                            @remove="sortUpdate">
                            <div v-for="item in panelTask(column.project_task)" class="task-item task-draggable">
                            <div :class="['task-head', item.desc ? 'has-desc' : '']">
                                <div class="task-title"><pre>{{item.name}}</pre></div>
                                <Icon type="ios-more" />
                            </div>
                            <div v-if="item.desc" class="task-desc" v-html="item.desc"></div>
                            <div v-if="item.task_tag.length > 0" class="task-tags">
                                <Tag v-for="(tag, keyt) in item.task_tag" :key="keyt" :color="tag.color">{{tag.name}}</Tag>
                            </div>
                            <div class="task-users">
                                <ul>
                                    <li v-for="(user, keyu) in item.task_user" :key="keyu">
                                        <UserAvatar :userid="user.userid" size="28"/>
                                    </li>
                                </ul>
                                <div v-if="item.file_num > 0" class="task-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                                <div v-if="item.msg_num > 0" class="task-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                            </div>
                            <div class="task-progress">
                                <Progress :percent="item.percent" :stroke-width="6" />
                                <Tooltip
                                    v-if="item.end_at"
                                    :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                                    :delay="600"
                                    :content="item.end_at"
                                    transfer>
                                    <Icon type="ios-time-outline"/>{{ expiresFormat(item.end_at) }}
                                </Tooltip>
                            </div>
                            <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                        </div>
                            <div class="task-item">
                                <TaskAddSimple
                                    :column-id="column.id"
                                    :project-id="projectDetail.id"
                                    @on-priority="addTaskOpen"
                                    @on-success="addTaskSuccess"/>
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
        <div v-else class="project-table">
            <div class="project-table-head">
                <Row class="project-row">
                    <Col span="12"># {{$L('任务名称')}}</Col>
                    <Col span="3">{{$L('列表')}}</Col>
                    <Col span="3">{{$L('优先级')}}</Col>
                    <Col span="3">{{$L('负责人')}}</Col>
                    <Col span="3">{{$L('到期时间')}}</Col>
                </Row>
            </div>
            <!--我的任务-->
            <div :class="['project-table-body', !taskMyShow ? 'project-table-hide' : '']">
                <div @click="toggleBoolean('taskMyShow')">
                    <Row class="project-row">
                        <Col span="12" class="row-title">
                            <i class="iconfont">&#xe689;</i>
                            <div class="row-h1">{{$L('我的任务')}}</div>
                            <div class="row-num">({{myList.length}})</div>
                        </Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                    </Row>
                </div>
                <div class="project-rows">
                    <Row v-for="(item, key) in myList" :key="key" class="project-row">
                        <Col span="12" class="row-item">
                            <Icon v-if="item.complete_at" class="completed" type="md-checkmark-circle" />
                            <Icon v-else type="md-radio-button-off" />
                            <div class="item-title">{{item.name}}</div>
                            <div v-if="item.file_num > 0" class="item-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                            <div v-if="item.msg_num > 0" class="item-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                        </Col>
                        <Col span="3">{{item.column_name}}</Col>
                        <Col span="3"><TaskPriority v-if="item.p_name" :backgroundColor="item.p_color">{{item.p_name}}</TaskPriority></Col>
                        <Col span="3" class="row-member">
                            <ul>
                                <li v-for="(user, keyu) in item.task_user" :key="keyu">
                                    <UserAvatar :userid="user.userid" size="28"/>
                                </li>
                            </ul>
                        </Col>
                        <Col span="3">
                            <Tooltip
                                v-if="item.end_at"
                                :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                                :delay="600"
                                :content="item.end_at"
                                transfer>
                                {{item.end_at ? expiresFormat(item.end_at) : ''}}
                            </Tooltip>
                        </Col>
                        <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                    </Row>
                </div>
                <div @click="addTaskOpen(0)">
                    <Row class="project-row">
                        <Col span="12" class="row-add">
                            <Icon type="ios-add" /> {{$L('添加任务')}}
                        </Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                    </Row>
                </div>
            </div>
            <!--未完成任务-->
            <div :class="['project-table-body', !taskUndoneShow ? 'project-table-hide' : '']">
                <div @click="toggleBoolean('taskUndoneShow')">
                    <Row class="project-row">
                        <Col span="12" class="row-title">
                            <i class="iconfont">&#xe689;</i>
                            <div class="row-h1">{{$L('未完成任务')}}</div>
                            <div class="row-num">({{undoneList.length}})</div>
                        </Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                    </Row>
                </div>
                <div class="project-rows">
                    <Row v-for="(item, key) in undoneList" :key="key" class="project-row">
                        <Col span="12" class="row-item">
                            <Icon v-if="item.complete_at" class="completed" type="md-checkmark-circle" />
                            <Icon v-else type="md-radio-button-off" />
                            <div class="item-title">{{item.name}}</div>
                            <div v-if="item.file_num > 0" class="item-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                            <div v-if="item.msg_num > 0" class="item-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                        </Col>
                        <Col span="3">{{item.column_name}}</Col>
                        <Col span="3"><TaskPriority v-if="item.p_name" :backgroundColor="item.p_color">{{item.p_name}}</TaskPriority></Col>
                        <Col span="3" class="row-member">
                            <ul>
                                <li v-for="(user, keyu) in item.task_user" :key="keyu">
                                    <UserAvatar :userid="user.userid" size="28"/>
                                </li>
                            </ul>
                        </Col>
                        <Col span="3">
                            <Tooltip
                                v-if="item.end_at"
                                :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                                :delay="600"
                                :content="item.end_at"
                                transfer>
                                {{item.end_at ? expiresFormat(item.end_at) : ''}}
                            </Tooltip>
                        </Col>
                        <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                    </Row>
                </div>
            </div>
            <!--已完成任务-->
            <div :class="['project-table-body', !taskCompletedShow ? 'project-table-hide' : '']">
                <div @click="toggleBoolean('taskCompletedShow')">
                    <Row class="project-row">
                        <Col span="12" class="row-title">
                            <i class="iconfont">&#xe689;</i>
                            <div class="row-h1">{{$L('已完成任务')}}</div>
                            <div class="row-num">({{completedList.length}})</div>
                        </Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                        <Col span="3"></Col>
                    </Row>
                </div>
                <div class="project-rows">
                    <Row v-for="(item, key) in completedList" :key="key" class="project-row">
                        <Col span="12" class="row-item">
                            <Icon v-if="item.complete_at" class="completed" type="md-checkmark-circle" />
                            <Icon v-else type="md-radio-button-off" />
                            <div class="item-title">{{item.name}}</div>
                            <div v-if="item.file_num > 0" class="item-icon">{{item.file_num}}<Icon type="ios-link-outline" /></div>
                            <div v-if="item.msg_num > 0" class="item-icon">{{item.msg_num}}<Icon type="ios-chatbubbles-outline" /></div>
                        </Col>
                        <Col span="3">{{item.column_name}}</Col>
                        <Col span="3"><TaskPriority v-if="item.p_name" :backgroundColor="item.p_color">{{item.p_name}}</TaskPriority></Col>
                        <Col span="3" class="row-member">
                            <ul>
                                <li v-for="(user, keyu) in item.task_user" :key="keyu">
                                    <UserAvatar :userid="user.userid" size="28"/>
                                </li>
                            </ul>
                        </Col>
                        <Col span="3">
                            <Tooltip
                                v-if="item.end_at"
                                :class="['task-time', item.today ? 'today' : '', item.overdue ? 'overdue' : '']"
                                :delay="600"
                                :content="item.end_at"
                                transfer>
                                {{item.end_at ? expiresFormat(item.end_at) : ''}}
                            </Tooltip>
                        </Col>
                        <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                    </Row>
                </div>
            </div>
        </div>

        <!--添加任务-->
        <Modal
            v-model="addShow"
            :title="$L('添加任务')"
            :styles="{
                width: '90%',
                maxWidth: '640px'
            }"
            :mask-closable="false"
            class-name="simple-modal">
            <TaskAdd v-model="addData"/>
            <div slot="footer">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="taskLoad > 0" @click="onAddTask">{{$L('添加')}}</Button>
            </div>
        </Modal>

        <!--项目设置-->
        <Modal
            v-model="settingShow"
            :title="$L('项目设置')"
            :mask-closable="false"
            class-name="simple-modal">
            <Form ref="addProject" :model="settingData" label-width="auto" @submit.native.prevent>
                <FormItem prop="name" :label="$L('项目名称')">
                    <Input type="text" v-model="settingData.name" :maxlength="32" :placeholder="$L('必填')"></Input>
                </FormItem>
                <FormItem prop="desc" :label="$L('项目描述')">
                    <Input type="textarea" :autosize="{ minRows: 3, maxRows: 5 }" v-model="settingData.desc" :maxlength="255" :placeholder="$L('选填')"></Input>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="settingShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="settingLoad > 0" @click="onSetting">{{$L('修改')}}</Button>
            </div>
        </Modal>

        <!--成员管理-->
        <Modal
            v-model="userShow"
            :title="$L('成员管理')"
            :mask-closable="false"
            class-name="simple-modal">
            <Form ref="addProject" :model="userData" label-width="auto" @submit.native.prevent>
                <FormItem prop="userids" :label="$L('项目成员')">
                    <UserInput v-if="userShow" v-model="userData.userids" :uncancelable="userData.uncancelable" :multiple-max="100" :placeholder="$L('选择项目成员')"/>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="userShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="userLoad > 0" @click="onUser">{{$L('保存')}}</Button>
            </div>
        </Modal>

        <!--移交项目-->
        <Modal
            v-model="transferShow"
            :title="$L('移交项目')"
            :mask-closable="false"
            class-name="simple-modal">
            <Form ref="addProject" :model="transferData" label-width="auto" @submit.native.prevent>
                <FormItem prop="owner_userid" :label="$L('项目负责人')">
                    <UserInput v-if="transferShow" v-model="transferData.owner_userid" :multiple-max="1" :placeholder="$L('选择项目负责人')"/>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" @click="transferShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="transferLoad > 0" @click="onTransfer">{{$L('移交')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import Draggable from 'vuedraggable'
import TaskPriority from "./TaskPriority";
import TaskAdd from "./TaskAdd";
import {mapState} from "vuex";
import UserInput from "../../../components/UserInput";
import TaskAddSimple from "./TaskAddSimple";
export default {
    name: "ProjectList",
    components: {Draggable, TaskAddSimple, UserInput, TaskAdd, TaskPriority},
    data() {
        return {
            nowTime: Math.round(new Date().getTime() / 1000),
            nowInterval: null,

            searchText: '',

            addShow: false,
            addData: {
                owner: 0,
                column_id: 0,
                times: [],
                subtasks: [],
                p_level: 0,
                p_name: '',
                p_color: '',
            },
            taskLoad: 0,

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

            transferShow: false,
            transferData: {},
            transferLoad: 0,

            colorList: [
                {name: '默认', color: ''},
                {name: '灰色', color: '#6C6F71'},
                {name: '棕色', color: '#695C56'},
                {name: '橘色', color: '#9E7549'},
                {name: '黄色', color: '#A0904F'},
                {name: '绿色', color: '#4D7771'},
                {name: '蓝色', color: '#4C7088'},
                {name: '紫色', color: '#6B5C8D'},
                {name: '粉色', color: '#8E5373'},
                {name: '红色', color: '#9D6058'},
            ]
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
        ...mapState([
            'userId',
            'dialogList',

            'projectDetail',
            'projectLoad',

            'projectChatShow',
            'projectListPanel',
            'taskMyShow',
            'taskUndoneShow',
            'taskCompletedShow'
        ]),

        msgUnread() {
            const {dialogList, projectDetail} = this;
            const dialog = dialogList.find(({id}) => id === projectDetail.dialog_id);
            return dialog ? dialog.unread : 0;
        },

        panelTask() {
            const {searchText} = this;
            return function (project_task) {
                if (searchText) {
                    return project_task.filter((task) => {
                        return $A.strExists(task.name, searchText) || $A.strExists(task.desc, searchText);
                    });
                }
                return project_task;
            }
        },

        myList() {
            const {searchText, userId, projectDetail} = this;
            const array = [];
            projectDetail.project_column.forEach(({project_task, name}) => {
                project_task.some((task) => {
                    if (searchText) {
                        if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                            return false;
                        }
                    }
                    if (task.task_user.find(({userid}) => userid == userId)) {
                        task.column_name = name;
                        array.push(task);
                    }
                });
            });
            return array;
        },

        undoneList() {
            const {searchText, projectDetail} = this;
            const array = [];
            projectDetail.project_column.forEach(({project_task, name}) => {
                project_task.some((task) => {
                    if (searchText) {
                        if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                            return false;
                        }
                    }
                    if (!task.complete_at) {
                        task.column_name = name;
                        array.push(task);
                    }
                });
            });
            return array;
        },

        completedList() {
            const {searchText, projectDetail} = this;
            const array = [];
            projectDetail.project_column.forEach(({project_task, name}) => {
                project_task.some((task) => {
                    if (searchText) {
                        if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                            return false;
                        }
                    }
                    if (task.complete_at) {
                        task.column_name = name;
                        array.push(task);
                    }
                });
            });
            return array;
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

    watch: {
        projectDetail() {
            this.sortData = this.getSort();
        }
    },

    methods: {
        getSort() {
            const sortData = [];
            this.projectDetail.project_column.forEach((column) => {
                sortData.push({
                    id: column.id,
                    task: column.project_task.map(({id}) => id)
                });
            });
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
            this.sortDisabled = true;
            $A.apiAjax({
                url: 'project/sort',
                data: {
                    project_id: this.projectDetail.id,
                    sort: this.sortData,
                    only_column: only_column === true ? 1 : 0
                },
                complete: () => {
                    this.sortDisabled = false;
                },
                error: () => {
                    $A.modalAlert('网络繁忙，请稍后再试！');
                    this.$store.commit('getProjectDetail', this.projectDetail.id);
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        $A.messageSuccess(msg);
                    } else {
                        $A.modalError(msg);
                        this.$store.commit('getProjectDetail', this.projectDetail.id);
                    }
                }
            });
        },

        onAddTask() {
            this.taskLoad++;
            $A.apiAjax({
                url: 'project/task/add',
                data: this.addData,
                method: 'post',
                complete: () => {
                    this.taskLoad--;
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        $A.messageSuccess(msg);
                        this.addTaskSuccess(data)
                        this.addShow = false;
                        this.addData = {
                            owner: 0,
                            column_id: 0,
                            times: [],
                            subtasks: [],
                            p_level: 0,
                            p_name: '',
                            p_color: '',
                        };
                    } else {
                        $A.modalError(msg);
                    }
                }
            });
        },

        addTopShow(column) {
            this.$set(column, 'addTopShow', true);
            this.$refs['column_' + column.id][0].scrollTop = 0;
        },

        addTaskOpen(column_id) {
            if ($A.isJson(column_id)) {
                this.addData = Object.assign(this.addData, column_id);
            } else {
                this.$set(this.addData, 'owner', this.userId);
                this.$set(this.addData, 'column_id', column_id);
                this.$set(this.addData, 'project_id', this.projectDetail.id);
            }
            this.addShow = true;
        },

        addTaskSuccess(data) {
            this.projectDetail.project_column.some((item) => {
                if (item.id === data.column_id) {
                    if (data.top) {
                        item.project_task.unshift(data);
                    } else {
                        item.project_task.push(data);
                    }
                }
            });
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
            $A.apiAjax({
                url: 'project/column/add',
                data: {
                    project_id: this.projectDetail.id,
                    name: name,
                },
                error: () => {
                    $A.modalAlert('网络繁忙，请稍后再试！');
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        $A.messageSuccess(msg);
                        this.addColumnName = '';
                        this.projectDetail.project_column.push(data)
                    } else {
                        $A.modalError(msg, 301);
                    }
                }
            });
        },

        modifyColumn(column) {
            $A.modalInput({
                value: column.name,
                title: "修改列表",
                placeholder: "输入列表名称",
                onOk: (value) => {
                    if (value) {
                        this.saveColumn(column, value, column.color);
                    }
                    return true;
                }
            })
        },

        removeColumn(column) {
            $A.modalConfirm({
                title: '删除列表',
                content: '你确定要删除列表【' + column.name + '】及列表内的任务吗？',
                loading: true,
                onOk: () => {
                    $A.apiAjax({
                        url: 'project/column/delete',
                        data: {
                            project_id: this.projectDetail.id,
                            column_id: column.id,
                        },
                        error: () => {
                            this.$Modal.remove();
                            $A.modalAlert('网络繁忙，请稍后再试！');
                        },
                        success: ({ret, data, msg}) => {
                            this.$Modal.remove();
                            if (ret === 1) {
                                $A.messageSuccess(msg);
                                this.$store.commit('getProjectDetail', this.projectDetail.id);
                            }else{
                                $A.modalError(msg, 301);
                            }
                        }
                    });
                }
            });
        },

        saveColumn(column, name, color) {
            let bakName = column.name;
            let bakColor = column.color;
            this.$set(column, 'name', name);
            this.$set(column, 'color', color);
            //
            $A.apiAjax({
                url: 'project/column/add',
                data: {
                    project_id: this.projectDetail.id,
                    column_id: column.id,
                    name: name,
                    color: color,
                },
                error: () => {
                    this.$set(column, 'name', bakName);
                    this.$set(column, 'color', bakColor);
                },
                success: ({ret, data, msg}) => {
                    if (ret !== 1) {
                        this.$set(column, 'name', data.name);
                        this.$set(column, 'color', data.color);
                    }
                }
            });
        },

        onSetting() {
            this.settingLoad++;
            $A.apiAjax({
                url: 'project/edit',
                data: this.settingData,
                complete: () => {
                    this.settingLoad--;
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        $A.messageSuccess(msg);
                        this.$set(this.projectDetail, 'name', this.settingData.name);
                        this.$set(this.projectDetail, 'desc', this.settingData.desc);
                        this.settingShow = false;
                    } else {
                        $A.modalError(msg);
                    }
                }
            });
        },

        onUser() {
            this.userLoad++;
            $A.apiAjax({
                url: 'project/user',
                data: {
                    project_id: this.userData.project_id,
                    userid: this.userData.userids,
                },
                complete: () => {
                    this.userLoad--;
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        $A.messageSuccess(msg);
                        this.$store.commit('getProjectDetail', this.userData.project_id);
                        this.userShow = false;
                    } else {
                        $A.modalError(msg);
                    }
                }
            });
        },

        onTransfer() {
            this.transferLoad++;
            $A.apiAjax({
                url: 'project/transfer',
                data: {
                    project_id: this.transferData.project_id,
                    owner_userid: this.transferData.owner_userid[0],
                },
                complete: () => {
                    this.transferLoad--;
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        $A.messageSuccess(msg);
                        this.$store.commit('getProjectDetail', this.transferData.project_id);
                        this.transferShow = false;
                    } else {
                        $A.modalError(msg);
                    }
                }
            });
        },

        onDelete() {
            $A.modalConfirm({
                title: '删除项目',
                content: '你确定要删除项目【' + this.projectDetail.name + '】吗？',
                loading: true,
                onOk: () => {
                    $A.apiAjax({
                        url: 'project/delete',
                        data: {
                            project_id: this.projectDetail.id,
                        },
                        error: () => {
                            this.$Modal.remove();
                            $A.modalAlert('网络繁忙，请稍后再试！');
                        },
                        success: ({ret, data, msg}) => {
                            this.$Modal.remove();
                            if (ret === 1) {
                                $A.messageSuccess(msg);
                                this.$store.commit('getProjectList');
                                this.goForward({path: '/manage/dashboard'}, true);
                            }else{
                                $A.modalError(msg, 301);
                            }
                        }
                    });
                }
            });
        },

        onExit() {
            $A.modalConfirm({
                title: '退出项目',
                content: '你确定要退出项目【' + this.projectDetail.name + '】吗？',
                loading: true,
                onOk: () => {
                    $A.apiAjax({
                        url: 'project/exit',
                        data: {
                            project_id: this.projectDetail.id,
                        },
                        error: () => {
                            this.$Modal.remove();
                            $A.modalAlert('网络繁忙，请稍后再试！');
                        },
                        success: ({ret, data, msg}) => {
                            this.$Modal.remove();
                            if (ret === 1) {
                                $A.messageSuccess(msg);
                                this.$store.commit('getProjectList');
                                this.goForward({path: '/manage/dashboard'}, true);
                            }else{
                                $A.modalError(msg, 301);
                            }
                        }
                    });
                }
            });
        },

        projectDropdown(name) {
            switch (name) {
                case "setting":
                    this.$set(this.settingData, 'project_id', this.projectDetail.id);
                    this.$set(this.settingData, 'name', this.projectDetail.name);
                    this.$set(this.settingData, 'desc', this.projectDetail.desc);
                    this.settingShow = true;
                    break;

                case "user":
                    this.$set(this.userData, 'project_id', this.projectDetail.id);
                    this.$set(this.userData, 'userids', this.projectDetail.project_user.map(({userid}) => userid));
                    this.$set(this.userData, 'uncancelable', [this.projectDetail.owner_userid]);
                    this.userShow = true;
                    break;

                case "transfer":
                    this.$set(this.transferData, 'project_id', this.projectDetail.id);
                    this.$set(this.transferData, 'owner_userid', [this.projectDetail.owner_userid]);
                    this.transferShow = true;
                    break;

                case "delete":
                    this.onDelete();
                    break;

                case "exit":
                    this.onExit();
                    break;
            }
        },

        toggleBoolean(type) {
            this.$store.commit('toggleBoolean', type);
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
