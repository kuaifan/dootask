<template>
    <div class="file-content">
        <div v-show="!['word', 'excel', 'ppt'].includes(file.type)" class="edit-header">
            <div class="header-title">
                <EPopover v-if="!equalContent" v-model="unsaveTip" class="file-unsave-tip">
                    <div class="task-detail-delete-file-popover">
                        <p>{{$L('未保存当前修改内容？')}}</p>
                        <div class="buttons">
                            <Button size="small" type="text" @click="unsaveGive">{{$L('放弃')}}</Button>
                            <Button size="small" type="primary" @click="unsaveSave">{{$L('保存')}}</Button>
                        </div>
                    </div>
                    <span slot="reference">[{{$L('未保存')}}*]</span>
                </EPopover>
                {{formatName(file.name, file.type)}}
            </div>
            <div class="header-user">
                <ul>
                    <li v-for="(userid, index) in editUser" :key="index" v-if="index <= 10">
                        <UserAvatar :userid="userid" :size="28" :border-witdh="2"/>
                    </li>
                    <li v-if="editUser.length > 10" class="more">{{editUser.length > 99 ? '99+' : editUser.length}}</li>
                </ul>
            </div>
            <div v-if="file.type=='document' && contentDetail" class="header-hint">
                <ButtonGroup size="small" shape="circle">
                    <Button :type="`${contentDetail.type=='md'?'primary':'default'}`" @click="$set(contentDetail, 'type', 'md')">{{$L('MD编辑器')}}</Button>
                    <Button :type="`${contentDetail.type!='md'?'primary':'default'}`" @click="$set(contentDetail, 'type', 'text')">{{$L('文本编辑器')}}</Button>
                </ButtonGroup>
            </div>
            <div v-if="file.type=='mind'" class="header-hint">
                {{$L('选中节点，按enter键添加同级节点，tab键添加子节点')}}
            </div>
            <Dropdown v-if="file.type=='mind' || file.type=='flow' || file.type=='sheet'"
                      trigger="click"
                      class="header-hint"
                      @on-click="exportMenu">
                <a href="javascript:void(0)">{{$L('导出')}}<Icon type="ios-arrow-down"></Icon></a>
                <DropdownMenu v-if="file.type=='sheet'" slot="list">
                    <DropdownItem name="xlsx">{{$L('导出XLSX')}}</DropdownItem>
                    <DropdownItem name="xlml">{{$L('导出XLS')}}</DropdownItem>
                    <DropdownItem name="csv">{{$L('导出CSV')}}</DropdownItem>
                    <DropdownItem name="txt">{{$L('导出TXT')}}</DropdownItem>
                </DropdownMenu>
                <DropdownMenu v-else slot="list">
                    <DropdownItem name="png">{{$L('导出PNG图片')}}</DropdownItem>
                    <DropdownItem name="pdf">{{$L('导出PDF文件')}}</DropdownItem>
                </DropdownMenu>
            </Dropdown>
            <Button v-if="!file.only_view" :disabled="equalContent" :loading="loadIng > 0" class="header-button" size="small" type="primary" @click="handleClick('save')">{{$L('保存')}}</Button>
        </div>
        <div v-if="contentDetail" class="content-body">
            <template v-if="file.type=='document'">
                <MDEditor v-if="contentDetail.type=='md'" v-model="contentDetail.content" height="100%"/>
                <TEditor v-else v-model="contentDetail.content" height="100%" @editorSave="handleClick('saveBefore')"/>
            </template>
            <Flow v-else-if="file.type=='flow'" ref="myFlow" v-model="contentDetail" @saveData="handleClick('saveBefore')"/>
            <Minder v-else-if="file.type=='mind'" ref="myMind" v-model="contentDetail" @saveData="handleClick('saveBefore')"/>
            <LuckySheet v-else-if="file.type=='sheet'" ref="mySheet" v-model="contentDetail"/>
            <OnlyOffice v-else-if="['word', 'excel', 'ppt'].includes(file.type)" v-model="contentDetail"/>
        </div>
        <div v-if="loadContent > 0" class="content-load"><Loading/></div>
    </div>
</template>

<script>
import Vue from 'vue'
import Minder from '../../../components/minder'
import {mapState} from "vuex";
Vue.use(Minder)

const MDEditor = () => import('../../../components/MDEditor/index');
const TEditor = () => import('../../../components/TEditor');
const LuckySheet = () => import('../../../components/LuckySheet');
const Flow = () => import('../../../components/flow');
const OnlyOffice = () => import('../../../components/OnlyOffice');

