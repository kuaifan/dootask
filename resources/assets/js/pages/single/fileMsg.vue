<template>
    <div class="single-file-msg">
        <PageTitle :title="title"/>
        <Loading v-if="loadIng > 0"/>
        <template v-else-if="!isWait">
            <VMPreview v-if="isType('md')" :value="msgDetail.content.content"/>
            <TEditor v-else-if="isType('text')" :value="msgDetail.content.content" height="100%" readOnly/>
            <Drawio v-else-if="isType('drawio')" v-model="msgDetail.content" :title="msgDetail.msg.name" readOnly/>
            <Minder v-else-if="isType('mind')" :value="msgDetail.content" readOnly/>
            <template v-else-if="isType('code')">
                <div v-if="isLongText(msgDetail.msg.name)" class="view-code" v-html="$A.formatTextMsg(msgDetail.content.content, userId)"></div>
                <AceEditor v-else v-model="msgDetail.content.content" :ext="msgDetail.msg.ext" class="view-editor" readOnly/>
            </template>
            <OnlyOffice v-else-if="isType('office')" v-model="officeContent" :code="officeCode" :documentKey="documentKey" readOnly/>
            <IFrame v-else-if="isType('preview')" class="preview-iframe" :src="previewUrl"/>
            <div v-else class="no-support">{{$L('不支持单独查看此消息')}}</div>
        </template>
    </div>
</template>

<style lang="scss">
.single-file-msg {
    display: flex;
    align-items: center;
    .preview-iframe,
    .ace_editor,
    .vmpreview-wrapper,
    .teditor-wrapper,
    .no-support,
    .view-code {
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
    .view-code {
        white-space: pre-wrap;
        word-wrap: break-word;
        overflow: auto;
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
import {mapState} from "vuex";
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
        ...mapState(['userId']),

        msgId() {
            const {msgId} = this.$route.params;
            return parseInt(/^\d+$/.test(msgId) ? msgId : 0);
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
            const {name, key} = this.msgDetail.content;
            return $A.onlinePreviewUrl(name, key)
        }
    },
    methods: {
        getInfo() {
            if (this.msgId <= 0) {
                return;
            }
            setTimeout(_ => {
                this.loadIng++;
            }, 600)
            this.isWait = true;
            this.$store.dispatch("call", {
                url: 'dialog/msg/detail',
                data: {
                    msg_id: this.msgId,
                },
            }).then(({data}) => {
                this.msgDetail = data;
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
                    url: 'dialog/msg/detail',
                    data: {
                        msg_id: this.msgId,
                        only_update_at: 'yes'
                    },
                }).then(({data}) => {
                    resolve(`${data.id}-${$A.Time(data.update_at)}`)
                }).catch((res) => {
                    reject(res)
                });
            });
        },

        isLongText(name) {
            return /^LongText-/.test(name)
        },
    }
}
</script>
