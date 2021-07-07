<template>
    <div class="page-file">
        <PageTitle :title="$L('文件')"/>
        <div class="file-wrapper">
            <div class="file-head">
                <div class="file-nav">
                    <h1>{{$L('文件')}}</h1>
                    <div v-if="loadIng == 0" class="file-refresh" @click="getFileList"><i class="taskfont">&#xe6ae;</i></div>
                </div>
                <div :class="['file-search', searchKey ? 'has-value' : '']">
                    <Input v-model="searchKey" suffix="ios-search" @on-change="onSearchChange" :placeholder="$L('搜索名称')"/>
                </div>
                <div class="file-add">
                    <EDropdown
                        trigger="click"
                        placement="bottom"
                        @command="addFile">
                        <i class="taskfont">&#xe6f2;</i>
                        <EDropdownMenu slot="dropdown" class="page-file-dropdown-menu">
                            <EDropdownItem v-for="(type, key) in types" :key="key" :command="type.value">
                                <div :class="['file-item ' + type.value]">{{$L('新建' + type.name)}}</div>
                            </EDropdownItem>
                        </EDropdownMenu>
                    </EDropdown>
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
                <div v-if="loadIng > 0" class="nav-load"><Loading/></div>
                <Button v-if="shearFile && shearFile.pid != pid" size="small" type="primary" @click="shearTo">
                    <div class="file-shear">
                        <span>{{$L('粘贴')}}</span>
                        "<em>{{shearFile.name}}</em>"
                    </div>
                </Button>
                <div class="flex-full"></div>
                <div :class="['switch-button', tableMode ? 'table' : '']" @click="tableMode=!tableMode">
                    <div><i class="taskfont">&#xe60c;</i></div>
                    <div><i class="taskfont">&#xe66a;</i></div>
                </div>
            </div>
            <div v-if="tableMode" class="file-table">
                <Table
                    :columns="columns"
                    :data="fileList"
                    :height="tableHeight"
                    :no-data-text="$L('没有任何文件')"
                    @on-cell-click="clickRow"
                    stripe/>
            </div>
            <template v-else>
                <div v-if="fileList.length == 0 && loadIng == 0" class="file-no">
                    <i class="taskfont">&#xe60b;</i>
                    <p>{{$L('没有任何文件')}}</p>
                </div>
                <div v-else class="file-list">
                    <ul class="clearfix">
                        <li v-for="item in fileList" :class="[item.type, item.id && shearId == item.id ? 'shear' : '']" @click="openFile(item)">
                            <EDropdown
                                trigger="click"
                                size="small"
                                placement="bottom"
                                class="file-menu"
                                @command="dropFile(item, $event)">
                                <Icon @click.stop="" type="ios-more" />
                                <EDropdownMenu slot="dropdown">
                                    <EDropdownItem command="open">{{$L('打开')}}</EDropdownItem>
                                    <EDropdownItem command="rename" divided>{{$L('重命名')}}</EDropdownItem>
                                    <EDropdownItem command="copy" :disabled="item.type=='folder'">{{$L('复制')}}</EDropdownItem>
                                    <EDropdownItem command="shear" :disabled="item.userid != userId">{{$L('剪切')}}</EDropdownItem>
                                    <EDropdownItem command="share" :disabled="item.userid != userId" divided>{{$L('共享')}}</EDropdownItem>
                                    <EDropdownItem command="delete" divided style="color:red">{{$L('删除')}}</EDropdownItem>
                                </EDropdownMenu>
                            </EDropdown>
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
                            <div v-else class="file-name" :title="item.name">{{item.name}}</div>
                        </li>
                    </ul>
                </div>
            </template>
        </div>

        <!--项目设置-->
        <Modal
            v-model="shareShow"
            :title="$L('共享设置')"
            :mask-closable="false">
            <Form ref="addProject" :model="shareInfo" label-width="auto" @submit.native.prevent>
                <FormItem prop="type" :label="$L('共享对象')">
                    <RadioGroup v-model="shareInfo.share">
                        <Radio :label="1">{{$L('所有人')}}</Radio>
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

        <!--查看修改文件-->
        <Drawer
            v-model="editShow"
            placement="bottom"
            :height="editHeight"
            :mask-closable="false"
            :mask-style="{backgroundColor:'rgba(0,0,0,0.7)'}">
            <FileContent v-if="editShowNum > 0" :parent-show="editShow" :file="editInfo"/>
        </Drawer>
    </div>
</template>

<script>
import {mapState} from "vuex";
import {sortBy} from "lodash";
import UserInput from "../../components/UserInput";
const FileContent = () => import('./components/FileContent');


export default {
    components: {UserInput, FileContent},
    data() {
        return {
            loadIng: 0,
            searchKey: '',
            searchTimeout: null,

            pid: this.$store.state.method.getStorageInt("fileOpenPid"),
            shearId: 0,

            types: [
                {value: 'folder', name: "目录"},
                {value: 'document', name: "文本"},
                {value: 'mind', name: "脑图"},
                {value: 'sheet', name: "表格"},
                {value: 'flow', name: "流程图"},
            ],

            tableHeight: 500,
            tableMode: this.$store.state.method.getStorageBoolean("fileTableMode"),
            columns: [],

            shareShow: false,
            shareInfo: {},
            shareLoad: 0,

            editShow: false,
            editShowNum: 0,
            editHeight: 0,
            editInfo: {},
        }
    },

    mounted() {
        this.tableHeight = window.innerHeight - 160;
        this.editHeight = window.innerHeight - 40;
    },

    activated() {
        this.$store.dispatch("websocketPath", "file");
        this.getFileList();
    },

    computed: {
        ...mapState(['userId', 'userInfo', 'files']),

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
                let file = files.find(({id}) => id == pid);
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
                this.editShowNum++;
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
                    resizable: true,
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
                            // 编辑
                            array.push(h('QuickEdit', {
                                props: {
                                    value: row.name,
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
                                h('AutoTip', row.name)
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
                        let type = this.types.find(({value}) => value == row.type);
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

        openFile(item) {
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
                this.editHeight = window.innerHeight - 40;
                this.editInfo = item;
                this.editShow = true;
            }
        },

        clickRow(row) {
            this.dropFile(row, 'open');
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
                    document.getElementById('input_' + id).focus();
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
        }
    }
}
</script>
