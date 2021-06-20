<template>
    <div class="project-list">
        <div class="project-head">
            <div class="project-titbox">
                <div class="project-title">
                    <h1>{{projectDetail.name}}</h1>
                    <div v-if="projectLoad > 0" class="project-load"><Loading/></div>
                </div>
                <ul class="project-icons">
                    <li>
                        <UserAvatar :userid="projectDetail.owner_userid" :size="36">
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
                    <li :class="['project-icon', projectChatShow ? 'active' : '']" @click="toggleBoolean('projectChatShow')">
                        <Icon class="menu-icon" type="ios-chatbubbles" />
                        <Badge class="menu-badge" :count="msgUnread"></Badge>
                    </li>
                    <li class="project-icon">
                        <EDropdown @command="projectDropdown" trigger="click" transfer>
                            <Icon class="menu-icon" type="ios-more" />
                            <EDropdownMenu v-if="projectDetail.owner_userid === userId" slot="dropdown">
                                <EDropdownItem command="setting">{{$L('项目设置')}}</EDropdownItem>
                                <EDropdownItem command="user">{{$L('成员管理')}}</EDropdownItem>
                                <EDropdownItem command="transfer" divided>{{$L('移交项目')}}</EDropdownItem>
                                <EDropdownItem command="delete" style="color:#f40">{{$L('删除项目')}}</EDropdownItem>
                            </EDropdownMenu>
                            <EDropdownMenu v-else slot="dropdown">
                                <EDropdownItem command="exit">{{$L('退出项目')}}</EDropdownItem>
                            </EDropdownMenu>
                        </EDropdown>
                    </li>
                </ul>
            </div>
            <div v-if="projectDetail.desc" class="project-subtitle">{{projectDetail.desc}}</div>
            <div class="project-switch">
                <div v-if="completedCount > 0" class="project-checkbox">
                    <Checkbox :value="projectCompleteShow" @on-change="toggleBoolean('projectCompleteShow', $event)">{{$L('显示已完成')}}</Checkbox>
                </div>
                <div :class="['project-switch-button', !projectTablePanel ? 'menu' : '']" @click="toggleBoolean('projectTablePanel')">
                    <div><i class="iconfont">&#xe60c;</i></div>
                    <div><i class="iconfont">&#xe66a;</i></div>
                </div>
            </div>
        </div>
        <div v-if="projectTablePanel" class="project-column overlay-x">
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
                        :style="column.color ? {backgroundColor: column.color} : {}">
                        <div class="column-head-title">
                            <AutoTip>{{column.name}}</AutoTip>
                            <em>({{panelTask(column.project_task).length}})</em>
                        </div>
                        <div class="column-head-icon">
                            <div v-if="column.loading === true" class="loading"><Loading /></div>
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
                                    <EDropdownItem command="delete">
                                        <div class="item">
                                            <Icon type="md-trash" />{{$L('删除')}}
                                        </div>
                                    </EDropdownItem>
                                    <EDropdownItem divided disabled>{{$L('颜色')}}</EDropdownItem>
                                    <EDropdownItem v-for="(c, k) in columnColorList" :key="k" :command="c">
                                        <div class="item">
                                            <i class="iconfont" :style="{color:c.color}" v-html="c.color == column.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                        </div>
                                    </EDropdownItem>
                                </EDropdownMenu>
                            </EDropdown>
                            <Icon class="last" type="md-add" @click="addTopShow(column)" />
                        </div>
                    </div>
                    <div :ref="'column_' + column.id" class="column-task overlay-y">
                        <div v-if="column.addTopShow===true" class="task-item additem">
                            <TaskAddSimple
                                :column-id="column.id"
                                :project-id="projectDetail.id"
                                :add-top="true"
                                @on-close="column.addTopShow=false"
                                @on-priority="addTaskOpen"
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
                            <div
                                v-for="item in column.project_task"
                                :class="['task-item task-draggable', item.complete_at ? 'complete' : '', taskHidden(item) ? 'hidden' : '']"
                                :style="item.color ? {backgroundColor: item.color} : {}"
                                @click="openTask(item)">
                                <div :class="['task-head', item.desc ? 'has-desc' : '']">
                                    <div class="task-title"><pre>{{item.name}}</pre></div>
                                    <div class="task-menu" @click.stop="">
                                        <div v-if="item.loading === true" class="loading"><Loading /></div>
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
                                                <EDropdownItem v-for="(c, k) in taskColorList" :key="k" :command="c">
                                                    <div class="item">
                                                        <i class="iconfont" :style="{color:c.color||'#f9f9f9'}" v-html="c.color == item.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                                    </div>
                                                </EDropdownItem>
                                            </EDropdownMenu>
                                        </EDropdown>
                                    </div>
                                </div>
                                <div v-if="item.desc" class="task-desc" v-html="item.desc"></div>
                                <div v-if="item.task_tag.length > 0" class="task-tags">
                                    <Tag v-for="(tag, keyt) in item.task_tag" :key="keyt" :color="tag.color">{{tag.name}}</Tag>
                                </div>
                                <div class="task-users">
                                    <ul>
                                        <li v-for="(user, keyu) in item.task_user" :key="keyu">
                                            <UserAvatar :userid="user.userid" size="32" :borderWitdh="2" :borderColor="item.color"/>
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
                                        <div v-if="!item.complete_at"><Icon type="ios-time-outline"/>{{ expiresFormat(item.end_at) }}</div>
                                    </ETooltip>
                                </div>
                                <em v-if="item.p_name" class="priority-color" :style="{backgroundColor:item.p_color}"></em>
                            </div>
                            <div class="task-item additem">
                                <TaskAddSimple
                                    :column-id="column.id"
                                    :project-id="projectDetail.id"
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
                    <Col span="3">{{$L('优先级')}}</Col>
                    <Col span="3">{{$L('负责人')}}</Col>
                    <Col span="3">{{$L('到期时间')}}</Col>
                </Row>
            </div>
            <!--我的任务-->
            <div :class="['project-table-body', !taskMyShow ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="iconfont" @click="toggleBoolean('taskMyShow')">&#xe689;</i>
                        <div class="row-h1">{{$L('我的任务')}}</div>
                        <div class="row-num">({{myList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow :list="myList" open-key="my" :color-list="taskColorList" @command="dropTask" fast-add-task/>
            </div>
            <!--未完成任务-->
            <div v-if="projectDetail.task_num > 0" :class="['project-table-body', !taskUndoneShow ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="iconfont" @click="toggleBoolean('taskUndoneShow')">&#xe689;</i>
                        <div class="row-h1">{{$L('未完成任务')}}</div>
                        <div class="row-num">({{undoneList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow :list="undoneList" open-key="undone" :color-list="taskColorList" @command="dropTask"/>
            </div>
            <!--已完成任务-->
            <div v-if="projectDetail.task_num > 0" :class="['project-table-body', !taskCompletedShow ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="iconfont" @click="toggleBoolean('taskCompletedShow')">&#xe689;</i>
                        <div class="row-h1">{{$L('已完成任务')}}</div>
                        <div class="row-num">({{completedList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow :list="completedList" open-key="completed" :color-list="taskColorList" @command="dropTask"/>
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
            :mask-closable="false">
            <TaskAdd ref="add" v-model="addData" @on-add="onAddTask"/>
            <div slot="footer">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="taskLoad > 0" @click="onAddTask">{{$L('添加')}}</Button>
            </div>
        </Modal>

        <!--项目设置-->
        <Modal
            v-model="settingShow"
            :title="$L('项目设置')"
            :mask-closable="false">
            <Form ref="addProject" :model="settingData" label-width="auto" @submit.native.prevent>
                <FormItem prop="name" :label="$L('项目名称')">
                    <Input ref="projectName" type="text" v-model="settingData.name" :maxlength="32" :placeholder="$L('必填')"></Input>
                </FormItem>
                <FormItem prop="desc" :label="$L('项目介绍')">
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
            :mask-closable="false">
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
            :mask-closable="false">
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
import TaskRow from "./TaskRow";
export default {
    name: "ProjectList",
    components: {TaskRow, Draggable, TaskAddSimple, UserInput, TaskAdd, TaskPriority},
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

            columnColorList: [
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
            ],

            taskColorList: [
                {name: '默认', color: ''},
                {name: '黄色', color: '#FCF4A7'},
                {name: '蓝色', color: '#BCF2FD'},
                {name: '绿色', color: '#C3FDAA'},
                {name: '粉色', color: '#F6C9C8'},
                {name: '紫色', color: '#BAC9FB'},
                {name: '灰色', color: '#EEEEEE'},
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

            'projectList',
            'projectDetail',
            'projectLoad',

            'projectChatShow',
            'projectTablePanel',
            'projectCompleteShow',
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
            const {searchText, projectCompleteShow} = this;
            return function (project_task) {
                if (!projectCompleteShow) {
                    project_task = project_task.filter(({complete_at}) => {
                        return !complete_at;
                    });
                }
                if (searchText) {
                    project_task = project_task.filter(({name, desc}) => {
                        return $A.strExists(name, searchText) || $A.strExists(desc, searchText);
                    });
                }
                return project_task;
            }
        },

        myList() {
            const {searchText, projectCompleteShow, userId, projectDetail} = this;
            const array = [];
            projectDetail.project_column.forEach(({project_task, name}) => {
                project_task.some((task) => {
                    if (!projectCompleteShow) {
                        if (task.complete_at) {
                            return false;
                        }
                    }
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
            return array.sort((a, b) => {
                if (a.p_level != b.p_level) {
                    return a.p_level - b.p_level;
                }
                if (a.sort != b.sort) {
                    return a.sort - b.sort;
                }
                return a.id - b.id;
            });
        },

        undoneList() {
            const {searchText, projectCompleteShow, projectDetail} = this;
            const array = [];
            projectDetail.project_column.forEach(({project_task, name}) => {
                project_task.some((task) => {
                    if (!projectCompleteShow) {
                        if (task.complete_at) {
                            return false;
                        }
                    }
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
            return array.sort((a, b) => {
                if (a.p_level != b.p_level) {
                    return a.p_level - b.p_level;
                }
                if (a.sort != b.sort) {
                    return a.sort - b.sort;
                }
                return a.id - b.id;
            });
        },

        completedCount() {
            const {projectDetail} = this;
            let count = 0;
            projectDetail.project_column.forEach(({project_task, name}) => {
                count += project_task.filter(({complete_at}) => !!complete_at).length;
            });
            return count;
        },

        completedList() {
            const {searchText, projectCompleteShow, projectDetail} = this;
            const array = [];
            projectDetail.project_column.forEach(({project_task, name}) => {
                project_task.some((task) => {
                    if (!projectCompleteShow) {
                        if (task.complete_at) {
                            return false;
                        }
                    }
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
            return array.sort((a, b) => {
                if (a.p_level != b.p_level) {
                    return a.p_level - b.p_level;
                }
                if (a.sort != b.sort) {
                    return a.sort - b.sort;
                }
                return a.id - b.id;
            });
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
            this.$store.dispatch("call", {
                url: 'project/sort',
                data: {
                    project_id: this.projectDetail.id,
                    sort: this.sortData,
                    only_column: only_column === true ? 1 : 0
                },
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.sortDisabled = false;
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.sortDisabled = false;
                this.$store.dispatch("getProjectDetail", this.projectDetail);
            });
        },

        onAddTask() {
            if (!this.addData.name) {
                $A.messageError("任务描述不能为空");
                return;
            }
            this.taskLoad++;
            this.$store.dispatch("taskAdd", this.addData).then(({msg}) => {
                $A.messageSuccess(msg);
                this.taskLoad--;
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
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.taskLoad--;
            });
        },

        addTopShow(column) {
            this.$set(column, 'addTopShow', true);
            this.$refs['column_' + column.id][0].scrollTop = 0;
        },

        addTaskOpen(column_id) {
            if ($A.isJson(column_id)) {
                this.addData = Object.assign({}, this.addData, column_id);
            } else {
                this.$set(this.addData, 'owner', this.userId);
                this.$set(this.addData, 'column_id', column_id);
                this.$set(this.addData, 'project_id', this.projectDetail.id);
            }
            this.$refs.add.defaultPriority();
            this.addShow = true;
            this.$nextTick(() => {
                this.$refs.add.$refs.input.focus();
            })
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
                    project_id: this.projectDetail.id,
                    name: name,
                },
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.addColumnName = '';
                this.$store.commit("columnAddSuccess", data);
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        dropColumn(column, command) {
            if (command === 'title') {
                this.titleColumn(column);
            }
            else if (command === 'delete') {
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
            if (column.loading === true) {
                return;
            }
            this.$set(column, 'loading', true);
            //
            const backup = $A.cloneJSON(column);
            Object.keys(updata).forEach(key => this.$set(column, key, updata[key]));
            //
            this.$store.dispatch("call", {
                url: 'project/column/update',
                data: Object.assign(updata, {
                    column_id: column.id,
                }),
            }).then(({data}) => {
                this.$set(column, 'loading', false);
                this.$store.commit("columnUpdateSuccess", data);
            }).catch(({msg}) => {
                this.$set(column, 'loading', false);
                Object.keys(updata).forEach(key => this.$set(column, key, backup[key]));
                $A.modalError(msg);
            });
        },

        removeColumn(column) {
            $A.modalConfirm({
                title: '删除列表',
                content: '你确定要删除列表【' + column.name + '】及列表内的任务吗？',
                loading: true,
                onOk: () => {
                    if (column.loading === true) {
                        return;
                    }
                    this.$set(column, 'loading', true);
                    //
                    this.$store.dispatch("call", {
                        url: 'project/column/remove',
                        data: {
                            column_id: column.id,
                        },
                    }).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        this.$set(column, 'loading', false);
                        this.$Modal.remove();
                        this.$store.commit("columnDeleteSuccess", data);
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$set(column, 'loading', false);
                        this.$Modal.remove();
                    });
                }
            });
        },

        dropTask(task, command) {
            switch (command) {
                case 'complete':
                    if (task.complete_at) return;
                    this.updateTask(task, {
                        complete_at: $A.formatDate("Y-m-d H:i:s")
                    })
                    break;
                case 'uncomplete':
                    if (!task.complete_at) return;
                    this.updateTask(task, {
                        complete_at: false
                    })
                    break;
                case 'archived':
                case 'remove':
                    this.archivedOrRemoveTask(task, command);
                    break;
                default:
                    if (command.name) {
                        this.updateTask(task, {
                            color: command.color
                        })
                    }
                    break;
            }
        },

        updateTask(task, updata) {
            if (task.loading === true) {
                return;
            }
            this.$set(task, 'loading', true);
            //
            Object.keys(updata).forEach(key => this.$set(task, key, updata[key]));
            this.$store.dispatch("taskUpdate", Object.assign(updata, {
                task_id: task.id,
            })).then(() => {
                this.$set(task, 'loading', false);
            }).catch(({msg}) => {
                this.$set(task, 'loading', false);
                $A.modalError(msg);
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
                    if (task.loading === true) {
                        this.$Modal.remove();
                        return;
                    }
                    this.$set(task, 'loading', true);
                    this.$store.dispatch(typeDispatch, task.id).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$Modal.remove();
                    });
                }
            });
        },

        onSetting() {
            this.settingLoad++;
            this.$store.dispatch("call", {
                url: 'project/update',
                data: this.settingData,
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
                    project_id: this.userData.project_id,
                    userid: this.userData.userids,
                },
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.userLoad--;
                this.userShow = false;
                this.$store.dispatch("getProjectDetail", data);
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
                    project_id: this.transferData.project_id,
                    owner_userid: this.transferData.owner_userid[0],
                },
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.transferLoad--;
                this.transferShow = false;
                this.$store.dispatch("getProjectDetail", data);
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.transferLoad--;
            });
        },

        onDelete() {
            $A.modalConfirm({
                title: '删除项目',
                content: '你确定要删除项目【' + this.projectDetail.name + '】吗？',
                loading: true,
                onOk: () => {
                    this.$store.dispatch("call", {
                        url: 'project/remove',
                        data: {
                            project_id: this.projectDetail.id,
                        },
                    }).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                        this.$store.dispatch("removeProject", data.id);
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
                content: '你确定要退出项目【' + this.projectDetail.name + '】吗？',
                loading: true,
                onOk: () => {
                    this.$store.dispatch("call", {
                        url: 'project/exit',
                        data: {
                            project_id: this.projectDetail.id,
                        },
                    }).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                        this.$store.dispatch("removeProject", data.id);
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
                    this.$set(this.settingData, 'project_id', this.projectDetail.id);
                    this.$set(this.settingData, 'name', this.projectDetail.name);
                    this.$set(this.settingData, 'desc', this.projectDetail.desc);
                    this.settingShow = true;
                    this.$nextTick(() => {
                        this.$refs.projectName.focus();
                    });
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

        openTask(task) {
            if (task.parent_id > 0) {
                this.$store.dispatch("openTask", task.parent_id)
            } else {
                this.$store.dispatch("openTask", task.id)
            }
        },

        toggleBoolean(type) {
            this.$store.dispatch("toggleBoolean", type);
        },

        taskHidden(task) {
            const {name, desc, complete_at} = task;
            const {searchText, projectCompleteShow} = this;
            if (!projectCompleteShow) {
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

        sortBy(field) {
            return function (a, b) {
                return a[field] - b[field];
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
        },
    }
}
</script>
