<template>
    <Modal
        :value="show"
        :styles="styles"
        :mask-closable="false"
        :footer-hide="true"
        :beforeClose="onBeforeClose"
        class-name="task-modal">
        <TaskDetail ref="taskDetail" :task-id="taskId" :open-task="taskData" modalMode/>
    </Modal>
</template>

<style lang="scss">
body {
    .ivu-modal-wrap {
        &.task-modal {
            display: flex;
            flex-direction: column;
            .ivu-modal-close {
                z-index: 2;
            }
        }
    }
}
</style>
<script>
import {mapGetters, mapState} from "vuex";
import TaskDetail from "./TaskDetail";

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
            return {
                width: '90%',
                maxWidth: this.taskData.dialog_id ? '1200px' : '700px'
            }
        }
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
        }
    }
}
</script>
