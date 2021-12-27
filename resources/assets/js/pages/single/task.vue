<template>
    <div class="electron-task">
        <PageTitle :title="taskInfo.name"/>
        <Loading v-if="loadIng > 0"/>
        <TaskDetail v-else :task-id="taskInfo.id" :open-task="taskInfo"/>
    </div>
</template>

<style lang="scss" scoped>
.electron-task {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: auto;
    .task-detail {
        flex: 1;
        margin: 0;
        padding: 18px 22px;
        border-radius: 0;
    }
}
</style>
<style lang="scss">
.electron-task {
    .task-detail {
        .task-info {
            .head {
                .function {
                    margin-right: 0;
                    .open {
                        display: none;
                    }
                }
            }
        }
    }
}
</style>
<script>
import TaskDetail from "../manage/components/TaskDetail";
export default {
    components: {TaskDetail},
    data() {
        return {
            loadIng: 0,

            taskInfo: {},
        }
    },
    mounted() {
        //
    },
    watch: {
        '$route': {
            handler() {
                this.getInfo();
            },
            immediate: true
        },
    },
    methods: {
        getInfo() {
            let task_id = $A.runNum(this.$route.params.id);
            if (task_id <= 0) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("getTaskOne", task_id).then(({data}) => {
                this.loadIng--;
                this.taskInfo = data;
                this.$store.dispatch("getTaskContent", task_id);
                this.$store.dispatch("getTaskFiles", task_id);
                this.$store.dispatch("getTasks", {parent_id: task_id});
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError({
                    content: msg,
                    onOk: () => {
                        if (this.isElectron) {
                            window.close();
                        }
                    }
                });
            });
        }
    }
}
</script>
