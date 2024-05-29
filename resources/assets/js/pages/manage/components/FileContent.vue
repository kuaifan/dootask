<template>
    <div v-if="ready" class="file-content">
        <IFrame v-if="isPreview" class="preview-iframe" :src="previewUrl" @on-load="onFrameLoad"/>
        <template v-else-if="contentDetail">
            <EPopover
                v-if="['word', 'excel', 'ppt'].includes(file.type)"
                v-model="historyShow"
                trigger="click">
                <div class="file-content-history">
                    <FileHistory :value="historyShow" :file="file" @on-restore="onRestoreHistory"/>
                </div>
                <div slot="reference" ref="officeHeader" class="office-header"></div>
            </EPopover>
            <div v-else class="edit-header">
                <div class="header-title">
                    <EPopover v-if="!equalContent" v-model="unsaveTip" class="file-unsave-tip">
                        <div class="confirm-popover">
                            <p>{{$L('未保存当前修改内容？')}}</p>
                            <div class="buttons">
                                <Button size="small" type="text" @click="unSaveGive">{{$L('放弃')}}</Button>
                                <Button size="small" type="primary" @click="onSaveSave">{{$L('保存')}}</Button>
                            </div>
                        </div>
                        <span slot="reference">[{{$L('未保存')}}*]</span>
                    </EPopover>
                    {{fileName}}
                </div>
                <div class="header-user">
                    <ul>
                        <li v-for="(userid, index) in editUser" :key="index" v-if="index <= 10">
                            <UserAvatar :userid="userid" :size="28" :border-witdh="2"/>
                        </li>
                        <li v-if="editUser.length > 10" class="more" :title="editUser.length">{{editUser.length > 999 ? '...' : editUser.length}}</li>
                    </ul>
                </div>
                <div v-if="file.type=='document' && contentDetail && !windowPortrait" class="header-hint">
                    <ButtonGroup size="small" shape="circle">
                        <Button :type="`${contentDetail.type=='md'?'primary':'default'}`" @click="setTextType('md')">{{$L('MD编辑器')}}</Button>
                        <Button :type="`${contentDetail.type!='md'?'primary':'default'}`" @click="setTextType('text')">{{$L('文本编辑器')}}</Button>
                    </ButtonGroup>
                </div>
                <div v-if="file.type=='mind'" class="header-hint">
                    {{$L('选中节点，按enter键添加同级节点，tab键添加子节点')}}
                </div>
                <Dropdown v-if="file.type=='mind'"
                          trigger="click"
                          class="header-hint"
                          @on-click="exportMenu"
                          transfer>
                    <a href="javascript:void(0)">{{$L('导出')}}<Icon type="ios-arrow-down"></Icon></a>
                    <DropdownMenu slot="list">
                        <DropdownItem name="png">{{$L('导出PNG图片')}}</DropdownItem>
                        <DropdownItem name="pdf">{{$L('导出PDF文件')}}</DropdownItem>
                    </DropdownMenu>
                </Dropdown>
                <template v-if="!file.only_view">
                    <div class="header-icons">
                        <ETooltip :disabled="$isEEUiApp || windowTouch" :content="$L('文件链接')">
                            <div class="header-icon" @click="handleClick('link')"><i class="taskfont">&#xe785;</i></div>
                        </ETooltip>
                        <EPopover v-model="historyShow" trigger="click">
                            <div class="file-content-history">
                                <FileHistory :value="historyShow" :file="file" @on-restore="onRestoreHistory"/>
                            </div>
                            <ETooltip slot="reference" ref="historyTip" :disabled="$isEEUiApp || windowTouch || historyShow" :content="$L('历史版本')">
                                <div class="header-icon"><i class="taskfont">&#xe71d;</i></div>
                            </ETooltip>
                        </EPopover>
                    </div>
                    <template v-if="windowPortrait && file.type=='document'">
                        <Button v-if="!edit" class="header-button" size="small" type="primary" @click="edit=true">{{$L('编辑')}}</Button>
                        <Button v-else-if="edit && equalContent" class="header-button" size="small"  @click="edit=false">{{$L('取消')}}</Button>
                        <Button v-else :disabled="equalContent" :loading="loadSave > 0" class="header-button" size="small" type="primary" @click="handleClick('save')">{{$L('保存')}}</Button>
                    </template>
                    <Button v-else :disabled="equalContent" :loading="loadSave > 0" class="header-button" size="small" type="primary" @click="handleClick('save')">{{$L('保存')}}</Button>
                </template>
            </div>
            <div class="content-body">
                <div v-if="historyShow" class="content-mask"></div>
                <template v-if="file.type=='document'">
                    <template v-if="contentDetail.type=='md'">
                        <VMEditor v-if="edit" v-model="contentDetail.content"/>
                        <VMPreview v-else :value="contentDetail.content"/>
                    </template>
                    <TEditor v-else :readOnly="!edit" v-model="contentDetail.content" height="100%" @editorSave="handleClick('saveBefore')"/>
                </template>
                <Drawio v-else-if="file.type=='drawio'" ref="myFlow" v-model="contentDetail" :title="file.name" @saveData="handleClick('saveBefore')"/>
                <Minder v-else-if="file.type=='mind'" ref="myMind" v-model="contentDetail" @saveData="handleClick('saveBefore')"/>
                <AceEditor v-else-if="['code', 'txt'].includes(file.type)" v-model="contentDetail.content" :ext="file.ext" @saveData="handleClick('saveBefore')"/>
                <OnlyOffice v-else-if="['word', 'excel', 'ppt'].includes(file.type)" v-model="contentDetail" :documentKey="documentKey" @on-document-ready="handleClick('officeReady')"/>
            </div>
        </template>
        <div v-if="contentLoad" class="content-load"><Loading/></div>

        <!--文件链接-->
        <Modal
            v-model="linkShow"
            :title="$L('文件链接')"
            :mask-closable="false">
            <div>
                <div style="margin:-10px 0 8px">{{$L('文件名称')}}: {{linkData.name}}</div>
                <Input ref="linkInput" v-model="linkData.url" type="textarea" :rows="3" @on-focus="linkFocus" readonly/>
                <div class="form-tip" style="padding-top:6px">
                    {{$L('可通过此链接浏览文件。')}}
                    <Poptip
                        confirm
                        placement="bottom"
                        :ok-text="$L('确定')"
                        :cancel-text="$L('取消')"
                        @on-ok="linkGet(true)"
                        transfer>
                        <div slot="title">
                            <p><strong>{{$L('注意：刷新将导致原来的链接失效！')}}</strong></p>
                        </div>
                        <a href="javascript:void(0)">{{$L('刷新链接')}}</a>
                    </Poptip>
                </div>
            </div>
            <div slot="footer" class="adaption">
                <Button type="default" @click="linkShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="linkLoad > 0" @click="linkCopy">{{$L('复制')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import {mapState} from "vuex";
