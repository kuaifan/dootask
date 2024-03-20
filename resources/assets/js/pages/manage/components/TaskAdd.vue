<template>
    <div class="task-add">
        <div class="head" :class="{empty:addData.cascader.length == 0,visible:cascaderShow}">
            <Cascader
                v-model="addData.cascader"
                :data="cascaderData"
                :clearable="false"
                :placeholder="$L('请选择项目')"
                :load-data="cascaderLoadData"
                @on-change="cascaderChange"
                @on-input-change="cascaderInputChange"
                @on-visible-change="cascaderShow=!cascaderShow"
                filterable/>
        </div>
        <div class="task-add-form">
            <div class="title">
                <Input
                    v-model="addData.name"
                    ref="input"
                    type="textarea"
                    :rows="1"
                    :autosize="{ minRows: 1, maxRows: 8 }"
                    :maxlength="255"
                    :placeholder="$L('任务描述')"
                    enterkeyhint="done"
                    @on-keydown="onKeydown"/>
            </div>
            <TEditorTask
                class="desc"
                v-model="addData.content"
                :placeholder="$L(windowLandscape ? '详细描述，选填...（点击右键使用工具栏）' : '详细描述，选填...')"
                :placeholderFull="$L('详细描述...')"/>
            <div class="advanced-option" :class="{'advanced-open': advanced}">
                <Button @click="advanced=!advanced">{{$L('高级选项')}}</Button>
                <ul class="advanced-priority">
                    <li v-for="(item, key) in taskPriority" :key="key">
                        <ETooltip :disabled="$isEEUiApp || windowTouch" :content="taskPriorityContent(item)">
                            <i
                                class="taskfont"
                                :style="{color:item.color}"
                                v-html="addData.p_name == item.name ? '&#xe61d;' : '&#xe61c;'"
                                @click="choosePriority(item)"></i>
                        </ETooltip>
                    </li>
                </ul>
                <DatePicker
                    v-if="taskDays > 0"
                    :open="taskTimeOpen"
                    v-model="addData.times"
                    :options="timeOptions"
                    :placeholder="$L('选择计划范围')"
                    format="yyyy/MM/dd HH:mm"
                    type="datetimerange"
                    placement="bottom"
                    @on-change="taskTimeChange(addData)"
                    @on-open-change="taskTimeOpenChange">
                    <div class="advanced-time" @click="taskTimeOpenChange(!taskTimeOpen)">
                        <Icon type="ios-clock-outline" />
                        <em type="primary" :style="addData.p_color ? {backgroundColor:addData.p_color} : {}">{{taskDays}}</em>
                    </div>
                </DatePicker>
            </div>
        </div>

        <Form v-if="advanced" class="task-add-advanced" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('计划时间')">
                <DatePicker
                    v-model="addData.times"
                    :options="timeOptions"
                    :editable="false"
                    :placeholder="$L('选择计划范围')"
                    format="yyyy/MM/dd HH:mm"
                    type="datetimerange"
                    @on-change="taskTimeChange(addData)"/>
            </FormItem>
            <FormItem :label="$L('负责人')" >
                <UserSelect
                    v-model="addData.owner"
                    :multiple-max="10"
                    :title="$L('选择任务负责人')"
                    :project-id="addData.project_id"
                    :avatar-size="24"
                    border/>
            </FormItem>
            <FormItem :label="$L('协助人员')" >
                <UserSelect
                    v-model="addData.assist"
                    :multiple-max="10"
                    :title="$L('选择任务协助人员')"
                    :project-id="addData.project_id"
                    :disabled-choice="addData.owner"
                    :avatar-size="24"
                    border/>
            </FormItem>
            <FormItem>
                <div slot="label" class="visibility-text" @click="showCisibleDropdown">
                    {{ $L('可见性') }}
                    <i class="taskfont">&#xe740;</i>
                </div>
                <div
                    v-if="addData.visibility_appoint == 1 || addData.visibility_appoint == 2"
                    ref="visibilityText"
                    class="ivu-input task-add-visibility"
                    @click="showCisibleDropdown">
                    {{ addData.visibility_appoint == 1 ? $L('项目人员可见') : $L('任务人员可见') }}
                </div>
                <UserSelect
                    v-else
                    ref="visibleUserSelectRef"
                    v-model="addData.visibility_appointor"
                    :avatar-size="24"
                    :title="$L('选择指定人员')"
                    :project-id="addData.project_id"
                    @on-show-change="visibleUserSelectShowChange"
                    border/>
            </FormItem>
            <EDropdown ref="eDropdownRef" class="calculate-dropdown" trigger="click" placement="bottom" @command="dropVisible">
                <div class="calculate-content"></div>
                <EDropdownMenu slot="dropdown">
                    <EDropdownItem :command="1">
                        <div class="task-menu-icon" >
                            <Icon v-if="addData.visibility_appoint == 1" class="completed" :type="'md-checkmark-circle'"/>
                            <Icon v-else class="uncomplete" :type="'md-radio-button-off'"/>
                            {{$L('项目人员')}}
                        </div>
                    </EDropdownItem>
                    <EDropdownItem :command="2">
                        <div class="task-menu-icon" >
                            <Icon v-if="addData.visibility_appoint == 2" class="completed" :type="'md-checkmark-circle'"/>
                            <Icon v-else class="uncomplete" :type="'md-radio-button-off'"/>
                            {{$L('任务人员')}}
                        </div>
                    </EDropdownItem>
                    <EDropdownItem :command="3">
                        <div class="task-menu-icon" >
                            <Icon v-if="addData.visibility_appoint == 3" class="completed" :type="'md-checkmark-circle'"/>
                            <Icon v-else class="uncomplete" :type="'md-radio-button-off'"/>
                            {{$L('指定成员')}}
                        </div>
                    </EDropdownItem>
                </EDropdownMenu>
            </EDropdown>
            <div class="subtasks">
                <div v-if="addData.subtasks.length > 0" class="sublist">
                    <Row>
                        <Col span="12">{{$L('任务描述')}}</Col>
                        <Col span="8">{{$L('计划时间')}}</Col>
                        <Col span="4">{{$L('负责人')}}</Col>
                    </Row>
                    <Row v-for="(item, key) in addData.subtasks" :key="key">
                        <Col span="12">
                            <Input
                                v-model="item.name"
                                :maxlength="255"
                                clearable
                                @on-clear="addData.subtasks.splice(key, 1)"/>
                        </Col>
                        <Col span="8" :title="formatDate(item.times)">
                            <DatePicker
                                v-model="item.times"
                                :options="timeOptions"
                                :editable="false"
                                :placeholder="$L('选择时间')"
                                format="yyyy/MM/dd HH:mm"
                                type="datetimerange"
                                @on-change="taskTimeChange(item)"/>
                        </Col>
                        <Col span="4">
                            <UserSelect
                                v-model="item.owner"
                                :multiple-max="1"
                                :title="$L('选择负责人')"
                                :project-id="addData.project_id"
                                :avatar-size="24"
                                border/>
                        </Col>
                    </Row>
                </div>
                <Input
                    type="text"
                    v-model="subName"
                    :class="['enter-input', subName == '' ? 'empty' : '']"
                    @on-enter="addSubTask"
                    :placeholder="$L('+ 输入子任务，回车添加子任务')"/>
            </div>
        </Form>

        <div class="ivu-modal-footer">
            <div class="adaption">
                <Button type="default" @click="close">{{$L('取消')}}</Button>
                <ButtonGroup class="page-manage-add-task-button-group">
                    <Button type="primary" :loading="loadIng > 0" @click="onAdd">{{$L('添加任务')}}</Button>
                    <Dropdown @on-click="onAdd(true)" transfer>
                        <Button type="primary">
                            <Icon type="ios-arrow-down"></Icon>
                        </Button>
                        <DropdownMenu slot="list">
                            <DropdownItem :disabled="loadIng > 0">{{$L('提交继续添加')}}</DropdownItem>
                        </DropdownMenu>
                    </Dropdown>
                </ButtonGroup>
            </div>
        </div>

        <TaskExistTips ref="taskExistTipsRef" @onAdd="onAdd(again,true)"/>
    </div>
