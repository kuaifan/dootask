<template>
    <div class="file-preview">
        <PageTitle :title="pageName"/>
        <Loading v-if="loadIng > 0"/>
        <div v-else-if="info" class="file-preview">
            <div v-if="showHeader" class="edit-header">
                <div class="header-title">
                    <div class="title-name">{{pageName}}</div>
                    <Tag color="default">{{$L('只读')}}</Tag>
                    <div class="refresh">
                        <Icon type="ios-refresh" @click="getInfo" />
                    </div>
                </div>
            </div>
            <div class="content-body">
                <TEditor :value="info.content" height="100%" readOnly/>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.file-preview {
    border-radius: 0;
}
</style>
<script>
import TEditor from "../../components/TEditor.vue";

export default {
    components: {TEditor},
    data() {
        return {
            loadIng: 0,
            info: null,
            showHeader: !$A.isEEUiApp,
        }
    },
    mounted() {

    },
    computed: {
        taskId() {
            return this.$route.params ? $A.runNum(this.$route.params.taskId) : 0;
        },
        historyId() {
            return this.$route.query ? $A.runNum(this.$route.query.history_id) : 0;
        },
        pageName() {
            if (this.$route.query && this.$route.query.history_title) {
                return this.$route.query.history_title
            }
            if (this.info) {
                return `${this.info.name} [${this.info.created_at}]`;
            }
            return '';
        }
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
            setTimeout(_ => {
                this.loadIng++;
            }, 600)
            this.$store.dispatch("call", {
                url: 'project/task/content',
                data: {
                    task_id: this.taskId,
                    history_id: this.historyId,
                },
            }).then(({data}) => {
                this.info = data;
            }).catch(({msg}) => {
                $A.modalError({
                    content: msg,
                    onOk: () => {
                        window.close();
                    }
                });
            }).finally(_ => {
                this.loadIng--;
            });
        }
    }
}
</script>
