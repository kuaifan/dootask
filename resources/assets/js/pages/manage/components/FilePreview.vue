<template>
    <div class="file-preview">
        <IFrame v-if="isPreview" class="preview-iframe" :src="previewUrl" @on-load="onFrameLoad"/>
        <template v-else-if="contentDetail">
            <div v-show="headerShow && !['word', 'excel', 'ppt'].includes(file.type)" class="edit-header">
                <div class="header-title">
                    <div class="title-name">{{$A.getFileName(file)}}</div>
                    <Tag color="default">{{$L('只读')}}</Tag>
                    <div class="refresh">
                        <Loading v-if="contentLoad"/>
                        <Icon v-else type="ios-refresh" @click="getContent" />
                    </div>
                </div>
            </div>
            <div class="content-body">
                <template v-if="file.type=='document'">
                    <VMPreview v-if="contentDetail.type=='md'" :value="contentDetail.content"/>
                    <TEditor v-else :value="contentDetail.content" height="100%" readOnly/>
                </template>
                <Drawio v-else-if="file.type=='drawio'" ref="myFlow" :value="contentDetail" :title="file.name" readOnly/>
                <Minder v-else-if="file.type=='mind'" ref="myMind" :value="contentDetail" readOnly/>
                <AceEditor v-else-if="['code', 'txt'].includes(file.type)" :value="contentDetail.content" :ext="file.ext" readOnly/>
                <OnlyOffice v-else-if="['word', 'excel', 'ppt'].includes(file.type)" :value="contentDetail" :code="code" :historyId="historyId" :documentKey="documentKey" readOnly/>
            </div>
        </template>
        <div v-if="contentLoad" class="content-load"><Loading/></div>
    </div>
</template>

<script>
import IFrame from "./IFrame";

const VMPreview = () => import('../../../components/VMEditor/preview');
const TEditor = () => import('../../../components/TEditor');
const AceEditor = () => import('../../../components/AceEditor');
const OnlyOffice = () => import('../../../components/OnlyOffice');
const Drawio = () => import('../../../components/Drawio');
const Minder = () => import('../../../components/Minder');

export default {
    name: "FilePreview",
    components: {IFrame, AceEditor, TEditor, VMPreview, OnlyOffice, Drawio, Minder},
    props: {
        code: {
            type: String,
            default: ''
        },
        historyId: {
            type: Number,
            default: 0
        },
        file: {
            type: Object,
            default: () => {
                return {};
            }
        },
        headerShow: {
            type: Boolean,
            default: true
        },
    },

    data() {
        return {
            loadContent: 0,
            contentDetail: null,
            loadPreview: true,
        }
    },

    watch: {
        'file.id': {
            handler(id) {
                if (id) {
                    this.contentDetail = null;
                    this.getContent();
                }
            },
            immediate: true,
            deep: true,
        },
    },

    computed: {
        contentLoad() {
            return this.loadContent > 0 || this.previewLoad;
        },

        isPreview() {
            return this.contentDetail && this.contentDetail.preview === true;
        },

        previewLoad() {
            return this.isPreview && this.loadPreview === true;
        },

        previewUrl() {
            if (this.isPreview) {
                const {name, key} = this.contentDetail;
                return $A.onlinePreviewUrl(name, key)
            }
            return '';
        },
    },

    methods: {
        onFrameLoad() {
            this.loadPreview = false;
        },

        getContent() {
            if (['word', 'excel', 'ppt'].includes(this.file.type)) {
                this.contentDetail = $A.cloneJSON(this.file);
                return;
            }
            setTimeout(_ => {
                this.loadContent++;
            }, 600)
            this.$store.dispatch("call", {
                url: 'file/content',
                data: {
                    id: this.code || this.file.id,
                    history_id: this.historyId
                },
            }).then(({data}) => {
                this.contentDetail = data.content;
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadContent--;
            })
        },

        documentKey() {
            return new Promise((resolve,reject) => {
                this.$store.dispatch("call", {
                    url: 'file/content',
                    data: {
                        id: this.code || this.file.id,
                        only_update_at: 'yes'
                    },
                }).then(({data}) => {
                    resolve(`${data.id}-${$A.Time(data.update_at)}`)
                }).catch((res) => {
                    reject(res)
                });
            })
        },

        exportMenu(type) {
            switch (this.file.type) {
                case 'mind':
                    this.$refs.myMind.exportHandle(type, this.file.name);
                    break;
            }
        },
    }
}
</script>
