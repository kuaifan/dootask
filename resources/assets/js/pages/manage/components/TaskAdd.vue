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
        <ul v-if="taskTemplateList.length > 0" class="task-add-template">
            <li
                v-for="item in taskTemplateList"
                :key="item.id"
                :class="{active:templateActiveID === item.id}"
                @click="setTaskTemplate(item)">
                {{ item.name }}
            </li>
        </ul>
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
                <div class="ai-btn" @click="onAI">
                    <i class="taskfont">&#xe8a1;</i>
                </div>
            </div>
            <TEditorTask
                ref="editorTaskRef"
                class="desc"
                v-model="addData.content"
                :placeholder="$L(windowLandscape ? '详细描述，选填...（点击右键使用工具栏）' : '详细描述，选填...')"
                :placeholderFull="$L('详细描述...')"/>
            <div class="advanced-option" :class="{'advanced-open': advanced}">
                <Button @click="advanced=!advanced">{{$L('高级选项')}}</Button>
                <ul class="advanced-priority">
                    <li v-for="(item, key) in taskPriority" :key="key">
                        <ETooltip :disabled="$isEEUIApp || windowTouch" :content="taskPriorityContent(item)">
                            <i
                                class="taskfont"
                                :style="{color:item.color}"
                                v-html="addData.p_name == item.name ? '&#xe61d;' : '&#xe61c;'"
                                @click="choosePriority(item)"></i>
                        </ETooltip>
                    </li>
                </ul>
                <DatePicker
                    v-if="taskDays > 0 || taskTimeOpen"
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

        <Form v-if="advanced" class="task-add-advanced" v-bind="formOptions" @submit.native.prevent>
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
                        <Col span="8" :title="timeTitle(item.times)">
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
                    <Button type="primary" :loading="loadIng > 0" @click="onAdd(false)">{{$L('添加任务')}}</Button>
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

        <TaskExistTips ref="taskExistTipsRef" @onContinue="onAdd(addContinue, true)"/>
    </div>
</template>

