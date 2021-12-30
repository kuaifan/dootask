<template>
    <Row v-if="rowMode" class="task-add-row">
        <Col span="12" :class="['row-add', active ? 'active' : '']">
            <div class="add-input" @mouseenter="mouseEnter=true" @mouseleave="mouseEnter=false">
                <Input
                    v-model="addData.name"
                    ref="input"
                    type="textarea"
                    :rows="1"
                    :autosize="{ minRows: 1, maxRows: 3 }"
                    :maxlength="255"
                    :placeholder="$L(typeName + '描述，回车创建')"
                    @on-focus="onFocus=true"
                    @on-blur="onFocus=false"
                    @on-keydown="onKeydown"/>
                <div v-if="parentId == 0" class="priority">
                    <ul>
                        <li v-for="(item, key) in taskPriority" :key="key">
                            <ETooltip v-if="active" :content="taskPriorityContent(item)">
                                <i
                                    class="taskfont"
                                    :style="{color:item.color}"
                                    v-html="addData.p_name == item.name ? '&#xe61d;' : '&#xe61c;'"
                                    @click="choosePriority(item)"></i>
                            </ETooltip>
                        </li>
                    </ul>
                    <Icon type="md-settings" @click="onPriority"/>
                </div>
            </div>
            <div class="add-btn" @click="openAdd">
                <Icon class="add-icon" type="md-add" />{{$L('添加' + typeName)}}
            </div>
        </Col>
        <Col span="3"></Col>
        <Col span="3"></Col>
        <Col span="3"></Col>
        <Col span="3"></Col>
    </Row>
    <div v-else :class="['task-add-simple', active ? 'active' : '']" @mouseenter="mouseEnter=true" @mouseleave="mouseEnter=false">
        <Input
            v-model="addData.name"
            ref="input"
            type="textarea"
            :rows="2"
            :autosize="{ minRows: 2, maxRows: 3 }"
            :maxlength="255"
            :placeholder="$L(typeName + '描述，回车创建')"
            @on-focus="onFocus=true"
            @on-blur="onFocus=false"
            @on-keydown="onKeydown"/>
        <div class="add-placeholder" @click="openAdd">
            <Icon type="md-add" />{{$L('添加' + typeName)}}
        </div>
        <div class="priority">
            <ul>
                <li v-for="(item, key) in taskPriority" :key="key">
                    <ETooltip v-if="active" :content="taskPriorityContent(item)">
                        <i
                            class="taskfont"
                            :style="{color:item.color}"
                            v-html="addData.p_name == item.name ? '&#xe61d;' : '&#xe61c;'"
                            @click="choosePriority(item)"></i>
                    </ETooltip>
                </li>
            </ul>
            <Icon type="md-settings" @click="onPriority"/>
        </div>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";

export default {
    name: "TaskAddSimple",
    props: {
        parentId: {
            type: Number,
            default: 0
        },
        projectId: {
            type: Number,
            default: 0
        },
        columnId: {
            type: Number,
            default: 0
        },
        addTop: {
            type: Boolean,
            default: false
        },
        autoActive: {
            type: Boolean,
            default: false
        },
        rowMode: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            addData: {
                name: "",
                owner: 0,
                column_id: 0,
                times: [],
                subtasks: [],
                p_level: 0,
                p_name: '',
                p_color: '',
            },
            active: false,

            onFocus: false,
            mouseEnter: false,
        }
    },

    mounted() {
        if (this.autoActive) {
            this.$nextTick(this.openAdd)
        }
    },

    computed: {
        ...mapState(['userId', 'taskPriority']),

        typeName() {
            return (this.parentId > 0 ? '子任务' : '任务');
        }
    },

    watch: {
        active(val) {
            if (!val) {
                this.$emit("on-close")
            }
        },
        mouseEnter() {
            this.chackClose()
        },
        onFocus() {
            this.chackClose()
        }
    },

    methods: {
        getData() {
            if (this.parentId > 0) {
                return {
                    task_id: this.parentId,
                    name: this.addData.name,
                }
            } else {
                this.addData.project_id = this.projectId || this.$store.state.projectId;
                this.addData.column_id = this.columnId || '';
                this.addData.owner = [this.userId];
                this.addData.top = this.addTop ? 1 : 0;
                return $A.cloneJSON(this.addData);
            }
        },

        openAdd() {
            this.active = true;
            this.defaultPriority();
            this.$nextTick(() => {
                this.$refs.input.focus();
            });
        },

        chackClose() {
            if (this.mouseEnter || this.onFocus) {
                return;
            }
            if (!this.addData.name) {
                this.active = false;
            }
        },

        onPriority() {
            this.$emit("on-priority", this.getData())
            this.active = false;
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

        onAdd() {
            if (!this.addData.name) {
                $A.messageWarning("请输入任务描述");
                return;
            }
            this.loadIng++;
            let type = this.parentId > 0 ? 'taskAddSub' : 'taskAdd';
            this.$store.dispatch(type, this.getData()).then(({msg}) => {
                $A.messageSuccess(msg);
                this.loadIng--;
                this.active = false;
                this.addData = {
                    name: "",
                    owner: 0,
                    column_id: 0,
                    times: [],
                    subtasks: [],
                    p_level: 0,
                    p_name: '',
                    p_color: '',
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.loadIng--;
            });
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
            let end = new Date(new Date().setDate(start.getDate() + $A.runNum(item.days)));
            this.$set(this.addData, 'times', $A.date2string([start, end]))
            this.$set(this.addData, 'p_level', item.priority)
            this.$set(this.addData, 'p_name', item.name)
            this.$set(this.addData, 'p_color', item.color)
            this.$nextTick(() => {
                this.$refs.input.focus();
            });
        },

        defaultPriority() {
            if (this.taskPriority.length === 0) {
                return;
            }
            if (this.addData.p_name) {
                return;
            }
            this.choosePriority(this.taskPriority[0]);
        }
    }
}
</script>
