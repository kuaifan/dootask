<template>
    <div class="page-messenger">
        <PageTitle :title="$L('消息')"/>
        <div class="messenger-wrapper">
            <div class="messenger-select" :class="{'show768-menu':dialogId == 0}">
                <div class="messenger-search">
                    <div class="search-wrapper">
                        <Input v-if="tabActive==='dialog'" prefix="ios-search" v-model="dialogKey" :placeholder="$L('搜索...')" clearable />
                        <Input v-else prefix="ios-search" v-model="contactsKey" :placeholder="$L('搜索...')" clearable />
                    </div>
                </div>
                <div v-if="tabActive==='dialog'" class="messenger-nav">
                    <p
                        v-for="(item, key) in dialogType"
                        :key="key"
                        :class="{active:dialogActive==item.type}"
                        @click="dialogActive=item.type">
                        <Badge class="nav-num" :count="msgUnread(item.type)"/>
                        {{$L(item.name)}}
                    </p>
                </div>
                <ScrollerY
                    ref="list"
                    class="messenger-list overlay-y"
                    @on-scroll="listScroll"
                    static>
                    <ul v-if="tabActive==='dialog'" class="dialog">
                        <li
                            v-for="(dialog, key) in dialogList"
                            :key="key"
                            :class="{active: dialog.id == dialogId}"
                            @click="openDialog(dialog, true)">
                            <template v-if="dialog.type=='group'">
                                <i v-if="dialog.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                                <i v-else-if="dialog.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                <Icon v-else class="icon-avatar" type="ios-people" />
                            </template>
                            <div v-else-if="dialog.dialog_user" class="user-avatar"><UserAvatar :userid="dialog.dialog_user.userid" :size="42"/></div>
                            <Icon v-else class="icon-avatar" type="md-person" />
                            <div class="dialog-box">
                                <div class="dialog-title">
                                    <span>{{dialog.name}}</span>
                                    <Icon v-if="dialog.type == 'user' && lastMsgReadDone(dialog.last_msg)" :type="lastMsgReadDone(dialog.last_msg)"/>
                                    <em v-if="dialog.last_at">{{$A.formatTime(dialog.last_at)}}</em>
                                </div>
                                <div class="dialog-text">{{formatLastMsg(dialog.last_msg)}}</div>
                            </div>
                            <Badge class="dialog-num" :count="dialog.unread"/>
                        </li>
                    </ul>
                    <ul v-else class="contacts">
                        <li v-for="(users, label) in contactsData">
                            <div class="label">{{label}}</div>
                            <ul>
                                <li v-for="(user, index) in users" :key="index" @click="openContacts(user)">
                                    <div class="avatar"><UserAvatar :userid="user.userid" :size="30"/></div>
                                    <div class="nickname">{{user.nickname}}</div>
                                </li>
                            </ul>
                        </li>
                        <li v-if="contactsLoad > 0" class="loading"><Loading/></li>
                        <li v-else-if="!contactsHasMorePages" class="loaded">{{$L('共' + contactsList.length + '位联系人')}}</li>
                    </ul>
                </ScrollerY>
                <div class="messenger-menu">
                    <Icon @click="tabActive='dialog'" :class="{active:tabActive==='dialog'}" type="ios-chatbubbles" />
                    <Icon @click="tabActive='contacts'" :class="{active:tabActive==='contacts'}" type="md-person" />
                </div>
            </div>

            <div class="messenger-msg">
                <div class="msg-dialog-bg">
                    <div class="msg-dialog-bg-icon"><Icon type="ios-chatbubbles" /></div>
                    <div class="msg-dialog-bg-text">{{$L('选择一个会话开始聊天')}}</div>
                </div>
                <DialogWrapper v-if="dialogId > 0" :dialogId="dialogId" @on-active="scrollIntoActive">
                    <div slot="inputBefore" class="dialog-back" @click="closeDialog">
                        <Icon type="md-arrow-back" />
                    </div>
                </DialogWrapper>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import DialogWrapper from "./components/DialogWrapper";
import ScrollerY from "../../components/ScrollerY";

