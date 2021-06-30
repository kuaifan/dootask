<template>
    <div class="page-file">
        <PageTitle :title="$L('文件')"/>
        <div class="file-wrapper">
            <div class="file-head">
                <h1 @click="pid=0">{{$L('文件')}}</h1>
                <div class="file-search">
                    <i class="iconfont">&#xe6f8;</i>
                </div>
                <div class="file-add">
                    <EDropdown
                        trigger="click"
                        placement="bottom"
                        @command="addFile">
                        <i class="iconfont">&#xe6f2;</i>
                        <EDropdownMenu slot="dropdown" class="page-file-dropdown-menu">
                            <EDropdownItem v-for="(type, key) in types" :key="key" :command="type.value">
                                <div :class="['file-item ' + type.value]">新建{{$L(type.name)}}</div>
                            </EDropdownItem>
                        </EDropdownMenu>
                    </EDropdown>
                </div>
            </div>
            <div v-if="files.length == 0" class="file-no">
                <i class="iconfont">&#xe60b;</i>
                <p>{{$L('没有任何文件')}}</p>
            </div>
            <div v-else class="file-list">
                <ul class="clearfix">
                    <li v-for="item in folderList" :class="item.type" @click="openFile(item)">
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
                        <div v-else class="file-name">{{item.name}}</div>
                    </li>
                    <li v-for="item in fileList" :class="item.type" @click="openFile(item)">
                        <div class="file-icon"></div>
                        <div v-if="item._edit" class="file-input">
                            <Input
                                :ref="'input_' + item.id"
                                v-model="item.newname"
                                size="small"
                                @on-blur="onBlur(item)"
                                @on-enter="onEnter(item)"/>
                            <div v-if="item._load" class="file-load"><Loading/></div>
                        </div>
                        <div v-else class="file-name">{{item.name}}</div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import {sortBy} from "lodash";

export default {
    data() {
        return {
            loadIng: 0,
            pid: 0,

            types: [
                {value: 'folder', name: "目录"},
                {value: 'document', name: "文本"},
                {value: 'mind', name: "脑图"},
                {value: 'sheet', name: "表格"},
                {value: 'flow', name: "流程图"},
            ],
            files: [],
        }
    },

    mounted() {

    },

    destroyed() {

    },

    computed: {
        ...mapState(['userInfo']),

        folderList() {
            return sortBy(this.files.filter(({type, pid}) => pid == this.pid && type == 'folder'), 'name')
        },

        fileList() {
            return sortBy(this.files.filter(({type, pid}) => pid == this.pid && type != 'folder'), 'name')
        }
    },

    watch: {
        pid: {
            handler() {
                this.getLists();
            },
            immediate: true
        }
    },

    methods: {
        getLists() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'file/lists',
                data: {
                    pid: this.pid,
                },
            }).then(({data}) => {
                this.loadIng--;
                this.saveFile(data);
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.loadIng--;
            })
        },

        saveFile(file) {
            if ($A.isArray(file)) {
                file.forEach((item) => {
                    this.saveFile(item);
                });
                return;
            }
            let index = this.files.findIndex(({id}) => id == file.id);
            if (index > -1) {
                this.files.splice(index, 1, file);
            } else {
                this.files.push(file)
            }
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
            this.$nextTick(() => {
                this.$refs['input_' + id][0].focus({
                    cursor: 'all'
                })
            })
        },

        openFile(item) {
            if (item.type == 'folder') {
                this.pid = item.id;
            }
        },

        onBlur(item) {
            if (!item.newname) {
                this.files = this.files.filter(({id}) => id != item.id);
            }
        },

        onEnter(item) {
            if (!item.newname) {
                this.files = this.files.filter(({id}) => id != item.id);
            } else {
                if (item._load) {
                    return;
                }
                this.$set(item, '_load', true);
                this.$store.dispatch("call", {
                    url: 'file/add',
                    data: {
                        pid: item.pid,
                        name: item.newname,
                        type: item.type,
                    },
                }).then(({data, msg}) => {
                    $A.messageSuccess(msg)
                    this.$set(item, '_load', false);
                    this.$set(item, '_edit', false);
                    this.files = this.files.filter(({id}) => id != item.id);
                    this.saveFile(data);
                }).catch(({msg}) => {
                    $A.modalError(msg)
                    this.$set(item, '_edit', false);
                    this.files = this.files.filter(({id}) => id != item.id);
                })
            }
        }
    }
}
</script>
