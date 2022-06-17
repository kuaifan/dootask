<template>
    <div class="task-menu-icon" @click="handleClick">
        <div v-if="loadIng" class="loading"><Loading/></div>
        <template v-else>
            <Icon v-if="task.complete_at" class="completed" :type="completedIcon" />
            <Icon v-else :type="icon" class="uncomplete"/>
        </template>
    </div>
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
    computed: {
        ...mapState(['loads', 'taskFlows']),

        loadIng() {
            if (this.loadStatus) {
                return true;
            }
            const load = this.loads.find(({key}) => key === `task-${this.task.id}`);
            return load && load.num > 0
        },
    },
    methods: {
        handleClick(event) {
            this.$store.state.taskOperation = {
                event,
                task: this.task,
                loadStatus: this.loadStatus,
                colorShow: this.colorShow,
                updateBefore: this.updateBefore,
                disabled: this.disabled,
                size: this.size,
                onUpdate: data => {
                    this.$emit("on-update", data)
                }
            }
        },
    },
}
</script>
