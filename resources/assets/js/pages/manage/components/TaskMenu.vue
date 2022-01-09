<template>
    <EDropdown
        ref="dropdown"
        trigger="click"
        :size="size"
        placement="bottom"
        @command="dropTask">
        <slot name="icon">
            <div class="task-menu-icon">
                <div v-if="loadIng" class="loading"><Loading /></div>
                <template v-else>
                    <Icon v-if="task.complete_at" class="completed" :type="completedIcon" />
                    <Icon v-else :type="icon" class="uncomplete"/>
                </template>
            </div>
        </slot>
        <EDropdownMenu slot="dropdown" class="task-menu-more-dropdown">
            <EDropdownItem v-if="task.complete_at" command="uncomplete">
                <div class="item red">
                    <Icon type="md-checkmark-circle-outline" />{{$L('标记未完成')}}
                </div>
            </EDropdownItem>
            <EDropdownItem v-else command="complete">
                <div class="item">
                    <Icon type="md-radio-button-off" />{{$L('完成')}}
                </div>
            </EDropdownItem>
            <EDropdownItem v-if="task.parent_id === 0" command="archived">
                <div class="item">
                    <Icon type="ios-filing" />{{$L('归档')}}
                </div>
            </EDropdownItem>
            <EDropdownItem command="remove">
                <div class="item hover-del">
                    <Icon type="md-trash" />{{$L('删除')}}
                </div>
            </EDropdownItem>
            <template v-if="task.parent_id === 0 && colorShow">
                <EDropdownItem v-for="(c, k) in $store.state.taskColorList" :key="k" :divided="k==0" :command="c">
                    <div class="item">
                        <i class="taskfont" :style="{color:c.color||'#f9f9f9'}" v-html="c.color == task.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                    </div>
                </EDropdownItem>
            </template>
        </EDropdownMenu>
    </EDropdown>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "TaskMenu",
    props: {
        task: {
            type: Object,
            default: () => {
                return {};
            }
        },
        loadStatus: {
            type: Boolean,
            default: false
        },
        colorShow: {
            type: Boolean,
            default: true
        },
        quickCompleted: {   // 如果没有任务流是快速完成
            type: Boolean,
            default: false
        },
        size: {
            type: String,
            default: 'small'
        },
        icon: {
            type: String,
            default: 'md-radio-button-off'
        },
        completedIcon: {
            type: String,
            default: 'md-checkmark-circle'
        },
    },
    data() {
        return {

        }
    },
    computed: {
        ...mapState(['taskLoading']),

        loadIng() {
            if (this.loadStatus) {
                return true;
            }
            const load = this.taskLoading.find(({id}) => id == this.task.id);
            return load && load.num > 0
        }
    },
    methods: {
        show() {
            this.$refs.dropdown.show()
        },

        hide() {
            this.$refs.dropdown.hide()
        },

        dropTask(command) {
            if ($A.isJson(command)) {
                if (command.name) {
                    // 修改背景色
                    this.updateTask({
                        color: command.color
                    })
                }
                return;
            }
            switch (command) {
                case 'complete':
                    if (this.task.complete_at) return;
                    this.updateTask({
                        complete_at: $A.formatDate("Y-m-d H:i:s")
                    }).then(() => {
                        // 已完成
                    })
                    break;

                case 'uncomplete':
                    if (!this.task.complete_at) return;
                    this.updateTask({
                        complete_at: false
                    }).then(() => {
                        // 已未完成
                    })
                    break;

                case 'archived':
                case 'remove':
                    this.archivedOrRemoveTask(command);
                    break;
            }
        },

        updateTask(updata) {
            return new Promise((resolve, reject) => {
                if (this.loadIng) {
                    reject()
                    return;
                }
                //
                Object.keys(updata).forEach(key => this.$set(this.task, key, updata[key]));
                //
                this.$store.dispatch("taskUpdate", Object.assign(updata, {
                    task_id: this.task.id,
                })).then(() => {
                    resolve()
                }).catch(({msg}) => {
                    $A.modalError(msg);
                    this.$store.dispatch("getTaskOne", this.task.id);
                    reject()
                });
            });
        },

        archivedOrRemoveTask(type) {
            let typeDispatch = type == 'remove' ? 'removeTask' : 'archivedTask';
            let typeName = type == 'remove' ? '删除' : '归档';
            let typeTask = this.task.parent_id > 0 ? '子任务' : '任务';
            $A.modalConfirm({
                title: typeName + typeTask,
                content: '你确定要' + typeName + typeTask + '【' + this.task.name + '】吗？',
                loading: true,
                onOk: () => {
                    if (this.loadIng) {
                        this.$Modal.remove();
                        return;
                    }
                    this.$store.dispatch(typeDispatch, this.task.id).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$Modal.remove();
                    });
                }
            });
        },
    },
}
</script>
