<template>
    <div class="single-file-task">
        <PageTitle :title="title"/>
        <Loading v-if="loadIng > 0"/>
        <template v-else>
            <AceEditor v-if="isCode" v-model="codeContent" :ext="codeExt" class="view-editor" readOnly/>
            <OnlyOffice v-else-if="isOffice" v-model="officeContent" :code="officeCode" readOnly/>
            <iframe v-else-if="isPreview" class="preview-iframe" :src="previewUrl"/>
            <div v-else class="no-support">{{$L('不支持单独查看此消息')}}</div>
        </template>
    </div>
</template>

<style lang="scss" scoped>
.single-file-task {
    display: flex;
    align-items: center;
    .preview-iframe,
    .ace_editor,
    .no-support {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: 0;
        margin: 0;
        outline: 0;
        padding: 0;
    }
    .preview-iframe {
        background: 0 0;
        float: none;
        max-width: none;
    }
    .view-editor,
    .no-support {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
</style>
<style lang="scss">
</style>
<script>
import AceEditor from "../../components/AceEditor";
import OnlyOffice from "../../components/OnlyOffice";

export default {
    components: {OnlyOffice, AceEditor},
    data() {
        return {
            loadIng: 0,

            fileDetail: {},
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
    computed: {
        title() {
            const {name} = this.fileDetail;
            if (name) {
                return name;
            }
            return "Loading..."
        },

        isCode() {
            return this.fileDetail.file_mode == 1;
        },
        codeContent() {
            if (this.isCode) {
                return this.fileDetail.content;
            }
            return '';
        },
        codeExt() {
            if (this.isCode) {
                return this.fileDetail.ext;
            }
            return 'txt'
        },

        isOffice() {
            return this.fileDetail.file_mode == 2;
        },
        officeContent() {
            return {
                id: this.isOffice ? this.fileDetail.id : 0,
                type: this.fileDetail.ext,
                updated_at: this.fileDetail.created_at,
                name: this.title
            }
        },
        officeCode() {
            if (this.isOffice) {
                return "taskFile_" + this.fileDetail.id;
            }
            return ''
        },

        isPreview() {
            return this.fileDetail.file_mode == 3;
        },
        previewUrl() {
            if (this.isPreview) {
                return $A.apiUrl("../fileview/onlinePreview?url=" + encodeURIComponent(this.fileDetail.url))
            }
            return ''
        }
    },
    methods: {
        getInfo() {
            let file_id = $A.runNum(this.$route.params.id);
            if (file_id <= 0) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/task/filedetail',
                data: {
                    file_id,
                },
            }).then(({data}) => {
                this.loadIng--;
                this.fileDetail = data;
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError({
                    content: msg,
                    onOk: () => {
                        if (this.$Electron) {
                            window.close();
                        }
                    }
                });
            });
        }
    }
}
</script>