<script>
import {mapState} from "vuex";
import emitter from "../../../store/events";
import UserSelect from "../../../components/UserSelect.vue";
import TaskExistTips from "./TaskExistTips.vue";
import TEditorTask from "../../../components/TEditorTask.vue";
import nostyle from "../../../components/VMEditor/engine/nostyle";
import {MarkdownConver} from "../../../utils/markdown";
import {extractPlainText} from "../../../utils/text";
import {AINormalizeJsonContent, TASK_AI_SYSTEM_PROMPT, withLanguagePreferencePrompt} from "../../../utils/ai";

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
                // 基本信息
                cascader: [],
                name: "",
                content: "",
                owner: [],
                assist: [],
                project_id: 0,
                column_id: 0,
                times: [],
                subtasks: [],
                // 优先级
                p_level: 0,
                p_name: '',
                p_color: '',
                // 可见性
                visibility_appoint: 1,
                visibility_appointor: [],
            },
            addDefault: {},

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

            addContinue: false,

            templateActiveID: 0,
            templateCompareData: {name: '', content: ''},
        }
    },

    created() {
        this.addDefault = $A.cloneJSON(this.addData);
    },

    async mounted() {
        this.initCascaderData();
        await this.initProjectData();
        this.$nextTick(() => {
            this.$refs.input.focus();
            this.templateCompareData = {name: this.addData.name, content: this.addData.content};
        })
        this.isMounted = true
    },

    beforeDestroy() {
        this.beforeClose.some(func => {
            typeof func === "function" && func()
        })
        this.beforeClose = [];
    },

    computed: {
        ...mapState(['cacheProjects', 'projectId', 'cacheColumns', 'taskPriority', 'taskTemplates', 'formOptions']),

        taskDays() {
            const {times} = this.addData;
            const temp = $A.newDateString(times, "YYYY-MM-DD HH:mm");
            if (temp[0] && temp[1]) {
                const d = Math.ceil($A.dayjs(temp[1]).diff(temp[0], 'day', true));
                if (d > 0) {
                    return d;
                }
            }
            return 0;
        },

        taskTemplateList() {
            return this.taskTemplates.filter(({project_id}) => project_id == this.addData.project_id) || []
        }
    },

    watch: {
        'addData.owner'(owner, newOwner) {
            if (JSON.stringify(owner) === JSON.stringify(newOwner)) {
                return;
            }
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
                this.$store.dispatch("updateTaskTemplates", projectId).then(this.setTaskDefaultTemplate)
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
                    return $A.sortDay(b.top_at, a.top_at);
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
                const defaultItem = this.taskPriority.find(item => item.is_default === 1) || this.taskPriority[0];
                await this.choosePriority(defaultItem);
            }
        },

        async taskTimeChange(data) {
            const times = $A.newDateString(data.times, "YYYY-MM-DD HH:mm");
            this.$set(data, 'times', await this.$store.dispatch("taskDefaultTime", times))
        },

        taskTimeOpenChange(val) {
            this.taskTimeOpen = val;
        },

        timeTitle(value) {
            return value ? $A.newDateString(value) : null
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
            const start = $A.daytz();
            const days = $A.runNum(item.days);
            if (days > 0) {
                const end = start.clone().add(days, 'day');
                this.$set(this.addData, 'times', await this.$store.dispatch("taskDefaultTime", $A.newDateString([start, end], 'YYYY-MM-DD 00:00')))
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

        async onAdd(continued = false, affirm = false) {
            if (!this.addData.name) {
                $A.messageError("任务描述不能为空");
                return;
            }

            // 存在任务提示
            if (!affirm && this.addData.owner.length > 0) {
                this.loadIng++;
                this.$refs.taskExistTipsRef.isExistTask({
                    userids: this.addData.owner,
                    timerange: this.addData.times
                }, 600).then(res => {
                    if (!res) {
                        this.onAdd(continued, true)
                    } else {
                        this.addContinue = continued
                    }
                    this.loadIng--;
                });
                return;
            }

            this.loadIng++;
            this.$store.dispatch("taskAdd", this.addData).then(({msg}) => {
                $A.messageSuccess(msg);
                if (continued === true) {
                    this.addData = Object.assign({}, this.addData, this.templateCompareData, {subtasks: []});
                    this.$refs.input.focus();
                } else {
                    this.addData = $A.cloneJSON(this.addDefault);
                    this.close()
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(() => {
                this.loadIng--;
            });
        },

        close() {
            this.$emit("input", !this.value)
        },

        showCisibleDropdown(event){
            const list = [
                {label: '项目人员', value: 1},
                {label: '任务人员', value: 2},
                {label: '指定成员', value: 3},
            ];
            this.$store.commit('menu/operation', {
                event,
                list,
                size: 'large',
                active: this.addData.visibility_appoint,
                onUpdate: (value) => {
                    this.dropVisible(value)
                }
            })
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

        setTaskTemplate(item, force = false) {
            if (force) {
                this.templateActiveID = item.id;
                this.addData.name = item.title;
                this.addData.content = nostyle(item.content, {sanitize: false});
                this.$nextTick(() => {
                    this.$refs.input.focus();
                    this.templateCompareData = {name: this.addData.name, content: this.addData.content};
                });
                return;
            }
            if ((this.addData.name !== this.templateCompareData.name && this.addData.name !== '') ||
                (this.addData.content !== this.templateCompareData.content && this.addData.content !== '')) {
                $A.modalConfirm({
                    content: '当前已有修改的内容，是否要覆盖？',
                    onOk: () => this.setTaskTemplate(item, true)
                });
            } else {
                this.setTaskTemplate(item, true);
            }
        },

        setTaskDefaultTemplate() {
            const defaultTemplate = this.taskTemplateList.find(({is_default}) => is_default);
            if (defaultTemplate) {
                this.setTaskTemplate(defaultTemplate);
            }
        },

        onAI() {
            emitter.emit('openAIAssistant', {
                sessionKey: 'task-create',
                title: this.$L('AI 任务助手'),
                placeholder: this.$L('请简要描述任务目标、背景或预期交付，AI 将生成标题、详细说明和子任务'),
                onBeforeSend: this.handleTaskAIBeforeSend,
                onRender: this.handleTaskAIRender,
                onApply: this.handleTaskAIApply,
            });
        },

        buildTaskAIContextData() {
            const prompts = [];
            const currentTitle = (this.addData.name || '').trim();
            const currentContent = extractPlainText(this.addData.content, 2000, true);
            if (currentTitle || currentContent) {
                prompts.push('## 当前任务信息');
                if (currentTitle) {
                    prompts.push(`当前标题：${currentTitle}`);
                }
                if (currentContent) {
                    prompts.push(`当前内容：${currentContent}`);
                }
                prompts.push('请在此基础上优化改进，而不是完全重写。');
            }

            const currentTemplate = this.templateActiveID
                ? this.taskTemplateList.find(item => item.id === this.templateActiveID)
                : null;
            if (currentTemplate) {
                const templateName = (currentTemplate.name || currentTemplate.title || '').trim();
                const templateContent = extractPlainText(nostyle(currentTemplate.content, {sanitize: false}), 1200, true);
                prompts.push('## 任务模板要求');
                if (templateName) {
                    prompts.push(`模板名称：${templateName}`);
                }
                if (templateContent) {
                    prompts.push(`模板内容结构：${templateContent}`);
                }
                prompts.push('请严格按照此模板的结构和格式要求生成内容。');
            }

            const statusInfo = [];
            if (Array.isArray(this.addData.owner) && this.addData.owner.length > 0) {
                statusInfo.push('已设置负责人');
            }
            if (Array.isArray(this.addData.times) && this.addData.times.length > 0) {
                statusInfo.push('已设置计划时间');
            }
            const priorityName = (this.addData.p_name || '').trim();
            if (priorityName) {
                statusInfo.push(`优先级：${priorityName}`);
            }
            if (statusInfo.length > 0) {
                prompts.push('## 任务状态');
                prompts.push(statusInfo.join('，'));
                prompts.push('请在任务描述中体现相应的要求和约束。');
            }

            const projectInfo = this.cacheProjects.find(({id}) => id == this.addData.project_id);
            const columnInfo = this.cacheColumns.find(({id}) => id == this.addData.column_id);
            if ((projectInfo && projectInfo.name) || (columnInfo && columnInfo.name)) {
                prompts.push('## 所属项目');
                if (projectInfo && projectInfo.name) {
                    prompts.push(`项目：${projectInfo.name}`);
                }
                if (columnInfo && columnInfo.name) {
                    prompts.push(`任务列表：${columnInfo.name}`);
                }
            }

            const subtasks = (this.addData.subtasks || [])
                .map(item => (item && item.name ? item.name.trim() : ''))
                .filter(Boolean)
                .slice(0, 8);
            if (subtasks.length > 0) {
                prompts.push('## 当前子任务');
                subtasks.forEach((name, index) => {
                    prompts.push(`${index + 1}. ${name}`);
                });
            }

            return prompts.join('\n').trim();
        },

        handleTaskAIBeforeSend(context = []) {
            const prepared = [
                ['system', withLanguagePreferencePrompt(TASK_AI_SYSTEM_PROMPT)]
            ];
            const contextPrompt = this.buildTaskAIContextData();
            if (contextPrompt) {
                let assistantContext = [
                    '以下是已有的上下文信息，可辅助你理解：',
                    contextPrompt,
                ].join('\n');
                if ($A.getObject(context, [0,0]) === 'human') {
                    assistantContext += "\n----\n请根据以上信息，结合以下用户输入的内容生成项目任务：++++";
                }
                prepared.push(['human', assistantContext]);
            }
            if (context.length > 0) {
                prepared.push(...context);
            }
            return prepared;
        },

        handleTaskAIApply({rawOutput}) {
            if (!rawOutput) {
                $A.messageWarning('AI 未生成内容');
                return;
            }
            const parsed = this.parseTaskAIContent(rawOutput);
            if (!parsed) {
                $A.modalError('AI 内容解析失败，请重试');
                return;
            }
            if (parsed.title) {
                this.addData.name = parsed.title;
                this.$nextTick(() => {
                    this.$refs.input && this.$refs.input.focus();
                });
            }
            if (parsed.description && this.$refs.editorTaskRef) {
                const html = MarkdownConver(parsed.description);
                this.$refs.editorTaskRef.setContent(html, {format: 'raw'});
            }
            if (parsed.subtasks.length > 0) {
                const mainOwner = Array.isArray(this.addData.owner) && this.addData.owner.length > 0
                    ? [this.addData.owner[0]]
                    : (this.userId ? [this.userId] : []);
                const subtasks = parsed.subtasks.map(name => ({
                    name,
                    owner: [...mainOwner],
                    times: [],
                }));
                this.$set(this.addData, 'subtasks', subtasks);
                this.advanced = true;
            }
        },

        parseTaskAIContent(content) {
            const payload = AINormalizeJsonContent(content);
            if (!payload || typeof payload !== 'object') {
                return null;
            }
            const title = this.pickFirstString([payload.title, payload.name, payload.task_title]);
            const description = this.pickFirstString([
                payload.description_markdown,
                payload.description,
                payload.content_markdown,
                payload.content,
                payload.body,
                payload.detail,
            ]);
            const subtasks = this.normalizeAISubtasks(payload.subtasks || payload.tasks || payload.checklist || payload.steps);
            if (!title && !description && subtasks.length === 0) {
                return null;
            }
            return {
                title,
                description,
                subtasks,
            };
        },

        normalizeAISubtasks(value) {
            let raw = [];
            if (Array.isArray(value)) {
                raw = value.map(item => {
                    if (typeof item === 'string') {
                        return item;
                    }
                    if (item && typeof item === 'object') {
                        return item.title || item.name || item.task || item.content || '';
                    }
                    return '';
                });
            } else if (typeof value === 'string') {
                raw = value.split(/[\n\r;；]+/);
            }
            const cleaned = raw
                .map(item => String(item || '').replace(/^[\d\.-]*\s*/, '').replace(/^[•*\-]\s*/, '').trim())
                .filter(Boolean);
            return Array.from(new Set(cleaned)).slice(0, 8);
        },

        pickFirstString(list = []) {
            for (const item of list) {
                if (typeof item === 'string' && item.trim()) {
                    return item.trim();
                }
            }
            return '';
        },

        handleTaskAIRender({rawOutput}) {
            if (!rawOutput) {
                return '';
            }
            const parsed = this.parseTaskAIContent(rawOutput);
            if (!parsed) {
                return rawOutput;
            }
            const blocks = [];
            if (parsed.title) {
                blocks.push(`## ${parsed.title}`);
            }
            if (parsed.description) {
                blocks.push(parsed.description);
            }
            if (parsed.subtasks.length > 0) {
                const list = parsed.subtasks.map((name, index) => `${index + 1}. ${name}`);
                blocks.push(list.join('\n'));
            }
            return blocks.join('\n\n').trim() || rawOutput;
        }
    }
}
</script>
