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
        <TaskDetail ref="taskDetail" :task-id="taskId" :open-task="taskData" modalMode/>
    </Modal>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import TaskDetail from "./TaskDetail";
import emitter from "../../../store/events";

export default {
    name: "TaskModal",
    components: {TaskDetail},

    computed: {
        ...mapState(['taskId']),
        ...mapGetters(['taskData']),

        show() {
            return this.taskId > 0
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
                this.$refs.taskDetail.checkUpdate(true);
                return true;
            }
        },
        handleMoveTop(type) {
            type === 'taskModal' && this.$refs.modal?.handleMoveTop();
        }
    }
}
</script>