export default {
    name: "FileContent",
    components: {TEditor, MDEditor, LuckySheet, Flow, OnlyOffice},
    props: {
        file: {
            type: Object,
            default: () => {
                return {};
            }
        },
        parentShow: {
            type: Boolean,
            default: true
        },
    },

    data() {
        return {
            loadContent: 0,
            loadIng: 0,

            fileId: 0,

            unsaveTip: false,

            contentDetail: null,
            contentBak: {},

            editUser: []
        }
    },

    watch: {
        file: {
            handler(info) {
                if (this.fileId != info.id) {
                    this.fileId = info.id;
                    this.contentDetail = null;
                    this.getContent();
                }
            },
            immediate: true,
            deep: true,
        },

        wsMsg: {
            handler({type, data}) {
                if (type == 'path') {
                    if (data.path == 'file/content/' + this.fileId) {
                        this.editUser = data.userids;
                    }
                } else if (type == 'fileContentChange') {
                    if (this.parentShow && data.id == this.fileId) {
                        $A.modalConfirm({
                            title: "更新提示",
                            content: '团队成员（' + data.nickname + '）更新了内容，<br/>更新时间：' + $A.formatDate("Y-m-d H:i:s", data.time) + '。<br/><br/>点击【确定】加载最新内容。',
                            onOk: () => {
                                this.getContent();
                            }
                        });
                    }
                }
            },
            deep: true,
        },

        parentShow: {
            handler(val) {
                if (!val) {
                    this.fileContent[this.fileId] = this.contentDetail;
                } else {
                    this.editUser = [this.userId];
                }
            },
            immediate: true,
        },
    },

    computed: {
        ...mapState(['fileContent', 'wsMsg', 'userId']),

        equalContent() {
            return this.contentBak == $A.jsonStringify(this.contentDetail);
        },
    },

    methods: {
        getContent() {
            if (!this.fileId) {
                this.contentDetail = {};
                return;
            }
            if (typeof this.fileContent[this.fileId] !== "undefined") {
                this.contentDetail = this.fileContent[this.fileId];
                return;
            }
            if (['word', 'excel', 'ppt'].includes(this.file.type)) {
                this.contentDetail = $A.cloneJSON(this.file);
                this.updateBak();
                return;
            }
            this.loadIng++;
            this.loadContent++;
            this.$store.dispatch("call", {
                url: 'file/content',
                data: {
                    id: this.fileId,
                },
            }).then(({data}) => {
                this.loadIng--;
                this.loadContent--;
                this.contentDetail = data.content;
                this.updateBak();
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.loadIng--;
                this.loadContent--;
            })
        },

        updateBak() {
            this.contentBak = $A.jsonStringify(this.contentDetail);
        },

        handleClick(act) {
            switch (act) {
                case "saveBefore":
                    if (!this.equalContent && this.loadIng == 0) {
                        this.handleClick('save');
                    } else {
                        $A.messageWarning('没有任何修改！');
                    }
                    return;

                case "save":
                    if (this.file.only_view) {
                        return;
                    }
                    this.updateBak();
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'file/content/save',
                        method: 'post',
                        data: {
                            id: this.fileId,
                            content: this.contentBak
                        },
                    }).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        this.loadIng--;
                        this.$store.dispatch("saveFile", {
                            id: this.fileId,
                            size: data.size,
                        });
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                        this.loadIng--;
                        this.getContent();
                    })
                    break;
            }
        },

        exportMenu(act) {
            switch (this.file.type) {
                case 'mind':
                    this.$refs.myMind.exportHandle(act == 'pdf' ? 1 : 0, this.file.name);
                    break;

                case 'flow':
                    this.$refs.myFlow[act == 'pdf' ? 'exportPDF' : 'exportPNG'](this.file.name, 3);
                    break;

                case 'sheet':
                    this.$refs.mySheet.exportExcel(this.file.name, act);
                    break;
            }
        },

        unsaveGive() {
            delete this.fileContent[this.fileId];
            this.getContent();
            this.unsaveTip = false;
        },

        unsaveSave() {
            this.handleClick('save');
            this.unsaveTip = false;
        },

        formatName(name, type) {
            if (type == 'word') {
                name += ".docx";
            } else if (type == 'excel') {
                name += ".xlsx";
            } else if (type == 'ppt') {
                name += ".pptx";
            }
            return name;
        },
    }
}
</script>
