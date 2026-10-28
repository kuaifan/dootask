<template>
    <Modal
        ref="modal"
        :value="show"
        :styles="styles"
        :mask-closable="false"
        :footer-hide="true"
        :fullscreen="windowPortrait"
        :beforeClose="onBeforeClose"
        class-name="common-task-modal">
        <TaskDetail v-if="contentReady" ref="taskDetail" :task-id="taskId" :open-task="taskData" modalMode/>
        <!--内容挂载前的占位（头部结构与 TaskDetail 一致 + 骨架），避免打开时整页空白-->
        <div v-else-if="show" class="task-detail task-detail-placeholder" :class="{completed: taskData.complete_at}">
            <div class="task-info">
                <div class="head">
                    <div class="task-menu-icon icon">
                        <Icon v-if="taskData.complete_at" class="completed" type="md-checkmark-circle"/>
                        <Icon v-else class="uncomplete" type="md-radio-button-off"/>
                    </div>
                    <div
                        v-if="taskData.flow_item_name"
                        class="flow"
                        :style="$A.generateColorVarStyle(taskData.flow_item_color, [10], 'flow-item-custom-color')">
                        <span :class="taskData.flow_item_status">{{taskData.flow_item_name}}</span>
                    </div>
                    <div v-if="taskData.archived_at" class="flow">
                        <span class="archived">{{$L('已归档')}}</span>
                    </div>
                    <div class="nav">
                        <p v-if="projectName"><span>{{projectName}}</span></p>
                        <p v-if="columnName"><span>{{columnName}}</span></p>
                        <p v-if="taskData.id"><span>{{taskData.id}}</span></p>
                    </div>
                    <div class="function">
                        <div class="menu">
                            <div class="task-menu-icon">
                                <Icon type="ios-more"/>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="scroller">
                    <div class="title">
                        <div class="placeholder-title">{{taskData.name}}</div>
                    </div>
                    <div class="placeholder-skeleton">
                        <div class="skeleton-desc">
                            <div class="skeleton-line" style="width:92%"></div>
                            <div class="skeleton-line" style="width:58%"></div>
                        </div>
                        <div class="skeleton-row">
                            <div class="skeleton-label" style="width:56px"></div>
                            <div class="skeleton-tag"></div>
                        </div>
                        <div class="skeleton-row">
                            <div class="skeleton-label" style="width:44px"></div>
                            <div class="skeleton-avatar"></div>
                        </div>
                        <div class="skeleton-row">
                            <div class="skeleton-label" style="width:64px"></div>
                            <div class="skeleton-avatar"></div>
                            <div class="skeleton-avatar"></div>
                        </div>
                        <div class="skeleton-row multi">
                            <div class="skeleton-label" style="width:58px"></div>
                            <div class="skeleton-values">
                                <div class="skeleton-line" style="width:180px"></div>
                                <div class="skeleton-line" style="width:84px"></div>
                            </div>
                        </div>
                        <div class="skeleton-add">
                            <div class="skeleton-line" style="width:62%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import TaskDetail from "./TaskDetail";
import emitter from "../../../store/events";

export default {
    name: "TaskModal",
    components: {TaskDetail},

    data() {
        return {
            contentReady: false,
        }
    },

    computed: {
        ...mapState(['taskId', 'cacheProjects', 'cacheColumns']),
        ...mapGetters(['taskData']),

        show() {
            return this.taskId > 0
        },

        projectName() {
            const {project_id, project_name} = this.taskData
            if (!project_id) {
                return ''
            }
            if (project_name) {
                return project_name
            }
            const project = this.cacheProjects.find(({id}) => id == project_id)
            return project ? project.name : ''
        },

        columnName() {
            const {column_id, column_name} = this.taskData
            if (!column_id) {
                return ''
            }
            if (column_name) {
                return column_name
            }
            const column = this.cacheColumns.find(({id}) => id == column_id)
            return column ? column.name : ''
        },

        styles() {
            return this.windowPortrait ? {
                width: '100%',
            } : {
                width: '90%',
                maxWidth: this.taskData.dialog_id ? '1200px' : '700px'
            }
        }
    },

    watch: {
        show(v) {
            $A.eeuiAppSetScrollDisabled(v && this.windowPortrait)
            if (v) {
                if (!this.windowPortrait) {
                    this.contentReady = true
                    return
                }
                // 任务详情渲染较重，等打开动画开始（交由合成线程执行）后再挂载，避免动画首帧被阻塞
                this.contentReady = false
                requestAnimationFrame(_ => requestAnimationFrame(_ => {
                    this.contentReady = this.show
                }))
            }
        }
    },

    mounted() {
        emitter.on('handleMoveTop', this.handleMoveTop);
    },

    beforeDestroy() {
        emitter.off('handleMoveTop', this.handleMoveTop);
    },

    methods: {
        onBeforeClose() {
            return new Promise(_ => {
                this.$store.dispatch("openTask", 0)
            })
        },
        checkUpdate() {
            if (this.show) {
                this.$refs.taskDetail?.checkUpdate(true);
                return true;
            }
        },
        handleMoveTop(type) {
            type === 'taskModal' && this.$refs.modal?.handleMoveTop();
        }
    }
}
</script>
