<template>
    <div class="single-file-task">
        <PageTitle :title="title"/>
        <Loading v-if="loadIng > 0"/>
        <template v-else>
            <MDPreview v-if="isType('md')" :initialValue="fileDetail.content.content"/>
            <TEditor v-else-if="isType('text')" :value="fileDetail.content.content" height="100%" readOnly/>
            <Drawio v-else-if="isType('drawio')" v-model="fileDetail.content" :title="fileDetail.name" readOnly/>
            <Minder v-else-if="isType('mind')" :value="fileDetail.content" readOnly/>
            <AceEditor v-else-if="isType('code')" v-model="fileDetail.content" :ext="fileDetail.ext" class="view-editor" readOnly/>
            <OnlyOffice v-else-if="isType('office')" v-model="officeContent" :code="officeCode" :documentKey="documentKey" readOnly/>
            <iframe v-else-if="isType('preview')" class="preview-iframe" :src="previewUrl"/>
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
    .markdown-preview-warp,
    .teditor-wrapper,
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
.single-file-task {
    .teditor-wrapper {
        .teditor-box {
            height: 100%;
        }
    }
}
</style>
<script>
import Vue from 'vue'
import Minder from '../../components/Minder'
Vue.use(Minder)

const MDPreview = () => import('../../components/MDEditor/preview');
const TEditor = () => import('../../components/TEditor');
const AceEditor = () => import('../../components/AceEditor');
const OnlyOffice = () => import('../../components/OnlyOffice');
const Drawio = () => import('../../components/Drawio');

export default {
    components: {AceEditor, TEditor, MDPreview, OnlyOffice, Drawio},
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
        fileId() {
            return $A.runNum(this.$route.params.id);
        },

        title() {
            const {name} = this.fileDetail;
            if (name) {
                return name;
            }
            return "Loading..."
        },

        isType() {
            const {fileDetail} = this;
            return function (type) {
                return fileDetail.file_mode == type;
            }
        },

        officeContent() {
            return {
                id: this.fileDetail.id || 0,
                type: this.fileDetail.ext,
                name: this.title,
            }
        },

        officeCode() {
            return "taskFile_" + this.fileDetail.id;
        },

        previewUrl() {
            return $A.apiUrl("../fileview/onlinePreview?url=" + encodeURIComponent(this.fileDetail.content.url))
        }
    },
    methods: {
        getInfo() {
            if (this.fileId <= 0) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/task/filedetail',
                data: {
                    file_id: this.fileId,
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
        },
        documentKey() {
            return new Promise(resolve => {
                this.$store.dispatch("call", {
                    url: 'project/task/filedetail',
                    data: {
                        file_id: this.fileId,
                        only_update_at: 'yes'
                    },
                }).then(({data}) => {
                    resolve($A.Date(data.update_at, true))
                }).catch(() => {
                    resolve(0)
                });
            })
        }
    }
}
</script>
