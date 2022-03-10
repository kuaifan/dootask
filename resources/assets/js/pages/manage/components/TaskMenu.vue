<template>
    <EDropdown
        ref="dropdown"
        trigger="click"
        :disabled="disabled"
        :size="size"
        placement="bottom"
        @command="dropTask"
        @visible-change="visibleChange">
        <slot name="icon">
            <div class="task-menu-icon">
                <div v-if="loadIng" class="loading"><Loading/></div>
                <template v-else>
                    <Icon v-if="task.complete_at" class="completed" :type="completedIcon" />
                    <Icon v-else :type="icon" class="uncomplete"/>
                </template>
            </div>
        </slot>
        <EDropdownMenu ref="dropdownMenu" slot="dropdown" class="task-menu-more-dropdown">
            <li class="task-menu-more-warp" :class="size">
                <ul>
                    <EDropdownItem v-if="!flow" class="load-flow" disabled>
                        <div class="load-flow-warp">
                            <Loading/>
                        </div>
                    </EDropdownItem>
                    <template v-else-if="turns.length > 0">
                        <EDropdownItem v-for="item in turns" :key="item.id" :command="`turn::${item.id}`">
                            <div class="item flow">
                                <Icon v-if="item.id == task.flow_item_id && flow.auto_assign !== true" class="check" type="md-checkmark-circle-outline" />
                                <Icon v-else type="md-radio-button-off" />
                                <div class="flow-name" :class="item.status">{{item.name}}</div>
                            </div>
                        </EDropdownItem>
                    </template>
                    <template v-else>
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
                    </template>

                    <template v-if="task.parent_id === 0">
                        <EDropdownItem :divided="turns.length > 0" command="archived">
                            <div class="item">
                                <Icon type="ios-filing" />{{$L(task.archived_at ? '还原归档' : '归档')}}
                            </div>
                        </EDropdownItem>
                        <EDropdownItem command="remove">
                            <div class="item hover-del">
                                <Icon type="md-trash" />{{$L('删除')}}
                            </div>
                        </EDropdownItem>
                        <template v-if="colorShow">
                            <EDropdownItem v-for="(c, k) in taskColorList" :key="'c_' + k" :divided="k==0" :command="c">
                                <div class="item">
                                    <i class="taskfont" :style="{color:c.color||'#f9f9f9'}" v-html="c.color == task.color ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                </div>
                            </EDropdownItem>
                        </template>
                    </template>
                    <EDropdownItem v-else command="remove" :divided="turns.length > 0">
                        <div class="item">
                            <Icon type="md-trash" />{{$L('删除')}}
                        </div>
                    </EDropdownItem>
                </ul>
            </li>
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
        updateBefore: {
            type: Boolean,
            default: false
        },
        disabled: {
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
        ...mapState(['taskColorList', 'taskLoading', 'taskFlows', 'taskFlowItems']),

        loadIng() {
            if (this.loadStatus) {
                return true;
            }
            const load = this.taskLoading.find(({id}) => id == this.task.id);
            return load && load.num > 0
        },

        flow() {
            return this.taskFlows.find(({task_id}) => task_id == this.task.id);
        },

        turns() {
            if (!this.flow) {
                return [];
            }
            let item = this.taskFlowItems.find(({id}) => id == this.flow.flow_item_id);
            if (!item) {
                return [];
            }
            return this.taskFlowItems.filter(({id}) => item.turns.includes(id))
        },
    },
    methods: {
        show() {
            this.$refs.dropdown.show()
        },

        hide() {
            this.$refs.dropdown.hide()
        },

        handleClick() {
            this.$refs.dropdown.handleClick()
        },

        dropTask(command) {
            const cacheTask = this.task;
            const completeTemp = (save) => {
                if (save) {
                    this.$store.dispatch("saveTaskCompleteTemp", cacheTask.id)
                } else {
                    this.$store.dispatch("forgetTaskCompleteTemp", cacheTask.id)
                }
            }
            // 修改背景色
            if ($A.isJson(command)) {
                if (command.name) {
                    this.updateTask({
                        color: command.color
                    }).catch(() => {})
                }
                return;
            }
            // 修改工作流状态
            if ($A.leftExists(command, 'turn::')) {
                let flow_item_id = $A.leftDelete(command, 'turn::');
                if (flow_item_id == this.task.flow_item_id) return;
                //
                let currentFlow = this.taskFlowItems.find(({id}) => id == this.flow.flow_item_id) || {};
                let updateFlow = this.taskFlowItems.find(({id}) => id == flow_item_id) || {};
                let isComplete = currentFlow.status !== 'end' && updateFlow.status === 'end';
                let isUnComplete = currentFlow.status === 'end' && updateFlow.status !== 'end';
                if (this.updateBefore) {
                    if (isComplete) {
                        completeTemp(true)
                    } else if (isUnComplete) {
                        completeTemp(false)
                    }
                }
                this.updateTask({
                    flow_item_id
                }).then(() => {
                    if (isComplete) {
                        completeTemp(true)
                    } else if (isUnComplete) {
                        completeTemp(false)
                    }
                }).catch(() => {
                    if (isComplete) {
                        completeTemp(false)
                    } else if (isUnComplete) {
                        completeTemp(true)
                    }
                })
                return;
            }
            // 其他操作
            switch (command) {
                case 'complete':
                    if (this.task.complete_at) {
                        return;
                    }
                    if (this.updateBefore) {
                        completeTemp(true)
                    }
                    this.updateTask({
                        complete_at: $A.formatDate("Y-m-d H:i:s")
                    }).then(() => {
                        completeTemp(true)
                    }).catch(() => {
                        completeTemp(false)
                    })
                    break;

                case 'uncomplete':
                    if (!this.task.complete_at) {
                        return;
                    }
                    if (this.updateBefore) {
                        completeTemp(false)
                    }
                    this.updateTask({
                        complete_at: false
                    }).then(() => {
                        completeTemp(false)
                    }).catch(() => {
                        completeTemp(true)
                    })
                    break;

                case 'archived':
                case 'remove':
                    this.archivedOrRemoveTask(command);
                    break;
            }
        },

        visibleChange(visible) {
            if (visible) {
                this.$store.dispatch("getTaskFlow", this.task.id)
                    .then(this.$refs.dropdownMenu.updatePopper)
                    .catch(this.$refs.dropdownMenu.updatePopper)
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
                })).then(({data, msg}) => {
                    $A.messageSuccess(msg);
                    resolve()
                    this.$emit("on-update", data)
                }).catch(({msg}) => {
                    $A.modalError(msg);
                    this.$store.dispatch("getTaskOne", this.task.id).catch(() => {})
                    reject()
                });
            })
        },

        archivedOrRemoveTask(type) {
            let typeDispatch = 'removeTask';
            let typeName = '删除';
            let typeData = {task_id: this.task.id};
            let typeTask = this.task.parent_id > 0 ? '子任务' : '任务';
            if (type == 'archived') {
                typeDispatch = 'archivedTask'
                typeName = '归档'
                if (this.task.archived_at) {
                    typeName = '还原归档'
                    typeData = {
                        task_id: this.task.id,
                        type: 'recovery'
                    }
                }
            }
            $A.modalConfirm({
                title: typeName + typeTask,
                content: '你确定要' + typeName + typeTask + '【' + this.task.name + '】吗？',
                loading: true,
                onOk: () => {
                    if (this.loadIng) {
                        this.$Modal.remove();
                        return;
                    }
                    this.$store.dispatch(typeDispatch, typeData).then(({msg}) => {
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