export default {
    components: {ScrollerY, DialogWrapper},
    data() {
        return {
            tabActive: 'dialog',

            dialogType: [
                {type: '', name: '全部'},
                {type: 'project', name: '项目'},
                {type: 'task', name: '任务'},
                {type: 'user', name: '个人'},
            ],
            dialogActive: '',
            dialogKey: '',
            dialogId: 0,

            contactsKey: '',
            contactsLoad: 0,
            contactsList: [],
            contactsData: null,
            contactsCurrentPage: 1,
            contactsHasMorePages: false,
        }
    },

    activated() {
        this.openDialogStorage();
    },

    computed: {
        ...mapState(['userId', 'dialogs', 'dialogOpenId']),

        dialogList() {
            const {dialogActive, dialogKey} = this;
            if (dialogActive == '' && dialogKey == '') {
                return this.dialogs.filter(({name}) => name !== undefined);
            }
            return this.dialogs.filter(({name, type, group_type, last_msg}) => {
                if (name === undefined) {
                    return false;
                }
                if (dialogActive) {
                    switch (dialogActive) {
                        case 'project':
                        case 'task':
                            if (group_type != dialogActive) {
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

        msgUnread() {
            return function (type) {
                let num = 0;
                this.dialogs.map((dialog) => {
                    if (dialog.unread) {
                        switch (type) {
                            case 'project':
                            case 'task':
                                if (type == dialog.group_type) {
                                    num += dialog.unread;
                                }
                                break;
                            case 'user':
                                if (type == dialog.type) {
                                    num += dialog.unread;
                                }
                                break;
                            default:
                                num += dialog.unread;
                                break;
                        }
                    }
                });
                return num;
            }
        },
    },

    watch: {
        tabActive(val) {
            if (val && this.contactsData === null) {
                this.getContactsList(1);
            }
        },
        dialogOpenId(id) {
            this.dialogId = id;
        },
        contactsKey(val) {
            setTimeout(() => {
                if (this.contactsKey == val) {
                    this.contactsData = null;
                    this.getContactsList(1);
                }
            }, 600);
        }
    },

    methods: {
        listScroll(res) {
            switch (res.directionreal) {
                case 'up':
                    if (res.scrollE < 10) {
                        if (this.tabActive === 'contacts'
                            && this.contactsLoad == 0
                            && this.contactsHasMorePages) {
                            this.getContactsList(this.contactsCurrentPage + 1);
                        }
                    }
                    break;
            }
        },

        closeDialog() {
            this.dialogId = 0;
            $A.setStorage("messenger::dialogId", 0)
        },

        openDialog(dialog, smooth) {
            $A.setStorage("messenger::dialogId", dialog.id)
            this.dialogId = dialog.id;
            this.scrollIntoActive(smooth);
        },

        openDialogStorage() {
            this.dialogId = $A.getStorageInt("messenger::dialogId")
            if (this.dialogId > 0) {
                const dialog = this.dialogs.find(({id}) => id === this.dialogId);
                dialog && this.openDialog(dialog, false);
            }
        },

        openContacts(user) {
            this.tabActive = 'dialog';
            this.$store.dispatch("openDialogUserid", user.userid).then(() => {
                this.scrollIntoActive()
            });
        },

        getContactsList(page) {
            if (this.contactsData === null) {
                this.contactsData = {};
            }
            this.contactsLoad++;
            this.$store.dispatch("call", {
                url: 'users/search',
                data: {
                    keys: {
                        key: this.contactsKey
                    },
                    sorts: {
                        az: 'asc'
                    },
                    page: page,
                    pagesize: 50
                },
            }).then(({data}) => {
                this.contactsLoad--;
                data.data.some((user) => {
                    if (user.userid === this.userId) {
                        return false;
                    }
                    let az = user.az ? user.az.toUpperCase() : "#";
                    if (typeof this.contactsData[az] === "undefined") this.contactsData[az] = [];
                    //
                    let index = this.contactsData[az].findIndex(({userid}) => userid === user.userid);
                    if (index > -1) {
                        this.contactsData[az].splice(index, 1, user);
                    } else {
                        this.contactsData[az].push(user);
                        this.contactsList.push(user);
                    }
                });
                this.contactsCurrentPage = data.current_page;
                this.contactsHasMorePages = data.current_page < data.last_page;
            }).catch(() => {
                this.contactsLoad--;
                this.contactsHasMorePages = false;
            });
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
                        let dialog = this.dialogs.find(({id}) => id == this.dialogId)
                        if (dialog && this.dialogActive) {
                            this.dialogActive = '';
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
