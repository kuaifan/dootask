<template>
    <div class="page-file">
        <PageTitle :title="$L('文件')"/>

        <div class="file-wrapper" ref="fileWrapper">

            <div class="file-head">
                <div class="file-nav">
                    <h1>{{$L('文件')}}</h1>
                    <div v-if="loadIng == 0" class="file-refresh" @click="getFileList"><i class="taskfont">&#xe6ae;</i></div>
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
                    <li @click="[pid=0,searchKey='']">{{$L('全部文件')}}</li>
                    <li v-if="searchKey">{{$L('搜索')}} "{{searchKey}}"</li>
                    <li v-else v-for="item in navigator" @click="pid=item.id">
                        <i v-if="item.share" class="taskfont">&#xe63f;</i>
                        <span :title="item.name">{{item.name}}</span>
                    </li>
                </ul>
                <Button v-if="shearFile" :disabled="shearFile.pid == pid" size="small" type="primary" @click="shearTo">
                    <div class="file-shear">
                        <span>{{$L('粘贴')}}</span>
                        "<em>{{shearFile.name}}</em>"
                    </div>
                </Button>
                <div v-if="loadIng > 0" class="nav-load"><Loading/></div>
                <div class="flex-full"></div>
                <div :class="['switch-button', tableMode ? 'table' : '']" @click="tableMode=!tableMode">
                    <div><i class="taskfont">&#xe60c;</i></div>
                    <div><i class="taskfont">&#xe66a;</i></div>
                </div>
            </div>

            <div v-if="tableMode" class="file-table" @contextmenu.prevent="handleRightClick">
                <Table
                    :columns="columns"
                    :data="fileList"
                    :height="tableHeight"
                    :no-data-text="$L('没有任何文件')"
                    @on-cell-click="clickRow"
                    @on-contextmenu="handleContextMenu"
                    context-menu
                    stripe/>
            </div>
            <template v-else>
                <div v-if="fileList.length == 0 && loadIng == 0" class="file-no" @contextmenu.prevent="handleRightClick">
                    <i class="taskfont">&#xe60b;</i>
                    <p>{{$L('没有任何文件')}}</p>
                </div>
                <div v-else class="file-list" @contextmenu.prevent="handleRightClick">
                    <ul class="clearfix">
                        <li
                            v-for="item in fileList"
                            :class="[item.type, item.id && shearId == item.id ? 'shear' : '', !!item._highlight ? 'highlight' : '']"
                            @contextmenu.prevent.stop="handleRightClick($event, item)"
                            @click="openFile(item)">
                            <div class="file-menu" @click.stop="handleRightClick($event, item)">
                                <Icon type="ios-more" />
                            </div>
                            <div class="file-icon">
                                <template v-if="item.share">
                                    <UserAvatar v-if="item.userid != userId" :userid="item.userid" class="share-avatar" :size="20"/>
                                    <div v-else class="share-icon">
                                        <i v-if="item.share == 1" class="taskfont" :title="$L('所有人')">&#xe75c;</i>
                                        <i v-else class="taskfont" :title="$L('指定成员')">&#xe757;</i>
                                    </div>
                                </template>
                            </div>
                            <div v-if="item._edit" class="file-input">
                                <Input
                                    :ref="'input_' + item.id"
                                    v-model="item.newname"
                                    size="small"
                                    :disabled="!!item._load"
                                    @on-blur="onBlur(item)"
                                    @on-enter="onEnter(item)"/>
                                <div v-if="item._load" class="file-load"><Loading/></div>
                            </div>
                            <div v-else class="file-name" :title="item.name">{{formatName(item)}}</div>
                        </li>
                    </ul>
                </div>
            </template>

            <div class="file-menu" :style="contextMenuStyles">
                <Dropdown trigger="custom" :visible="contextMenuVisible" transfer @on-clickoutside="handleClickContextMenuOutside" @on-visible-change="handleVisibleChangeMenu">
                    <DropdownMenu slot="list" class="page-file-dropdown-menu">
                        <template v-if="contextMenuItem.id">
                            <DropdownItem @click.native="handleContextClick('open')">{{$L('打开')}}</DropdownItem>
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
                                        <div :class="['file-item ' + type.value]">{{$L(type.label)}}</div>
                                    </DropdownItem>
                                </DropdownMenu>
                            </Dropdown>
                            <DropdownItem @click.native="handleContextClick('rename')" divided>{{$L('重命名')}}</DropdownItem>
                            <DropdownItem @click.native="handleContextClick('copy')" :disabled="contextMenuItem.type=='folder'">{{$L('复制')}}</DropdownItem>
                            <DropdownItem @click.native="handleContextClick('shear')" :disabled="contextMenuItem.userid != userId">{{$L('剪切')}}</DropdownItem>
                            <DropdownItem @click.native="handleContextClick('share')" :disabled="contextMenuItem.userid != userId" divided>{{$L('共享')}}</DropdownItem>
                            <DropdownItem @click.native="handleContextClick('delete')" divided style="color:red">{{$L('删除')}}</DropdownItem>
                        </template>
                        <template v-else>
                            <DropdownItem
                                v-for="(type, key) in types"
                                v-if="type.label"
                                :key="key"
                                :divided="!!type.divided"
                                @click.native="addFile(type.value)">
                                <div :class="['file-item ' + type.value]">{{$L(type.label)}}</div>
                            </DropdownItem>
                        </template>
                    </DropdownMenu>
                </Dropdown>
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
            :mask-closable="false">
            <Form ref="addProject" :model="shareInfo" label-width="auto" @submit.native.prevent>
                <FormItem prop="type" :label="$L('共享对象')">
                    <RadioGroup v-model="shareInfo.share">
                        <Radio v-if="userIsAdmin" :label="1">{{$L('所有人')}}</Radio>
                        <Radio :label="2">{{$L('指定成员')}}</Radio>
                    </RadioGroup>
                </FormItem>
                <FormItem v-if="shareInfo.share === 2" prop="userids" :label="$L('共享成员')">
                    <UserInput v-if="!shareInfo.userTmpHide" v-model="shareInfo.userids" :disabledChoice="[shareInfo.userid]" :multiple-max="100" :placeholder="$L('选择共享成员')"/>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="default" :loading="shareLoad > 0" @click="onShare(false)">{{$L('取消共享')}}</Button>
                <Button type="primary" :loading="shareLoad > 0" @click="onShare(true)">{{$L('设置共享')}}</Button>
            </div>
        </Modal>

        <!--查看/修改文件-->
        <DrawerOverlay
            v-model="editShow"
            class="page-file-drawer"
            :mask-closable="false">
            <FileContent v-if="editNum > 0" :parent-show="editShow" :file="editInfo"/>
        </DrawerOverlay>

    </div>
