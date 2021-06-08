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
                            <Icon v-if="dialog.type=='group'" class="icon-avatar" type="ios-people" />
                            <div v-else-if="dialog.dialog_user" class="user-avatar"><UserAvatar :userid="dialog.dialog_user.userid" :size="46" hide-icon-menu/></div>
                            <Icon v-else class="icon-avatar" type="md-person" />
                            <div class="dialog-box">
                                <div class="dialog-title">
                                    <span>{{dialog.name}}</span>
                                    <Icon v-if="dialog.type == 'user' && lastMsgReadDone(dialog.last_msg)" :type="lastMsgReadDone(dialog.last_msg)"/>
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
            dialogKey: '',
            dialogLoad: 0,
        }
    },

    mounted() {
        this.dialogLoad++;
        this.$store.commit("getDialogList", () => {
            this.dialogLoad--;
            this.openDialogStorage();
        });
    },

    activated() {
        this.openDialogStorage();
    },

    computed: {
        ...mapState(['userId', 'dialogId', 'dialogList']),

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

    methods: {
        openDialog(dialog) {
            this.$store.state.method.setStorage('messengerDialogId', dialog.id)
            this.$store.commit('getDialogMsgList', dialog.id);
        },

        openDialogStorage() {
            let tmpId = this.$store.state.method.getStorageInt('messengerDialogId')
            if (tmpId > 0) {
                const dialog = this.dialogList.find(({id}) => id === tmpId);
                dialog && this.openDialog(dialog);
            }
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

        lastMsgReadDone(data) {
            if ($A.isJson(data)) {
                const {userid, percentage} = data;
                if (userid === this.userId) {
                    return percentage === 100 ? 'md-done-all' : 'md-checkmark';
                }
            }
            return null;
        }
    }
}
</script>
