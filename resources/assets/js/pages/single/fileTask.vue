<template>
    <div class="single-file-task">
        <PageTitle :title="title"/>
        <Loading v-if="loadIng > 0"/>
        <template v-else-if="!isWait">
            <VMPreview v-if="isType('md')" :value="fileDetail.content.content"/>
            <TEditor v-else-if="isType('text')" :value="fileDetail.content.content" height="100%" readOnly/>
            <Drawio v-else-if="isType('drawio')" v-model="fileDetail.content" :title="fileDetail.name" readOnly/>
            <Minder v-else-if="isType('mind')" :value="fileDetail.content" readOnly/>
            <AceEditor v-else-if="isType('code')" v-model="fileDetail.content.content" :ext="fileDetail.ext" class="view-editor" readOnly/>
            <OnlyOffice v-else-if="isType('office')" v-model="officeContent" :code="officeCode" :documentKey="documentKey" readOnly/>
            <IFrame v-else-if="isType('preview')" class="preview-iframe" :src="previewUrl"/>
            <div v-else class="no-support">{{$L('不支持单独查看此消息')}}</div>
        </template>
    </div>
</template>

<style lang="scss">
.single-file-task {
    display: flex;
    align-items: center;
    .preview-iframe,
    .ace_editor,
    .vmpreview-wrapper,
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
    .vmpreview-wrapper {
        overflow: auto;
    }
    .preview-iframe {
        background: 0 0;
        float: none;
        max-width: none;
    }
    .teditor-wrapper {
        .teditor-box {
            height: 100%;
        }
    }
    .view-editor,
    .no-support {
        display: flex;
        align-items: center;
        justify-content: center;
    }
}
</style>
<script>
import IFrame from "../manage/components/IFrame";

const VMPreview = () => import('../../components/VMEditor/preview');
const TEditor = () => import('../../components/TEditor');
const AceEditor = () => import('../../components/AceEditor');
const OnlyOffice = () => import('../../components/OnlyOffice');
const Drawio = () => import('../../components/Drawio');
const Minder = () => import('../../components/Minder');

export default {
    components: {IFrame, AceEditor, TEditor, VMPreview, OnlyOffice, Drawio, Minder},
    data() {
        return {
            loadIng: 0,
            isWait: false,

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
            const {fileId} = this.$route.params;
            return parseInt(/^\d+$/.test(fileId) ? fileId : 0);
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
            const {name, key} = this.fileDetail.content;
            return $A.onlinePreviewUrl(name, key)
        }
    },
    methods: {
        getInfo() {
            if (this.fileId <= 0) {
                return;
            }
            setTimeout(_ => {
                this.loadIng++;
            }, 600)
            this.isWait = true;
            this.$store.dispatch("call", {
                url: 'project/task/filedetail',
                data: {
                    file_id: this.fileId,
                },
            }).then(({data}) => {
                this.fileDetail = data;
            }).catch(({msg}) => {
                $A.modalError({
                    content: msg,
                    onOk: () => {
                        if (this.$Electron) {
                            window.close();
                        }
                    }
                });
            }).finally(_ => {
                this.loadIng--;
                this.isWait = false;
            });
        },
        documentKey() {
            return new Promise((resolve,reject) => {
                this.$store.dispatch("call", {
                    url: 'project/task/filedetail',
                    data: {
                        file_id: this.fileId,
                        only_update_at: 'yes'
                    },
                }).then(({data}) => {
                    resolve(`${data.id}-${$A.Time(data.update_at)}`)
                }).catch((res) => {
                    reject(res)
                });
            })
        }
    }
}
</script>
