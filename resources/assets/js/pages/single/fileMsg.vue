<template>
    <div class="single-file-msg">
        <PageTitle :title="title"/>
        <Loading v-if="loadIng > 0"/>
        <template v-else>
            <MDPreview v-if="isType('md')" :initialValue="msgDetail.content.content"/>
            <TEditor v-else-if="isType('text')" :value="msgDetail.content.content" height="100%" readOnly/>
            <Drawio v-else-if="isType('drawio')" v-model="msgDetail.content" :title="msgDetail.msg.name" readOnly/>
            <Minder v-else-if="isType('mind')" :value="msgDetail.content" readOnly/>
            <AceEditor v-else-if="isType('code')" v-model="msgDetail.content" :ext="msgDetail.msg.ext" class="view-editor" readOnly/>
            <OnlyOffice v-else-if="isType('office')" v-model="officeContent" :code="officeCode" :documentKey="documentKey" readOnly/>
            <iframe v-else-if="isType('preview')" class="preview-iframe" :src="previewUrl"/>
            <div v-else class="no-support">{{$L('不支持单独查看此消息')}}</div>
        </template>
    </div>
</template>

<style lang="scss" scoped>
.single-file-msg {
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
.single-file-msg {
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

            msgDetail: {},
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
        msgId() {
            return $A.runNum(this.$route.params.id);
        },

        title() {
            const {msg} = this.msgDetail;
            if (msg && msg.name) {
                return msg.name;
            }
            return "Loading..."
        },

        isType() {
            const {msgDetail} = this;
            return function (type) {
                return msgDetail.type == 'file' && msgDetail.file_mode == type;
            }
        },

        officeContent() {
            return {
                id: this.msgDetail.id || 0,
                type: this.msgDetail.msg.ext,
                name: this.title,
            }
        },

        officeCode() {
            return "msgFile_" + this.msgDetail.id;
        },

        previewUrl() {
            return $A.apiUrl("../fileview/onlinePreview?url=" + encodeURIComponent(this.msgDetail.content.url))
        }
    },
    methods: {
        getInfo() {
            if (this.msgId <= 0) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'dialog/msg/detail',
                data: {
                    msg_id: this.msgId,
                },
            }).then(({data}) => {
                this.loadIng--;
                this.msgDetail = data;
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
                    url: 'dialog/msg/detail',
                    data: {
                        msg_id: this.msgId,
                        only_update_at: 'yes'
                    },
                }).then(({data}) => {
                    resolve($A.Date(data.update_at, true))
                }).catch(() => {
                    resolve(0)
                });
            });
        }
    }
}
</script>
