<template>
    <div class="page-messenger">
        <PageTitle :title="$L(tabActive==='dialog' ? '消息' : '通讯录')"/>
        <div class="messenger-wrapper">
            <div class="messenger-select">
                <div class="messenger-search">
                    <div class="search-wrapper">
                        <Input v-if="tabActive==='dialog'" v-model="dialogKey" :placeholder="$L(loadDialogs ? '更新中...' : '搜索消息')" clearable >
                            <div class="search-pre" slot="prefix">
                                <Loading v-if="loadDialogs"/>
                                <Icon v-else type="ios-search" />
                            </div>
                        </Input>
                        <Input v-else prefix="ios-search" v-model="contactsKey" :placeholder="$L('搜索联系人')" clearable />
                    </div>
                </div>
                <div v-if="tabActive==='dialog' && !dialogKey" class="messenger-nav">
                    <p
                        v-for="(item, key) in dialogType"
                        :key="key"
                        :class="{active:dialogActive==item.type}"
                        @click="onActive(item.type)">
                        <Badge class="nav-num" :count="msgUnread(item.type)"/>
                        {{$L(item.name)}}
                    </p>
                </div>
                <ScrollerY
                    ref="list"
                    class="messenger-list"
                    :class="overlayClass"
                    @on-scroll="listScroll"
                    static>
                    <ul
                        v-if="tabActive==='dialog'"
                        ref="dialogWrapper"
                        class="dialog" >
                        <li v-if="dialogList.length === 0" class="nothing">
                            {{$L(dialogKey ? `没有任何与"${dialogKey}"相关的对话` : `没有任何对话`)}}
                        </li>
                        <li
                            v-else
                            v-for="(dialog, key) in dialogList"
                            :ref="`dialog_${dialog.id}`"
                            :key="key"
                            :class="{
                                top: dialog.top_at,
                                active: dialog.id == dialogId,
                                operate: dialog.id == topOperateItem.id && topOperateVisible,
                                completed: $A.dialogCompleted(dialog)
                            }"
                            @click="openDialog(dialog.id)"
                            @contextmenu.prevent.stop="handleRightClick($event, dialog)">
                            <template v-if="dialog.type=='group'">
                                <i v-if="dialog.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                                <i v-else-if="dialog.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                <Icon v-else class="icon-avatar" type="ios-people" />
                            </template>
                            <div v-else-if="dialog.dialog_user" class="user-avatar"><UserAvatar :userid="dialog.dialog_user.userid" :size="42"/></div>
                            <Icon v-else class="icon-avatar" type="md-person" />
                            <div class="dialog-box">
                                <div class="dialog-title">
                                    <div v-if="$A.getDialogMention(dialog) > 0" class="mention">[@{{$A.getDialogMention(dialog)}}]</div>
                                    <template v-for="tag in $A.dialogTags(dialog)" v-if="tag.color != 'success'">
                                        <Tag :color="tag.color" :fade="false">{{$L(tag.text)}}</Tag>
                                    </template>
                                    <span>{{dialog.name}}</span>
                                    <Icon v-if="dialog.type == 'user' && lastMsgReadDone(dialog.last_msg)" :type="lastMsgReadDone(dialog.last_msg)"/>
                                    <em v-if="dialog.last_at">{{$A.formatTime(dialog.last_at)}}</em>
                                </div>
                                <div class="dialog-text no-dark-content">
                                    <template v-if="dialog.type=='group' && dialog.last_msg">
                                        <div v-if="dialog.last_msg.userid == userId" class="last-self">{{$L('你')}}</div>
                                        <UserAvatar v-else :userid="dialog.last_msg.userid" :show-name="true" :show-icon="false" tooltip-disabled/>
                                    </template>
                                    <div class="last-text">{{formatLastMsg(dialog.last_msg)}}</div>
                                </div>
                            </div>
                            <Badge class="dialog-num" :count="$A.getDialogUnread(dialog)"/>
                            <div class="dialog-split"></div>
                        </li>
                    </ul>
                    <ul v-else class="contacts">
                        <template v-if="contactsFilter.length === 0">
                            <li v-if="contactsLoad > 0" class="loading"><Loading/></li>
                            <li v-else class="nothing">
                                {{$L(contactsKey ? `没有任何与"${contactsKey}"相关的联系人` : `没有任何联系人`)}}
                            </li>
                        </template>
                        <template v-else>
                            <li v-for="items in contactsList">
                                <div class="label">{{items.az}}</div>
                                <ul>
                                    <li v-for="(user, index) in items.list" :key="index" @click="openContacts(user)">
                                        <div class="avatar"><UserAvatar :userid="user.userid" :size="30"/></div>
                                        <div class="nickname">{{user.nickname}}</div>
                                    </li>
                                </ul>
                            </li>
                            <li class="loaded">{{$L('共' + contactsFilter.length + '位联系人')}}</li>
                        </template>
                    </ul>
                    <div class="top-operate" :style="topOperateStyles">
                        <Dropdown
                            trigger="custom"
                            :visible="topOperateVisible"
                            transfer-class-name="page-file-dropdown-menu"
                            @on-clickoutside="handleClickTopOperateOutside"
                            transfer>
                            <DropdownMenu slot="list">
                                <DropdownItem @click.native="handleTopClick">
                                    {{ $L(topOperateItem.top_at ? '取消置顶' : '置顶该聊天') }}
                                </DropdownItem>
                                <DropdownItem @click.native="updateRead('read')" v-if="$A.getDialogUnread(topOperateItem) > 0">
                                    {{ $L('标记已读') }}
                                </DropdownItem>
                                <DropdownItem @click.native="updateRead('unread')" v-else>
                                    {{ $L('标记未读') }}
                                </DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                    </div>
                </ScrollerY>
                <div class="messenger-menu">
                    <div class="menu-icon">
                        <Icon @click="tabActive='dialog'" :class="{active:tabActive==='dialog'}" type="ios-chatbubbles" />
                        <Badge class="menu-num" :count="msgUnread('all')"/>
                    </div>
                    <div class="menu-icon">
                        <Icon @click="tabActive='contacts'" :class="{active:tabActive==='contacts'}" type="md-person" />
                    </div>
                </div>
            </div>

            <transition :name="windowMax768 ? 'mobile-dialog' : ''">
                <div v-if="dialogId || !windowMax768" class="messenger-msg">
                    <div class="msg-dialog-bg">
                        <div class="msg-dialog-bg-icon"><Icon type="ios-chatbubbles" /></div>
                        <div class="msg-dialog-bg-text">{{$L('选择一个会话开始聊天')}}</div>
                    </div>
                    <DialogWrapper v-if="dialogId > 0" :dialogId="dialogId" @on-active="scrollIntoActive"/>
                </div>
            </transition>

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

            contactsKey: '',
            contactsLoad: 0,
            contactsData: null,
            contactsCurrentPage: 1,
            contactsHasMorePages: false,

            topOperateStyles: {},
            topOperateVisible: false,
            topOperateItem: {},
        }
    },

    activated() {
        this.updateDialogs(1000);
    },

    deactivated() {
        this.updateDialogs(-1);
    },

    computed: {
        ...mapState(['userId', 'cacheDialogs', 'loadDialogs', 'windowMax768']),

        dialogId() {
            const {dialogId} = this.$route.params;
            if (['dialog', 'contacts'].includes(dialogId)) {
                this.tabActive = dialogId
            }
            return parseInt(/^\d+$/.test(dialogId) ? dialogId : 0);
        },

        dialogList() {
            const {dialogActive, dialogKey} = this;
            if (dialogActive == '' && dialogKey == '') {
                return this.cacheDialogs.filter(dialog => this.filterDialog(dialog)).sort((a, b) => {
                    if (a.top_at || b.top_at) {
                        return $A.Date(b.top_at) - $A.Date(a.top_at);
                    }
                    return $A.Date(b.last_at) - $A.Date(a.last_at);
                });
            }
            return this.cacheDialogs.filter(dialog => {
                if (!this.filterDialog(dialog)) {
                    return false;
                }
                if (dialogKey) {
                    let existName = $A.strExists(dialog.name, dialogKey);
                    let existMsg = dialog.last_msg && dialog.last_msg.type === 'text' && $A.strExists(dialog.last_msg.msg.text, dialogKey);
                    if (!existName && !existMsg) {
                        return false;
                    }
                } else if (dialogActive) {
                    switch (dialogActive) {
                        case 'project':
                        case 'task':
                            if (dialog.group_type != dialogActive) {
                                return false;
                            }
                            break;
                        case 'user':
                            if (dialog.type != 'user') {
                                return false;
                            }
                            break;
                        default:
                            return false;
                    }
                }
                return true;
            }).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.Date(b.top_at) - $A.Date(a.top_at);
                }
                return $A.Date(b.last_at) - $A.Date(a.last_at);
            })
        },

        contactsFilter() {
            const {contactsData, contactsKey} = this;
            if (contactsData === null) {
                return [];
            }
            if (contactsKey) {
                return contactsData.filter(item => $A.strExists(`${item.email}||${item.nickname}`, contactsKey))
            }
            return contactsData;
        },

        contactsList() {
            let list = [];
            this.contactsFilter.some(user => {
                let az = user.az ? user.az.toUpperCase() : "#";
                let item = list.find(item => item.az = az);
                if (item) {
                    if (item.list.findIndex(({userid}) => userid == user.userid) === -1) {
                        item.list.push(user)
                    }
                } else {
                    list.push({
                        az,
                        list: [user]
                    })
                }
            })
            return list;
        },

        msgUnread() {
            return function (type) {
                let num = 0;
                this.cacheDialogs.some((dialog) => {
                    let unread = $A.getDialogUnread(dialog);
                    if (unread) {
                        switch (type) {
                            case 'project':
                            case 'task':
                                if (type == dialog.group_type) {
                                    num += unread;
                                }
                                break;
                            case 'user':
                                if (type == dialog.type) {
                                    num += unread;
                                }
                                break;
                            default:
                                num += unread;
                                break;
                        }
                    }
                });
                return num;
            }
        },

        overlayClass() {
            return {
                'overlay-y': true,
                'overlay-none': this.topOperateVisible === true,
            }
        }
    },

    watch: {
        dialogKey(val) {
            switch (val) {
                case 'vlog.open':
                case 'vlog:open':
                    $A.setStorage("vlog::open", "open");
                    $A.reloadUrl();
                    break;
                case 'vlog.close':
                case 'vlog:close':
                    $A.setStorage("vlog::open", "close");
                    $A.reloadUrl();
                    break;
            }
        },
        contactsKey(val) {
            if (val == '') {
                return;
            }
            this.contactsLoad++;
            setTimeout(() => {
                if (this.contactsKey == val) {
                    this.getContactsList(1);
                }
                this.contactsLoad--;
            }, 600);
        },
        tabActive: {
            handler(val) {
                if (val == 'contacts') {
                    this.contactsData === null && this.getContactsList(1);
                } else {
                    this.updateDialogs(1000);
                }
            },
            immediate: true
        },
        dialogId: {
            handler(id) {
                if (id > 0) {
                    $A.setStorage("messenger::dialogId", id);
                    this.scrollIntoActive()
                }
            },
            immediate: true
        },
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
            this.topOperateVisible = false;
        },

        onActive(type) {
            if (this.dialogActive == type) {
                // 再次点击滚动到未读条目
                const dialog = this.dialogList.find(dialog => $A.getDialogUnread(dialog) > 0)
                if (dialog) {
                    $A.scrollToView(this.$refs[`dialog_${dialog.id}`][0], {
                        behavior: 'smooth',
                        scrollMode: 'if-needed',
                    })
                }
            }
            this.dialogActive = type
        },

        openDialog(dialogId) {
            if (dialogId > 0) {
                this.goForward({name: 'manage-messenger', params: {dialogId}});
            } else {
                this.goForward({name: 'manage-messenger'});
            }
        },

        openContacts(user) {
            if (this.$isDesktop) {
                this.tabActive = 'dialog';
            }
            this.$store.dispatch("openDialogUserid", user.userid).then(({data}) => {
                this.openDialog(data.id)
            });
        },

        filterDialog(dialog) {
            if ($A.getDialogUnread(dialog) > 0 || dialog.id == this.dialogId || dialog.top_at) {
                return true
            }
            if (dialog.name === undefined) {
                return false;
            }
            if (!dialog.last_at) {
                return false;
            }
            if (dialog.type == 'group') {
                if (['project', 'task'].includes(dialog.group_type) && $A.isJson(dialog.group_info)) {
                    if (dialog.group_type == 'task' && dialog.group_info.complete_at) {
                        // 已完成5天后隐藏对话
                        let time = Math.max($A.Date(dialog.last_at, true), $A.Date(dialog.group_info.complete_at, true))
                        if (5 * 86400 + time < $A.Time()) {
                            return false
                        }
                    }
                    if (dialog.group_info.deleted_at) {
                        // 已删除2天后隐藏对话
                        let time = Math.max($A.Date(dialog.last_at, true), $A.Date(dialog.group_info.deleted_at, true))
                        if (2 * 86400 + time < $A.Time()) {
                            return false
                        }
                    }
                    if (dialog.group_info.archived_at) {
                        // 已归档3天后隐藏对话
                        let time = Math.max($A.Date(dialog.last_at, true), $A.Date(dialog.group_info.archived_at, true))
                        if (3 * 86400 + time < $A.Time()) {
                            return false
                        }
                    }
                }
            }
            return true;
        },

        getContactsList(page) {
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
                if (this.contactsData === null) {
                    this.contactsData = [];
                }
                data.data.some((user) => {
                    if (user.userid === this.userId) {
                        return false;
                    }
                    if (this.contactsData.findIndex(item => item.userid == user.userid) === -1) {
                        this.contactsData.push(user)
                    }
                });
                this.contactsCurrentPage = data.current_page;
                this.contactsHasMorePages = data.current_page < data.last_page;
            }).catch(() => {
                this.contactsHasMorePages = false;
            }).finally(_ => {
                this.contactsLoad--;
            });
        },

        formatLastMsg(data) {
            if ($A.isJson(data)) {
                switch (data.type) {
                    case 'text':
                        return $A.getMsgTextPreview(data.msg.text)
                    case 'file':
                        if (data.msg.type == 'img') {
                            return `[${this.$L('图片')}]`
                        }
                        return `[${this.$L('文件')}] ${data.msg.name}`
                    default:
                        return `[${this.$L('未知的消息')}]`
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

        scrollIntoActive() {
            this.$nextTick(() => {
                if (this.$isDesktop && this.$refs.list) {
                    let active = this.$refs.list.querySelector(".active")
                    if (active) {
                        $A.scrollToView(active, {
                            behavior: 'instant',
                            scrollMode: 'if-needed',
                        });
                    } else {
                        let dialog = this.cacheDialogs.find(({id}) => id == this.dialogId)
                        if (dialog && this.dialogActive) {
                            this.dialogActive = '';
                            this.$nextTick(() => {
                                let active = this.$refs.list.querySelector(".active")
                                if (active) {
                                    $A.scrollToView(active, {
                                        behavior: 'instant',
                                        scrollMode: 'if-needed',
                                    });
                                }
                            });
                        }
                    }
                }
            })
        },

        handleRightClick(event, item) {
            this.handleClickTopOperateOutside();
            this.topOperateItem = $A.isJson(item) ? item : {};
            this.$nextTick(() => {
                const dialogWrap = this.$refs.dialogWrapper;
                const dialogBounding = dialogWrap.getBoundingClientRect();
                this.topOperateStyles = {
                    left: `${event.clientX - dialogBounding.left}px`,
                    top: `${event.clientY - dialogBounding.top + 100 - this.$refs.list.scrollInfo().scrollY}px`
                };
                this.topOperateVisible = true;
            })
        },

        handleClickTopOperateOutside() {
            this.topOperateVisible = false;
        },

        handleTopClick() {
            this.$store.dispatch("call", {
                url: 'dialog/top',
                data: {
                    dialog_id: this.topOperateItem.id,
                },
            }).then(({data}) => {
                this.$store.dispatch("saveDialog", data);
                this.$nextTick(this.scrollIntoActive);
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        updateRead(type) {
            this.$store.dispatch("call", {
                url: 'dialog/msg/mark',
                data: {
                    dialog_id: this.topOperateItem.id,
                    type: type
                },
            }).then(({data}) => {
                this.$store.dispatch("saveDialog", data);
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        updateDialogs(timeout) {
            this.__updateDialogs && clearTimeout(this.__updateDialogs)
            if (timeout > -1) {
                this.__updateDialogs = setTimeout(_ => {
                    if (this.tabActive === 'dialog') {
                        this.$store.dispatch("getDialogs", true).catch(() => {
                        });
                    }
                }, timeout)
            }
        },
    }
}
</script>
