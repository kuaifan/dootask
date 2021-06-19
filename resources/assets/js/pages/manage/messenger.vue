<template>
    <div class="page-messenger">
        <PageTitle>{{ $L('消息') }}</PageTitle>
        <div class="messenger-wrapper">
            <div class="messenger-select">
                <div class="messenger-search">
                    <div class="search-wrapper">
                        <Input prefix="ios-search" v-model="dialogKey" :placeholder="$L('搜索...')" clearable />
                    </div>
                </div>
                <div v-if="tabActive==='dialog'" class="messenger-nav">
                    <p :class="{active:dialogType==''}" @click="dialogType=''">{{$L('全部')}}</p>
                    <p :class="{active:dialogType=='project'}" @click="dialogType='project'">{{$L('项目')}}</p>
                    <p :class="{active:dialogType=='task'}" @click="dialogType='task'">{{$L('任务')}}</p>
                    <p :class="{active:dialogType=='user'}" @click="dialogType='user'">{{$L('个人')}}</p>
                </div>
                <div ref="list" class="messenger-list overlay-y">
                    <ul v-if="tabActive==='dialog'" class="dialog">
                        <li
                            v-for="(dialog, key) in dialogLists"
                            :key="key"
                            :class="{active: dialog.id == dialogId}"
                            @click="openDialog(dialog, true)">
                            <template v-if="dialog.type=='group'">
                                <i v-if="dialog.group_type=='project'" class="iconfont icon-avatar project">&#xe6f9;</i>
                                <i v-else-if="dialog.group_type=='task'" class="iconfont icon-avatar task">&#xe6f4;</i>
                                <Icon v-else class="icon-avatar" type="ios-people" />
                            </template>
                            <div v-else-if="dialog.dialog_user" class="user-avatar"><UserAvatar :userid="dialog.dialog_user.userid" :size="42" hide-icon-menu/></div>
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
                        <li v-if="dialogLoad > 0" class="loading"><Loading/></li>
                    </ul>
                    <ul v-else class="contacts">
                        <li v-for="(users, label) in contactsLists">
                            <div class="label">{{label}}</div>
                            <ul>
                                <li v-for="(user, index) in users" :key="index" @click="openContacts(user)">
                                    <div class="avatar"><UserAvatar :userid="user.userid" :size="30" hide-icon-menu/></div>
                                    <div class="nickname">{{user.nickname}}</div>
                                </li>
                            </ul>
                        </li>
                        <li v-if="contactsLoad > 0" class="loading"><Loading/></li>
                    </ul>
                </div>
                <div class="messenger-menu">
                    <Icon @click="tabActive='dialog'" :class="{active:tabActive==='dialog'}" type="ios-chatbubbles" />
                    <Icon @click="tabActive='contacts'" :class="{active:tabActive==='contacts'}" type="md-person" />
                </div>
            </div>

            <div class="messenger-msg">
                <DialogWrapper v-if="dialogId > 0" @on-active="scrollIntoActive"/>
                <div v-else class="dialog-no">
                    <div class="dialog-no-icon"><Icon type="ios-chatbubbles" /></div>
                    <div class="dialog-no-text">{{$L('选择一个会话开始聊天')}}</div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import DialogWrapper from "./components/DialogWrapper";

