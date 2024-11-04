<template>
    <div class="task-menu-icon" @click="handleClick">
        <div v-if="loadIng && showLoad" class="loading"><Loading/></div>
        <template v-else>
            <Icon v-if="task.complete_at" class="completed" :type="completedIcon" />
            <Icon v-else :type="icon" class="uncomplete"/>
        </template>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";

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
        operationShow: {
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
        projectId:{
            type: Number,
            default: 0
        },
        showLoad: {
            type: Boolean,
            default: true
        }
    },
    computed: {
        ...mapState(['loads', 'taskFlows']),
        ...mapGetters(['isLoad']),

        loadIng() {
            if (this.loadStatus) {
                return true;
            }
            return this.isLoad(`task-${this.task.id}`)
        },
    },
    methods: {
        handleClick(event) {
            this.$store.state.taskOperation = {
                event,
                task: this.task,
                loadStatus: this.loadStatus,
                colorShow: this.colorShow,
                operationShow: this.operationShow,
                updateBefore: this.updateBefore,
                disabled: this.disabled,
                size: this.size,
                projectId: this.projectId,
                onUpdate: data => {
                    this.$emit("on-update", data)
                }
            }
        },

        updateTask(updata) {
            if (this.loadIng) {
                return;
            }
            //
            Object.keys(updata).forEach(key => this.$set(this.task, key, updata[key]));
            //
            const updateData = Object.assign(updata, {
                task_id: this.task.id,
            });
            this.$store.dispatch("taskUpdate", updateData).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.$store.dispatch("saveTaskBrowse", updateData.task_id);
                this.$emit("on-update", data)
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.$store.dispatch("getTaskOne", updateData.task_id).catch(() => {})
            });
        },
    },
}
</script>
