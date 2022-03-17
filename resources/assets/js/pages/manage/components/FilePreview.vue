<template>
    <div class="file-preview">
        <iframe v-if="isPreview" ref="myPreview" class="preview-iframe" :src="previewUrl"></iframe>
        <template v-else>
            <div v-show="!['word', 'excel', 'ppt'].includes(file.type)" class="edit-header">
                <div class="header-title">
                    {{formatName(file)}}
                    <Tag color="default">{{$L('只读')}}</Tag>
                    <div class="refresh">
                        <Loading v-if="contentLoad"/>
                        <Icon v-else type="ios-refresh" @click="getContent" />
                    </div>
                </div>
                <Dropdown v-if="file.type=='mind'"
                          trigger="click"
                          class="header-hint"
                          @on-click="exportMenu">
                    <a href="javascript:void(0)">{{$L('导出')}}<Icon type="ios-arrow-down"></Icon></a>
                    <DropdownMenu slot="list">
                        <DropdownItem name="png">{{$L('导出PNG图片')}}</DropdownItem>
                        <DropdownItem name="pdf">{{$L('导出PDF文件')}}</DropdownItem>
                    </DropdownMenu>
                </Dropdown>
            </div>
            <div v-if="contentDetail" class="content-body">
                <template v-if="file.type=='document'">
                    <MDPreview v-if="contentDetail.type=='md'" :initialValue="contentDetail.content"/>
                    <TEditor v-else :value="contentDetail.content" height="100%" readOnly/>
                </template>
                <Drawio v-else-if="file.type=='drawio'" ref="myFlow" :value="contentDetail" :title="file.name" readOnly/>
                <Minder v-else-if="file.type=='mind'" ref="myMind" :value="contentDetail" readOnly/>
                <AceEditor v-else-if="['code', 'txt'].includes(file.type)" :value="contentDetail" :ext="file.ext" readOnly/>
                <OnlyOffice v-else-if="['word', 'excel', 'ppt'].includes(file.type)" :value="contentDetail" :code="code" :documentKey="documentKey" readOnly/>
            </div>
        </template>
        <div v-if="contentLoad" class="content-load"><Loading/></div>
    </div>
</template>

<script>
import Vue from 'vue'
import Minder from '../../../components/Minder'
Vue.use(Minder)

const MDPreview = () => import('../../../components/MDEditor/preview');
const TEditor = () => import('../../../components/TEditor');
const AceEditor = () => import('../../../components/AceEditor');
const OnlyOffice = () => import('../../../components/OnlyOffice');
const Drawio = () => import('../../../components/Drawio');

export default {
    name: "FilePreview",
    components: {AceEditor, TEditor, MDPreview, OnlyOffice, Drawio},
    props: {
        code: {
            type: String,
            default: ''
        },
        file: {
            type: Object,
            default: () => {
                return {};
            }
        },
    },

    data() {
        return {
            loadContent: 0,
            contentDetail: null,
            loadPreview: true,
        }
    },

    mounted() {
        window.addEventListener('message', this.handleMessage)
    },
    beforeDestroy() {
        window.removeEventListener('message', this.handleMessage)
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
                return $A.apiUrl("../fileview/onlinePreview?url=" + encodeURIComponent(this.contentDetail.url))
            } else {
                return '';
            }
        },
    },

    methods: {
        handleMessage (event) {
            const data = event.data;
            switch (data.act) {
                case 'ready':
                    this.loadPreview = false;
                    break
            }
        },

        getContent() {
            if (['word', 'excel', 'ppt'].includes(this.file.type)) {
                this.contentDetail = $A.cloneJSON(this.file);
                return;
            }
            this.loadContent++;
            this.$store.dispatch("call", {
                url: 'file/content',
                data: {
                    id: this.code || this.file.id,
                },
            }).then(({data}) => {
                this.loadContent--;
                this.contentDetail = data.content;
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.loadContent--;
            })
        },

        documentKey() {
            return new Promise(resolve => {
                this.$store.dispatch("call", {
                    url: 'file/content',
                    data: {
                        id: this.code || this.file.id,
                        only_update_at: 'yes'
                    },
                }).then(({data}) => {
                    resolve($A.Date(data.update_at, true))
                }).catch(() => {
                    resolve(0)
                });
            })
        },

        exportMenu(act) {
            switch (this.file.type) {
                case 'mind':
                    this.$refs.myMind.exportHandle(act == 'pdf' ? 1 : 0, this.file.name);
                    break;
            }
        },

        formatName(file) {
            let {name, ext} = file;
            if (ext != '') {
                name += "." + ext;
            }
            return name;
        },
    }
}
</script>