export default {
    components: {DialogWrapper},
    data() {
        return {
            tabActive: 'dialog',

            dialogLoad: 0,
            dialogKey: '',
            dialogType: '',
            dialogMounted: false,

            contactsLoad: 0,
            contactsLists: null,
        }
    },

    mounted() {
        this.dialogLoad++;
        this.$store.dispatch("getDialogList").then(() => {
            this.dialogLoad--;
            this.dialogMounted = true;
            this.openDialogStorage();
        }).catch(() => {
            this.dialogLoad--;
            this.dialogMounted = true;
            this.openDialogStorage();
        });
    },

    activated() {
        if (this.dialogMounted) {
            this.$store.dispatch("getDialogList");
            this.openDialogStorage();
        }
    },

    computed: {
        ...mapState(['userId', 'dialogId', 'dialogList']),

        dialogLists() {
            const {dialogType, dialogKey} = this;
            if (dialogType == '' && dialogKey == '') {
                return this.dialogList;
            }
            return this.dialogList.filter(({name, type, group_type, last_msg}) => {
                if (dialogType) {
                    switch (dialogType) {
                        case 'project':
                        case 'task':
                            if (group_type != dialogType) {
                                return false;
                            }
                            break;
                        case 'user':
                            if (type != 'user') {
                                return false;
                            }
                            break;
                        default:
                            return false;
                    }
                }
                if (dialogKey) {
                    let existName = $A.strExists(name, dialogKey);
                    let existMsg = last_msg && last_msg.type === 'text' && $A.strExists(last_msg.msg.text, dialogKey);
                    if (!existName && !existMsg) {
                        return false;
                    }
                }
                return true;
            })
        },
    },

    watch: {
        tabActive(val) {
            if (val && this.contactsLists === null) {
                this.getContactsList();
            }
        }
    },

    methods: {
        openDialog(dialog, smooth) {
            this.$store.state.method.setStorage("messengerDialogId", dialog.id)
            this.$store.dispatch("getDialogMsgList", dialog.id);
            this.scrollIntoActive(smooth);
        },

        openDialogStorage() {
            let tmpId = this.$store.state.method.getStorageInt("messengerDialogId")
            if (tmpId > 0) {
                const dialog = this.dialogList.find(({id}) => id === tmpId);
                dialog && this.openDialog(dialog, false);
            }
        },

        openContacts(user) {
            this.tabActive = 'dialog';
            this.$store.dispatch("openDialogUserid", user.userid).then(() => {
                this.scrollIntoActive()
            });
        },

        getContactsList() {
            if (this.contactsLists === null) {
                this.contactsLists = {};
            }
            this.contactsLoad++;
            this.$store.dispatch("call", {
                url: 'users/search',
                data: {
                    take: 50
                },
            }).then(({data}) => {
                this.contactsLoad--;
                data.some((user) => {
                    if (user.userid === this.userId) {
                        return false;
                    }
                    let az = user.az ? user.az.toUpperCase() : "#";
                    if (typeof this.contactsLists[az] === "undefined") this.contactsLists[az] = [];
                    //
                    let index = this.contactsLists[az].findIndex(({userid}) => userid === user.userid);
                    if (index > -1) {
                        this.contactsLists[az].splice(index, 1, user);
                    } else {
                        this.contactsLists[az].push(user);
                    }
                });
            }).catch(() => {
                this.contactsLoad--;
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

        lastMsgReadDone(data) {
            if ($A.isJson(data)) {
                const {userid, percentage} = data;
                if (userid === this.userId) {
                    return percentage === 100 ? 'md-done-all' : 'md-checkmark';
                }
            }
            return null;
        },

        scrollIntoActive(smooth) {
            this.$nextTick(() => {
                if (this.$refs.list) {
                    let active = this.$refs.list.querySelector(".active")
                    if (active) {
                        scrollIntoView(active, {
                            behavior: smooth === true ? 'smooth' : 'instant',
                            scrollMode: 'if-needed',
                        });
                    } else {
                        let dialog = this.dialogList.find(({id}) => id == this.dialogId)
                        if (dialog && this.dialogType) {
                            this.dialogType = '';
                            this.$nextTick(() => {
                                let active = this.$refs.list.querySelector(".active")
                                if (active) {
                                    scrollIntoView(active, {
                                        behavior: smooth === true ? 'smooth' : 'instant',
                                        scrollMode: 'if-needed',
                                    });
                                }
                            });
                        }
                    }
                }
            })
        }
    }
}
</script>
