<template>
    <div :class="['task-add-simple', active ? 'active' : '']" @mouseenter="mouseEnter=true" @mouseleave="mouseEnter=false">
        <Input
            v-model="addData.name"
            ref="input"
            type="textarea"
            :rows="2"
            :autosize="{ minRows: 2, maxRows: 3 }"
            :maxlength="255"
            :placeholder="$L('任务描述，回车创建')"
            @on-focus="onFocus=true"
            @on-blur="onFocus=false"
            @on-keydown="onKeydown"></Input>
        <div class="add-placeholder" @click="openAdd">
            <Icon type="md-add" />{{$L('添加任务')}}
        </div>
        <div class="priority">
            <ul>
                <li v-for="(item, key) in taskPriority" :key="key">
                    <ETooltip v-if="active" :content="item.name + ' (' + item.days + $L('天') + ')'">
                        <i
                            class="iconfont"
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
import {mapState} from "vuex";

export default {
    name: "TaskAddSimple",
    props: {
        projectId: {
            default: ''
        },
        columnId: {
            default: ''
        },
        addTop: {
            type: Boolean,
            default: false
        },
        autoActive: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            addData: {
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
            this.addData.project_id = this.projectId;
            this.addData.column_id = this.columnId;
            this.addData.owner = [this.userId];
            this.addData.top = this.addTop ? 1 : 0;
            return $A.cloneJSON(this.addData);
        },

        openAdd() {
            this.active = true;
            this.$nextTick(() => {
                if (this.taskPriority.length === 0) {
                    this.$store.dispatch('taskPriority').then(() => {
                        if (!this.addData.p_name && this.taskPriority.length > 0) {
                            this.choosePriority(this.taskPriority[0])
                        }
                    });
                } else {
                    if (!this.addData.p_name && this.taskPriority.length > 0) {
                        this.choosePriority(this.taskPriority[0])
                    }
                }
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
            this.$store.dispatch("taskAdd", this.getData()).then(({msg}) => {
                this.loadIng--;
                this.active = false;
                this.addData = {
                    owner: 0,
                    column_id: 0,
                    times: [],
                    subtasks: [],
                    p_level: 0,
                    p_name: '',
                    p_color: '',
                }
                $A.messageSuccess(msg);
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError(msg);
            });
        },

        choosePriority(item) {
            let start = new Date();
            let end = new Date(new Date().setDate(start.getDate() + $A.runNum(item.days)));
            this.$set(this.addData, 'times', $A.date2string([start, end]))
            this.$set(this.addData, 'p_level', item.priority)
            this.$set(this.addData, 'p_name', item.name)
            this.$set(this.addData, 'p_color', item.color)
            this.$refs.input.focus()
        }
    }
}
</script>