</template>

<script>
import {mapState} from "vuex";
import UserSelect from "../../../components/UserSelect.vue";
import TaskExistTips from "./TaskExistTips.vue";
import TEditorTask from "../../../components/TEditorTask.vue";

export default {
    name: "TaskAdd",
    components: {TEditorTask, UserSelect, TaskExistTips},
    props: {
        value: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            addData: {
                cascader: [],
                name: "",
                content: "",
                owner: [],
                assist: [],
                project_id: 0,
                column_id: 0,
                times: [],
                subtasks: [],
                p_level: 0,
                p_name: '',
                p_color: '',
                // 可见性
                visibility_appoint: 1,
                visibility_appointor: [],
            },

            cascaderShow: false,
            cascaderData: [],
            cascaderValue: '',
            cascaderLoading: 0,
            cascaderAlready: [],

            advanced: false,
            subName: '',

            taskTimeOpen: false,

            timeOptions: {shortcuts: $A.timeOptionShortcuts()},

            loadIng: 0,
            isMounted: false,

            beforeClose: [],

            again: false
        }
    },

    async mounted() {
        this.initCascaderData();
        await this.initProjectData();
        this.$nextTick(() => this.$refs.input.focus())
        this.isMounted = true
    },

    beforeDestroy() {
        this.beforeClose.some(func => {
            typeof func === "function" && func()
        })
        this.beforeClose = [];
    },

    computed: {
        ...mapState(['cacheProjects', 'projectId', 'cacheColumns', 'taskPriority']),

        taskDays() {
            const {times} = this.addData;
            let temp = $A.date2string(times, "Y-m-d H:i");
            if (temp[0] && temp[1]) {
                let d = Math.ceil(($A.Date(temp[1], true) - $A.Date(temp[0], true)) / 86400);
                if (d > 0) {
                    return d;
                }
            }
            return 0;
        }
    },

    watch: {
        'addData.owner'(owner) {
            this.addData.assist = this.addData.assist.filter(item => {
                return owner.indexOf(item) === -1;
            })
            if (this.addData.assist.length === 0 && owner.indexOf(this.userId) === -1) {
                this.addData.assist = [this.userId];
            }
        },
        'addData.project_id'(projectId) {
            if (projectId > 0) {
                $A.IDBSave("cacheAddTaskProjectId", projectId);
            }
        },
        'addData.column_id'(columnId) {
            if (columnId > 0) {
                $A.IDBSave("cacheAddTaskColumnId", columnId);
            }
            const {project_id} = this.addData;
            if (project_id && columnId) {
                this.$set(this.addData, 'cascader', [project_id, columnId]);
            } else {
                this.$set(this.addData, 'cascader', []);
            }
        }
    },

    methods: {
        /**
         * 初始化级联数据
         */
        initCascaderData() {
            const data = $A.cloneJSON(this.cacheProjects).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.Date(b.top_at) - $A.Date(a.top_at);
                }
                return b.id - a.id;
            });
            this.cascaderData = data.map(project => {
                const children = this.cacheColumns.filter(({project_id}) => project_id == project.id).map(column => {
                    return {
                        value: column.id,
                        label: column.name
                    }
                });
                const data = {
                    value: project.id,
                    label: project.name,
                    children,
                };
                if (children.length == 0) {
                    data.loading = false;
                }
                return data
            });
        },

        /**
         * 初始化项目、列表、优先级
         */
        async initProjectData() {
            // 项目、列表
            let cacheAddTaskProjectId = await $A.IDBInt("cacheAddTaskProjectId");
            let project = this.cacheProjects.find(({id}) => id == this.projectId)
                || this.cacheProjects.find(({id}) => id == cacheAddTaskProjectId)
                || this.cacheProjects.find(({id}) => id > 0);
            if (project) {
                let cacheAddTaskColumnId = await $A.IDBInt("cacheAddTaskColumnId");
                let column = this.cacheColumns.find(({project_id, id}) => project_id == project.id && id == cacheAddTaskColumnId)
                    || this.cacheColumns.find(({project_id}) => project_id == project.id);
                if (column) {
                    this.addData.project_id = column.project_id;
                    this.addData.column_id = column.id;
                } else {
                    this.$store.dispatch("getColumns", project.id).then(() => {
                        column = this.cacheColumns.find(({project_id, id}) => project_id == project.id && id == cacheAddTaskColumnId)
                            || this.cacheColumns.find(({project_id}) => project_id == project.id);
                        if (column) {
                            this.addData.project_id = column.project_id;
                            this.addData.column_id = column.id;
                        }
                    }).catch(() => {});
                }
            }
            // 优先级
            if (this.taskPriority.length > 0) {
                await this.choosePriority(this.taskPriority[0]);
            }
        },

        async taskTimeChange(data) {
            const times = $A.date2string(data.times, "Y-m-d H:i");
            if ($A.rightExists(times[0], '00:00') && $A.rightExists(times[1], '00:00')) {
                this.$set(data, 'times', await this.$store.dispatch("taskDefaultTime", times))
            }
        },

        taskTimeOpenChange(val) {
            this.taskTimeOpen = val;
        },

        formatDate(value) {
            return value ? $A.date2string(value) : null
        },

        onKeydown(e) {
            if (e.keyCode === 13) {
                if (e.shiftKey) {
                    return;
                }
                e.preventDefault();
                this.onAdd();
            }
        },

        addSubTask() {
            if (this.subName.trim() !== '') {
                this.addData.subtasks.push({
                    name: this.subName.trim(),
                    owner: [this.userId],
                    times: [],
                });
                this.subName = '';
            }
        },

        taskPriorityContent(item) {
            const days = $A.runNum(item.days);
            if (days <= 0) {
                return item.name + ' (' + this.$L('无时间限制') + ')';
            }
            return item.name + ' (' + days + this.$L('天') + ')';
        },

        async choosePriority(item) {
            const start = new Date();
            const days = $A.runNum(item.days);
            if (days > 0) {
                const end = new Date(new Date().setDate(start.getDate() + days));
                this.$set(this.addData, 'times', await this.$store.dispatch("taskDefaultTime", $A.date2string([start, end])))
            } else {
                this.$set(this.addData, 'times', [])
            }
            this.$set(this.addData, 'p_level', item.priority)
            this.$set(this.addData, 'p_name', item.name)
            this.$set(this.addData, 'p_color', item.color)
        },

        cascaderLoadData(item, callback) {
            item.loading = true;
            this.$store.dispatch("getColumns", item.value).then((data) => {
                item.children = data.map(column => {
                    return {
                        value: column.id,
                        label: column.name
                    }
                });
                item.loading = false;
                callback();
            }).catch(() => {
                item.loading = false;
                callback();
            });
        },

        cascaderChange(value) {
            if (value[1]) {
                this.$set(this.addData, 'project_id', value[0])
                this.$set(this.addData, 'column_id', value[1])
            }
        },

        cascaderInputChange(key) {
            this.cascaderValue = key || "";
            //
            if (this.cascaderAlready[this.cascaderValue] === true) {
                return;
            }
            this.cascaderAlready[this.cascaderValue] = true;
            //
            setTimeout(() => {
                this.cascaderLoading++;
            }, 1000)
            this.$store.dispatch("getProjects", {
                keys: {
                    name: this.cascaderValue,
                },
                getcolumn: 'yes'
            }).then(() => {
                this.cascaderLoading--;
                this.initCascaderData();
            }).catch(() => {
                this.cascaderLoading--;
            });
        },

        setData(data) {
            if (!this.isMounted) {
                this.__setData && clearTimeout(this.__setData)
                this.__setData = setTimeout(_ => this.setData(data) , 10)
                return
            }
            if (typeof data.beforeClose !== "undefined") {
                this.beforeClose.push(data.beforeClose)
                delete data.beforeClose;
            }
            this.addData = Object.assign({}, this.addData, data);
        },

        async onAdd(again,affirm=false) {
            if (!this.addData.name) {
                $A.messageError("任务描述不能为空");
                return;
            }

            this.loadIng++;

            // 存在任务提示
            if (!affirm && this.addData.owner.length > 0) {
                this.$refs['taskExistTipsRef'].isExistTask({
                    userids: this.addData.owner,
                    timerange: this.addData.times
                }).then(res => {
                    if (!res) {
                        this.onAdd(again, true)
                    } else {
                        this.loadIng--;
                        this.again = again
                    }
                });
                return;
            }

            this.$store.dispatch("taskAdd", this.addData).then(({msg}) => {
                this.loadIng--;
                $A.messageSuccess(msg);
                if (again === true) {
                    this.addData = Object.assign({}, this.addData, {
                        name: "",
                        content: "",
                        subtasks: [],
                    });
                    this.$refs.input.focus();
                } else {
                    this.addData = {
                        cascader: [],
                        name: "",
                        content: "",
                        owner: [],
                        assist: [],
                        column_id: 0,
                        times: [],
                        subtasks: [],
                        p_level: 0,
                        p_name: '',
                        p_color: '',
                    };
                    this.close()
                }
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError(msg);
            });
        },

        close() {
            this.$emit("input", !this.value)
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
            const boxRect = this.$el.getBoundingClientRect()
            const refEl = this.$refs.eDropdownRef.$el
            refEl.style.top = (eRect.top - boxRect.top) + 'px'
            refEl.style.left = (eRect.left - boxRect.left) + 'px'
            refEl.style.width = eRect.width + 'px'
            refEl.style.height = eRect.height + 'px'
            //
            if (this.$refs.eDropdownRef.visible) {
                this.$refs.eDropdownRef.hide()
            }
            setTimeout(() => {
                this.$refs.eDropdownRef.show()
            }, 0)
        },

        visibleUserSelectShowChange(isShow){
            if(!isShow && (this.addData.visibility_appointor.length == 0 || !this.addData.visibility_appointor[0])){
                let old = this.addData.old_visibility_appoint;
                this.addData.visibility_appoint = old > 2 ? 1 : (old || 1);
                if(this.addData.visibility_appoint < 3 ){
                }
            }
        },

        dropVisible(command) {
            switch (command) {
                case 1:
                case 2:
                    this.addData.visibility_appoint = command
                    break;
                case 3:
                    this.addData.old_visibility_appoint = this.addData.visibility_appoint
                    this.addData.visibility_appoint = command
                    this.$nextTick(() => {
                        this.$refs.visibleUserSelectRef.onSelection()
                    });
                    break;
            }
        },
    }
}
</script>
