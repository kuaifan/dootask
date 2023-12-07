<template>
    <div>
        <EDropdown
            ref="dropdown"
            trigger="click"
            :disabled="disabled"
            :size="size"
            :style="styles"
            class="task-operation-dropdown"
            placement="bottom"
            @command="dropTask"
            @visible-change="visibleChange">
            <div ref="icon" class="task-operation-icon"></div>
            <EDropdownMenu ref="dropdownMenu" slot="dropdown" class="task-operation-more-dropdown">
                <li class="task-operation-more-warp" :class="size">
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
                            <template v-if="operationShow">
                                <EDropdownItem :divided="turns.length > 0" command="archived">
                                    <div class="item">
                                        <Icon type="ios-filing" />{{$L(task.archived_at ? '还原归档' : '归档')}}
                                    </div>
                                </EDropdownItem>
                                <EDropdownItem command="move">
                                    <div class="item">
                                        <i class="taskfont movefont">&#xe7fc;</i>{{$L('移动')}}
                                    </div>
                                </EDropdownItem>
                                <EDropdownItem command="remove">
                                    <div class="item hover-del">
                                        <Icon type="md-trash" />{{$L('删除')}}
                                    </div>
                                </EDropdownItem>
                            </template>
                            <template v-if="colorShow">
                                <EDropdownItem v-for="(c, k) in taskColorList" :key="'c_' + k" :divided="k==0" :command="c">
                                    <div class="item">
                                        <i class="taskfont" :style="{color:c.primary||'#ddd'}" v-html="c.color == (task.color||'') ? '&#xe61d;' : '&#xe61c;'"></i>{{$L(c.name)}}
                                    </div>
                                </EDropdownItem>
                            </template>
                        </template>
                        <EDropdownItem v-else-if="operationShow" command="remove" :divided="turns.length > 0">
                            <div class="item">
                                <Icon type="md-trash" />{{$L('删除')}}
                            </div>
                        </EDropdownItem>
                    </ul>
                </li>
            </EDropdownMenu>
        </EDropdown>

        <!--移动任务-->
        <Modal
            v-model="moveTaskShow"
            :title="$L('移动任务')"
            :mask-closable="false"
            :styles="{
                width: '90%',
                maxWidth: '540px'
            }"
            footer-hide>
            <TaskMove ref="addTask" v-model="moveTaskShow" :task="task"/>
        </Modal>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import TaskMove from "./TaskMove";

