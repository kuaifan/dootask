<template>
    <div class="page-file">
        <PageTitle :title="$L('文件')"/>

        <div class="file-wrapper" ref="fileWrapper">

            <div class="file-head">
                <div class="file-nav">
                    <h1>{{$L('文件')}}</h1>
                    <div v-if="loadIng == 0" class="file-refresh" @click="getFileList"><i class="taskfont">&#xe6ae;</i></div>
                </div>
                <div v-if="uploadList.length > 0" class="file-status" @click="uploadShow=true">
                    <Loading v-if="uploadList.find(({status}) => status !== 'finished')"/>
                    <Button v-else shape="circle" icon="md-checkmark"></Button>
                </div>
                <div :class="['file-search', searchKey ? 'has-value' : '']" @click="onSearchFocus" @mouseenter="onSearchFocus">
                    <Input v-model="searchKey" ref="searchInput" suffix="ios-search" @on-change="onSearchChange" :placeholder="$L('搜索名称')"/>
                </div>
                <div class="file-add">
                    <Button shape="circle" icon="md-add" @click.stop="handleRightClick($event, null, true)"></Button>
                </div>
            </div>

            <div class="file-navigator">
                <ul>
                    <li @click="backHomeDirectory">{{$L('全部文件')}}</li>
                    <li v-if="searchKey">{{$L('搜索')}} "{{searchKey}}"</li>
                    <li v-else v-for="item in navigator" @click="pid=item.id">
                        <i v-if="item.share" class="taskfont">&#xe63f;</i>
                        <span :title="item.name">{{item.name}}</span>
                        <span v-if="item.share && item.permission == 0" class="readonly">{{$L('只读')}}</span>
                    </li>
                </ul>
                <Button v-if="shearFirst" :disabled="shearFirst.pid == pid" size="small" type="primary" @click="shearTo">
                    <div class="file-shear">
                        <span>{{$L('粘贴')}}</span>
                        "<em>{{shearFirst.name}}</em>"
                        <span v-if="shearIds.length > 1">{{$L('等')}}{{shearIds.length}}{{$L('个文件')}}</span>
                    </div>
                </Button>
                <template v-else-if="selectIds.length > 0">
                    <Button size="small" type="info" @click="handleContextClick('shearSelect')">
                        <Icon type="ios-cut" />
                        {{$L('剪切')}}
                    </Button>
                    <Button size="small" type="error" @click="deleteFile(selectIds)">
                        <Icon type="ios-trash" />
                        {{$L('删除')}}
                    </Button>
                    <Button type="primary" size="small" @click="clearSelect">{{$L('取消选择')}}</Button>
                </template>
                <div v-if="loadIng > 0" class="nav-load"><Loading/></div>
                <div class="flex-full"></div>
                <div :class="['switch-button', tableMode]">
                    <div @click="tableMode='table'"><i class="taskfont">&#xe66a;</i></div>
                    <div @click="tableMode='block'"><i class="taskfont">&#xe60c;</i></div>
                </div>
            </div>

            <div
                class="file-drag"
                @drop.prevent="filePasteDrag($event, 'drag')"
                @dragover.prevent="fileDragOver(true, $event)"
                @dragleave.prevent="fileDragOver(false, $event)">
                <template v-if="tableMode === 'block'">
                    <div v-if="fileList.length == 0 && loadIng == 0" class="file-no" @contextmenu.prevent="handleRightClick">
                        <i class="taskfont">&#xe60b;</i>
                        <p>{{$L('没有任何文件')}}</p>
                    </div>
                    <div v-else class="file-list" @contextmenu.prevent="handleRightClick">
                        <ul class="clearfix">
                            <li
                                v-for="item in fileList"
                                :class="{
                                    shear: shearIds.includes(item.id),
                                    highlight: selectIds.includes(item.id),
                                }"
                                @contextmenu.prevent.stop="handleRightClick($event, item)"
                                @click="openFile(item)">
                                <div class="file-check" :class="{'file-checked':selectIds.includes(item.id)}" @click.stop="dropFile(item, 'select')">
                                    <Checkbox :value="selectIds.includes(item.id)"/>
                                </div>
                                <div class="file-menu" @click.stop="handleRightClick($event, item)">
                                    <Icon type="ios-more" />
                                </div>
                                <div :class="`no-dark-mode-before file-icon ${item.type}`">
                                    <template v-if="item.share">
                                        <UserAvatar v-if="item.userid != userId" :userid="item.userid" class="share-avatar" :size="20">
                                            <p>{{$L('共享权限')}}: {{$L(item.permission == 1 ? '读/写' : '只读')}}</p>
                                        </UserAvatar>
                                        <div v-else class="share-icon no-dark-mode">
                                            <i class="taskfont">&#xe757;</i>
                                        </div>
                                    </template>
                                    <template v-else-if="isParentShare">
                                        <UserAvatar :userid="item.created_id" class="share-avatar" :size="20">
                                            <p v-if="item.created_id != item.userid"><strong>{{$L('成员创建于')}}: {{item.created_at}}</strong></p>
                                            <p v-else>{{$L('所有者创建于')}}: {{item.created_at}}</p>
                                        </UserAvatar>
                                    </template>
                                </div>
                                <div v-if="item._edit" class="file-input">
                                    <Input
                                        :ref="'input_' + item.id"
                                        v-model="item.newname"
                                        size="small"
                                        :disabled="!!item._load"
                                        @on-blur="onBlur(item)"
                                        @on-keyup="onKeyup($event, item)"/>
                                    <div v-if="item._load" class="file-load"><Loading/></div>
                                </div>
                                <div v-else class="file-name" :title="item.name">{{formatName(item)}}</div>
                            </li>
                        </ul>
                    </div>
                </template>
                <div v-else class="file-table" @contextmenu.prevent="handleRightClick">
                    <Table
                        :columns="columns"
                        :data="fileList"
                        :height="tableHeight"
                        :no-data-text="$L('没有任何文件')"
                        @on-cell-click="clickRow"
                        @on-contextmenu="handleContextMenu"
                        @on-select="handleTableSelect"
                        @on-select-cancel="handleTableSelect"
                        @on-select-all-cancel="handleTableSelect"
                        @on-select-all="handleTableSelect"
                        context-menu
                        stripe/>
                </div>
                <div v-if="dialogDrag" class="drag-over" @click="dialogDrag=false">
                    <div class="drag-text">{{$L('拖动到这里发送')}}</div>
                </div>
            </div>

            <div class="file-menu" :style="contextMenuStyles">
                <Dropdown
                    trigger="custom"
                    :visible="contextMenuVisible"
                    transfer-class-name="page-file-dropdown-menu"
                    @on-clickoutside="handleClickContextMenuOutside"
                    @on-visible-change="handleVisibleChangeMenu"
                    transfer>
                    <DropdownMenu slot="list">
                        <template v-if="contextMenuItem.id">
                            <DropdownItem @click.native="handleContextClick('open')">{{$L('打开')}}</DropdownItem>
                            <DropdownItem @click.native="handleContextClick('select')">{{$L(selectIds.includes(contextMenuItem.id) ? '取消选择' : '选择')}}</DropdownItem>

                            <Dropdown placement="right-start" transfer>
                                <DropdownItem divided>
                                    <div class="arrow-forward-item">{{$L('新建')}}<Icon type="ios-arrow-forward"></Icon></div>
                                </DropdownItem>
                                <DropdownMenu slot="list" class="page-file-dropdown-menu">
                                    <DropdownItem
                                        v-for="(type, key) in types"
                                        v-if="type.label"
                                        :key="key"
                                        :divided="!!type.divided"
                                        @click.native="addFile(type.value)">
                                        <div :class="`no-dark-mode-before file-item file-icon ${type.value}`">{{$L(type.label)}}</div>
                                    </DropdownItem>
                                </DropdownMenu>
                            </Dropdown>

                            <DropdownItem @click.native="handleContextClick('rename')" divided>{{$L('重命名')}}</DropdownItem>
                            <DropdownItem @click.native="handleContextClick('copy')" :disabled="contextMenuItem.type == 'folder'">{{$L('复制')}}</DropdownItem>
                            <DropdownItem @click.native="handleContextClick('shear')" :disabled="contextMenuItem.userid != userId">{{$L('剪切')}}</DropdownItem>

                            <DropdownItem v-if="contextMenuItem.userid == userId" @click.native="handleContextClick('share')" divided>{{$L('共享')}}</DropdownItem>
                            <DropdownItem v-else-if="contextMenuItem.share" @click.native="handleContextClick('outshare')" divided>{{$L('退出共享')}}</DropdownItem>
                            <DropdownItem @click.native="handleContextClick('link')" :divided="contextMenuItem.userid != userId && !contextMenuItem.share" :disabled="contextMenuItem.type == 'folder'">{{$L('链接')}}</DropdownItem>
                            <DropdownItem @click.native="handleContextClick('download')" :disabled="contextMenuItem.ext == ''">{{$L('下载')}}</DropdownItem>

                            <DropdownItem @click.native="handleContextClick('delete')" divided style="color:red">{{$L('删除')}}</DropdownItem>
                        </template>
                        <template v-else>
                            <DropdownItem
                                v-for="(type, key) in types"
                                v-if="type.label"
                                :key="key"
                                :divided="!!type.divided"
                                @click.native="addFile(type.value)">
                                <div :class="`no-dark-mode-before file-item file-icon ${type.value}`">{{$L(type.label)}}</div>
                            </DropdownItem>
                        </template>
                    </DropdownMenu>
                </Dropdown>
            </div>
        </div>

        <div v-if="uploadShow && uploadList.length > 0" class="file-upload-list">
            <div class="upload-wrap">
                <div class="title">
                    {{$L('上传列表')}} ({{uploadList.length}})
                    <em v-if="uploadList.find(({status}) => status === 'finished')" @click="uploadClear">{{$L('清空已完成')}}</em>
                </div>
                <ul class="content">
                    <li v-for="(item, index) in uploadList" :key="index" v-if="index < 100">
                        <AutoTip class="file-name">{{uploadName(item)}}</AutoTip>
                        <AutoTip v-if="item.status === 'finished' && item.response && item.response.ret !== 1" class="file-error">{{item.response.msg}}</AutoTip>
                        <Progress v-else :percent="uploadPercentageParse(item.percentage)" :stroke-width="5" />
                        <Icon class="file-close" type="ios-close-circle-outline" @click="uploadList.splice(index, 1)"/>
                    </li>
                </ul>
                <Icon class="close" type="md-close" @click="uploadShow=false"/>
            </div>
        </div>

        <!--上传文件-->
        <Upload
            name="files"
            ref="fileUpload"
            v-show="false"
            :action="actionUrl"
            :headers="headers"
            :multiple="true"
            :webkitdirectory="false"
            :format="uploadFormat"
            :accept="uploadAccept"
            :show-upload-list="false"
            :max-size="maxSize"
            :on-progress="handleProgress"
            :on-success="handleSuccess"
            :on-error="handleError"
            :on-format-error="handleFormatError"
            :on-exceeded-size="handleMaxSize"
            :before-upload="handleBeforeUpload"/>

        <!--上传文件夹-->
        <Upload
            name="files"
            ref="dirUpload"
            v-show="false"
            :action="actionUrl"
            :headers="headers"
            :multiple="true"
            :webkitdirectory="true"
            :format="uploadFormat"
            :accept="uploadAccept"
            :show-upload-list="false"
            :max-size="maxSize"
            :on-progress="handleProgress"
            :on-success="handleSuccess"
            :on-error="handleError"
            :on-format-error="handleFormatError"
            :on-exceeded-size="handleMaxSize"
            :before-upload="handleBeforeUpload"/>

        <!--共享设置-->
        <Modal
            v-model="shareShow"
            :title="$L('共享设置')"
            :mask-closable="false"
            footer-hide>
            <Form class="page-file-share-form" :model="shareInfo" @submit.native.prevent inline>
                <FormItem prop="userids" class="share-userid">
                    <UserInput
                        v-model="shareInfo.userids"
                        :disabledChoice="shareAlready"
                        :multiple-max="100"
                        :placeholder="$L('选择共享成员')">
                        <Option slot="option-prepend" :value="0" :label="$L('所有人')" :disabled="shareAlready.includes(0)">
                            <div class="user-input-option">
                                <div class="user-input-avatar"><EAvatar class="avatar" icon="el-icon-s-custom"/></div>
                                <div class="user-input-nickname">{{ $L('所有人') }}</div>
                                <div class="user-input-userid">All</div>
                            </div>
                        </Option>
                    </UserInput>
                </FormItem>
                <FormItem>
                    <Select v-model="shareInfo.permission" :placeholder="$L('权限')">
                        <Option :value="1">{{$L('读/写')}}</Option>
                        <Option :value="0">{{$L('只读')}}</Option>
                    </Select>
                </FormItem>
                <FormItem>
                    <Button type="primary" :loading="shareLoad > 0" @click="onShare">{{$L('共享')}}</Button>
                </FormItem>
            </Form>
            <div v-if="shareList.length > 0">
                <div class="page-file-share-title">{{ $L('已共享成员') }}:</div>
                <ul class="page-file-share-list">
                    <li v-for="item in shareList">
                        <div v-if="item.userid == 0" class="all-avatar">
                            <EAvatar class="avatar-text" icon="el-icon-s-custom"/>
                            <span class="avatar-name">{{$L('所有人')}}</span>
                        </div>
                        <UserAvatar v-else :size="32" :userid="item.userid" showName tooltipDisabled/>
                        <Select v-model="item.permission" :placeholder="$L('权限')" @on-change="upShare(item)">
                            <Option :value="1">{{ $L('读/写') }}</Option>
                            <Option :value="0">{{ $L('只读') }}</Option>
                            <Option :value="-1" class="delete">{{ $L('删除') }}</Option>
                        </Select>
                    </li>
                </ul>
            </div>
        </Modal>

        <!--文件链接-->
        <Modal
            v-model="linkShow"
            :title="$L('文件链接')"
            :mask-closable="false">
            <div>
                <Input ref="linkInput" v-model="linkData.url" type="textarea" :rows="3" @on-focus="linkFocus" readonly/>
                <div class="form-tip" style="padding-top:6px">{{$L('可通过此链接浏览文件。')}}</div>
            </div>
            <div slot="footer" class="adaption">
                <Button type="default" @click="linkShow=false">{{$L('取消')}}</Button>
                <Poptip
                    confirm
                    placement="bottom"
                    style="margin-left:8px"
                    @on-ok="linkGet(true)"
                    transfer>
                    <div slot="title">
                        <p><strong>{{$L('注意：刷新将导致原来的链接失效！')}}</strong></p>
                    </div>
                    <Button type="primary" :loading="linkLoad > 0">{{$L('刷新')}}</Button>
                </Poptip>
            </div>
        </Modal>

        <!--查看/修改文件-->
        <DrawerOverlay
            v-model="fileShow"
            class="page-file-drawer"
            :mask-closable="false">
            <FilePreview v-if="fileInfo.permission === 0" :file="fileInfo"/>
            <FileContent v-else v-model="fileShow" :file="fileInfo"/>
        </DrawerOverlay>

        <!--拖动上传提示-->
        <Modal
            v-model="pasteShow"
            :title="$L(pasteTitle)"
            :cancel-text="$L('取消')"
            :ok-text="$L('立即上传')"
            :enter-ok="true"
            @on-ok="pasteSend">
            <div class="dialog-wrapper-paste">
                <template v-for="item in pasteItem">
                    <img v-if="item.type == 'image'" :src="item.result"/>
                    <div v-else>{{$L('文件')}}: {{item.name}} ({{$A.bytesToSize(item.size)}})</div>
                </template>
            </div>
        </Modal>
    </div>
