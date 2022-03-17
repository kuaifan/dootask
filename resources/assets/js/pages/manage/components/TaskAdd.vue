<template>
    <div v-if="ready" class="task-add">
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
                    @on-keydown="onKeydown"/>
            </div>
            <div class="desc">
                <TEditor
                    v-model="addData.content"
                    :plugins="taskPlugins"
                    :options="taskOptions"
                    :option-full="taskOptionFull"
                    :placeholder="$L(isDesktop ? '详细描述，选填...（点击右键使用工具栏）' : '详细描述，选填...')"
                    :placeholderFull="$L('详细描述...')"
                    inline/>
            </div>
            <div class="advanced-option">
                <Button :class="{advanced: advanced}" @click="advanced=!advanced">{{$L('高级选项')}}</Button>
                <ul class="advanced-priority">
                    <li v-for="(item, key) in taskPriority" :key="key">
                        <ETooltip :content="taskPriorityContent(item)">
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
                    @on-change="taskTimeChange(addData.times)"
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
                    @on-change="taskTimeChange(addData.times)"/>
            </FormItem>
            <FormItem :label="$L('任务负责人')">
                <UserInput
                    v-model="addData.owner"
                    :multiple-max="10"
                    :placeholder="$L('选择任务负责人')"
                    :project-id="addData.project_id"
                    :transfer="false"/>
                <div v-if="showAddAssist" class="task-add-assist">
                    <Checkbox v-model="addData.add_assist" :true-value="1" :false-value="0">{{$L('加入任务协助人员列表')}}</Checkbox>
                    <ETooltip :content="$L('你不是任务负责人时建议加入任务协助人员列表')">
                        <Icon type="ios-alert-outline" />
                    </ETooltip>
                </div>
            </FormItem>
            <div class="subtasks">
                <div v-if="addData.subtasks.length > 0" class="sublist">
                    <Row>
                        <Col span="12">{{$L('任务描述')}}</Col>
                        <Col span="6">{{$L('计划时间')}}</Col>
                        <Col span="6">{{$L('负责人')}}</Col>
                    </Row>
                    <Row v-for="(item, key) in addData.subtasks" :key="key">
                        <Col span="12">
                            <Input
                                v-model="item.name"
                                :maxlength="255"
                                clearable
                                @on-clear="addData.subtasks.splice(key, 1)"/>
                        </Col>
                        <Col span="6">
                            <DatePicker
                                v-model="item.times"
                                :options="timeOptions"
                                :editable="false"
                                :placeholder="$L('选择时间')"
                                format="yyyy/MM/dd HH:mm"
                                type="datetimerange"
                                @on-change="taskTimeChange(item.times)"/>
                        </Col>
                        <Col span="6">
                            <UserInput
                                v-model="item.owner"
                                :multiple-max="1"
                                :placeholder="$L('选择负责人')"
                                :project-id="addData.project_id"
                                :transfer="false"/>
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
                    <Dropdown @on-click="onAdd(true)">
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
    </div>
</template>

<script>
import TEditor from "../../../components/TEditor";
import {mapState} from "vuex";
import UserInput from "../../../components/UserInput";

