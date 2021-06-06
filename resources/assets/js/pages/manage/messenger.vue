<template>
    <div class="messenger">
        <PageTitle>{{ $L('消息') }}</PageTitle>
        <div class="messenger-wrapper">

            <div class="messenger-select">
                <div class="messenger-search">
                    <div class="search-wrapper">
                        <Input prefix="ios-search" v-model="dialogKey" :placeholder="$L('搜索...')" clearable />
                    </div>
                </div>
                <div class="messenger-list overlay-y">
                    <ul>
                        <li
                            v-for="(dialog, key) in dialogLists"
                            :key="key"
                            :class="{active: dialog.id == dialogId}"
                            @click="openDialog(dialog)">
                            <Icon v-if="dialog.type=='group'" class="group-avatar" type="ios-people" />
                            <UserAvatar v-else-if="dialog.dialog_user" :userid="dialog.dialog_user.userid" :size="46"/>
                            <div class="dialog-box">
                                <div class="dialog-title">
                                    <span>{{dialog.name}}</span>
                                    <em v-if="dialog.last_at">{{formatTime(dialog.last_at)}}</em>
                                </div>
                                <div class="dialog-text">{{formatLastMsg(dialog.last_msg)}}</div>
                            </div>
                            <Badge class="dialog-num" :count="dialog.unread"/>
                        </li>
                    </ul>
                </div>
                <div class="messenger-menu">
                    <Icon class="active" type="ios-chatbubbles" />
                    <Icon type="md-person" />
                </div>
            </div>

            <div class="messenger-msg">
                <DialogWrapper v-if="dialogId > 0"/>
                <div v-else class="dialog-no">
                    <div class="dialog-no-icon"><Icon type="ios-chatbubbles" /></div>
                    <div class="dialog-no-text">{{$L('选择一个会话开始聊天')}}</div>
                </div>
            </div>

        </div>
    </div>
</template>

<style lang="scss" scoped>
:global {
    .messenger {
        display: flex;
    }
}
</style>

<script>
import {mapState} from "vuex";
import DialogWrapper from "./components/DialogWrapper";

export default {
    components: {DialogWrapper},
    data() {
        return {
            dialogLoad: 0,
            dialogKey: '',
            dialogList: [],
        }
    },

    mounted() {
        this.getDialogLists();
    },

    computed: {
        ...mapState(['dialogId', 'wsMsg']),

        dialogLists() {
            const {dialogKey} = this;
            if (dialogKey == '') {
                return this.dialogList;
            }
            return this.dialogList.filter(({name, last_msg}) => {
                if ($A.strExists(name, dialogKey)) {
                    return true;
                }
                if (last_msg && last_msg.type === 'text' && $A.strExists(last_msg.msg.text, dialogKey)) {
                    return true;
                }
                return false;
            })
        },
    },

    watch: {
        /**
         * 收到新消息
         * @param msg
         */
        wsMsg(msg) {
            const {type, data} = msg;
            if (type === "dialog") {
                if (this.dialogId == data.dialog_id) {
                    return;
                }
                let dialog = this.dialogList.find(({id}) => id == data.dialog_id);
                if (dialog) {
                    this.$set(dialog, 'unread', dialog.unread + 1);
                    this.$set(dialog, 'last_msg', data);
                } else {
                    this.getDialogOne(data.dialog_id)
                }
            }
        },

        /**
         * 打开对话，标记已读
         * @param dialog_id
         */
        dialogId(dialog_id) {
            let dialog = this.dialogList.find(({id}) => id == dialog_id);
            if (dialog) {
                this.$set(dialog, 'unread', 0);
            }
        }
    },

    methods: {
        openDialog(dialog) {
            this.$store.commit('getDialogMsg', dialog.id);
        },

        getDialogLists() {
            this.dialogLoad++;
            $A.apiAjax({
                url: 'dialog/lists',
                complete: () => {
                    this.dialogLoad--;
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        this.dialogList = data.data;
                    }
                }
            });
        },

        getDialogOne(dialog_id) {
            $A.apiAjax({
                url: 'dialog/one',
                data: {
                    dialog_id,
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        let index = this.dialogList.findIndex(({id}) => id == data.id);
                        if (index > -1) {
                            this.dialogList.splice(index, 1, data);
                        } else {
                            this.dialogList.unshift(data)
                        }
                    }
                }
            });
        },

        formatTime(date) {
            let time = Math.round(new Date(date).getTime() / 1000),
                string = '';
            if ($A.formatDate('Ymd') === $A.formatDate('Ymd', time)) {
                string = $A.formatDate('H:i', time)
            } else if ($A.formatDate('Y') === $A.formatDate('Y', time)) {
                string = $A.formatDate('m-d', time)
            } else {
                string = $A.formatDate('Y-m-d', time)
            }
            return string || '';
        },

        formatLastMsg(data) {
            if ($A.isJson(data)) {
                switch (data.type) {
                    case 'text':
                        return data.msg.text
                    case 'file':
                        if (data.msg.type == 'img') {
                            return '[' + this.$L('图片') + ']'
                        }
                        return '[' + this.$L('文件') + '] ' + data.msg.name
                    default:
                        return '[' + this.$L('未知的消息') + ']'
                }
            }
            return '';
        },
    }
}
</script>
