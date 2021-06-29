<template>
    <div class="page-file">
        <PageTitle :title="$L('文件')"/>
        <div class="file-wrapper">
            <div class="file-head">
                <h1>{{$L('文件')}}</h1>
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
            <div v-if="files.length + temps.length == 0" class="file-no">
                <i class="iconfont">&#xe60b;</i>
                <p>{{$L('没有任何文件')}}</p>
            </div>
            <div v-else class="file-list">
                <ul class="clearfix">
                    <li v-for="item in folderList" :class="item.type">
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
                    <li v-for="item in fileList" :class="item.type">
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

export default {
    data() {
        return {
            types: [
                {value: 'folder', name: "目录"},
                {value: 'document', name: "文本"},
                {value: 'mind', name: "脑图"},
                {value: 'sheet', name: "表格"},
                {value: 'flow', name: "流程图"},
            ],
            files: [],
            temps: [],
        }
    },

    mounted() {

    },

    destroyed() {

    },

    computed: {
        ...mapState(['userInfo']),

        folderList() {
            const data = this.files.filter(({type}) => type == 'folder')
            return Object.assign(data, this.temps.filter(({type}) => type == 'folder'))
        },

        fileList() {
            const data = this.files.filter(({type}) => type != 'folder')
            return Object.assign(data, this.temps.filter(({type}) => type != 'folder'))
        }
    },

    watch: {

    },

    methods: {
        addFile(command) {
            let id = $A.randomString(8);
            this.temps.push({
                _edit: true,
                id: id,
                type: command,
                newname: this.$L('未命名')
            });
            this.$nextTick(() => {
                this.$refs['input_' + id][0].focus({
                    cursor: 'all'
                })
            })
        },

        onBlur(item) {
            this.onEnter(item);
        },

        onEnter(item) {
            if (item._load) {
                return;
            }
            if (!item.newname) {
                this.temps = this.temps.filter(({id}) => id != item.id);
            } else {
                this.$set(item, '_load', true);
                this.$store.dispatch("call", {
                    url: 'file/add',
                    data: {
                        name: item.newname,
                        type: item.type,
                    },
                }).then(({data, msg}) => {
                    $A.messageSuccess(msg)
                    this.$set(item, '_load', false);
                    this.$set(item, '_edit', false);
                    if (this.temps.find(({id}) => id == item.id)) {
                        this.temps = this.temps.filter(({id}) => id != item.id);
                        this.files.push(data);
                    } else {
                        Object.keys(data).forEach((key) => {
                            this.$set(item, key, data[key]);
                        })
                    }
                }).catch(({msg}) => {
                    $A.modalError(msg)
                    this.$set(item, '_edit', false);
                    this.temps = this.temps.filter(({id}) => id != item.id);
                })
            }
        }
    }
}
</script>
