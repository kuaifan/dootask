<template>
    <div class="page-messenger">
        <PageTitle :title="$L(tabActive==='dialog' ? '消息' : '通讯录')"/>
        <div class="messenger-wrapper">
            <div class="messenger-select">
                <div class="messenger-search">
                    <div class="search-wrapper">
                        <div class="search-pre">
                            <Loading v-if="searchLoading"/>
                            <Icon v-else type="ios-search" />
                        </div>
                        <Input
                            v-if="tabActive==='dialog'"
                            v-model="dialogSearchKey"
                            ref="searchInput"
                            :placeholder="$L(loadDialogs > 0 ? '更新中...' : '搜索')"
                            @on-keydown="onKeydown"
                            clearable/>
                        <Input
                            v-else
                            v-model="contactsKey"
                            ref="contactInput"
                            :placeholder="$L('搜索')"
                            @on-keydown="onKeydown"
                            clearable/>
                    </div>
                </div>
                <div v-if="tabActive==='dialog' && !dialogSearchKey" class="messenger-nav">
                    <EDropdown
                        ref="navMenu"
                        trigger="click"
                        placement="bottom-start"
                        class="nav-menu"
                        @command="onActive">
                        <div class="nav-icon"><i class="taskfont">&#xe634;</i></div>
                        <EDropdownMenu slot="dropdown" class="messenger-nav-menu">
                            <EDropdownItem v-for="(item, key) in dialogMenus" :key="key" :command="item.type">
                                <div class="messenger-nav-item" :class="{active: dialogActive==item.type}">
                                    <div class="nav-title">{{$L(item.name)}}</div>
                                    <Badge class="nav-num" :overflow-count="999" :count="msgUnread(item.type)"/>
                                </div>
                            </EDropdownItem>
                        </EDropdownMenu>
                    </EDropdown>
                    <div
                        v-for="(item, key) in typeItems"
                        :key="key"
                        :class="{active:dialogActive==item.type}"
                        @click="onActive(item.type)">
                        <div class="nav-title">
                            <em>{{$L(item.name)}}</em>
                            <Badge class="nav-num" :overflow-count="999" :count="msgUnread(item.type)"/>
                        </div>
                    </div>
                </div>
                <div v-if="$isEEUiApp && !appNotificationPermission" class="messenger-notify-permission" @click="onOpenAppSetting">
                    {{$L('未开启通知权限')}}<i class="taskfont">&#xe733;</i>
                </div>
                <Scrollbar
                    ref="list"
                    class="messenger-list"
                    :hide-bar="this.operateVisible"
                    @touchstart.native="listTouch"
                    @on-scroll="listScroll">
                    <ul v-if="tabActive==='dialog'" ref="ul" class="dialog">
                        <template v-if="dialogList.length > 0">
                            <li
                                v-for="(dialog, key) in dialogList"
                                :ref="`dialog_${dialog.id}`"
                                :key="key"
                                :data-id="dialog.id"
                                :class="dialogClass(dialog)"
                                @click="openDialog({
                                    dialog_id: dialog.id,
                                    dialog_msg_id: dialog.search_msg_id,
                                    search_msg_id: dialog.search_msg_id,
                                })"
                                v-longpress="handleDialogLongpress"
                                :style="{'background-color':dialog.color}">
                                <template v-if="dialog.type=='group'">
                                    <EAvatar v-if="dialog.avatar" class="img-avatar" :src="dialog.avatar" :size="42"></EAvatar>
                                    <i v-else-if="dialog.group_type=='department'" class="taskfont icon-avatar department">&#xe75c;</i>
                                    <i v-else-if="dialog.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                                    <i v-else-if="dialog.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                    <i v-else-if="dialog.group_type=='okr'" class="taskfont icon-avatar task">&#xe6f4;</i>
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
                                        <template v-if="dialog.extra_draft_has && dialog.id != dialogId">
                                            <div class="last-draft">[{{$L('草稿')}}]</div>
                                            <div class="last-text"><span>{{formatDraft(dialog.extra_draft_content)}}</span></div>
                                        </template>
                                        <template v-else>
                                            <template v-if="dialog.type=='group' && dialog.last_msg && dialog.last_msg.userid">
                                                <div v-if="dialog.last_msg.userid == userId" class="last-self">{{$L('你')}}</div>
                                                <UserAvatar v-else :userid="dialog.last_msg.userid" :show-name="true" :show-icon="false"/>
                                            </template>
                                            <div class="last-text">
                                                <em v-if="formatMsgEmojiDesc(dialog.last_msg)">{{formatMsgEmojiDesc(dialog.last_msg)}}</em>
                                                <span>{{$A.getMsgSimpleDesc(dialog.last_msg)}}</span>
                                            </div>
                                        </template>
                                        <div v-if="dialog.silence" class="taskfont last-silence">&#xe7d7;</div>
                                    </div>
                                </div>
                                <Badge class="dialog-num" :type="dialog.silence ? 'normal' : 'error'" :overflow-count="999" :count="$A.getDialogUnread(dialog, true)"/>
                                <div class="dialog-line"></div>
                            </li>
                        </template>
                        <li v-else-if="dialogSearchLoad === 0" class="nothing">
                            {{$L(dialogSearchKey ? `没有任何与"${dialogSearchKey}"相关的结果` : `没有任何会话`)}}
                        </li>
                    </ul>
                    <ul v-else class="contacts">
                        <template v-if="contactsFilter.length > 0">
                            <li v-for="items in contactsList">
                                <div class="label">{{items.az}}</div>
                                <ul>
                                    <li
                                        v-for="(user, index) in items.list"
                                        :key="index"
                                        :data-id="user.userid"
                                        :class="userClass(user)"
                                        @click="openContacts(user)"
                                        v-longpress="handleUserLongpress">
                                        <div class="avatar"><UserAvatar :userid="user.userid" :size="contactAvatarSize"/></div>
                                        <div class="nickname">
                                            <em>{{user.nickname}}</em>
                                            <div v-if="user.tags" class="tags">
                                                <span v-for="tag in user.tags" :style="tagField(tag,'style')">{{tagField(tag, 'label')}}</span>
                                            </div>
                                        </div>
                                        <div v-if="user.loading" class="loading"><Loading/></div>
                                    </li>
                                </ul>
                            </li>
                            <li class="loaded">
                                <template v-if="contactsKey">{{$L('搜索到' + contactsFilter.length + '位联系人')}}</template>
                                <template v-else>{{$L('共' + contactsTotal + '位联系人')}}</template>
                            </li>
                        </template>
                        <li v-else-if="contactsLoad == 0" class="nothing">
                            {{$L(contactsKey ? `没有任何与"${contactsKey}"相关的结果` : `没有任何联系人`)}}
                        </li>
                    </ul>
                    <div class="operate-position" :style="operateStyles" v-show="operateVisible">
                        <Dropdown
                            trigger="custom"
                            transferClassName="scrollbar-hidden"
                            :placement="windowLandscape ? 'bottom' : 'top'"
                            :visible="operateVisible"
                            @on-clickoutside="operateVisible = false"
                            transfer>
                            <div :style="{userSelect:operateVisible ? 'none' : 'auto', height: operateStyles.height}"></div>
                            <DropdownMenu slot="list" class="messenger-dialog-operation">
                                <template v-if="operateType==='dialog'">
                                    <DropdownItem @click.native="handleDialogClick('top')">
                                        <div class="item">
                                            {{ $L(operateItem.top_at ? '取消置顶' : '置顶') }}
                                            <i class="taskfont" v-html="operateItem.top_at ? '&#xe7e3;' : '&#xe7e6;'"></i>
                                        </div>
                                    </DropdownItem>
                                    <DropdownItem @click.native="handleDialogClick('read')">
                                        <div class="item">
                                            {{ $L($A.getDialogUnread(operateItem, true) > 0 ? '标记已读' : '标记未读') }}
                                            <i class="taskfont" v-html="$A.getDialogUnread(operateItem, true) > 0 ? '&#xe7e8;' : '&#xe7e9;'"></i>
                                        </div>

                                    </DropdownItem>
                                    <DropdownItem @click.native="handleDialogClick('silence')" :disabled="silenceDisabled(operateItem)">
                                        <div class="item">
                                            {{ $L(operateItem.silence ? '允许消息通知' : '消息免打扰') }}
                                            <i class="taskfont" v-html="operateItem.silence ? '&#xe7eb;' : '&#xe7d7;'"></i>
                                        </div>
                                    </DropdownItem>
                                    <DropdownItem @click.native="handleDialogClick('hide')" :disabled="!!operateItem.top_at">
                                        <div class="item">
                                            {{ $L('不显示该会话') }}
                                            <i class="taskfont">&#xe881;</i>
                                        </div>
                                    </DropdownItem>
                                    <DropdownItem @click.native="handleDialogClick('color', c.color)" v-for="(c, k) in taskColorList" :key="'c_' + k" :divided="k==0"  v-if="k<6" >
                                        <div class="item">
                                            {{$L(c.name)}}
                                            <i class="taskfont color" :style="{color:c.primary||'#ddd'}" v-html="c.color == (operateItem.color||'') ? '&#xe61d;' : '&#xe61c;'"></i>
                                        </div>
                                    </DropdownItem>
                                </template>
                                <template v-else>
                                    <DropdownItem @click.native="handleUserClick('msg')">
                                        <div class="item">
                                            {{ $L('发送消息') }}
                                            <i class="taskfont">&#xe6eb;</i>
                                        </div>
                                    </DropdownItem>
                                    <DropdownItem @click.native="handleUserClick('meet')">
                                        <div class="item">
                                            {{ $L('发起会议') }}
                                            <i class="taskfont">&#xe794;</i>
                                        </div>
                                    </DropdownItem>
                                    <DropdownItem @click.native="handleUserClick('group')">
                                        <div class="item">
                                            {{ $L('创建群组') }}
                                            <i class="taskfont">&#xe63f;</i>
                                        </div>
                                    </DropdownItem>
                                    <DropdownItem @click.native="handleUserClick('avatar')">
                                        <div class="item">
                                            {{ $L('查看头像') }}
                                            <i class="taskfont">&#xe7bc;</i>
                                        </div>
                                    </DropdownItem>
                                </template>
                            </DropdownMenu>
                        </Dropdown>
                    </div>
                </Scrollbar>
                <div class="messenger-menu">
                    <div class="menu-icon">
                        <Icon @click="onActive(null)" :class="{active:tabActive==='dialog'}" type="ios-chatbubbles" />
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
                <DialogWrapper v-if="windowLandscape && dialogId > 0" :dialogId="dialogId" @on-active="scrollIntoActive" :auto-focus="$A.isDesktop()" location="messenger"/>
            </div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import DialogWrapper from "./components/DialogWrapper";
