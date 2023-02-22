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
                    <div
                        v-for="(item, key) in dialogType"
                        :key="key"
                        :class="{active:dialogActive==item.type}"
                        @click="onActive(item.type)">
                        <Badge class="nav-num" :overflow-count="999" :count="msgUnread(item.type)"/>
                        <div class="nav-title">{{$L(item.name)}}</div>
                    </div>
                </div>
                <div v-if="$isEEUiApp && !appNotificationPermission" class="messenger-notify-permission" @click="onOpenAppSetting">
                    {{$L('未开启通知权限')}}<i class="taskfont">&#xe733;</i>
                </div>
                <ScrollerY
                    ref="list"
                    class="messenger-list"
                    :class="listClassName"
                    @on-scroll="listScroll"
                    static>
                    <ul
                        v-if="tabActive==='dialog'"
                        class="dialog">
                        <template v-if="dialogList.length === 0">
                            <li v-if="dialogLoad > 0" class="loading"><Loading/></li>
                            <li v-else class="nothing">
                                {{$L(dialogKey ? `没有任何与"${dialogKey}"相关的会话` : `没有任何会话`)}}
                            </li>
                        </template>
                        <li
                            v-else
                            v-for="(dialog, key) in dialogList"
                            :ref="`dialog_${dialog.id}`"
                            :key="key"
                            :data-id="dialog.id"
                            :class="dialogClass(dialog)"
                            @click="openDialog({
                                dialog_id: dialog.id,
                                search_msg_id: dialog.search_msg_id
                            })"
                            v-longpress="handleLongpress">
                            <template v-if="dialog.type=='group'">
                                <i v-if="dialog.group_type=='department'" class="taskfont icon-avatar department">&#xe75c;</i>
                                <i v-else-if="dialog.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                                <i v-else-if="dialog.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                <Icon v-else class="icon-avatar" type="ios-people" />
                            </template>
                            <div v-else-if="dialog.dialog_user" class="user-avatar"><UserAvatar :userid="dialog.dialog_user.userid" :size="42"/></div>
                            <Icon v-else class="icon-avatar" type="md-person" />
                            <div class="dialog-box">
                                <div class="dialog-title">
                                    <div v-if="dialog.todo_num" class="todo">[{{$L('待办')}}{{formatTodoNum(dialog.todo_num)}}]</div>
                                    <div v-if="$A.getDialogMention(dialog) > 0" class="mention">[@{{$A.getDialogMention(dialog)}}]</div>
                                    <div v-if="dialog.bot" class="taskfont bot">&#xe68c;</div>
                                    <template v-for="tag in $A.dialogTags(dialog)" v-if="tag.color != 'success'">
                                        <Tag :color="tag.color" :fade="false" @on-click="openDialog(dialog.id)">{{$L(tag.text)}}</Tag>
                                    </template>
                                    <span>{{dialog.name}}</span>
                                    <Icon v-if="dialog.type == 'user' && lastMsgReadDone(dialog.last_msg) && dialog.dialog_user.userid != userId" :type="lastMsgReadDone(dialog.last_msg)"/>
                                    <em v-if="dialog.last_at">{{$A.formatTime(dialog.last_at)}}</em>
                                </div>
                                <div class="dialog-text no-dark-content">
                                    <template v-if="dialog.type=='group' && dialog.last_msg && dialog.last_msg.userid">
                                        <div v-if="dialog.last_msg.userid == userId" class="last-self">{{$L('你')}}</div>
                                        <UserAvatar v-else :userid="dialog.last_msg.userid" :show-name="true" :show-icon="false" tooltip-disabled/>
                                    </template>
                                    <div class="last-text">
                                        <em v-if="formatMsgEmojiDesc(dialog.last_msg)">{{formatMsgEmojiDesc(dialog.last_msg)}}</em>
                                        <span>{{$A.getMsgSimpleDesc(dialog.last_msg)}}</span>
                                    </div>
                                    <div v-if="dialog.silence" class="taskfont last-silence">&#xe7d7;</div>
                                </div>
                            </div>
                            <Badge class="dialog-num" :type="dialog.silence ? 'normal' : 'error'" :overflow-count="999" :count="$A.getDialogUnread(dialog, true)"/>
                            <div class="dialog-line"></div>
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
                                        <div v-if="user.loading" class="loading"><Loading/></div>
                                    </li>
                                </ul>
                            </li>
                            <li class="loaded">{{$L('共' + contactsFilter.length + '位联系人')}}</li>
                        </template>
                    </ul>
                    <div class="operate-position" :style="operateStyles">
                        <Dropdown
                            trigger="custom"
                            :placement="windowLarge ? 'bottom' : 'top'"
                            :visible="operateVisible"
                            @on-clickoutside="operateVisible = false"
                            transfer>
                            <div :style="{userSelect:operateVisible ? 'none' : 'auto', height: operateStyles.height}"></div>
                            <DropdownMenu slot="list">
                                <DropdownItem @click.native="handleTopClick">
                                    {{ $L(operateItem.top_at ? '取消置顶' : '置顶') }}
                                </DropdownItem>
                                <DropdownItem @click.native="handleReadClick">
                                    {{ $L($A.getDialogUnread(operateItem, true) > 0 ? '标记已读' : '标记未读') }}
                                </DropdownItem>
                                <DropdownItem @click.native="handleSilenceClick" :disabled="silenceDisabled(operateItem)">
                                    {{ $L(operateItem.silence ? '允许消息通知' : '消息免打扰') }}
                                </DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                    </div>
                </ScrollerY>
                <div class="messenger-menu">
                    <div class="menu-icon" @click="onActive(null)">
                        <Icon @click="tabActive='dialog'" :class="{active:tabActive==='dialog'}" type="ios-chatbubbles" />
                        <Badge class="menu-num" :overflow-count="999" :count="msgUnread('all')"/>
                    </div>
                    <div class="menu-icon">
                        <Icon @click="tabActive='contacts'" :class="{active:tabActive==='contacts'}" type="md-person" />
                    </div>
                </div>
            </div>

            <div v-if="activeNum > 0 && routeName === 'manage-messenger'" class="messenger-msg">
                <div class="msg-dialog-bg">
                    <div class="msg-dialog-bg-icon"><Icon type="ios-chatbubbles" /></div>
                    <div class="msg-dialog-bg-text">{{$L('选择一个会话开始聊天')}}</div>
                </div>
                <DialogWrapper v-if="windowLarge && dialogId > 0" :dialogId="dialogId" @on-active="scrollIntoActive" :auto-focus="$A.isDesktop()"/>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import DialogWrapper from "./components/DialogWrapper";