</template>

<script>
import Vue from 'vue'
import VueClipboard from 'vue-clipboard2'
Vue.use(VueClipboard)

import {mapState} from "vuex";
import {sortBy} from "lodash";
import UserInput from "../../components/UserInput";
import DrawerOverlay from "../../components/DrawerOverlay";

const FilePreview = () => import('./components/FilePreview');
const FileContent = () => import('./components/FileContent');


export default {
    components: {FilePreview, DrawerOverlay, UserInput, FileContent},
    data() {
        return {
            loadIng: 0,
            searchKey: '',
            searchTimeout: null,

            pid: $A.getStorageInt("fileOpenPid"),

            types: [
                {
                    "value": "folder",
                    "label": "新建文件夹",
                    "name": "文件夹",
                },
                {
                    "value": "upload",
                    "label": "上传文件",
                    "name": null,
                    "divided": true
                },
                {
                    "value": "updir",
                    "label": "上传文件夹",
                    "name": null,
                },
                {
                    "value": "document",
                    "label": "文本",
                    "name": "文本",
                    "divided": true
                },
                {
                    "value": "drawio",
                    "label": "图表",
                    "name": "图表",
                },
                {
                    "value": "mind",
                    "label": "思维导图",
                    "name": "导图",
                },
                {
                    "value": "word",
                    "label": "Word 文档",
                    "name": "Word",
                    "divided": true
                },
                {
                    "value": "excel",
                    "label": "Excel 工作表",
                    "name": "Excel",
                },
                {
                    "value": "ppt",
                    "label": "PPT 演示文稿",
                    "name": "PPT",
                }
            ],

            tableHeight: 500,
            tableMode: $A.getStorageString("fileTableMode"),
            columns: [],

            shareShow: false,
            shareInfo: {id: 0, userid: 0, permission: 1},
            shareList: [],
            shareLoad: 0,

            linkShow: false,
            linkData: {},
            linkLoad: 0,

            fileShow: false,
            fileInfo: {permission: -1},

            uploadDir: false,
            uploadIng: 0,
            uploadShow: false,
            uploadList: [],
            uploadFormat: [
                'text', 'md', 'markdown',
                'drawio',
                'mind',
                'docx', 'wps', 'doc', 'xls', 'xlsx', 'ppt', 'pptx',
                'jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico', 'raw', 'svg',
                'rar', 'zip', 'jar', '7-zip', 'tar', 'gzip', '7z', 'gz', 'apk', 'dmg',
                'tif', 'tiff',
                'dwg', 'dxf',
                'ofd',
                'pdf',
                'txt',
                'htaccess', 'htgroups', 'htpasswd', 'conf', 'bat', 'cmd', 'cpp', 'c', 'cc', 'cxx', 'h', 'hh', 'hpp', 'ino', 'cs', 'css',
                'dockerfile', 'go', 'golang', 'html', 'htm', 'xhtml', 'vue', 'we', 'wpy', 'java', 'js', 'jsm', 'jsx', 'json', 'jsp', 'less', 'lua', 'makefile', 'gnumakefile',
                'ocamlmakefile', 'make', 'mysql', 'nginx', 'ini', 'cfg', 'prefs', 'm', 'mm', 'pl', 'pm', 'p6', 'pl6', 'pm6', 'pgsql', 'php',
                'inc', 'phtml', 'shtml', 'php3', 'php4', 'php5', 'phps', 'phpt', 'aw', 'ctp', 'module', 'ps1', 'py', 'r', 'rb', 'ru', 'gemspec', 'rake', 'guardfile', 'rakefile',
                'gemfile', 'rs', 'sass', 'scss', 'sh', 'bash', 'bashrc', 'sql', 'sqlserver', 'swift', 'ts', 'typescript', 'str', 'vbs', 'vb', 'v', 'vh', 'sv', 'svh', 'xml',
                'rdf', 'rss', 'wsdl', 'xslt', 'atom', 'mathml', 'mml', 'xul', 'xbl', 'xaml', 'yaml', 'yml',
                'asp', 'properties', 'gitignore', 'log', 'bas', 'prg', 'python', 'ftl', 'aspx',
                'mp3', 'wav', 'mp4', 'flv',
                'avi', 'mov', 'wmv', 'mkv', '3gp', 'rm',
                'xmind',
                'rp',
            ],
            uploadAccept: '',
            maxSize: 1024000,

            contextMenuItem: {},
            contextMenuVisible: false,
            contextMenuStyles: {
                top: 0,
                left: 0
            },

            shearIds: [],
            selectIds: [],

            dialogDrag: false,
            pasteShow: false,
            pasteFile: [],
            pasteItem: [],
        }
    },

    mounted() {
        this.tableHeight = window.innerHeight - 160;
        this.uploadAccept = this.uploadFormat.map(item => {
            return '.' + item
        }).join(",");
    },

    activated() {
        this.$store.dispatch("websocketPath", "file");
        this.getFileList();
    },

    computed: {
        ...mapState(['userId', 'userToken', 'userIsAdmin', 'userInfo', 'files', 'wsOpenNum']),

        actionUrl() {
            return $A.apiUrl('file/content/upload?pid=' + this.pid)
        },

        headers() {
            return {
                fd: $A.getStorageString("userWsFd"),
                token: this.userToken,
            }
        },

        shareAlready() {
            let data = this.shareList ? this.shareList.map(({userid}) => userid) : [];
            if (this.shareInfo.userid) {
                data.push(this.shareInfo.userid);
            }
            return data
        },

        fileList() {
            const {files, searchKey, pid, selectIds} = this;
            const list = $A.cloneJSON(sortBy(files.filter((file) => {
                if (searchKey) {
                    return file.name.indexOf(searchKey) !== -1;
                }
                return file.pid == pid;
            }), (file) => {
                return (file.type == 'folder' ? 'a' : 'b') + file.name;
            }));
            return list.map(item => {
                item._checked = selectIds.includes(item.id)
                return item;
            })
        },

        shearFirst() {
            const {files, shearIds} = this;
            if (shearIds.length === 0) {
                return null;
            }
            return files.find(item => item.id == shearIds[0])
        },

        navigator() {
            let {pid, files} = this;
            let array = [];
            while (pid > 0) {
                let file = files.find(({id, permission}) => id == pid && permission > -1);
                if (file) {
                    array.unshift(file);
                    pid = file.pid;
                } else {
                    pid = 0;
                }
            }
            return array;
        },

        isParentShare() {
            const {navigator} = this;
            return !!navigator.find(({share}) => share);
        },

        pasteTitle() {
            const {pasteItem} = this;
            let hasImage = pasteItem.find(({type}) => type == 'image')
            let hasFile = pasteItem.find(({type}) => type != 'image')
            if (hasImage && hasFile) {
                return '上传文件/图片'
            } else if (hasImage) {
                return '上传图片'
            }
            return '上传文件'
        }
    },

    watch: {
        pid() {
            this.selectIds = [];
            this.getFileList();
        },

        tableMode(val) {
            $A.setStorage("fileTableMode", val)
        },

        fileShow(val) {
            if (val) {
                this.$store.dispatch("websocketPath", "file/content/" + this.fileInfo.id);
            } else {
                this.$store.dispatch("websocketPath", "file");
                this.getFileList();
            }
        },

        selectIds: {
            handler(ids) {
                if (ids.length > 0) {
                    this.shearIds = [];
                }
            },
            deep: true
        },

        shearIds: {
            handler(list) {
                if (list.length > 0) {
                    this.selectIds = [];
                }
            },
            deep: true
        },

        wsOpenNum(num) {
            if (num <= 1) return
            this.wsOpenTimeout && clearTimeout(this.wsOpenTimeout)
            this.wsOpenTimeout = setTimeout(() => {
                if (this.$route.name == 'manage-file') {
                    this.getFileList();
                }
            }, 5000)
        }
    },

    methods: {
        initLanguage() {
            this.columns = [
                {
                    type: 'selection',
                    width: 50,
                    align: 'right'
                },
                {
                    title: this.$L('文件名'),
                    key: 'name',
                    minWidth: 200,
                    sortable: true,
                    render: (h, {row}) => {
                        let array = [];
                        let isCreate = !/^\d+$/.test(row.id);
                        if (isCreate) {
                            // 新建
                            array.push(h('Input', {
                                props: {
                                    elementId: 'input_' + row.id,
                                    value: row.newname,
                                    autofocus: true,
                                    disabled: !!row._load,
                                },
                                style: {
                                    width: 'auto'
                                },
                                on: {
                                    'on-change': (event) => {
                                        row.newname = event.target.value;
                                    },
                                    'on-blur': () => {
                                        const file = this.files.find(({id}) => id == row.id);
                                        if (file) {
                                            file.newname = row.newname;
                                            this.onBlur(file)
                                        }
                                    },
                                    'on-enter': () => {
                                        const file = this.files.find(({id}) => id == row.id);
                                        if (file) {
                                            file.newname = row.newname;
                                            this.onEnter(file)
                                        }
                                    }
                                }
                            }))
                            return h('div', {
                                class: 'file-nbox'
                            }, [
                                h('div', {
                                    class: `no-dark-mode-before file-name file-icon ${row.type}`,
                                }, array),
                            ]);
                        } else {
                            // 编辑、查看
                            array.push(h('QuickEdit', {
                                props: {
                                    value: row.name,
                                    autoEdit: !!row._edit,
                                    clickOutSide: false,
                                },
                                on: {
                                    'on-edit-change': (b) => {
                                        const file = this.files.find(({id}) => id == row.id);
                                        if (file) {
                                            setTimeout(() => {
                                                this.setEdit(file.id, b)
                                            }, 100);
                                        }
                                    },
                                    'on-update': (val, cb) => {
                                        const file = this.files.find(({id}) => id == row.id);
                                        if (file) {
                                            file.newname = val
                                            this.onEnter(file);
                                        }
                                        cb();
                                    }
                                }
                            }, [
                                h('AutoTip', this.formatName(row))
                            ]));
                            //
                            const iconArray = [];
                            if (row.share) {
                                if (row.userid != this.userId) {
                                    iconArray.push(h('UserAvatar', {
                                        props: {
                                            userid: row.userid,
                                            size: 20
                                        },
                                    }))
                                } else {
                                    iconArray.push(h('i', {
                                        class: 'taskfont',
                                        domProps: {
                                            innerHTML: '&#xe757;'
                                        },
                                    }))
                                }
                            } else if (this.isParentShare) {
                                iconArray.push(h('UserAvatar', {
                                    props: {
                                        userid: row.created_id,
                                        size: 20
                                    },
                                }, [
                                    row.created_id != row.userid ? h('p', [h('strong', this.$L('成员创建于') + ": " + row.created_at)]) : h('p', this.$L('所有者创建') + ": " + row.created_at)
                                ]))
                            }
                            return h('div', {
                                class: `file-nbox ${this.shearIds.includes(row.id) ? 'shear' : ''}`,
                            }, [
                                h('div', {
                                    class: `no-dark-mode-before file-name file-icon ${row.type}`,
                                }, array),
                                iconArray
                            ]);
                        }
                    }
                },
                {
                    title: this.$L('大小'),
                    key: 'size',
                    width: 110,
                    resizable: true,
                    sortable: true,
                    render: (h, {row}) => {
                        if (row.type == 'folder') {
                            return h('div', '-')
                        }
                        return h('AutoTip', $A.bytesToSize(row.size));
                    }
                },
                {
                    title: this.$L('类型'),
                    key: 'type',
                    width: 110,
                    resizable: true,
                    sortable: true,
                    render: (h, {row}) => {
                        let type = this.types.find(({value, name}) => value == row.type && name);
                        if (type) {
                            return h('AutoTip', type.name);
                        } else {
                            return h('div', (row.ext || row.type).replace(/^\S/, s => s.toUpperCase()))
                        }
                    }
                },
                {
                    title: this.$L('所有者'),
                    key: 'userid',
                    width: 130,
                    resizable: true,
                    sortable: true,
                    render: (h, {row}) => {
                        return h('UserAvatar', {
                            props: {
                                size: 18,
                                userid: row.userid,
                                showIcon: false,
                                showName: true,
                            }
                        });
                    }
                },
                {
                    title: this.$L('最后修改'),
                    key: 'updated_at',
                    width: 168,
                    resizable: true,
                    sortable: true,
                },
            ];
        },

        formatName(file) {
            let {name, ext} = file;
            if (ext != '') {
                name += "." + ext;
            }
            return name;
        },

        backHomeDirectory() {
            this.pid = 0
            this.searchKey = ''
        },

        getFileList() {
            this.loadIng++;
            this.$store.dispatch("getFiles", this.pid).then(() => {
                this.loadIng--;
                $A.setStorage("fileOpenPid", this.pid)
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError({
                    content: msg,
                    onOk: () => {
                        this.backHomeDirectory();
                    }
                });
            });
        },

        addFile(command) {
            if (command == 'upload') {
                this.uploadDir = false
                this.$refs.fileUpload.handleClick();
                return;
            } else if (command == 'updir') {
                this.uploadDir = true
                this.$refs.dirUpload.handleClick();
                return;
            }
            let id = $A.randomString(8);
            this.files.push({
                _edit: true,
                pid: this.pid,
                id: id,
                type: command,
                name: '',
                newname: this.$L('未命名')
            });
            this.autoBlur(id)
        },

        handleRightClick(event, item, isAddButton) {
            this.contextMenuItem = $A.isJson(item) ? item : {};
            if (this.contextMenuVisible) {
                this.handleClickContextMenuOutside();
            }
            this.$nextTick(() => {
                const fileWrap = this.$refs.fileWrapper;
                const fileBounding = fileWrap.getBoundingClientRect();
                this.contextMenuStyles = {
                    left: `${event.clientX - fileBounding.left}px`,
                    top: `${event.clientY - fileBounding.top}px`
                };
                if (isAddButton === true) {
                    this.contextMenuStyles.top = `${event.target.clientHeight + event.target.offsetTop - 5}px`
                }
                this.contextMenuVisible = true;
            })
        },

        openFile(item, checkMenuVisible = true) {
            if (checkMenuVisible && this.contextMenuVisible) {
                return;
            }
            if (this.fileList.findIndex((file) => file._edit === true) > -1) {
                return;
            }
            if (item._load) {
                return;
            }
            if (item.type == 'folder') {
                this.searchKey = '';
                this.pid = item.id;
            } else {
                // 图片直接浏览
                if (item.image_url) {
                    const list = this.fileList.filter(({image_url}) => !!image_url)
                    if (list.length > 0) {
                        this.$store.state.previewImageIndex = list.findIndex(({id}) => item.id === id);
                        this.$store.state.previewImageList = list.map(item => item.image_url);
                        return;
                    }
                }
                // 客户端打开独立窗口
                if (this.$Electron) {
                    this.openSingle(item);
                    return;
                }
                // 正常显示弹窗
                this.fileInfo = item;
                this.fileShow = true;
            }
        },

        openSingle(item) {
            this.$Electron.sendMessage('windowRouter', {
                name: 'file-' + item.id,
                path: "/single/file/" + item.id,
                userAgent: "/hideenOfficeTitle/",
                force: false, // 如果窗口已存在不重新加载
                config: {
                    title: this.formatName(item),
                    titleFixed: true,
                    parent: null,
                    width: Math.min(window.screen.availWidth, 1440),
                    height: Math.min(window.screen.availHeight, 900),
                },
                webPreferences: {
                    nodeIntegrationInSubFrames: item.type === 'drawio'
                },
            });
        },

        clickRow(row, column) {
            if (column.type == "selection") {
                this.dropFile(row, 'select');
            } else {
                this.dropFile(row, 'open');
            }
        },

        handleContextMenu(row, event) {
            this.handleRightClick(event, this.files.find(({id}) => id === row.id) || {});
        },

        handleContextClick(command) {
            this.dropFile(this.contextMenuItem, command)
        },

        handleClickContextMenuOutside() {
            this.contextMenuVisible = false;
        },

        handleVisibleChangeMenu(visible) {
            let file = this.files.find(({_highlight}) => !!_highlight)
            if (file) {
                this.$set(file, '_highlight', false);
            }
            if (visible && this.contextMenuItem.id) {
                this.$set(this.contextMenuItem, '_highlight', true);
            }
        },

        dropFile(item, command) {
            switch (command) {
                case 'open':
                    this.openFile(item, false);
                    break;

                case 'select':
                    let index = this.selectIds.findIndex(id => id == item.id)
                    if (index > -1) {
                        this.selectIds.splice(index, 1)
                    } else {
                        this.selectIds.push(item.id)
                    }
                    break;

                case 'rename':
                    this.setEdit(item.id, true)
                    this.autoBlur(item.id)
                    break;

                case 'copy':
                    this.$store.dispatch("call", {
                        url: 'file/copy',
                        data: {
                            id: item.id,
                        },
                    }).then(({data, msg}) => {
                        $A.messageSuccess(msg);
                        this.$store.dispatch("saveFile", data);
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    });
                    break;

                case 'shear':
                    this.shearIds = [item.id];
                    break;

                case 'shearSelect':
                    this.shearIds = $A.cloneJSON(this.selectIds);
                    break;

                case 'share':
                    this.shareInfo = {
                        id: item.id,
                        userid: item.userid,
                        permission: 1,
                    };
                    this.shareList = [];
                    this.shareShow = true;
                    this.getShare();
                    break;

                case 'outshare':
                    $A.modalConfirm({
                        content: '你确定要退出【' + item.name + '】共享成员吗？',
                        loading: true,
                        onOk: () => {
                            this.$store.dispatch("call", {
                                url: 'file/share/out',
                                data: {
                                    id: item.id,
                                },
                            }).then(({msg}) => {
                                $A.messageSuccess(msg);
                                this.$Modal.remove();
                                this.$store.dispatch("forgetFile", item.id);
                            }).catch(({msg}) => {
                                this.$Modal.remove();
                                $A.modalError(msg, 301);
                            });
                        }
                    });
                    break;

                case 'link':
                    this.linkData = {
                        id: item.id
                    };
                    this.linkShow = true;
                    this.linkGet()
                    break;

                case 'download':
                    if (!item.ext) {
                        return;
                    }
                    $A.modalConfirm({
                        title: '下载文件',
                        content: `${item.name}.${item.ext} (${$A.bytesToSize(item.size)})`,
                        okText: '立即下载',
                        onOk: () => {
                            this.$store.dispatch('downUrl', $A.apiUrl(`file/content?id=${item.id}&down=yes`))
                        }
                    });
                    break;

                case 'delete':
                    this.deleteFile([item.id])
                    break;
            }
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
                this.linkLoad--;
                this.linkData = Object.assign(data, {
                    id: this.linkData.id
                });
                this.linkCopy();
            }).catch(({msg}) => {
                this.linkLoad--;
                this.linkShow = false
                $A.modalError(msg);
            });
        },

        linkCopy() {
            if (!this.linkData.url) {
                return;
            }
            this.$copyText(this.linkData.url).then(() => {
                $A.messageSuccess(this.$L('复制成功！'));
            }, () => {
                $A.messageError(this.$L('复制失败！'));
            });
        },

        linkFocus() {
            this.$refs.linkInput.focus({cursor:'all'});
        },

        shearTo() {
            if (this.shearIds.length == 0) {
                return;
            }
            this.$store.dispatch("call", {
                url: 'file/move',
                data: {
                    ids: this.shearIds,
                    pid: this.pid,
                },
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.shearIds = [];
                this.$store.dispatch("saveFile", data);
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        deleteFile(ids) {
            if (ids.length === 0) {
                return
            }
            const firstFile = this.files.find(item => item.id == ids[0]) || {};
            const allFolder = !ids.find(id => {
                return this.files.find(item => item.type != 'folder' && item.id == id)
            });
            let typeName = allFolder ? "文件夹" : "文件"
            let fileName = `【${firstFile.name}】等${ids.length}个${typeName}`
            if (ids.length === 1) {
                fileName = `【${firstFile.name}】${typeName}`
            }
            $A.modalConfirm({
                title: '删除' + typeName,
                content: '你确定要删除' + fileName + '吗？',
                loading: true,
                onOk: () => {
                    this.$store.dispatch("call", {
                        url: 'file/remove',
                        data: {
                            ids,
                        },
                    }).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.$Modal.remove();
                        this.$store.dispatch("forgetFile", ids);
                        this.selectIds = this.selectIds.filter(id => !ids.includes(id))
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301);
                        this.$Modal.remove();
                    });
                }
            });
        },

        autoBlur(id) {
            this.$nextTick(() => {
                if (this.$refs['input_' + id]) {
                    this.$refs['input_' + id][0].focus({
                        cursor: 'all'
                    })
                } else if (document.getElementById('input_' + id)) {
                    const el = document.getElementById('input_' + id);
                    const len = el.value.length;
                    el.focus();
                    el.setSelectionRange(0, len);
                }
            })
        },

        onBlur(item) {
            if (this.files.find(({id, _edit}) => id == item.id && !_edit)) {
                return;
            }
            this.onEnter(item);
        },

        onKeyup(e, item) {
            if (e.keyCode === 13) {
                this.onEnter(item);
            } else if (e.keyCode === 27) {
                this.setLoad(item.id, false)
                this.setEdit(item.id, false)
            }
        },

        onEnter(item) {
            let isCreate = !/^\d+$/.test(item.id);
            if (!item.newname) {
                if (isCreate) {
                    this.$store.dispatch("forgetFile", item.id);
                } else {
                    this.setEdit(item.id, false)
                }
                return;
            }
            if (item.newname == item.name) {
                this.setEdit(item.id, false)
                return;
            }
            if (item._load) {
                return;
            }
            this.setLoad(item.id, true)
            this.$store.dispatch("call", {
                url: 'file/add',
                data: {
                    id: isCreate ? 0 : item.id,
                    pid: item.pid,
                    name: item.newname,
                    type: item.type,
                },
            }).then(({data, msg}) => {
                $A.messageSuccess(msg)
                this.setLoad(item.id, false)
                this.setEdit(item.id, false)
                this.$store.dispatch("saveFile", data);
                if (isCreate) {
                    this.$store.dispatch("forgetFile", item.id);
                }
            }).catch(({msg}) => {
                $A.modalError(msg)
                this.setLoad(item.id, false)
                if (isCreate) {
                    this.$store.dispatch("forgetFile", item.id);
                }
            })
        },

        setEdit(fileId, is) {
            let item = this.$store.state.files.find(({id}) => id == fileId)
            if (item) {
                this.$set(item, '_edit', is);
                if (is) {
                    this.$set(item, 'newname', item.name);
                }
            }
        },

        setLoad(fileId, is) {
            let item = this.$store.state.files.find(({id}) => id == fileId)
            if (item) {
                this.$set(item, '_load', is);
            }
        },

        onSearchFocus() {
            this.$nextTick(() => {
                this.$refs.searchInput.focus({
                    cursor: "end"
                });
            })
        },

        onSearchChange() {
            clearTimeout(this.searchTimeout);
            if (this.searchKey.trim() != '') {
                this.searchTimeout = setTimeout(() => {
                    this.loadIng++;
                    this.$store.dispatch("searchFiles", this.searchKey).then(() => {
                        this.loadIng--;
                    }).catch(() => {
                        this.loadIng--;
                    });
                }, 600)
            }
        },

        getShare() {
            this.shareLoad++;
            this.$store.dispatch("call", {
                url: 'file/share',
                data: {
                    id: this.shareInfo.id
                },
            }).then(({data}) => {
                this.shareLoad--;
                if (data.id == this.shareInfo.id) {
                    this.shareList = data.list.map(item => {
                        item._permission = item.permission;
                        return item;
                    });
                }
            }).catch(({msg}) => {
                this.shareLoad--;
                this.shareShow = false;
                $A.modalError(msg)
            })
        },

        onShare(force = false) {
            if (this.shareInfo.userids.length == 0) {
                $A.messageWarning("请选择共享成员")
                return;
            }
            this.shareLoad++;
            this.$store.dispatch("call", {
                url: 'file/share/update',
                data: Object.assign(this.shareInfo, {
                    force: force === true ? 1 : 0
                }),
            }).then(({data, msg}) => {
                this.shareLoad--;
                $A.messageSuccess(msg)
                this.$store.dispatch("saveFile", data);
                this.$set(this.shareInfo, 'userids', []);
                this.getShare();
            }).catch(({ret, msg}) => {
                this.shareLoad--;
                if (ret === -3001) {
                    $A.modalConfirm({
                        content: '此文件夹内已有共享文件夹，子文件的共享状态将被取消，是否继续？',
                        onOk: () => {
                            this.onShare(true)
                        }
                    })
                } else {
                    $A.modalError(msg, force === true ? 301 : 0)
                }
            })
        },

        upShare(item, force = false) {
            if (item.loading === true) {
                return;
            }
            item.loading = true;
            //
            this.$store.dispatch("call", {
                url: 'file/share/update',
                data: {
                    id: this.shareInfo.id,
                    userids: [item.userid],
                    permission: item.permission,
                    force: force === true ? 1 : 0
                },
            }).then(({data, msg}) => {
                item.loading = false;
                item._permission = item.permission;
                $A.messageSuccess(msg);
                this.$store.dispatch("saveFile", data);
                if (item.permission === -1) {
                    let index = this.shareList.findIndex(({userid}) => userid == item.userid);
                    if (index > -1) {
                        this.shareList.splice(index, 1)
                    }
                }
            }).catch(({ret, msg}) => {
                item.loading = false;
                if (ret === -3001) {
                    $A.modalConfirm({
                        content: '此文件夹内已有共享文件夹，子文件的共享状态将被取消，是否继续？',
                        onOk: () => {
                            this.upShare(item, true)
                        },
                        onCancel: () => {
                            item.permission = item._permission;
                        }
                    })
                } else {
                    item.permission = item._permission;
                    $A.modalError(msg, force === true ? 301 : 0)
                }
            })
        },

        uploadName(item) {
            return $A.getObject(item, 'response.data.full_name') || item.name
        },

        handleTableSelect(selection) {
            this.selectIds = selection.map(item => item.id);
        },

        clearSelect() {
            this.selectIds = [];
        },

        /********************拖动上传部分************************/

        pasteDragNext(e, type) {
            let files = type === 'drag' ? e.dataTransfer.files : e.clipboardData.files;
            files = Array.prototype.slice.call(files);
            if (files.length > 0) {
                e.preventDefault();
                if (files.length > 0) {
                    this.pasteFile = [];
                    this.pasteItem = [];
                    files.some(file => {
                        let reader = new FileReader();
                        reader.readAsDataURL(file);
                        reader.onload = ({target}) => {
                            this.pasteFile.push(file)
                            this.pasteItem.push({
                                type: $A.getMiddle(file.type, null, '/'),
                                name: file.name,
                                size: file.size,
                                result: target.result
                            })
                            this.pasteShow = true
                        }
                    });
                }
            }
        },

        filePasteDrag(e, type) {
            this.dialogDrag = false;
            this.pasteDragNext(e, type);
        },

        fileDragOver(show, e) {
            let random = (this.__dialogDrag = $A.randomString(8));
            if (!show) {
                setTimeout(() => {
                    if (random === this.__dialogDrag) {
                        this.dialogDrag = show;
                    }
                }, 150);
            } else {
                if (e.dataTransfer.effectAllowed === 'move') {
                    return;
                }
                this.dialogDrag = true;
            }
        },

        pasteSend() {
            this.pasteFile.some(file => {
                this.$refs.fileUpload.upload(file)
            });
        },

        /********************文件上传部分************************/

        uploadUpdate(fileList) {
            fileList.forEach(file => {
                let index = this.uploadList.findIndex(({uid}) => uid == file.uid)
                if (index > -1) {
                    this.uploadList.splice(index, 1, file)
                } else {
                    this.uploadList.unshift(file)
                }
            })
        },

        uploadClear() {
            this.uploadList = this.uploadList.filter(({status}) => status !== 'finished')
        },

        uploadPercentageParse(val) {
            return parseInt(val, 10);
        },

        handleProgress(event, file, fileList) {
            //开始上传
            if (file._uploadIng === undefined) {
                file._uploadIng = true;
                this.uploadIng++;
            }
            this.uploadUpdate(fileList);
        },

        handleSuccess(res, file, fileList) {
            //上传完成
            this.uploadIng--;
            this.uploadUpdate(fileList);
            if (res.ret === 1) {
                this.$store.dispatch("saveFile", res.data);
            } else {
                $A.modalWarning({
                    title: '上传失败',
                    content: '文件 ' + file.name + ' 上传失败，' + res.msg
                });
            }
        },

        handleError(error, file, fileList) {
            //上传错误
            this.uploadIng--;
            this.uploadUpdate(fileList);
        },

        handleFormatError(file) {
            //上传类型错误
            if (this.uploadDir) {
                return;
            }
            $A.modalWarning({
                title: '文件格式不正确',
                content: '文件 ' + file.name + ' 格式不正确，仅支持上传：' + this.uploadFormat.join(',')
            });
        },

        handleMaxSize(file) {
            //上传大小错误
            $A.modalWarning({
                title: '超出文件大小限制',
                content: '文件 ' + file.name + ' 太大，不能超过：' + $A.bytesToSize(this.maxSize * 1024) + '。'
            });
        },

        handleBeforeUpload() {
            //上传前判断
            this.uploadShow = true;
            return true;
        },
    }
}
</script>
