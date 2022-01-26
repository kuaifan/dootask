<template>
    <div class="single-msgview">
        <PageTitle :title="title"/>
        <Loading v-if="loadIng > 0"/>
        <template v-else>
            <AceEditor v-if="isCode" v-model="codeContent" :ext="codeExt" class="view-editor" readOnly/>
            <iframe v-else-if="isPreview" class="preview-iframe" :src="previewUrl"></iframe>
            <div v-else class="no-support">{{$L('不支持单独查看此消息')}}</div>
        </template>
    </div>
</template>

<style lang="scss" scoped>
.single-msgview {
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

export default {
    components: {AceEditor},
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
        title() {
            const {msg} = this.msgDetail;
            if (msg && msg.name) {
                return msg.name;
            }
            return "Loading..."
        },
        isCode() {
            return this.msgDetail.type == 'file' && this.msgDetail.file_mode == 1;
        },
        codeContent() {
            if (this.isCode) {
                return this.msgDetail.content;
            }
            return '';
        },
        codeExt() {
            if (this.isCode) {
                return this.msgDetail.msg.ext;
            }
            return 'txt'
        },
        isPreview() {
            return this.msgDetail.type == 'file' && this.msgDetail.file_mode == 2;
        },
        previewUrl() {
            if (this.isPreview) {
                return $A.apiUrl("../fileview/onlinePreview?url=" + encodeURIComponent(this.msgDetail.url))
            }
            return ''
        }
    },
    methods: {
        getInfo() {
            let msg_id = $A.runNum(this.$route.params.id);
            if (msg_id <= 0) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'dialog/msg/detail',
                data: {
                    msg_id,
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
        }
    }
}
</script>