import FileHistory from "./FileHistory";
import IFrame from "./IFrame";

const VMEditor = () => import('../../../components/VMEditor/index');
const VMPreview = () => import('../../../components/VMEditor/preview');
const TEditor = () => import('../../../components/TEditor');
const AceEditor = () => import('../../../components/AceEditor');
const OnlyOffice = () => import('../../../components/OnlyOffice');
const Drawio = () => import('../../../components/Drawio');
const Minder = () => import('../../../components/Minder');

export default {
    name: "FileContent",
    components: {IFrame, FileHistory, AceEditor, TEditor, VMEditor, OnlyOffice, Drawio, Minder, VMPreview},
    props: {
        value: {
            type: Boolean,
            default: false
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
            ready: false,

            loadSave: 0,
            loadContent: 0,

            unsaveTip: false,

            fileExt: null,
            contentDetail: null,
            contentBak: {},

            editUser: [],

            loadPreview: true,

            linkShow: false,
            linkData: {},
            linkLoad: 0,

            historyShow: false,
            officeReady: false,

            edit: false,
        }
    },

    mounted() {
        this.edit = !this.windowPortrait
        document.addEventListener('keydown', this.keySave)
        window.addEventListener('message', this.handleOfficeMessage)
        //
        if (this.$isSubElectron) {
            window.__onBeforeUnload = () => {
                if (!this.equalContent) {
                    $A.modalConfirm({
                        content: '修改的内容尚未保存，确定要放弃修改吗？',
                        cancelText: '取消',
                        okText: '放弃',
                        onOk: () => {
                            this.$Electron.sendMessage('windowDestroy');
                        }
                    });
                    return true
                }
            }
        }
    },

    beforeDestroy() {
        document.removeEventListener('keydown', this.keySave)
        window.removeEventListener('message', this.handleOfficeMessage)
    },

    watch: {
        value: {
            handler(val) {
                if (val) {
                    this.ready = true;
                    this.editUser = [this.userId];
                    this.getContent();
                } else {
                    this.linkShow = false;
                    this.historyShow = false;
                    this.officeReady = false;
                    this.fileExt = null;
                }
            },
            immediate: true,
        },

        historyShow(val) {
            if (!val && this.$refs.historyTip) {
                this.$refs.historyTip.updatePopper()
            }
        },

        wsMsg: {
            handler(info) {
                const {type, action, data} = info;
                switch (type) {
                    case 'path':
                        if (data.path == '/single/file/' + this.fileId) {
                            this.editUser = data.userids;
                        }
                        break;

                    case 'file':
                        if (action == 'content') {
                            if (this.value && data.id == this.fileId) {
                                $A.modalConfirm({
                                    title: "更新提示",
                                    content: '团队成员（' + info.nickname + '）更新了内容，<br/>更新时间：' + $A.formatDate("Y-m-d H:i:s", info.time) + '。<br/><br/>点击【确定】加载最新内容。',
                                    onOk: () => {
                                        this.getContent();
                                    }
                                });
                            }
                        }
                        break;
                }
            },
            deep: true,
        },
    },

    computed: {
        ...mapState(['wsMsg']),

        fileId() {
            return this.file.id || 0
        },

        fileName() {
            if (this.fileExt) {
                return $A.getFileName(Object.assign(this.file, {
                    ext: this.fileExt
                }))
            }
            return $A.getFileName(this.file)
        },

        equalContent() {
            return this.contentBak == $A.jsonStringify(this.contentDetail);
        },

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
        handleOfficeMessage({data, source}) {
            if (data.source === 'onlyoffice') {
                switch (data.action) {
                    case 'ready':
                        source.postMessage("createMenu", "*");
                        break;

                    case 'link':
                        this.handleClick('link')
                        break;

                    case 'history':
                        const dom = this.$refs.officeHeader;
                        if (dom) {
                            dom.style.top = `${data.rect.top}px`;
                            dom.style.left = `${data.rect.left}px`;
                            dom.style.width = `${data.rect.width}px`;
                            dom.style.height = `${data.rect.height}px`;
                            dom.click();
                        }
                        break;
                }
            }
        },

        onFrameLoad() {
            this.loadPreview = false;
        },

        keySave(e) {
            if (this.value && e.keyCode === 83) {
                if (e.metaKey || e.ctrlKey) {
                    e.preventDefault();
                    this.onSaveSave();
                }
            }
        },

        getContent() {
            if (this.fileId === 0) {
                this.contentDetail = {};
                this.updateBak();
                return;
            }
            if (['word', 'excel', 'ppt'].includes(this.file.type)) {
                this.contentDetail = $A.cloneJSON(this.file);
                this.updateBak();
                return;
            }
            this.loadSave++;
            setTimeout(_ => {
                this.loadContent++;
            }, 600)
            this.$store.dispatch("call", {
                url: 'file/content',
                data: {
                    id: this.fileId,
                },
            }).then(({data}) => {
                this.contentDetail = data.content;
                this.updateBak();
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadSave--;
                this.loadContent--;
            })
        },

        updateBak() {
            this.contentBak = $A.jsonStringify(this.contentDetail);
        },

        handleClick(act) {
            switch (act) {
                case "link":
                    this.linkData = {
                        id: this.fileId,
                        name: this.file.name
                    };
                    this.linkShow = true;
                    this.linkGet()
                    break;

                case "saveBefore":
                    if (!this.equalContent && this.loadSave == 0) {
                        this.handleClick('save');
                    } else {
                        $A.messageWarning('没有任何修改！');
                    }
                    break;

                case "save":
                    if (this.file.only_view) {
                        return;
                    }
                    this.updateBak();
                    this.loadSave++;
                    this.$store.dispatch("call", {
                        url: 'file/content/save',
                        method: 'post',
                        data: {
                            id: this.fileId,
                            content: this.contentBak
                        },
                    }).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        const newData = {
                            id: this.fileId,
                            size: data.size,
                        };
                        if (this.fileExt) {
                            newData.ext = this.fileExt;
                            this.fileExt = null;
                        }
                        this.edit = this.windowPortrait ? false : true;
                        this.$store.dispatch("saveFile", newData);
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                        this.getContent();
                    }).finally(_ => {
                        this.loadSave--;
                    })
                    break;

                case "officeReady":
                    this.officeReady = true
                    break;
            }
        },

        onRestoreHistory(item) {
            this.historyShow = false;
            $A.modalConfirm({
                content: `你确定文件还原至【${item.created_at}】吗？`,
                cancelText: '取消',
                okText: '确定',
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'file/content/restore',
                            data: {
                                id: this.fileId,
                                history_id: item.id,
                            }
                        }).then(({msg}) => {
                            resolve(msg);
                            this.contentDetail = null;
                            this.getContent();
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                }
            });
        },

        linkGet(refresh) {
            this.linkLoad++;
            this.$store.dispatch("call", {
                url: 'file/link',
                data: {
                    id: this.linkData.id,
                    refresh: refresh === true ? 'yes' : 'no'
                },
            }).then(({data}) => {
                this.linkData = Object.assign(data, {
                    id: this.linkData.id,
                    name: this.linkData.name,
                });
                this.linkCopy();
            }).catch(({msg}) => {
                this.linkShow = false
                $A.modalError(msg);
            }).finally(_ => {
                this.linkLoad--;
            });
        },

        linkCopy() {
            if (!this.linkData.url) {
                return;
            }
            this.linkFocus();
            this.copyText(this.linkData.url);
        },

        linkFocus() {
            this.$nextTick(_ => {
                this.$refs.linkInput.focus({cursor:'all'});
            });
        },

        exportMenu(type) {
            switch (this.file.type) {
                case 'mind':
                    this.$refs.myMind.exportHandle(type, this.file.name);
                    break;
            }
        },

        unSaveGive() {
            this.getContent();
            this.unsaveTip = false;
        },

        onSaveSave() {
            this.handleClick('save');
            this.unsaveTip = false;
        },

        setTextType(type) {
            this.fileExt = type
            this.$set(this.contentDetail, 'type', type)
        },

        documentKey() {
            return new Promise((resolve,reject) => {
                this.$store.dispatch("call", {
                    url: 'file/content',
                    data: {
                        id: this.fileId,
                        only_update_at: 'yes'
                    },
                }).then(({data}) => {
                    resolve(`${data.id}-${$A.Time(data.update_at)}`)
                }).catch((res) => {
                    reject(res)
                });
            })
        },
    }
}
</script>