import ScrollerY from "../../components/ScrollerY";
import longpress from "../../directives/longpress";
import {Store} from "le5le-store";

export default {
    components: {ScrollerY, DialogWrapper},
    directives: {longpress},
    data() {
        return {
            activeNum: 0,
            tabActive: 'dialog',

            dialogLoad: 0,
            dialogKey: '',
            dialogSearch: [],

            dialogActive: '',
            dialogType: [
                {type: '', name: '全部'},
                {type: 'project', name: '项目'},
                {type: 'task', name: '任务'},
                {type: 'user', name: '个人'},
            ],

            contactsKey: '',
            contactsLoad: 0,
            contactsData: null,
            contactsCurrentPage: 1,
            contactsHasMorePages: false,
            contactsLastTime: 0,

            operateItem: {},
            operateStyles: {},
            operateVisible: false,

            clickAgainSubscribe: null,
        }
    },

    mounted() {
        const id = $A.runNum(this.$route.query.dialog_id);
        if (id > 0) {
            this.openDialog(id)
        }
        this.clickAgainSubscribe = Store.subscribe('clickAgainDialog', this.shakeUnread);
    },

    beforeDestroy() {
        if (this.clickAgainSubscribe) {
            this.clickAgainSubscribe.unsubscribe();
            this.clickAgainSubscribe = null;
        }
        //
        document.removeEventListener('keydown', this.shortcutEvent);
    },

    activated() {
        this.updateDialogs(1000);
        this.$nextTick(_ => this.activeNum++)
        //
        if ($A.isEEUiApp) {
            $A.eeuiAppSendMessage({action: 'getNotificationPermission'});
        }
    },

    deactivated() {
        this.updateDialogs(-1);
        this.$nextTick(_ => this.activeNum--)
    },

    computed: {
        ...mapState(['cacheDialogs', 'loadDialogs', 'dialogId', 'appNotificationPermission']),

        routeName() {
            return this.$route.name
        },

        dialogList() {
            const {dialogActive, dialogKey, dialogSearch} = this;
            if (dialogActive == '' && dialogKey == '') {
                return this.cacheDialogs.filter(dialog => this.filterDialog(dialog)).sort((a, b) => {
                    if (a.top_at || b.top_at) {
                        return $A.Date(b.top_at) - $A.Date(a.top_at);
                    }
                    if (a.todo_num > 0 || b.todo_num > 0) {
                        return b.todo_num - a.todo_num;
                    }
                    return $A.Date(b.last_at) - $A.Date(a.last_at);
                });
            }
            const list = this.cacheDialogs.filter(dialog => {
                if (!this.filterDialog(dialog)) {
                    return false;
                }
                if (dialogKey) {
                    const {name, pinyin, last_msg} = dialog;
                    let searchString = `${name} ${pinyin}`
                    if (last_msg) {
                        switch (last_msg.type) {
                            case 'text':
                                searchString += ` ${last_msg.msg.text.replace(/<[^>]+>/g,"")}`
                                break
                            case 'meeting':
                            case 'file':
                                searchString += ` ${last_msg.msg.name}`
                                break
                        }
                    }
                    if (!$A.strExists(searchString, dialogKey)) {
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
                            if (dialog.type != 'user' || dialog.bot) {
                                return false;
                            }
                            break;
                        default:
                            return false;
                    }
                }
                return true;
            })
            if (dialogSearch.length > 0) {
                const msgIds = [];
                const userIds = [];
                list.forEach(item => {
                    if (item.last_msg && !msgIds.includes(item.last_msg.id)) {
                        msgIds.push(item.last_msg.id)
                    }
                    if (item.dialog_user && !userIds.includes(item.dialog_user.userid)) {
                        userIds.push(item.dialog_user.userid)
                    }
                })
                dialogSearch.forEach(item => {
                    if ($A.leftExists(item.id, "u:")) {
                        if (!userIds.includes(item.dialog_user.userid)) {
                            list.push(Object.assign(item, {is_search: true}))
                        }
                    } else {
                        if (!item.last_msg || !msgIds.includes(item.last_msg.id)) {
                            list.push(Object.assign(item, {is_search: true}))
                        }
                    }
                })
            }
            return list.sort((a, b) => {
                // 搜索结果排在后面
                a._by_search = a.is_search === true ? 1 : 0
                b._by_search = b.is_search === true ? 1 : 0
                if (a._by_search || b._by_search) {
                    return a._by_search - b._by_search
                }
                // 根据置顶时间排序
                if (a.top_at || b.top_at) {
                    return $A.Date(b.top_at) - $A.Date(a.top_at);
                }
                // 根据未读数排序
                if (a.todo_num > 0 || b.todo_num > 0) {
                    return b.todo_num - a.todo_num;
                }
                // 根据最后会话时间排序
                return $A.Date(b.last_at) - $A.Date(a.last_at);
            })
        },

        contactsFilter() {
            const {contactsData, contactsKey} = this;
            if (contactsData === null) {
                return [];
            }
            if (contactsKey) {
                return contactsData.filter(item => $A.strExists(`${item.email} ${item.nickname} ${item.profession} ${item.pinyin}`, contactsKey))
            }
            return contactsData;
        },

        contactsList() {
            const list = [];
            this.contactsFilter.some(user => {
                let az = user.az ? user.az.toUpperCase() : "#";
                let item = list.find(item => item.az == az);
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
                let num = 0
                this.cacheDialogs.some((dialog) => {
                    switch (type) {
                        case 'project':
                        case 'task':
                            if (type != dialog.group_type) {
                                return false
                            }
                            break;
                        case 'user':
                            if (type != dialog.type || dialog.bot) {
                                return false
                            }
                            break;
                    }
                    num += $A.getDialogNum(dialog);
                });
                return num;
            }
        },

        listClassName() {
            return {
                'scrollbar-overlay': true,
                'scrollbar-hidden': this.operateVisible === true,
            }
        }
    },

    watch: {
        '$route': {
            handler({params}) {
                if (['dialog', 'contacts'].includes(params.dialogAction)) {
                    this.tabActive = params.dialogAction
                }
            },
            immediate: true
        },

        dialogKey(val) {
            switch (val) {
                case 'log.open':
                case 'log:open':
                case 'eruda:open':
                    $A.IDBSet("logOpen", "open").then(_ => $A.reloadUrl());
                    break;
                case 'log.close':
                case 'log:close':
                case 'eruda:close':
                    $A.IDBSet("logOpen", "close").then(_ => $A.reloadUrl());
                    break;
            }
            //
            this.dialogSearch = [];
            if (val == '') {
                return;
            }
            this.dialogLoad++;
            setTimeout(() => {
                if (this.dialogKey == val) {
                    this.searchDialog(val);
                }
                this.dialogLoad--;
            }, 600);
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
                    if ($A.Time() - this.contactsLastTime > 24 * 3600) {
                        this.contactsData = null;   // 24个小时重新加载列表
                    }
                    if (this.contactsData === null) {
                        this.getContactsList(1);
                    } else {
                        this.updateContactsList(1000);
                    }
                } else {
                    this.updateDialogs(1000);
                }
            },
            immediate: true
        },

        dialogId: {
            handler(id) {
                if (id > 0) {
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
            this.operateVisible = false;
        },

        onActive(type) {
            let block = "start"
            if (type === null) {
                if (this.tabActive !== 'dialog') {
                    return;
                }
                type = this.dialogActive
                block = "end"
            }
            if (this.dialogActive == type) {
                // 再次点击滚动到未读条目
                this.shakeUnread()
            }
            this.dialogActive = type
        },

        shakeUnread() {
            let index = this.dialogList.findIndex(dialog => $A.getDialogNum(dialog) > 0)
            if (index === -1) {
                index = this.dialogList.findIndex(dialog => $A.getDialogUnread(dialog, true) > 0)
            }
            if (index > -1) {
                const el = this.$refs[`dialog_${this.dialogList[index]?.id}`][0]
                if (el) {
                    $A.scrollIntoViewIfNeeded(el)
                    el.classList.remove("common-shake")
                    requestAnimationFrame(_ => el.classList.add("common-shake"))
                }
            }
        },

        dialogClass(dialog) {
            if (this.dialogKey) {
                return null
            }
            return {
                top: dialog.top_at,
                active: dialog.id == this.dialogId,
                operate: this.operateVisible && dialog.id == this.operateItem.id,
                completed: $A.dialogCompleted(dialog)
            }
        },

        openDialog(dialogId) {
            if (this.operateVisible) {
                return
            }
            this.dialogKey = "";
            //
            if ($A.isJson(dialogId) && $A.leftExists(dialogId.dialog_id, "u:")) {
                this.$store.dispatch("showSpinner", 300)
                this.$store.dispatch("openDialogUserid", $A.leftDelete(dialogId.dialog_id, "u:")).catch(({msg}) => {
                    $A.modalError(msg)
                }).finally(_ => {
                    this.$store.dispatch("hiddenSpinner")
                })
            } else {
                this.$store.dispatch("openDialog", dialogId)
            }
        },

        openContacts(user) {
            if (user.loading) {
                return
            }
            this.$set(user, 'loading', true);
            this.$store.dispatch("openDialogUserid", user.userid).then(_ => {
                if (this.windowLarge) {
                    this.tabActive = 'dialog';
                }
            }).catch(({msg}) => {
                $A.modalError(msg)
            }).finally(_ => {
                this.$set(user, 'loading', false);
            });
        },

        filterDialog(dialog) {
            if ($A.getDialogNum(dialog) > 0 || dialog.id == this.dialogId || dialog.top_at || dialog.todo_num > 0) {
                return true
            }
            if (dialog.name === undefined || dialog.dialog_delete === 1) {
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

        searchDialog(key) {
            this.dialogLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/search',
                data: {key},
            }).then(({data}) => {
                this.dialogSearch = data;
            }).finally(_ => {
                this.dialogLoad--;
            });
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
                this.contactsLastTime = $A.Time()
            });
        },

        updateContactsList(timeout) {
            this.__updateContactsList && clearTimeout(this.__updateContactsList)
            if (timeout > -1) {
                this.__updateContactsList = setTimeout(_ => {
                    if (this.tabActive === 'contacts') {
                        this.$store.dispatch("call", {
                            url: 'users/search',
                            data: {
                                updated_time: this.contactsLastTime,
                                take: 100
                            },
                        }).then(({data}) => {
                            data.some((user) => {
                                const index = this.contactsData.findIndex(item => item.userid == user.userid);
                                if (index > -1) {
                                    this.contactsData.splice(index, 1, user);
                                } else {
                                    this.contactsData.push(user);
                                }
                            });
                        }).finally(_ => {
                            this.contactsLastTime = $A.Time()
                        });
                    }
                }, timeout)
            }
        },

        formatTodoNum(num) {
            return num > 999 ? '999+' : (num > 1 ? num : '')
        },

        formatMsgEmojiDesc(data) {
            if ($A.isJson(data) && $A.arrayLength(data.emoji) > 0) {
                return data.emoji[0].symbol;
            }
            return null;
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
                if (this.windowLarge && this.$refs.list) {
                    const active = this.$refs.list.querySelector(".active")
                    if (active) {
                        $A.scrollIntoViewIfNeeded(active);
                    } else {
                        const dialog = this.cacheDialogs.find(({id}) => id == this.dialogId)
                        if (dialog && this.dialogActive) {
                            this.dialogActive = '';
                            this.$nextTick(() => {
                                const active = this.$refs.list.querySelector(".active")
                                if (active) {
                                    $A.scrollIntoViewIfNeeded(active);
                                }
                            });
                        }
                    }
                }
            })
        },

        handleLongpress(event, el) {
            const dialogId = $A.getAttr(el, 'data-id')
            const dialogItem = this.dialogList.find(item => item.id == dialogId)
            if (!dialogItem) {
                return
            }
            this.operateVisible = false;
            this.operateItem = $A.isJson(dialogItem) ? dialogItem : {};
            this.$nextTick(() => {
                const dialogRect = el.getBoundingClientRect();
                const wrapRect = this.$refs.list.$el.getBoundingClientRect();
                this.operateStyles = {
                    left: `${event.clientX - wrapRect.left}px`,
                    top: `${dialogRect.top + this.windowScrollY}px`,
                    height: dialogRect.height + 'px',
                }
                this.operateVisible = true;
            })
        },

        handleTopClick() {
            this.$store.dispatch("call", {
                url: 'dialog/top',
                data: {
                    dialog_id: this.operateItem.id,
                },
            }).then(({data}) => {
                this.$store.dispatch("saveDialog", data);
                this.$nextTick(this.scrollIntoActive);
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        handleReadClick() {
            this.$store.dispatch("dialogMsgMark", {
                dialog_id: this.operateItem.id,
                type: $A.getDialogUnread(this.operateItem, true) > 0 ? 'read' : 'unread'
            }).catch(({msg}) => {
                $A.modalError(msg)
            })
        },

        handleSilenceClick() {
            if (this.silenceDisabled(this.operateItem)) {
                return
            }
            this.$store.dispatch("call", {
                url: 'dialog/msg/silence',
                data: {
                    dialog_id: this.operateItem.id,
                    type: this.operateItem.silence ? 'cancel' : 'set'
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
                        this.$store.dispatch("getDialogs", {hideLoad: true}).catch(() => {});
                    }
                }, timeout)
            }
        },

        onOpenAppSetting() {
            $A.eeuiAppSendMessage({
                action: 'gotoSetting',
            });
        },

        silenceDisabled(data) {
            const {type, group_type} = data
            return type === 'group' && group_type !== 'user'
        },
    }
}
</script>