export default {
    name: "TaskOperation",
    components: {
        TaskMove,
    },
    data() {
        return {
            task: {},
            loadStatus: false,
            colorShow: true,
            operationShow: true,
            updateBefore: false,
            disabled: false,
            size: 'small',
            projectId: 0,
            onUpdate: null,

            element: null,
            target: null,
            styles: {},

            moveTaskShow: false,
        }
    },
    beforeDestroy() {
        if (this.target) {
            this.target.removeEventListener('scroll', this.handlerEventListeners);
        }
    },
    computed: {
        ...mapState(['loads', 'taskOperation', 'taskColorList', 'taskFlows', 'taskFlowItems']),
        ...mapGetters(['isLoad']),

        loadIng() {
            if (this.loadStatus) {
                return true;
            }
            return this.isLoad(`task-${this.task.id}`)
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
    watch: {
        taskOperation(data) {
            if (data.event && data.task) {
                if (this.$refs.dropdown.visible && this.element === data.event.target) {
                    this.hide();
                    return;
                }
                const eventRect = data.event.target.getBoundingClientRect();
                this.styles = {
                    left: `${eventRect.left}px`,
                    top: `${eventRect.top}px`,
                    width: `${eventRect.width}px`,
                    height: `${eventRect.height}px`,
                }
                this.task = data.task;
                this.loadStatus = typeof data.loadStatus === "undefined" ? false : data.loadStatus;
                this.colorShow = typeof data.colorShow === "undefined" ? true : data.colorShow;
                this.operationShow = typeof data.operationShow === "undefined" ? true : data.operationShow;
                this.updateBefore = typeof data.updateBefore === "undefined" ? false : data.updateBefore;
                this.disabled = typeof data.disabled === "undefined" ? false : data.disabled;
                this.size = typeof data.size === "undefined" ? "small" : data.size;
                this.projectId = typeof data.projectId === "undefined" ? 0 : data.projectId;
                this.onUpdate = typeof data.onUpdate === "function" ? data.onUpdate : null;
                //
                this.$refs.icon.focus();
                this.updatePopper();
                this.show();
                this.$store.dispatch("getTaskFlow", {task_id: this.task.id, project_id: this.projectId}).finally(this.updatePopper)
                this.setupEventListeners(data.event)
            } else {
                this.hide();
            }
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
                    flow_item_id,
                    flow_item_status: updateFlow.status,
                    flow_item_name: updateFlow.name
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

                case 'move':
                    this.moveTaskShow = true;
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
                const updateData = Object.assign(updata, {
                    task_id: this.task.id,
                });
                if(!this.operationShow){
                    if (typeof this.onUpdate === "function") {
                        this.onUpdate(updateData)
                    }
                    reject()
                    return;
                }
                //
                Object.keys(updata).forEach(key => this.$set(this.task, key, updata[key]));
                //
                this.$store.dispatch("taskUpdate", updateData).then(({data, msg}) => {
                    $A.messageSuccess(msg);
                    resolve()
                    this.$store.dispatch("saveTaskBrowse", updateData.task_id);
                    if (typeof this.onUpdate === "function") {
                        this.onUpdate(data)
                    }
                }).catch(({msg}) => {
                    $A.modalError(msg);
                    this.$store.dispatch("getTaskOne", updateData.task_id).catch(() => {})
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
                        return;
                    }
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch(typeDispatch, typeData).then(({msg}) => {
                            resolve(msg);
                            this.$store.dispatch("saveTaskBrowse", typeData.task_id);
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                }
            });
        },

        visibleChange(visible) {
            this.visible = visible;
        },

        updatePopper() {
            this.$nextTick(this.$refs.dropdownMenu.updatePopper)
        },

        setupEventListeners(event) {
            this.element = event.target;
            let target = this.getScrollParent(this.element);
            if (target === window.document.body || target === window.document.documentElement) {
                target = window;
            }
            if (this.target) {
                if (this.target === target) {
                    return;
                }
                this.target.removeEventListener('scroll', this.handlerEventListeners);
            }
            this.target = target;
            this.target.addEventListener('scroll', this.handlerEventListeners);
        },

        handlerEventListeners(e) {
            if (!this.visible || !this.element) {
                return
            }
            const scrollRect = e.target.getBoundingClientRect();
            const eventRect = this.element.getBoundingClientRect();
            if (eventRect.top < scrollRect.top || eventRect.top > scrollRect.top + scrollRect.height) {
                this.hide();
                return;
            }
            this.styles = {
                left: `${eventRect.left}px`,
                top: `${eventRect.top}px`,
                width: `${eventRect.width}px`,
                height: `${eventRect.height}px`,
            };
            this.updatePopper();
        },

        getScrollParent(element) {
            const parent = element.parentNode;
            if (!parent) {
                return element;
            }
            if (parent === window.document) {
                if (window.document.body.scrollTop || window.document.body.scrollLeft) {
                    return window.document.body;
                } else {
                    return window.document.documentElement;
                }
            }
            if (
                ['scroll', 'auto'].indexOf(this.getStyleComputedProperty(parent, 'overflow')) !== -1 ||
                ['scroll', 'auto'].indexOf(this.getStyleComputedProperty(parent, 'overflow-x')) !== -1 ||
                ['scroll', 'auto'].indexOf(this.getStyleComputedProperty(parent, 'overflow-y')) !== -1
            ) {
                return parent;
            }
            return this.getScrollParent(element.parentNode);
        },

        getStyleComputedProperty(element, property) {
            const css = window.getComputedStyle(element, null);
            return css[property];
        }
    },
}
</script>