import longpress from "../../directives/longpress";
import {Store} from "le5le-store";

const MessengerObject = {menuHistory: []};

export default {
    components: {DialogWrapper},
    directives: {longpress},
    data() {
        return {
            firstLoad: true,
            activeNum: 0,
            tabActive: 'dialog',

            dialogSearchLoad: 0,
            dialogSearchKey: '',
            dialogSearchList: [],

            dialogActive: '',
            dialogMenus: [
                {type: '', name: '全部'},
                {type: 'project', name: '项目'},
                {type: 'task', name: '任务'},
                {type: 'user', name: '单聊'},
                {type: 'group', name: '群聊'},
                {type: 'bot', name: '机器人'},
                {type: 'mark', name: '标注'},
                {type: '@', name: '@我'},
            ],
            dialogHistory: MessengerObject.menuHistory,

            contactsKey: '',
            contactsLoad: 0,
            contactsData: null,
            contactsTotal: 0,
            contactsCurrentPage: 1,
            contactsHasMorePages: false,
            contactsLastTime: 0,

            operateItem: {},
            operateStyles: {},
            operateVisible: false,
            operateType: 'dialog',

            clickAgainSubscribe: null,
        }
    },

    async beforeRouteEnter(to, from, next) {
        MessengerObject.menuHistory = await $A.IDBArray("dialogMenuHistory")
        next()
    },

    mounted() {
        const id = $A.runNum(this.$route.query.dialog_id);
        if (id > 0) {
            this.openDialog(id)
        }
        //
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
        this.updateDialogs(this.firstLoad ? 0 : 1000);
        this.firstLoad = false;
        //
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
        ...mapState(['cacheDialogs', 'loadDialogs', 'dialogId', 'dialogMsgId', 'dialogMsgs', 'messengerSearchKey', 'appNotificationPermission', 'taskColorList']),

        routeName() {
            return this.$route.name
        },

        contactAvatarSize() {
            return this.windowPortrait ? 36 : 30
        },

        typeItems() {
            const {dialogActive, dialogMenus, dialogHistory} = this
            const types = []
            if (this.dialogHistory.includes(dialogActive)) {
                types.push(...this.dialogHistory)
            } else {
                types.push('')
                if (dialogActive) {
                    types.push(dialogActive)
                }
                dialogHistory.some(item => {
                    if (!types.includes(item)) {
                        types.push(item)
                    }
                });
                ['project', 'task', 'user'].some(item => {
                    if (!types.includes(item)) {
                        types.push(item)
                    }
                })
                this.dialogHistory = types.slice(0, 4)
                $A.IDBSave("dialogMenuHistory", this.dialogHistory)
            }
            //
            return this.dialogHistory.map(type => {
                return dialogMenus.find(item => item.type == type)
            })
        },

        dialogList() {
            const {dialogActive, dialogSearchKey, dialogSearchList} = this
            if (dialogSearchList.length > 0) {
                return dialogSearchList.sort((a, b) => {
                    // 搜索结果排在后面
                    return (a.is_search === true ? 1 : 0) - (b.is_search === true ? 1 : 0)
                })
            }
            if (dialogActive == '' && dialogSearchKey == '') {
                return this.cacheDialogs.filter(dialog => this.filterDialog(dialog)).sort(this.dialogSort);
            }
            if (dialogActive == 'mark' && !dialogSearchKey) {
                const lists = [];
                this.dialogMsgs.filter(h => h.tag).forEach(h => {
                    let dialog = $A.cloneJSON(this.cacheDialogs).find(p => p.id == h.dialog_id)
                    if (dialog) {
                        dialog.last_msg = h;
                        dialog.search_msg_id = h.id;
                        lists.push(dialog);
                    }
                });
                return lists;
            }
            const list = this.cacheDialogs.filter(dialog => {
                if (!this.filterDialog(dialog)) {
                    return false;
                }
                if (dialogSearchKey) {
                    const {name, pinyin, last_msg} = dialog;
                    let searchString = `${name} ${pinyin}`
                    if (last_msg) {
                        switch (last_msg.type) {
                            case 'text':
                                searchString += ` ${last_msg.msg.text.replace(/<[^>]+>/g, "")}`
                                break
                            case 'meeting':
                            case 'file':
                                searchString += ` ${last_msg.msg.name}`
                                break
                        }
                    }
                    if (!$A.strExists(searchString, dialogSearchKey)) {
                        return false;
                    }
                } else if (dialogActive) {
                    switch (dialogActive) {
                        case 'project':
                        case 'task':
                            if (dialogActive != dialog.group_type) {
                                return false;
                            }
                            break;
                        case 'user':
                            if (dialogActive != dialog.type || dialog.bot) {
                                return false;
                            }
                            break;
                        case 'group':
                            if (dialogActive != dialog.type || ['project', 'task'].includes(dialog.group_type)) {
                                return false;
                            }
                            break;
                        case 'bot':
                            if (!dialog.bot) {
                                return false;
                            }
                            break;
                        case '@':
                            if (!$A.getDialogMention(dialog)) {
                                return false;
                            }
                            break;
                        default:
                            return false;
                    }
                }
                return true;
            })
            return list.sort(this.dialogSort)
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
                        case 'group':
                            if (type != dialog.type || ['project', 'task'].includes(dialog.group_type)) {
                                return false
                            }
                            break;
                        case 'bot':
                            if (!dialog.bot) {
                                return false;
                            }
                            break;
                        case 'mark':
                            return false;
                        case '@':
                            return false;
                    }
                    num += $A.getDialogNum(dialog);
                });
                return num;
            }
        },

        searchLoading({tabActive, loadDialogs, dialogSearchLoad, contactsLoad}) {
            if (tabActive==='dialog') {
                return loadDialogs > 0 || dialogSearchLoad > 0
            } else {
                return contactsLoad > 0
            }
        },
    },

    watch: {
        '$route': {
            handler({params}) {
                if (['dialog', 'contacts'].includes(params.dialogAction)) {
                    this.tabActive = params.dialogAction
                }
                if (params.dialog_id) {
                    this.tabActive = 'dialog'
                    const id = $A.runNum(params.dialog_id);
                    if (id > 0) {
                        this.openDialog(id)
                    }
                    this.clickAgainSubscribe = Store.subscribe('clickAgainDialog', this.shakeUnread);
                }
            },
            immediate: true
        },

        messengerSearchKey: {
            handler(obj) {
                this.$nextTick(_ => {
                    this.dialogSearchKey = obj.dialog
                    this.contactsKey = obj.contacts
                })
            },
            deep: true
        },

        dialogSearchKey(val) {
            this.$store.state.messengerSearchKey.dialog = val
            if ($A.loadVConsole(val)) {
                this.dialogSearchKey = '';
                return;
            }
            //
            this.dialogSearchList = [];
            if (val == '') {
                return
            }
            this.__search_timer && clearTimeout(this.__search_timer)
            this.__search_timer = setTimeout(this.searchDialog, 600)
            this.dialogSearchLoad++
            setTimeout(_ => this.dialogSearchLoad--, 600)
        },

        contactsKey(val) {
            this.$store.state.messengerSearchKey.contacts = val
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

        windowActive(val) {
            this.updateDialogs(val ? 1000 : -1);
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

        dialogActive(){
            this.dialogSearchList = [];
            if(this.dialogActive == 'mark' && !this.dialogSearchKey){
                this.searchTagDialog()
            }
        }
    },

    methods: {
        listTouch() {
            if (this.$refs.navMenu?.visible) {
                this.$refs.navMenu.hide()
            }
        },

        listScroll() {
            if (this.scrollE() < 10) {
                this.getContactsNextPage()
            }
            this.operateVisible = false;
        },

        scrollE() {
            if (!this.$refs.list) {
                return 0
            }
            const scrollInfo = this.$refs.list.scrollInfo()
            return scrollInfo.scrollE
        },

        onKeydown(e) {
            if (e.key === "Escape") {
                this.$refs.searchInput?.handleClear()
                this.$refs.contactInput?.handleClear()
            }
        },

        onActive(type) {
            if (type === null) {
                if (this.tabActive !== 'dialog') {
                    this.tabActive = 'dialog'
                    return;
                }
                type = this.dialogActive
            }
            if (this.dialogActive == type) {
                this.shakeUnread()  // 再次点击滚动到未读条目
            } else {
                this.dialogActive = type
            }
        },

        shakeUnread() {
            let index = this.dialogList.findIndex(dialog => $A.getDialogNum(dialog) > 0)
            if (index === -1) {
                index = this.dialogList.findIndex(dialog => dialog.todo_num > 0)
            }
            if (index === -1) {
                index = this.dialogList.findIndex(dialog => $A.getDialogUnread(dialog, true) > 0)
            }
            if (index > -1) {
                const el = this.$refs[`dialog_${this.dialogList[index]?.id}`]
                if (el && el[0]) {
                    if (el[0].classList.contains("common-shake")) {
                        return
                    }
                    $A.scrollIntoViewIfNeeded(el[0])
                    requestAnimationFrame(_ => {
                        el[0].classList.add("common-shake")
                        setTimeout(_ => {
                            el[0].classList.remove("common-shake")
                        }, 600)
                    })
                }
            }
        },

        dialogClass(dialog) {
            if (this.dialogSearchKey) {
                return null
            }
            return {
                top: dialog.top_at,
                active: dialog.id == this.dialogId && (dialog.search_msg_id == this.dialogMsgId || !this.dialogMsgId),
                operate: this.operateVisible && this.operateType === 'dialog' && dialog.id == this.operateItem.id,
                completed: $A.dialogCompleted(dialog)
            }
        },

        dialogSort(a, b) {
            // 根据置顶时间排序
            if (a.top_at || b.top_at) {
                return $A.Date(b.top_at) - $A.Date(a.top_at);
            }
            // 根据未读数排序
            if (a.todo_num > 0 || b.todo_num > 0) {
                return b.todo_num - a.todo_num;
            }
            // 根据草稿排序
            if (a.extra_draft_has || b.extra_draft_has) {
                return b.extra_draft_has - a.extra_draft_has;
            }
            // 根据最后会话时间排序
            return $A.Date(b.last_at) - $A.Date(a.last_at);
        },

        userClass(user) {
            return {
                operate: this.operateVisible && this.operateType === 'contacts' && user.userid == this.operateItem.userid
            }
        },

        openDialog(dialogId) {
            if (this.operateVisible) {
                return
            }
            //
            if ($A.isJson(dialogId) && $A.leftExists(dialogId.dialog_id, "u:")) {
                this.$store.dispatch("openDialogUserid", $A.leftDelete(dialogId.dialog_id, "u:")).catch(({msg}) => {
                    $A.modalError(msg)
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
                if (this.windowLandscape) {
                    this.tabActive = 'dialog';
                }
            }).catch(({msg}) => {
                $A.modalError(msg)
            }).finally(_ => {
                this.$set(user, 'loading', false);
            });
        },

        tagField(item, field) {
            if (!$A.isJson(item)) {
                item = {label: item}
            }
            switch (field) {
                case 'style':
                    return item.style || null
                case 'label':
                    return item.label
            }
            return null
        },

        filterDialog(dialog) {
            if ((dialog.id > 0 && dialog.id == this.dialogId) || dialog.top_at || dialog.todo_num > 0 || $A.getDialogNum(dialog) > 0) {
                return true
            }
            if (dialog.name === undefined || dialog.dialog_delete === 1) {
                return false;
            }
            if (dialog.hide || !dialog.last_at) {
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

        searchDialog() {
            const key = this.dialogSearchKey
            if (key == '') {
                return
            }
            //
            this.dialogSearchLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/search',
                data: {key},
            }).then(({data}) => {
                if (key !== this.dialogSearchKey) {
                    return
                }
                const list = $A.cloneJSON(this.dialogList)
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
                data.some(item => {
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
                this.dialogSearchList = list;
            }).finally(_ => {
                this.dialogSearchLoad--;
            });
        },

        searchTagDialog() {
            this.dialogSearchLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/search/tag',
            }).then(({data}) => {
                const msgIds = [];
                const lists = [];
                this.dialogList.forEach(h=>{
                    lists.push(h);
                    msgIds.push(h.search_msg_id)
                });
                data.some(item => {
                    if (!item.last_msg || !msgIds.includes(item.search_msg_id)) {
                        lists.push(Object.assign(item, {is_search: true}))
                    }
                })
                this.dialogSearchList = lists;
            }).finally(_ => {
                this.dialogSearchLoad--;
            });
        },

        getContactsList(page) {
            this.contactsLoad++;
            const key = this.contactsKey
            this.$store.dispatch("call", {
                url: 'users/search',
                data: {
                    keys: {key},
                    sorts: {
                        az: 'asc'
                    },
                    page: page,
                    pagesize: 50
                },
            }).then(({data}) => {
                if (key == '') {
                    this.contactsTotal = data.total;
                }
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
                this.$nextTick(this.getContactsNextPage);
            }).catch(() => {
                if (key == '') {
                    this.contactsTotal = 0;
                }
                this.contactsHasMorePages = false;
            }).finally(_ => {
                this.contactsLoad--;
                this.contactsLastTime = $A.Time()
            });
        },

        getContactsNextPage() {
            if (this.scrollE() < 10
                && this.tabActive === 'contacts'
                && this.contactsLoad === 0
                && this.contactsHasMorePages) {
                this.getContactsList(this.contactsCurrentPage + 1);
            }
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

        formatDraft(value) {
            return value?.replace(/<img[^>]*>/gi, `[${$A.L('图片')}]`)
                .replace(/<[^>]*>/g, '')
                .replace(/&nbsp;/g, ' ')|| null
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
            if (this.windowPortrait || this.windowScrollY > 0) {
                return;
            }
            this.$nextTick(() => {
                if (!this.$refs.list) {
                    return;
                }
                const active = this.$refs.list.querySelector(".active")
                if (active) {
                    $A.scrollIntoViewIfNeeded(active);
                    return;
                }
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
            })
        },

        handleDialogLongpress(event, el) {
            if (this.dialogSearchKey) {
                return;
            }
            const dialogId = $A.getAttr(el, 'data-id')
            const dialogItem = this.dialogList.find(item => item.id == dialogId)
            if (!dialogItem) {
                return
            }
            this.handleLongpress(dialogItem, el.getBoundingClientRect(), event.clientX)
        },

        handleUserLongpress(event, el) {
            if (this.contactsKey) {
                return;
            }
            const userId = $A.getAttr(el, 'data-id')
            const userItem = this.contactsFilter.find(item => item.userid == userId)
            if (!userItem) {
                return
            }
            this.handleLongpress(userItem, el.getBoundingClientRect(), event.clientX)
        },

        handleLongpress(item, rect, clientX) {
            this.operateType = this.tabActive;
            this.operateVisible = false;
            this.operateItem = $A.isJson(item) ? item : {};
            this.$nextTick(() => {
                this.operateStyles = {
                    left: `${clientX}px`,
                    top: `${rect.top + this.windowScrollY}px`,
                    height: rect.height + 'px',
                }
                this.operateVisible = true;
            })
        },

        handleDialogClick(act, value = undefined) {
            switch (act) {
                case 'top':
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
                    break;

                case 'read':
                    this.$store.dispatch("showSpinner", 600)
                    this.$store.dispatch("dialogMsgMark", {
                        type: $A.getDialogUnread(this.operateItem, true) > 0 ? 'read' : 'unread',
                        dialog_id: this.operateItem.id,
                    }).catch(({msg}) => {
                        $A.modalError(msg)
                    }).finally(_ => {
                        this.$store.dispatch("hiddenSpinner")
                    })
                    break;

                case 'silence':
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
                    break;

                case 'hide':
                    this.$store.dispatch("call", {
                        url: 'dialog/hide',
                        data: {
                            dialog_id: this.operateItem.id,
                        },
                    }).then(({data}) => {
                        if (this.dialogId == this.operateItem.id) {
                            this.$store.dispatch("openDialog", 0)
                        }
                        this.$store.dispatch("saveDialog", data);
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    });
                    break;

                case 'color':
                    this.$store.dispatch("call", {
                        url: 'dialog/msg/color',
                        data: {
                            dialog_id: this.operateItem.id,
                            color: value
                        },
                    }).then(({data}) => {
                        this.$store.dispatch("saveDialog", data);
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    });
                    break;
            }
        },

        handleUserClick(act) {
            switch (act) {
                case 'msg':
                    this.openContacts(this.operateItem);
                    break;

                case 'meet':
                case 'group':
                    const userids = [this.userId]
                    if (this.operateItem.userid && this.userId != this.operateItem.userid) {
                        userids.push(this.operateItem.userid)
                    }
                    if (act === 'meet') {
                        Store.set('addMeeting', {
                            type: 'create',
                            userids,
                        });
                    } else {
                        Store.set('createGroup', userids);
                    }
                    break;

                case 'avatar':
                    this.$store.dispatch("previewImage", this.operateItem.userimg)
                    break;
            }
        },

        updateDialogs(timeout) {
            this.__updateDialogs && clearTimeout(this.__updateDialogs)
            if (timeout > -1) {
                this.__updateDialogs = setTimeout(_ => {
                    if (this.tabActive === 'dialog' && this.routeName === 'manage-messenger') {
                        this.$store.dispatch("getDialogAuto").catch(() => {});
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
