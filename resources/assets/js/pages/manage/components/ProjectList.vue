<template>
    <div class="project-list">
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
                    <li :class="['project-icon', tablePanel('chat') ? 'active' : '']" @click="$store.dispatch('toggleTablePanel', 'chat')">
                        <Icon class="menu-icon" type="ios-chatbubbles" />
                        <Badge class="menu-badge" :count="msgUnread"></Badge>
                    </li>
                    <li class="project-icon">
                        <EDropdown @command="projectDropdown" trigger="click" transfer>
                            <Icon class="menu-icon" type="ios-more" />
                            <EDropdownMenu v-if="projectData.owner_userid === userId" slot="dropdown">
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
            <div v-if="projectData.desc" class="project-subtitle">{{projectData.desc}}</div>
            <div class="project-switch">
                <div v-if="completedCount > 0" class="project-checkbox">
                    <Checkbox :value="showCompletedTask" @on-change="$store.dispatch('toggleBoolean', 'showCompletedTask')">{{$L('显示已完成')}}</Checkbox>
                </div>
                <div :class="['project-switch-button', !tablePanel('card') ? 'menu' : '']" @click="$store.dispatch('toggleTablePanel', 'card')">
                    <div><i class="iconfont">&#xe60c;</i></div>
                    <div><i class="iconfont">&#xe66a;</i></div>
                </div>
            </div>
        </div>
        <div v-if="tablePanel('card')" class="project-column overlay-x">
            <Draggable
                :list="projectData.columns"
                :animation="150"
                :disabled="sortDisabled"
                class="column-list"
                tag="ul"
                draggable=".column-item"
                @sort="sortUpdate(true)">
                <li v-for="column in projectData.columns" class="column-item">
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
                                :project-id="projectId"
                                :add-top="true"
                                @on-close="column.addTopShow=false"
                                @on-priority="addTaskOpen"
                                auto-active/>
                        </div>
                        <Draggable
                            :list="column.tasks"
                            :animation="150"
                            :disabled="sortDisabled"
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
                    <Col span="3">{{$L('优先级')}}</Col>
                    <Col span="3">{{$L('负责人')}}</Col>
                    <Col span="3">{{$L('到期时间')}}</Col>
                </Row>
            </div>
            <!--我的任务-->
            <div :class="['project-table-body', !tablePanel('showMy') ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="iconfont" @click="$store.dispatch('toggleTablePanel', 'showMy')">&#xe689;</i>
                        <div class="row-h1">{{$L('我的任务')}}</div>
                        <div class="row-num">({{myList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow :list="myList" open-key="my" @command="dropTask" fast-add-task/>
            </div>
            <!--未完成任务-->
            <div v-if="projectData.task_num > 0" :class="['project-table-body', !tablePanel('showUndone') ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="iconfont" @click="$store.dispatch('toggleTablePanel', 'showUndone')">&#xe689;</i>
                        <div class="row-h1">{{$L('未完成任务')}}</div>
                        <div class="row-num">({{undoneList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow :list="undoneList" open-key="undone" @command="dropTask"/>
            </div>
            <!--已完成任务-->
            <div v-if="projectData.task_num > 0" :class="['project-table-body', !tablePanel('showCompleted') ? 'project-table-hide' : '']">
                <Row class="task-row">
                    <Col span="12" class="row-title">
                        <i class="iconfont" @click="$store.dispatch('toggleTablePanel', 'showCompleted')">&#xe689;</i>
                        <div class="row-h1">{{$L('已完成任务')}}</div>
                        <div class="row-num">({{completedList.length}})</div>
                    </Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                    <Col span="3"></Col>
                </Row>
                <TaskRow :list="completedList" open-key="completed" @command="dropTask"/>
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
            <TaskAdd ref="add" @on-add="onAddTask"/>
            <div slot="footer">
                <Button type="default" @click="addShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="addLoad > 0" @click="onAddTask">{{$L('添加')}}</Button>
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
import {mapGetters, mapState} from "vuex";
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

            columnLoad: {},
            taskLoad: {},

            searchText: '',

            addShow: false,
            addLoad: 0,

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
            'dialogs',

            'projectId',
            'projectLoad',
            'tasks',
            'columns',

            'showCompletedTask',
        ]),

        ...mapGetters(['projectData', 'tablePanel']),

        msgUnread() {
            const {dialogs, projectData} = this;
            const dialog = dialogs.find(({id}) => id === projectData.dialog_id);
            return dialog ? dialog.unread : 0;
        },

        panelTask() {
            const {searchText, showCompletedTask} = this;
            return function (list) {
                if (!showCompletedTask) {
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

        myList() {
            const {projectId, tasks, searchText, showCompletedTask, userId} = this;
            const array = tasks.filter((task) => {
                if (task.project_id != projectId) {
                    return false;
                }
                if (!showCompletedTask) {
                    if (task.complete_at) {
                        return false;
                    }
                }
                if (searchText) {
                    if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                        return false;
                    }
                }
                return task.task_user.find(({userid}) => userid == userId);
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
            const {projectId, tasks, searchText, showCompletedTask} = this;
            const array = tasks.filter((task) => {
                if (task.project_id != projectId) {
                    return false;
                }
                if (!showCompletedTask) {
                    if (task.complete_at) {
                        return false;
                    }
                }
                if (searchText) {
                    if (!$A.strExists(task.name, searchText) && !$A.strExists(task.desc, searchText)) {
                        return false;
                    }
                }
                return !task.complete_at;
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
            const {projectId, tasks} = this;
            return tasks.filter((task) => {
                if (task.project_id != projectId) {
                    return false;
                }
                return task.complete_at;
            }).length;
        },

        completedList() {
            const {projectId, tasks, searchText} = this;
            const array = tasks.filter((task) => {
                if (task.project_id != projectId) {
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
        projectData() {
            this.sortData = this.getSort();
        }
    },

    methods: {
        getSort() {
            const sortData = [];
            this.projectData.columns.forEach((column) => {
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
            this.sortDisabled = true;
            this.$store.dispatch("call", {
                url: 'project/sort',
                data: {
                    project_id: this.projectId,
                    sort: this.sortData,
                    only_column: only_column === true ? 1 : 0
                },
            }).then(({msg}) => {
                $A.messageSuccess(msg);
                this.sortDisabled = false;
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.sortDisabled = false;
                this.$store.dispatch("getTasks", {project_id: this.projectId})
            });
        },

        onAddTask() {
            this.addLoad++;
            this.$refs.add.onAdd((success) => {
                this.addLoad--;
                if (success) {
                    this.addShow = false;
                }
            })
        },

        addTopShow(column) {
            this.$set(column, 'addTopShow', true);
            this.$refs['column_' + column.id][0].scrollTop = 0;
        },

        addTaskOpen(column_id) {
            this.$refs.add.defaultPriority();
            this.$refs.add.setData($A.isJson(column_id) ? column_id : {
                'owner': this.userId,
                'column_id': column_id,
            });
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
                this.$store.dispatch("getColumns", {project_id: this.projectId})
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
                    this.$store.dispatch("call", {
                        url: 'project/column/remove',
                        data: {
                            column_id: column.id,
                        },
                    }).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        this.$set(this.columnLoad, column.id, false);
                        this.$Modal.remove();
                        this.$store.dispatch("forgetColumn", data.id);
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$set(this.columnLoad, column.id, false);
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
            if (this.taskLoad[task.id] === true) {
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
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.$set(this.taskLoad, task.id, false);
                this.$store.dispatch("getTaskOne", task.id);
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
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.transferLoad--;
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
                    this.$set(this.userData, 'userids', this.projectData.project_user.map(({userid}) => userid));
                    this.$set(this.userData, 'uncancelable', [this.projectData.owner_userid]);
                    this.userShow = true;
                    break;

                case "transfer":
                    this.$set(this.transferData, 'owner_userid', [this.projectData.owner_userid]);
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

        taskIsHidden(task) {
            const {name, desc, complete_at} = task;
            const {searchText, showCompletedTask} = this;
            if (!showCompletedTask) {
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