</template>

<script>
import {mapState} from "vuex";
import {sortBy} from "lodash";
import UserInput from "../../components/UserInput";
import DrawerOverlay from "../../components/DrawerOverlay";

const FileContent = () => import('./components/FileContent');


export default {
    components: {DrawerOverlay, UserInput, FileContent},
    data() {
        return {
            loadIng: 0,
            searchKey: '',
            searchTimeout: null,

            pid: this.$store.state.method.getStorageInt("fileOpenPid"),
            shearId: 0,

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
                    "value": "sheet",
                    "label": null,
                    "name": "表格",
                },
                {
                    "value": "flow",
                    "label": "流程图",
                    "name": "流程图",
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
            tableMode: this.$store.state.method.getStorageBoolean("fileTableMode"),
            columns: [],

            shareShow: false,
            shareInfo: {},
            shareLoad: 0,

            editShow: false,
            editNum: 0,
            editInfo: {},

            uploadDir: false,
            uploadIng: 0,
            uploadFormat: [
                'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
                'txt', 'html', 'htm', 'asp', 'jsp', 'xml', 'json', 'properties', 'md', 'gitignore', 'log', 'java', 'py', 'c', 'cpp', 'sql', 'sh', 'bat', 'm', 'bas', 'prg', 'cmd',
                'jpg', 'jpeg', 'png', 'gif',
                'zip', 'rar', 'jar', 'tar', 'gzip',
                'mp3', 'wav', 'mp4', 'flv',
                'pdf',
                'dwg'
            ],
            uploadAccept: '',
            maxSize: 204800,

            contextMenuItem: {},
            contextMenuVisible: false,
            contextMenuStyles: {
                top: 0,
                left: 0
            },
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
        ...mapState(['userId', 'userToken', 'userIsAdmin', 'userInfo', 'files']),

        actionUrl() {
            return this.$store.state.method.apiUrl('file/content/upload?pid=' + this.pid)
        },

        headers() {
            return {
                fd: this.$store.state.method.getStorageString("userWsFd"),
                token: this.userToken,
            }
        },

        shearFile() {
            const {files, shearId} = this;
            if (shearId > 0) {
                let file = files.find(({id}) => id == shearId);
                if (file) {
                    return file;
                }
            }
            return null;
        },

        fileList() {
            const {files, searchKey, pid} = this;
            return sortBy(files.filter((file) => {
                if (searchKey) {
                    return file.name.indexOf(searchKey) !== -1;
                }
                return file.pid == pid;
            }), (file) => {
                return (file.type == 'folder' ? 'a' : 'b') + file.name;
            })
        },

        navigator() {
            let {pid, files} = this;
            let array = [];
            while (pid > 0) {
                let file = files.find(({id, allow}) => id == pid && allow !== false);
                if (file) {
                    array.unshift(file);
                    pid = file.pid;
                } else {
                    pid = 0;
                }
            }
            return array;
        }
    },

    watch: {
        pid() {
            this.getFileList();
        },

        tableMode(val) {
            this.$store.state.method.setStorage("fileTableMode", val)
        },

        editShow(val) {
            if (val) {
                this.editNum++;
                this.$store.dispatch("websocketPath", "file/content/" + this.editInfo.id);
            } else {
                this.$store.dispatch("websocketPath", "file");
                this.getFileList();
            }
        },
    },

    methods: {
        initLanguage() {
            this.columns = [
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
                                    class: 'file-name ' + row.type,
                                }, array),
                            ]);
                        } else {
                            // 编辑、查看
                            array.push(h('QuickEdit', {
                                props: {
                                    value: row.name,
                                    autoEdit: !!row._edit
                                },
                                on: {
                                    'on-edit-change': (b) => {
                                        const file = this.files.find(({id}) => id == row.id);
                                        if (file) {
                                            setTimeout(() => {
                                                this.$set(file, '_edit', b);
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
                                if (row.share == 1) {
                                    iconArray.push(h('i', {
                                        class: 'taskfont',
                                        domProps: {
                                            title: this.$L('所有人'),
                                            innerHTML: '&#xe75c;'
                                        },
                                    }))
                                } else {
                                    iconArray.push(h('i', {
                                        class: 'taskfont',
                                        domProps: {
                                            title: this.$L('指定成员'),
                                            innerHTML: '&#xe757;'
                                        },
                                    }))
                                }
                            }
                            return h('div', {
                                class: 'file-nbox'
                            }, [
                                h('div', {
                                    class: 'file-name ' + row.type,
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
                            return h('div', '-')
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
            ]
        },

        formatName({name, ext}) {
            if (ext != '') {
                name += "." + ext;
            }
            return name;
        },

        getFileList() {
            this.loadIng++;
            this.$store.dispatch("getFiles", this.pid).then(() => {
                this.loadIng--;
                this.$store.state.method.setStorage("fileOpenPid", this.pid)
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.loadIng--;
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

        openFile(item) {
            if (this.contextMenuVisible) {
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
                this.editInfo = item;
                this.editShow = true;
            }
        },

        clickRow(row) {
            this.dropFile(row, 'open');
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
                    this.openFile(item);
                    break;

                case 'rename':
                    this.$set(item, 'newname', item.name);
                    this.$set(item, '_edit', true);
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
                    this.shearId = item.id;
                    break;

                case 'share':
                    this.shareInfo = {
                        id: item.id,
                        name: item.name,
                        userid: item.userid,
                        share: item.share,
                        _share: item.share,
                    };
                    if (!this.userIsAdmin) {
                        // 不是管理员只能共享给指定人
                        this.shareInfo.share = 2;
                    }
                    this.shareShow = true;
                    this.getShare();
                    break;

                case 'delete':
                    let typeName = item.type == 'folder' ? '文件夹' : '文件';
                    $A.modalConfirm({
                        title: '删除' + typeName,
                        content: '你确定要删除' + typeName +'【' + item.name + '】吗？',
                        loading: true,
                        onOk: () => {
                            this.$store.dispatch("call", {
                                url: 'file/remove',
                                data: {
                                    id: item.id,
                                },
                            }).then(({msg}) => {
                                $A.messageSuccess(msg);
                                this.$Modal.remove();
                                this.$store.dispatch("forgetFile", item.id);
                            }).catch(({msg}) => {
                                $A.modalError(msg);
                                this.$Modal.remove();
                            });
                        }
                    });
                    break;
            }
        },

        shearTo() {
            if (!this.shearFile) {
                return;
            }
            this.$store.dispatch("call", {
                url: 'file/move',
                data: {
                    id: this.shearFile.id,
                    pid: this.pid,
                },
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.shearId = 0;
                this.$store.dispatch("saveFile", data);
            }).catch(({msg}) => {
                $A.modalError(msg);
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
            this.onEnter(item);
        },

        onEnter(item) {
            let isCreate = !/^\d+$/.test(item.id);
            if (!item.newname) {
                if (isCreate) {
                    this.$store.dispatch("forgetFile", item.id);
                } else {
                    this.$set(item, '_edit', false);
                }
                return;
            }
            if (item.newname == item.name) {
                this.$set(item, '_edit', false);
                return;
            }
            if (item._load) {
                return;
            }
            this.$set(item, '_load', true);
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
                this.$set(item, '_load', false);
                this.$set(item, '_edit', false);
                this.$store.dispatch("saveFile", data);
                if (isCreate) {
                    this.$store.dispatch("forgetFile", item.id);
                }
            }).catch(({msg}) => {
                $A.modalError(msg)
                this.$set(item, '_load', false);
                if (isCreate) {
                    this.$store.dispatch("forgetFile", item.id);
                }
            })
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
                    this.shareInfo = Object.assign({userTmpHide: true}, this.shareInfo, data);
                    this.$nextTick(() => {
                        this.$set(this.shareInfo, 'userTmpHide', false);
                    })
                }
            }).catch(({msg}) => {
                this.shareLoad--;
                this.shareShow = false;
                $A.modalError(msg)
            })
        },

        onShare(share) {
            if (!share && !this.shareInfo._share) {
                this.shareShow = false;
                return;
            }
            if (![1, 2].includes(this.shareInfo.share)) {
                $A.messageWarning("请选择共享对象")
                return;
            }
            this.shareLoad++;
            this.$store.dispatch("call", {
                url: 'file/share/update',
                data: Object.assign(this.shareInfo, {
                    action: share ? 'share' : 'unshare'
                }),
            }).then(({data, msg}) => {
                this.shareLoad--;
                this.shareShow = false;
                $A.messageSuccess(msg)
                this.$store.dispatch("saveFile", data);
            }).catch(({msg}) => {
                this.shareLoad--;
                $A.modalError(msg)
            })
        },

        /********************文件上传部分************************/

        handleProgress() {
            //开始上传
            this.uploadIng++;
        },

        handleSuccess(res, file) {
            //上传完成
            this.uploadIng--;
            if (res.ret === 1) {
                this.$store.dispatch("saveFile", res.data);
            } else {
                $A.modalWarning({
                    title: '上传失败',
                    content: '文件 ' + file.name + ' 上传失败，' + res.msg
                });
            }
        },

        handleError() {
            //上传错误
            this.uploadIng--;
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
            return true;
        },
    }
}
</script>
