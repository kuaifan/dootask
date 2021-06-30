<template>
    <div class="page-file">
        <PageTitle :title="$L('文件')"/>
        <div class="file-wrapper">
            <div class="file-head">
                <div class="file-nav">
                    <h1>{{$L('文件')}}</h1>
                </div>
                <div :class="['file-search', searchKey ? 'has-value' : '']">
                    <Input v-model="searchKey" suffix="ios-search" :placeholder="$L('搜索名称')"/>
                </div>
                <div class="file-add">
                    <EDropdown
                        trigger="click"
                        placement="bottom"
                        @command="addFile">
                        <i class="iconfont">&#xe6f2;</i>
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
                    <li v-else v-for="item in navigator" @click="pid=item.id"><span :title="item.name">{{item.name}}</span></li>
                </ul>
                <Button v-if="shearFile && shearFile.pid != pid" size="small" type="primary" @click="shearTo">
                    <div class="file-shear">
                        <span>{{$L('粘贴')}}</span>
                        "<em>{{shearFile.name}}</em>"
                    </div>
                </Button>
            </div>
            <div v-if="fileList.length == 0 && loadIng == 0" class="file-no">
                <i class="iconfont">&#xe60b;</i>
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
                                <EDropdownItem divided command="rename">{{$L('重命名')}}</EDropdownItem>
                                <EDropdownItem :disabled="item.type=='folder'" command="copy">{{$L('复制')}}</EDropdownItem>
                                <EDropdownItem command="shear">{{$L('剪切')}}</EDropdownItem>
                                <EDropdownItem divided command="delete" style="color:red">{{$L('删除')}}</EDropdownItem>
                            </EDropdownMenu>
                        </EDropdown>
                        <div class="file-icon"></div>
                        <div v-if="item._edit" class="file-input">
                            <Input
                                :ref="'input_' + item.id"
                                v-model="item.newname"
                                size="small"
                                :disabled="item._load"
                                @on-blur="onBlur(item)"
                                @on-enter="onEnter(item)"/>
                            <div v-if="item._load" class="file-load"><Loading/></div>
                        </div>
                        <div v-else class="file-name" :title="item.name">{{item.name}}</div>
                    </li>
                </ul>
            </div>
        </div>

        <!--查看修改文件-->
        <Drawer
            v-model="editShow"
            placement="bottom"
            :height="editHeight"
            :mask-closable="false"
            :mask-style="{backgroundColor:'rgba(0,0,0,0.7)'}">
            <FileContent v-if="editShow" :file="editInfo"/>
        </Drawer>
    </div>
</template>

<script>
import {mapState} from "vuex";
import {sortBy} from "lodash";
const FileContent = () => import('./components/FileContent');


export default {
    components: {FileContent},
    data() {
        return {
            loadIng: 0,
            searchKey: '',

            pid: 0,
            shearId: 0,

            types: [
                {value: 'folder', name: "目录"},
                {value: 'document', name: "文本"},
                {value: 'mind', name: "脑图"},
                {value: 'sheet', name: "表格"},
                {value: 'flow', name: "流程图"},
            ],

            editShow: false,
            editHeight: 0,
            editInfo: {},
        }
    },

    mounted() {
        this.editHeight = window.innerHeight - 40;
    },

    computed: {
        ...mapState(['userInfo', 'files']),

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
                }
            }
            return array;
        }
    },

    watch: {
        pid: {
            handler() {
                this.loadIng++;
                this.$store.dispatch("getFiles", this.pid).then(() => {
                    this.loadIng--;
                }).catch(({msg}) => {
                    $A.modalError(msg);
                    this.loadIng--;
                })
            },
            immediate: true
        }
    },

    methods: {
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
            this.$nextTick(() => {
                this.$refs['input_' + id][0].focus({
                    cursor: 'all'
                })
            })
        },

        openFile(item) {
            if (item._edit || item._load) {
                return;
            }
            if (item.type == 'folder') {
                this.searchKey = '';
                this.pid = item.id;
            } else {
                this.editHeight = window.innerHeight - 40;
                this.editShow = true;
                this.editInfo = item;
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
                    this.$nextTick(() => {
                        this.$refs['input_' + item.id][0].focus({
                            cursor: 'all'
                        })
                    })
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
        }
    }
}
</script>
