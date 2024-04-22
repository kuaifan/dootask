<template>
    <div class="single-file">
        <PageTitle :title="pageName"/>
        <Loading v-if="loadIng > 0"/>
        <template v-else-if="fileInfo">
            <FilePreview v-if="isPreview" :code="code" :file="fileInfo" :historyId="historyId" :headerShow="!$isEEUiApp"/>
            <FileContent v-else v-model="fileShow" :file="fileInfo"/>
        </template>
    </div>
</template>

<style lang="scss" scoped>
.single-file {
    display: flex;
    align-items: center;
    .file-content,
    .file-preview {
        border-radius: 0;
    }
}
</style>
<script>
import FileContent from "../manage/components/FileContent";
import FilePreview from "../manage/components/FilePreview";

export default {
    components: {FilePreview, FileContent},
    data() {
        return {
            loadIng: 0,

            code: null,

            fileShow: true,
            fileInfo: null,
        }
    },
    mounted() {
        //
    },
    computed: {
        historyId() {
            return this.$route.query ? $A.runNum(this.$route.query.history_id) : 0;
        },
        isPreview() {
            return this.windowPortrait || this.code || this.historyId > 0 || (this.fileInfo && this.fileInfo.permission === 0)
        },
        pageName() {
            if (this.$route.query && this.$route.query.history_title) {
                return this.$route.query.history_title
            }
            if (this.fileInfo) {
                return `${this.fileInfo.name} [${this.fileInfo.created_at}]`;
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
            let {codeOrFileId} = this.$route.params;
            let data = {id: codeOrFileId};
            if (/^\d+$/.test(codeOrFileId)) {
                this.code = null;
            } else if (codeOrFileId) {
                this.code = codeOrFileId;
            } else {
                return;
            }
            setTimeout(_ => {
                this.loadIng++;
            }, 600)
            this.$store.dispatch("call", {
                url: 'file/one',
                data,
            }).then(({data}) => {
                this.fileInfo = data;
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