export default {
    name: "TaskAdd",
    components: {UserInput, TEditor},
    props: {
        value: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            ready: false,

            addData: {
                cascader: [],
                name: "",
                content: "",
                owner: [],
                add_assist: 1,
                project_id: 0,
                column_id: 0,
                times: [],
                subtasks: [],
                p_level: 0,
                p_name: '',
                p_color: '',
            },

            cascaderShow: false,
            cascaderData: [],
            cascaderValue: '',
            cascaderLoading: 0,
            cascaderAlready: [],

            advanced: false,
            subName: '',

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

            taskTimeOpen: false,

            timeOptions: {shortcuts:$A.timeOptionShortcuts()},

            loadIng: 0,

            beforeClose: [],
        }
    },
    mounted() {

    },
    computed: {
        ...mapState(['isDesktop', 'userId', 'cacheProjects', 'projectId', 'cacheColumns', 'taskPriority']),

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
        },

        showAddAssist() {
            return !this.addData.owner.includes(this.userId);
        }
    },
    watch: {
        value(val) {
            if (val) {
                this.ready = true;
                this.initCascaderData();
                this.initProjectData();
                this.$nextTick(() => {
                    this.$refs.input.focus()
                })
            } else {
                this.beforeClose.some(func => {
                    typeof func === "function" && func()
                })
                this.beforeClose = [];
                this.taskTimeOpen = false;
            }
        },
        'addData.project_id'(id) {
            if (id > 0) {
                $A.setStorage("cacheAddTaskProjectId", id);
            }
        },
        'addData.column_id'(id) {
            const {project_id} = this.addData;
            this.$nextTick(() => {
                if (project_id && id) {
                    this.$set(this.addData, 'cascader', [project_id, id]);
                } else {
                    this.$set(this.addData, 'cascader', []);
                }
            })
            if (id > 0) {
                $A.setStorage("cacheAddTaskColumnId", id);
            }
        }
    },
    methods: {
        initLanguage() {

        },

        initCascaderData() {
            this.cascaderData = this.cacheProjects.map(project => {
                const children = this.cacheColumns.filter(({project_id}) => project_id == project.id).map(column => {
                    return {
                        value: column.id,
                        label: column.name
                    }
                });
                let data = {
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

        initProjectData() {
            let column_id = this.addData.column_id;
            if (column_id) {
                let column = this.cacheColumns.find(({id}) => id == column_id);
                if (column) {
                    this.addData.project_id = column.project_id;
                    this.addData.column_id = column.id;
                }
            } else {
                let cacheAddTaskProjectId = $A.getStorageInt("cacheAddTaskProjectId");
                let cacheAddTaskColumnId = $A.getStorageInt("cacheAddTaskColumnId");
                let project = this.cacheProjects.find(({id}) => id == this.projectId)
                    || this.cacheProjects.find(({id}) => id == cacheAddTaskProjectId)
                    || this.cacheProjects.find(({id}) => id > 0);
                if (project) {
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
            }
        },

        taskTimeOpenChange(val) {
            this.taskTimeOpen = val;
        },

        taskTimeChange(times) {
            let tempc = $A.date2string(times, "Y-m-d H:i");
            if (tempc[0] && tempc[1]) {
                if ($A.rightExists(tempc[0], '00:00') && $A.rightExists(tempc[1], '00:00')) {
                    this.$set(this.addData, 'times', [
                        tempc[0],
                        tempc[1].replace("00:00", "23:59")
                    ])
                }
            }
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
            let days = $A.runNum(item.days);
            if (days <= 0) {
                return item.name + ' (' + this.$L('无时间限制') + ')';
            }
            return item.name + ' (' + days + this.$L('天') + ')';
        },

        choosePriority(item) {
            let start = new Date();
            let days = $A.runNum(item.days);
            if (days > 0) {
                let end = new Date(new Date().setDate(start.getDate() + days));
                this.$set(this.addData, 'times', $A.date2string([start, end]))
            } else {
                this.$set(this.addData, 'times', [])
            }
            this.$set(this.addData, 'p_level', item.priority)
            this.$set(this.addData, 'p_name', item.name)
            this.$set(this.addData, 'p_color', item.color)
        },

        defaultPriority() {
            if (this.taskPriority.length === 0) {
                return;
            }
            if (this.addData.p_name) {
                return;
            }
            this.choosePriority(this.taskPriority[0]);
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
            value[1] && this.$set(this.addData, 'column_id', value[1])
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
            if (typeof data.beforeClose !== "undefined") {
                this.beforeClose.push(data.beforeClose)
                delete data.beforeClose;
            }
            this.addData = Object.assign({}, this.addData, data);
        },

        onAdd(again) {
            if (!this.addData.name) {
                $A.messageError("任务描述不能为空");
                return;
            }
            this.loadIng++;
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
                        add_assist: 1,
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
    }
}
</script>
