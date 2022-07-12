<template>
    <div
        v-if="isReady"
        class="dialog-wrapper"
        :class="wrapperClass"
        @drop.prevent="chatPasteDrag($event, 'drag')"
        @dragover.prevent="chatDragOver(true, $event)"
        @dragleave.prevent="chatDragOver(false, $event)"
        @touchstart="onTouchStart"
        @touchmove="onTouchMove">
        <!--顶部导航-->
        <div class="dialog-nav" :style="navStyle">
            <slot name="head">
                <div class="nav-wrapper" :class="{completed: $A.dialogCompleted(dialogData)}">
                    <div class="dialog-back" @click="onBack">
                        <i class="taskfont">&#xe676;</i>
                        <div v-if="msgUnreadOnly" class="back-num">{{msgUnreadOnly}}</div>
                    </div>

                    <div class="dialog-block">
                        <div class="dialog-avatar">
                            <template v-if="dialogData.type=='group'">
                                <i v-if="dialogData.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                                <i v-else-if="dialogData.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                <Icon v-else class="icon-avatar" type="ios-people" />
                            </template>
                            <div v-else-if="dialogData.dialog_user" class="user-avatar">
                                <UserAvatar :online.sync="dialogData.online_state" :userid="dialogData.dialog_user.userid" :size="42">
                                    <p v-if="dialogData.type === 'user' && dialogData.online_state !== true" slot="end">
                                        {{$L(dialogData.online_state)}}
                                    </p>
                                </UserAvatar>
                            </div>
                            <Icon v-else class="icon-avatar" type="md-person" />
                        </div>
                        <div class="dialog-title">
                            <div class="main-title">
                                <template v-for="tag in $A.dialogTags(dialogData)" v-if="tag.color != 'success'">
                                    <Tag :color="tag.color" :fade="false">{{$L(tag.text)}}</Tag>
                                </template>
                                <h2>{{dialogData.name}}</h2>
                                <em v-if="peopleNum > 0">({{peopleNum}})</em>
                            </div>
                            <ul class="title-desc">
                                <li v-if="dialogData.type === 'user'" :class="[dialogData.online_state === true ? 'online' : 'offline']">
                                    {{$L(dialogData.online_state === true ? '在线' : dialogData.online_state)}}
                                </li>
                            </ul>
                            <ul v-if="tagShow" class="title-tags scrollbar-hidden">
                                <li
                                    v-for="item in msgTags"
                                    :key="item.type"
                                    :class="{
                                        [item.type || 'msg']: true,
                                        active: msgType === item.type,
                                    }"
                                    @click="onMsgType(item.type)">
                                    <i class="no-dark-content"></i>
                                    <span>{{$L(item.label)}}</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <template v-if="dialogData.type === 'group'">
                        <ETooltip
                            placement="top"
                            :disabled="windowSmall"
                            :openDelay="600"
                            :content="$L('群设置')">
                            <i class="taskfont dialog-create" @click="groupInfoShow = true">&#xe6e9;</i>
                        </ETooltip>
                    </template>
                    <ETooltip v-else-if="dialogData.type === 'user' && !isMyDialog" placement="top" :disabled="windowSmall" :content="$L('创建群组')">
                        <i class="taskfont dialog-create" @click="openCreateGroup">&#xe646;</i>
                    </ETooltip>
                </div>
            </slot>
        </div>

        <!--消息列表-->
        <VirtualList
            ref="scroller"
            class="dialog-scroller scrollbar-overlay"
            :class="scrollerClass"
            :data-key="'id'"
            :data-sources="allMsgs"
            :data-component="msgItem"

            :item-class-add="itemClassAdd"
            :extra-props="{dialogData, operateVisible, operateItem, isMyDialog, msgId}"
            :estimate-size="78"
            :keeps="50"
            :disabled="scrollDisabled"
            @scroll="onScroll"
            @range="onRange"
            @totop="onPrevPage"

            @on-longpress="onLongpress"
            @on-view-reply="onViewReply"
            @on-view-text="onViewText"
            @on-view-file="onViewFile"
            @on-down-file="onDownFile"
            @on-reply-list="onReplyList"
            @on-emoji="onEmoji"
            @on-show-emoji-user="onShowEmojiUser">
            <template slot="header">
                <div v-if="(allMsgs.length === 0 && loadMsg) || prevId > 0" class="dialog-item loading"><Loading/></div>
                <div v-else-if="allMsgs.length === 0" class="dialog-item nothing">{{$L('暂无消息')}}</div>
            </template>
        </VirtualList>

        <!--底部输入-->
        <div class="dialog-footer" :class="footerClass" @click="onActive">
            <div class="dialog-newmsg" @click="onToBottom">{{$L(`有${msgNew}条新消息`)}}</div>
            <div class="dialog-goto" @click="onToBottom"><i class="taskfont">&#xe72b;</i></div>
            <DialogUpload
                ref="chatUpload"
                class="chat-upload"
                :dialog-id="dialogId"
                :reply-id="replyId"
                @on-progress="chatFile('progress', $event)"
                @on-success="chatFile('success', $event)"
                @on-error="chatFile('error', $event)"/>
            <div v-if="todoShow" class="chat-todo">
                <div class="todo-label">{{$L('待办')}}:</div>
                <ul class="scrollbar-hidden">
                    <li v-for="item in todoList" @click.stop="onViewTodo(item)">
                        <div class="todo-desc no-dark-content">{{$A.getMsgSimpleDesc(item.msg_data)}}</div>
                    </li>
                </ul>
            </div>
            <div v-if="isMute" class="chat-mute">
                {{$L('禁言发言')}}
            </div>
            <ChatInput
                v-else
                ref="input"
                v-model="msgText"
                :dialog-id="dialogId"
                :reply-id="replyActiveId"
                :emoji-bottom="windowSmall"
                :maxlength="200000"
                @on-focus="onEventFocus"
                @on-blur="onEventBlur"
                @on-more="onEventMore"
                @on-file="sendFileMsg"
                @on-send="sendMsg"
                @on-record="sendRecord"
                @on-record-state="onRecordState"
                @on-emoji-visible-change="onEventEmojiVisibleChange"
                @on-height-change="onHeightChange"
                @on-cancel-reply="onCancelReply"
                :placeholder="$L('输入消息...')"/>
        </div>

        <!--长按、右键-->
        <div class="operate-position" :style="operateStyles">
            <Dropdown
                trigger="custom"
                placement="top"
                :visible="operateVisible"
                @on-clickoutside="operateVisible = false"
                transferClassName="dialog-wrapper-operate"
                transfer>
                <div :style="{userSelect:operateVisible ? 'none' : 'auto', height: operateStyles.height}"></div>
                <DropdownMenu slot="list">
                    <DropdownItem name="action">
                        <ul class="operate-action">
                            <li v-if="msgId === 0" @click="onOperate('reply')">
                                <i class="taskfont">&#xe6eb;</i>
                                <span>{{ $L('回复') }}</span>
                            </li>
                            <li v-if="operateItem.userid == userId && operateItem.type === 'text'" @click="onOperate('update')">
                                <i class="taskfont">&#xe779;</i>
                                <span>{{ $L('编辑') }}</span>
                            </li>
                            <li v-if="operateHasText" @click="onOperate('copy')">
                                <i class="taskfont">&#xe77f;</i>
                                <span>{{ $L('复制') }}</span>
                            </li>
                            <li @click="onOperate('forward')">
                                <i class="taskfont">&#xe638;</i>
                                <span>{{ $L('转发') }}</span>
                            </li>
                            <li v-if="operateItem.userid == userId" @click="onOperate('withdraw')">
                                <i class="taskfont">&#xe637;</i>
                                <span>{{ $L('撤回') }}</span>
                            </li>
                            <template v-if="operateItem.type === 'file'">
                                <li @click="onOperate('view')">
                                    <i class="taskfont">&#xe77b;</i>
                                    <span>{{ $L('查看') }}</span>
                                </li>
                                <li @click="onOperate('down')">
                                    <i class="taskfont">&#xe7a8;</i>
                                    <span>{{ $L('下载') }}</span>
                                </li>
                            </template>
                            <li @click="onOperate('tag')">
                                <i class="taskfont">&#xe61e;</i>
                                <span>{{ $L(operateItem.tag ? '取消标注' : '标注') }}</span>
                            </li>
                            <li v-if="operateItem.type === 'text'" @click="onOperate('newTask')">
                                <i class="taskfont">&#xe7b8;</i>
                                <span>{{ $L('新任务') }}</span>
                            </li>
                            <li @click="onOperate('todo')">
                                <i class="taskfont">&#xe7b7;</i>
                                <span>{{ $L(operateItem.todo ? '取消待办' : '设待办') }}</span>
                            </li>
                        </ul>
                    </DropdownItem>
                    <DropdownItem name="emoji" class="dropdown-emoji">
                        <ul class="operate-emoji scrollbar-hidden">
                            <li
                                v-for="(emoji, key) in operateEmojis"
                                :key="key"
                                v-html="emoji"
                                class="no-dark-content"
                                @click="onOperate('emoji', emoji)"></li>
                        </ul>
                    </DropdownItem>
                </DropdownMenu>
            </Dropdown>
        </div>

        <!--拖动提示-->
        <div v-if="dialogDrag" class="drag-over" @click="dialogDrag=false">
            <div class="drag-text">{{$L('拖动到这里发送')}}</div>
        </div>

        <!--拖动发送提示-->
        <Modal
            v-model="pasteShow"
            :title="$L(pasteTitle)"
            :cancel-text="$L('取消')"
            :ok-text="$L('发送')"
            :enter-ok="true"
            @on-ok="pasteSend">
            <ul class="dialog-wrapper-paste" :class="pasteWrapperClass">
                <li v-for="item in pasteItem">
                    <img v-if="item.type == 'image'" :src="item.result"/>
                    <div v-else>{{$L('文件')}}: {{item.name}} ({{$A.bytesToSize(item.size)}})</div>
                </li>
            </ul>
        </Modal>

        <!--创建群组-->
        <Modal
            v-model="createGroupShow"
            :title="$L('创建群组')"
            :mask-closable="false">
            <Form :model="createGroupData" label-width="auto" @submit.native.prevent>
                <FormItem prop="userids" :label="$L('群成员')">
                    <UserInput v-model="createGroupData.userids" :uncancelable="createGroupData.uncancelable" :multiple-max="100" :placeholder="$L('选择项目成员')"/>
                </FormItem>
                <FormItem prop="chat_name" :label="$L('群名称')">
                    <Input v-model="createGroupData.chat_name" :placeholder="$L('输入群名称（选填）')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="createGroupShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="createGroupLoad > 0" @click="onCreateGroup">{{$L('创建')}}</Button>
            </div>
        </Modal>

        <!-- 转发 -->
        <Modal
            v-model="forwardShow"
            :title="$L('转发')"
            :mask-closable="false">
            <Form ref="forwardForm" :model="forwardData" label-width="auto" @submit.native.prevent>
                <FormItem prop="dialogids" :label="$L('最近聊天')">
                    <Select
                        v-model="forwardData.dialogids"
                        :placeholder="$L('选择转发对话')"
                        :multiple-max="20"
                        multiple
                        filterable
                        transfer-class-name="dialog-wrapper-forward">
                        <div slot="drop-prepend" class="forward-drop-prepend">{{$L('最多只能选择20个')}}</div>
                        <Option
                            v-for="(dialog, key) in dialogList"
                            :value="dialog.id"
                            :key="key"
                            :key-value="dialog.name"
                            :label="dialog.name">
                            <div class="forward-option">
                                <div class="forward-avatar">
                                    <template v-if="dialog.type=='group'">
                                        <i v-if="dialog.group_type=='project'" class="taskfont icon-avatar project">&#xe6f9;</i>
                                        <i v-else-if="dialog.group_type=='task'" class="taskfont icon-avatar task">&#xe6f4;</i>
                                        <Icon v-else class="icon-avatar" type="ios-people" />
                                    </template>
                                    <div v-else-if="dialog.dialog_user" class="user-avatar"><UserAvatar :userid="dialog.dialog_user.userid" :size="26"/></div>
                                    <Icon v-else class="icon-avatar" type="md-person" />
                                </div>
                                <div class="forward-name">{{ dialog.name }}</div>
                            </div>
                        </Option>
                    </Select>
                </FormItem>
                <FormItem prop="userids" :label="$L('团队成员')">
                    <UserInput v-model="forwardData.userids" :multiple-max="20" :placeholder="`(${$L('或')}) ${$L('选择转发成员')}`"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="forwardShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="forwardLoad" @click="onForward('submit')">{{$L('转发')}}</Button>
            </div>
        </Modal>

        <!-- 设置待办 -->
        <Modal
            v-model="todoSettingShow"
            :title="$L('设置待办')"
            :mask-closable="false">
            <Form ref="todoSettingForm" :model="todoSettingData" label-width="auto" @submit.native.prevent>
                <FormItem prop="type" :label="$L('当前会话')">
                    <RadioGroup v-model="todoSettingData.type">
                        <Radio label="all">{{$L('所有成员')}}</Radio>
                        <Radio label="user">{{$L('指定成员')}}</Radio>
                    </RadioGroup>
                </FormItem>
                <FormItem v-if="todoSettingData.type === 'user'" prop="userids">
                    <UserInput v-model="todoSettingData.userids" :dialog-id="dialogId" :placeholder="$L('选择指定成员')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="todoSettingShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="todoSettingLoad > 0" @click="onTodo('submit')">{{$L('确定')}}</Button>
            </div>
        </Modal>

        <!--群设置-->
        <DrawerOverlay
            v-model="groupInfoShow"
            placement="right"
            :size="400">
            <DialogGroupInfo v-if="groupInfoShow" :dialogId="dialogId" @on-close="groupInfoShow=false"/>
        </DrawerOverlay>

        <!--回复列表-->
        <DrawerOverlay
            v-model="replyListShow"
            placement="right"
            class-name="dialog-wrapper-drawer-list"
            :size="500">
            <DialogWrapper
                v-if="replyListShow"
                :dialogId="dialogId"
                :msgId="replyListId"
                class="drawer-list">
                <div slot="head" class="drawer-title">{{$L('回复消息')}}</div>
            </DialogWrapper>
        </DrawerOverlay>

        <!--回应详情-->
        <DrawerOverlay
            v-model="respondShow"
            placement="right"
            :size="400">
            <DialogRespond v-if="respondShow" :respond-data="respondData" @on-close="respondShow=false"/>
        </DrawerOverlay>

        <!--待办完成-->
        <DrawerOverlay
            v-model="todoViewShow"
            placement="right"
            class-name="dialog-wrapper-drawer-list"
            :size="500">
            <div class="dialog-wrapper drawer-list">
                <div class="dialog-nav">
                    <div class="drawer-title">{{$L('待办消息')}}</div>
                </div>
                <div class="dialog-scroller scrollbar-overlay">
                    <DialogItem
                        v-if="todoViewMsg"
                        :source="todoViewMsg"
                        @on-view-text="onViewText"
                        @on-view-file="onViewFile"
                        @on-down-file="onDownFile"
                        @on-emoji="onEmoji"
                        simpleView/>
                    <Button class="original-button" icon="md-exit" type="text" @click="onPosTodo">{{ $L("回到原文") }}</Button>
                </div>
                <div class="todo-button">
                    <Button type="primary" size="large" icon="md-checkbox-outline" @click="onDoneTodo" :loading="todoViewLoad" long>{{ $L("完成") }}</Button>
                </div>
            </div>
        </DrawerOverlay>
    </div>
</template>

<script>
import {mapGetters, mapState} from "vuex";
import DialogItem from "./DialogItem";
import DialogUpload from "./DialogUpload";
import UserInput from "../../../components/UserInput";
import DrawerOverlay from "../../../components/DrawerOverlay";
import DialogGroupInfo from "./DialogGroupInfo";
import DialogRespond from "./DialogRespond";
import ChatInput from "./ChatInput";

import VirtualList from 'vue-virtual-scroll-list-hi'
import {Store} from "le5le-store";

export default {
    name: "DialogWrapper",
    components: {
        DialogRespond,
        DialogItem,
        VirtualList,
        ChatInput,
        DialogGroupInfo,
        DrawerOverlay,
        UserInput,
        DialogUpload
    },

    props: {
        dialogId: {
            type: Number,
            default: 0
        },
        msgId: {
            type: Number,
            default: 0
        },
        autoFocus: {
            type: Boolean,
            default: false
        },
        beforeBack: Function
    },

    data() {
        return {
            msgItem: DialogItem,
            msgText: '',
            msgNew: 0,
            msgType: '',

            allMsgs: [],
            tempMsgs: [],

            pasteShow: false,
            pasteFile: [],
            pasteItem: [],

            createGroupShow: false,
            createGroupData: {},
            createGroupLoad: 0,

            forwardShow: false,
            forwardLoad: false,
            forwardData: {
                dialogids: [],
                userids: [],
            },

            openId: 0,
            dialogDrag: false,
            groupInfoShow: false,

            navStyle: {},

            operateVisible: false,
            operateHasText: false,
            operateStyles: {},
            operateItem: {},
            operateEmojis: ['👌', '🤝', '🎉', '❤️', '👍', '🥰', '🥳️', '✅', '❌', '⭕️', '❓', '🚀', '👀'],

            recordState: '',
            wrapperStart: {},

            scrollTail: 0,
            preventMoreLoad: false,
            preventToBottom: false,

            replyActiveId: 0,
            replyActiveIndex: -1,
            replyActiveUpdate: false,

            replyListShow: false,
            replyListId: 0,

            respondShow: false,
            respondData: {},

            todoSettingShow: false,
            todoSettingLoad: 0,
            todoSettingData: {
                type: 'all',
                userids: [],
            },

            todoViewLoad: false,
            todoViewShow: false,
            todoViewTmp: {},
            todoViewMid: 0,
            todoViewId: 0,

            scrollDisabled: false,
            scrollDirection: null,
            scrollAction: 0,
            scrollTmp: 0,
        }
    },

    beforeDestroy() {
        this.$store.dispatch('forgetInDialog', this._uid)
    },

    computed: {
        ...mapState([
            'userIsAdmin',
            'taskId',
            'dialogSearchMsgId',
            'dialogMsgs',
            'dialogTodos',
            'dialogMsgTransfer',
            'cacheDialogs',
            'wsOpenNum',
            'touchBackInProgress',
            'dialogIns'
        ]),

        ...mapGetters(['isLoad']),

        isReady() {
            return this.dialogId > 0 && this.dialogData.id > 0
        },

        dialogData() {
            return this.cacheDialogs.find(({id}) => id == this.dialogId) || {};
        },

        dialogList() {
            return this.cacheDialogs.filter(dialog => {
                return !(dialog.name === undefined || dialog.dialog_delete === 1);
            }).sort((a, b) => {
                if (a.top_at || b.top_at) {
                    return $A.Date(b.top_at) - $A.Date(a.top_at);
                }
                if (a.todo_num > 0 || b.todo_num > 0) {
                    return b.todo_num - a.todo_num;
                }
                return $A.Date(b.last_at) - $A.Date(a.last_at);
            });
        },

        dialogMsgList() {
            if (!this.isReady) {
                return [];
            }
            return this.dialogMsgs.filter(item => item.dialog_id == this.dialogId);
        },

        tempMsgList() {
            if (!this.isReady) {
                return [];
            }
            return this.tempMsgs.filter(item => item.dialog_id == this.dialogId);
        },

        allMsgList() {
            const array = [];
            array.push(...this.dialogMsgList.filter(item => this.msgFilter(item)));
            if (this.msgId > 0) {
                const msgItem = this.dialogMsgs.find(item => item.id == this.msgId)
                if (msgItem) {
                    array.unshift(msgItem)
                }
            }
            if (this.tempMsgList.length > 0) {
                const ids = array.map(({id}) => id)
                const tempMsgList = this.tempMsgList.filter(item => !ids.includes(item.id) && this.msgFilter(item))
                if (tempMsgList.length > 0) {
                    array.push(...tempMsgList)
                }
            }
            return array.sort((a, b) => {
                return a.id - b.id;
            })
        },

        loadMsg() {
            return this.isLoad(`msg::${this.dialogId}-${this.msgId}-${this.msgType}`)
        },

        prevId() {
            if (this.allMsgs.length > 0) {
                return $A.runNum(this.allMsgs[0].prev_id)
            }
            return 0
        },

        peopleNum() {
            return this.dialogData.type === 'group' ? $A.runNum(this.dialogData.people) : 0;
        },

        pasteTitle() {
            const {pasteItem} = this;
            let hasImage = pasteItem.find(({type}) => type == 'image')
            let hasFile = pasteItem.find(({type}) => type != 'image')
            if (hasImage && hasFile) {
                return '发送文件/图片'
            } else if (hasImage) {
                return '发送图片'
            }
            return '发送文件'
        },

        msgTags() {
            const array = [
                {type: '', label: '消息'},
            ];
            if (this.dialogData.has_tag) {
                array.push({type: 'tag', label: '标注'})
            }
            if (this.dialogData.has_image) {
                array.push({type: 'image', label: '图片'})
            }
            if (this.dialogData.has_file) {
                array.push({type: 'file', label: '文件'})
            }
            if (this.dialogData.has_link) {
                array.push({type: 'link', label: '链接'})
            }
            if (this.dialogData.group_type === 'project') {
                array.push({type: 'project', label: '打开项目'})
            }
            if (this.dialogData.group_type === 'task') {
                array.push({type: 'task', label: '打开任务'})
            }
            return array
        },

        todoList() {
            if (!this.dialogData.todo_num) {
                return []
            }
            return this.dialogTodos.filter(item => !item.done_at && item.dialog_id == this.dialogId).sort((a, b) => {
                return b.id - a.id;
            });
        },

        todoShow() {
            return this.todoList.length > 0 && this.windowScrollY === 0 && this.replyId === 0
        },

        wrapperClass() {
            if (['ready', 'ing'].includes(this.recordState)) {
                return ['record-ready']
            }
            return null
        },

        tagShow() {
            return this.msgTags.length > 1 && this.windowScrollY === 0
        },

        scrollerClass() {
            return !this.$slots.head && this.tagShow ? 'default-header' : null
        },

        pasteWrapperClass() {
            if (this.pasteItem.find(({type}) => type !== 'image')) {
                return ['multiple'];
            }
            return [];
        },

        footerClass() {
            if (this.msgNew > 0 && this.allMsgs.length > 0) {
                return 'newmsg'
            }
            if (this.scrollTail > 500) {
                return 'goto'
            }
            return null
        },

        msgUnreadOnly() {
            let num = 0;
            this.cacheDialogs.some(dialog => {
                num += $A.getDialogUnread(dialog);
            })
            if (num <= 0) {
                return '';
            }
            if (num > 99) {
                num = "99+"
            }
            return String(num);
        },

        isMyDialog() {
            const {dialogData, userId} = this;
            return dialogData.dialog_user && dialogData.dialog_user.userid == userId
        },

        isMute() {
            if (this.dialogData.group_type === 'all') {
                if (this.dialogData.all_group_mute === 'all') {
                    return true
                } else if (this.dialogData.all_group_mute === 'user') {
                    if (!this.userIsAdmin) {
                        return true
                    }
                }
            }
            return false
        },

        replyId() {
            return parseInt(this.msgId > 0 ? this.msgId : this.replyActiveId)
        },

        replyItem() {
            return this.replyId ? this.dialogMsgs.find(({id}) => id === this.replyId) : null
        },

        todoViewMsg() {
            if (this.todoViewMid) {
                const msg = this.dialogMsgs.find(item => item.id == this.todoViewMid)
                if (msg) {
                    return msg
                }
                if (this.todoViewTmp.id === this.todoViewMid) {
                    return this.todoViewTmp
                }
            }
            return null
        }
    },

    watch: {
        dialogId: {
            handler(dialog_id) {
                if (dialog_id) {
                    this.tempMsgs = []
                    this.msgNew = 0
                    //
                    if (this.allMsgList.length > 0) {
                        this.allMsgs = this.allMsgList
                        requestAnimationFrame(this.onToBottom)
                    }
                    this.msgType = '';
                    this.$store.dispatch("getDialogMsgs", {
                        dialog_id,
                        msg_id: this.msgId
                    }).then(_ => {
                        this.openId = dialog_id;
                        setTimeout(this.onSearchMsgId, 100)
                    }).catch(_ => {});
                    //
                    this.$store.dispatch('saveInDialog', {
                        uid: this._uid,
                        dialog_id,
                    })
                    //
                    if (this.autoFocus) {
                        this.inputFocus()
                    }
                }
            },
            immediate: true
        },

        msgType(msg_type) {
            if (msg_type) {
                this.$store.dispatch("getDialogMsgs", {
                    dialog_id: this.dialogId,
                    msg_id: this.msgId,
                    msg_type,
                    save_cancel: true,
                }).then(({data}) => {
                    if (data.list.length > 0) {
                        const ids = this.tempMsgs.map(item => item.id)
                        const list = data.list.filter(item => !ids.includes(item.id))
                        if (list.length > 0) {
                            this.tempMsgs.push(...list.map(item => Object.assign(item, {
                                isMsgType: true
                            })))
                        }
                    }
                }).catch(_ => {});
            } else {
                this.tempMsgs = this.tempMsgs.filter(({isMsgType}) => isMsgType !== true)
            }
            requestAnimationFrame(this.onToBottom)
        },

        dialogSearchMsgId() {
            this.onSearchMsgId();
        },

        dialogMsgTransfer: {
            handler({time, msgFile, msgRecord, msgText}) {
                if (time > $A.Time()) {
                    this.$store.state.dialogMsgTransfer.time = 0;
                    this.$nextTick(() => {
                        if ($A.isArray(msgFile) && msgFile.length > 0) {
                            this.sendFileMsg(msgFile);
                        } else if ($A.isJson(msgRecord) && msgRecord.duration > 0) {
                            this.sendRecord(msgRecord);
                        } else if (msgText) {
                            this.sendMsg(msgText);
                        }
                    });
                }
            },
            immediate: true
        },

        wsOpenNum(num) {
            if (num <= 1 || this.msgType) {
                return
            }
            this.$store.dispatch("getDialogMsgs", {
                dialog_id: this.dialogId,
                msg_id: this.msgId,
            }).catch(_ => {});
        },

        allMsgList(newList, oldList) {
            const {tail} = this.scrollInfo();
            this.allMsgs = newList;
            //
            if (!this.windowActive || (tail > 10 && oldList.length > 0)) {
                const lastId = oldList[oldList.length - 1].id
                const tmpList = newList.filter(item => item.id && item.id > lastId)
                this.msgNew += tmpList.length
            } else {
                if (!this.preventToBottom) {
                    this.$nextTick(this.onToBottom)
                }
            }
        },

        windowScrollY(val) {
            if ($A.isIos()) {
                const {tail} = this.scrollInfo();
                this.navStyle = {
                    marginTop: val + 'px'
                }
                if (tail <= 10) {
                    requestAnimationFrame(this.onToBottom)
                }
            }
        },

        windowActive(active) {
            if (active && this.autoFocus) {
                const lastDialog = $A.last(this.dialogIns)
                if (lastDialog && lastDialog.uid === this._uid) {
                    this.inputFocus()
                }
            }
        },

        dialogDrag(val) {
            if (val) {
                this.operateVisible = false;
            }
        },

        replyActiveIndex(index) {
            if (index > -1) {
                setTimeout(_ => this.replyActiveIndex = -1, 800)
            }
        }
    },

    methods: {
        /**
         * 发送消息
         * @param text
         */
        sendMsg(text) {
            let msgText;
            if (typeof text === "string" && text) {
                msgText = text;
            } else {
                msgText = this.msgText;
                this.msgText = '';
            }
            if (msgText == '') {
                this.inputFocus();
                return;
            }
            msgText = msgText.replace(/<\/span> <\/p>$/, "</span></p>")
            //
            if (this.replyActiveUpdate) {
                // 修改
                const update_id = this.replyId
                this.$store.dispatch("setLoad", {
                    key: `msg-${update_id}`,
                    delay: 600
                })
                this.replyActiveId = 0;
                this.onActive();
                //
                this.$store.dispatch("call", {
                    url: 'dialog/msg/sendtext',
                    data: {
                        dialog_id: this.dialogId,
                        update_id,
                        text: msgText,
                    },
                    method: 'post',
                    complete: _ => this.$store.dispatch("cancelLoad", `msg-${update_id}`)
                }).then(({data}) => {
                    this.sendSuccess(data)
                    this.onPositionId(update_id)
                }).catch(({msg}) => {
                    $A.modalError(msg)
                });
            } else {
                // 发送
                const tempId = $A.randNum(1000000000, 9999999999)
                const typeLoad = $A.stringLength(msgText) > 2000
                const tempMsg = {
                    id: tempId,
                    dialog_id: this.dialogData.id,
                    reply_id: this.replyId,
                    reply_data: this.replyItem,
                    type: typeLoad ? 'loading' : 'text',
                    userid: this.userId,
                    msg: {
                        text: typeLoad ? '' : msgText,
                    },
                }
                this.tempMsgs.push(tempMsg)
                this.msgType = ''
                this.replyActiveId = 0;
                this.onActive();
                this.$nextTick(this.onToBottom)
                //
                this.$store.dispatch("call", {
                    url: 'dialog/msg/sendtext',
                    data: {
                        dialog_id: tempMsg.dialog_id,
                        reply_id: tempMsg.reply_id,
                        text: msgText,
                    },
                    method: 'post',
                    complete: _ => this.tempMsgs = this.tempMsgs.filter(({id}) => id != tempId)
                }).then(({data}) => {
                    this.sendSuccess(data)
                }).catch(({msg}) => {
                    $A.modalError(msg)
                });
            }
        },

        /**
         * 发送录音
         * @param msg {base64, duration}
         */
        sendRecord(msg) {
            const tempId = $A.randNum(1000000000, 9999999999);
            const tempMsg = {
                id: tempId,
                dialog_id: this.dialogData.id,
                reply_id: this.replyId,
                reply_data: this.replyItem,
                type: 'loading',
                userid: this.userId,
                msg,
            }
            this.tempMsgs.push(tempMsg)
            this.msgType = ''
            this.onActive()
            this.$nextTick(this.onToBottom)
            //
            this.$store.dispatch("call", {
                url: 'dialog/msg/sendrecord',
                data: Object.assign(msg, {
                    dialog_id: this.dialogId,
                    reply_id: this.replyId,
                }),
                method: 'post',
                complete: _ => this.tempMsgs = this.tempMsgs.filter(({id}) => id != tempId)
            }).then(({data}) => {
                this.sendSuccess(data);
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        /**
         * 发送文件
         * @param row
         */
        sendFileMsg(row) {
            const files = $A.isArray(row) ? row : [row];
            if (files.length > 0) {
                this.msgType = '';
                this.pasteFile = [];
                this.pasteItem = [];
                files.some(file => {
                    const item = {
                        type: $A.getMiddle(file.type, null, '/'),
                        name: file.name,
                        size: file.size,
                        result: null
                    }
                    if (item.type === 'image') {
                        const reader = new FileReader();
                        reader.readAsDataURL(file);
                        reader.onload = ({target}) => {
                            item.result = target.result
                            this.pasteFile.push(file)
                            this.pasteItem.push(item)
                            this.pasteShow = true
                        }
                    } else {
                        this.pasteFile.push(file)
                        this.pasteItem.push(item)
                        this.pasteShow = true
                    }
                });
            }
        },

        msgFilter(item) {
            if (this.msgType) {
                if (this.msgType === 'tag') {
                    if (!item.tag) {
                        return false
                    }
                } else if (this.msgType === 'link') {
                    if (!item.link) {
                        return false
                    }
                } else if (this.msgType !== item.mtype) {
                    return false
                }
            }
            if (this.msgId) {
                if (item.reply_id != this.msgId) {
                    return false
                }
            }
            return true
        },

        onSearchMsgId() {
            if (this.dialogSearchMsgId > 0 && this.openId === this.dialogId) {
                this.onPositionId(this.dialogSearchMsgId)
                this.$store.state.dialogSearchMsgId = 0
            }
        },

        onPositionId(position_id, msg_id = 0) {
            return new Promise(resolve => {
                if (position_id === 0) {
                    return
                }
                const index = this.allMsgs.findIndex(item => item.id === position_id)
                if (index > -1) {
                    this.onToIndex(index)
                    resolve()
                } else {
                    if (msg_id > 0) {
                        this.$store.dispatch("setLoad", {
                            key: `msg-${msg_id}`,
                            delay: 600
                        })
                    }
                    this.msgType = '';
                    this.preventToBottom = true;
                    this.$store.dispatch("getDialogMsgs", {
                        dialog_id: this.dialogId,
                        msg_id: this.msgId,
                        position_id
                    }).finally(_ => {
                        const index = this.allMsgs.findIndex(item => item.id === position_id)
                        if (index > -1) {
                            this.onToIndex(index)
                            resolve()
                        }
                        if (msg_id > 0) {
                            this.$store.dispatch("cancelLoad", `msg-${msg_id}`)
                        }
                        this.preventToBottom = false;
                    })
                }
            })
        },

        onViewTodo(item) {
            if (this.operateVisible) {
                return
            }
            this.todoViewId = item.id
            this.todoViewMid = item.msg_id
            this.todoViewShow = true
            //
            const index = this.allMsgs.findIndex(item => item.id === this.todoViewMid)
            if (index === -1) {
                this.$store.dispatch("call", {
                    url: 'dialog/msg/one',
                    data: {
                        msg_id: this.todoViewMid
                    },
                }).then(({data}) => {
                    this.todoViewTmp = data
                })
            }
        },

        onCloseTodo() {
            this.todoViewLoad = false
            this.todoViewShow = false
            this.todoViewTmp = {}
            this.todoViewMid = 0
            this.todoViewId = 0
        },

        onPosTodo() {
            if (!this.todoViewMid) {
                return
            }
            this.onPositionId(this.todoViewMid).then(this.onCloseTodo)
        },

        onDoneTodo() {
            if (!this.todoViewId || this.todoViewLoad) {
                return
            }
            this.todoViewLoad = true
            //
            this.$store.dispatch("call", {
                url: 'dialog/msg/done',
                data: {
                    id: this.todoViewId,
                },
            }).then(({data}) => {
                this.$store.dispatch("saveDialogTodo", {
                    id: this.todoViewId,
                    done_at: $A.formatDate("Y-m-d H:i:s")
                })
                this.$store.dispatch("saveDialog", {
                    id: this.dialogId,
                    todo_num: this.todoList.length
                })
                if (data.add) {
                    this.sendSuccess(data.add)
                }
                if (this.todoList.length === 0) {
                    this.$store.dispatch("getDialogTodo", this.dialogId)
                }
                this.onCloseTodo()
            }).catch(({msg}) => {
                $A.modalError(msg)
            }).finally(_ => {
                this.todoViewLoad = false
            });
        },

        itemClassAdd(index) {
            return index === this.replyActiveIndex ? 'common-shake' : '';
        },

        inputFocus() {
            this.$nextTick(_ => {
                this.$refs.input && this.$refs.input.focus()
            })
        },

        onRecordState(state) {
            this.recordState = state;
        },

        chatPasteDrag(e, type) {
            this.dialogDrag = false;
            const files = type === 'drag' ? e.dataTransfer.files : e.clipboardData.files;
            const postFiles = Array.prototype.slice.call(files);
            if (postFiles.length > 0) {
                e.preventDefault();
                this.sendFileMsg(postFiles);
            }
        },

        chatDragOver(show, e) {
            let random = (this.__dialogDrag = $A.randomString(8));
            if (!show) {
                setTimeout(() => {
                    if (random === this.__dialogDrag) {
                        this.dialogDrag = show;
                    }
                }, 150);
            } else {
                if (e.dataTransfer.effectAllowed === 'move') {
                    return;
                }
                this.dialogDrag = true;
            }
        },

        onTouchStart(e) {
            this.wrapperStart = Object.assign(this.scrollInfo(), {
                clientY: e.touches[0].clientY,
                exclud: !this.$refs.scroller.$el.contains(e.target),
            });
        },

        onTouchMove(e) {
            if (this.windowSmall && this.windowScrollY > 0) {
                if (this.wrapperStart.exclud) {
                    e.preventDefault();
                    return;
                }
                if (this.wrapperStart.clientY > e.touches[0].clientY) {
                    // 向上滑动
                    if (this.wrapperStart.tail === 0) {
                        e.preventDefault();
                    }
                } else {
                    // 向下滑动
                    if (this.wrapperStart.offset === 0) {
                        e.preventDefault();
                    }
                }
            }
        },

        pasteSend() {
            this.pasteFile.some(file => {
                this.$refs.chatUpload.upload(file)
            });
        },

        chatFile(type, file) {
            switch (type) {
                case 'progress':
                    this.tempMsgs.push({
                        id: file.tempId,
                        dialog_id: this.dialogData.id,
                        reply_id: this.replyId,
                        type: 'loading',
                        userid: this.userId,
                        msg: { },
                    })
                    this.replyActiveId = 0;
                    this.onActive()
                    this.$nextTick(this.onToBottom)
                    break;

                case 'error':
                    this.tempMsgs = this.tempMsgs.filter(({id}) => id != file.tempId)
                    break;

                case 'success':
                    this.tempMsgs = this.tempMsgs.filter(({id}) => id != file.tempId)
                    this.sendSuccess(file.data)
                    break;
            }
        },

        sendSuccess(data) {
            if ($A.isArray(data)) {
                data.some(this.sendSuccess)
                return;
            }
            this.$store.dispatch("saveDialogMsg", data);
            if (!this.replyActiveUpdate) {
                this.$store.dispatch("increaseTaskMsgNum", this.dialogId);
                this.$store.dispatch("increaseMsgReplyNum", data.reply_id);
                this.$store.dispatch("updateDialogLastMsg", data);
            }
            this.onCancelReply();
            this.onActive();
        },

        onEventFocus() {
            this.$emit("on-focus")
        },

        onEventBlur() {
            this.$emit("on-blur")
        },

        onEventMore(e) {
            switch (e) {
                case 'call':
                    this.onCallTel()
                    break;

                case 'image':
                case 'file':
                    this.$refs.chatUpload.handleClick()
                    break;
            }
        },

        onCallTel() {
            this.$store.dispatch("call", {
                url: 'dialog/tel',
                data: {
                    dialog_id: this.dialogId,
                },
                spinner: 300,
            }).then(({data}) => {
                if (data.tel) {
                    $A.eeuiAppSendMessage({
                        action: 'callTel',
                        tel: data.tel
                    });
                }
                if (data.add) {
                    this.$store.dispatch("saveDialogMsg", data.add);
                    this.$store.dispatch("updateDialogLastMsg", data.add);
                    this.onActive();
                }
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        onEventEmojiVisibleChange(val) {
            if (val && this.windowSmall) {
                this.onToBottom();
            }
        },

        onHeightChange({newVal, oldVal}) {
            const diff = newVal - oldVal;
            if (diff !== 0) {
                const {offset, tail} = this.scrollInfo()
                if (tail > 0) {
                    this.onToOffset(offset + diff)
                }
            }
        },

        onActive() {
            this.$emit("on-active");
        },

        onToBottom() {
            this.msgNew = 0;
            const scroller = this.$refs.scroller;
            if (scroller) {
                scroller.scrollToBottom();
                requestAnimationFrame(_ => scroller.scrollToBottom())    // 确保滚动到
            }
        },

        onToIndex(index) {
            const scroller = this.$refs.scroller;
            if (scroller) {
                scroller.stopToBottom();
                scroller.scrollToIndex(index, -100);
                requestAnimationFrame(_ => scroller.scrollToIndex(index, -100))    // 确保滚动到
            }
            requestAnimationFrame(_ => this.replyActiveIndex = index)
        },

        onToOffset(offset) {
            const scroller = this.$refs.scroller;
            if (scroller) {
                scroller.stopToBottom();
                scroller.scrollToOffset(offset);
                setTimeout(_ => scroller.scrollToOffset(offset), 10)  // 预防出现白屏的情况
            }
        },

        scrollInfo() {
            const scroller = this.$refs.scroller;
            if (scroller) {
                return scroller.scrollInfo();
            } else {
                return {
                    offset: 0,
                    scale: 0,
                    tail: 0
                }
            }
        },

        openProject() {
            if (!this.dialogData.group_info) {
                return;
            }
            if (this.windowSmall) {
                this.$store.dispatch("openDialog", 0);
            }
            this.goForward({name: 'manage-project', params: {projectId:this.dialogData.group_info.id}});
        },

        openTask() {
            if (!this.dialogData.group_info) {
                return;
            }
            if (this.taskId > 0) {
                // 如果当前打开着任务窗口则关闭对话窗口
                this.$store.dispatch("openDialog", 0);
            }
            this.$store.dispatch("openTask", this.dialogData.group_info.id);
        },

        onPrevPage() {
            if (this.prevId === 0) {
                return
            }
            this.$store.dispatch('getDialogMsgs', {
                dialog_id: this.dialogId,
                msg_id: this.msgId,
                msg_type: this.msgType,
                prev_id: this.prevId,
                save_before: _ => this.scrollDisabled = true
            }).then(({data}) => {
                const ids = data.list.map(item => item.id)
                this.$nextTick(() => {
                    const scroller = this.$refs.scroller
                    const reducer = ids.reduce((previousValue, currentId) => {
                        const previousSize = typeof previousValue === "object" ? previousValue.size : scroller.getSize(previousValue)
                        return {size: previousSize + scroller.getSize(currentId)}
                    })
                    let offset = scroller.getOffset() + reducer.size
                    if (this.prevId === 0) {
                        offset -= 36
                    }
                    this.onToOffset(offset)
                    this.scrollDisabled = false
                });
            }).catch(() => {})
        },

        openCreateGroup() {
            this.createGroupData = {
                userids: this.dialogData.dialog_user ? [this.userId, this.dialogData.dialog_user.userid] : [this.userId],
                uncancelable: [this.userId]
            };
            this.createGroupShow = true;
        },

        onCreateGroup() {
            this.createGroupLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/group/add',
                data: this.createGroupData
            }).then(({data, msg}) => {
                $A.messageSuccess(msg);
                this.createGroupShow = false;
                this.createGroupData = {};
                this.$store.dispatch("saveDialog", data);
                this.$store.dispatch('openDialog', data.id)
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.createGroupLoad--;
            });
        },

        onForward(type) {
            if (type === 'open') {
                this.forwardData = {
                    dialogids: [],
                    userids: [],
                    msg_id: this.operateItem.id
                };
                this.forwardShow = true;
            } else if (type === 'submit') {
                if ($A.arrayLength(this.forwardData.dialogids) === 0 && $A.arrayLength(this.forwardData.userids) === 0) {
                    $A.messageWarning("请选择转发对话或成员");
                    return
                }
                this.forwardLoad = true;
                this.$store.dispatch("call", {
                    url: 'dialog/msg/forward',
                    data: this.forwardData
                }).then(({data, msg}) => {
                    this.forwardShow = false;
                    this.$store.dispatch("saveDialogMsg", data.msgs);
                    this.$store.dispatch("updateDialogLastMsg", data.msgs);
                    $A.messageSuccess(msg);
                }).catch(({msg}) => {
                    $A.modalError(msg);
                }).finally(_ => {
                    this.forwardLoad = false;
                });
            }
        },

        onScroll(event) {
            this.operateVisible = false;
            //
            const {tail} = this.scrollInfo();
            this.scrollTail = tail;
            if (this.scrollTail <= 10) {
                this.msgNew = 0;
            }
            //
            this.scrollAction = event.target.scrollTop;
            this.scrollDirection = this.scrollTmp <= this.scrollAction ? 'down' : 'up';
            setTimeout(_ => this.scrollTmp = this.scrollAction, 0);
        },

        onRange(range) {
            if (this.preventMoreLoad) {
                return
            }
            const key = this.scrollDirection === 'down' ? 'next_id' : 'prev_id';
            for (let i = range.start; i <= range.end; i++) {
                const rangeValue = this.allMsgs[i][key]
                if (rangeValue) {
                    const nearMsg = this.allMsgs[i + (key === 'next_id' ? 1 : -1)]
                    if (nearMsg && nearMsg.id != rangeValue) {
                        this.preventMoreLoad = true
                        this.$store.dispatch("getDialogMsgs", {
                            dialog_id: this.dialogId,
                            msg_id: this.msgId,
                            msg_type: this.msgType,
                            [key]: rangeValue,
                        }).finally(_ => {
                            this.preventMoreLoad = false
                        })
                    }
                }
            }
        },

        onBack() {
            if (!this.beforeBack) {
                return this.handleBack();
            }
            const before = this.beforeBack();
            if (before && before.then) {
                before.then(() => {
                    this.handleBack();
                });
            } else {
                this.handleBack();
            }
        },

        handleBack() {
            const {name, params} = this.$store.state.routeHistoryLast;
            if (name === this.$route.name && /\d+/.test(params.dialogId)) {
                this.goForward({name: this.$route.name});
            } else {
                this.goBack();
            }
        },

        onMsgType(type) {
            switch (type) {
                case 'project':
                    this.openProject()
                    break;

                case 'task':
                    this.openTask()
                    break;

                default:
                    this.msgType = type
                    break;
            }
        },

        onLongpress({event, el, msgData}) {
            this.operateVisible = this.operateItem.id === msgData.id;
            this.operateItem = $A.isJson(msgData) ? msgData : {};
            this.operateHasText = msgData.type === 'text' && msgData.msg.text.replace(/<[^>]+>/g,"").length > 0
            this.$nextTick(() => {
                const projectRect = el.getBoundingClientRect();
                const wrapRect = this.$el.getBoundingClientRect();
                this.operateStyles = {
                    left: `${event.clientX - wrapRect.left}px`,
                    top: `${projectRect.top + this.windowScrollY}px`,
                    height: projectRect.height + 'px',
                }
                this.operateVisible = true;
            })
        },

        onOperate(action, value = null) {
            this.operateVisible = false;
            this.$nextTick(_ => {
                switch (action) {
                    case "reply":
                        this.onReply()
                        break;

                    case "update":
                        this.onUpdate()
                        break;

                    case "copy":
                        if (this.operateHasText) {
                            const text = this.operateItem.msg.text.replace(/<[^>]+>/g, "")
                            this.$copyText(text).then(_ => $A.messageSuccess('复制成功')).catch(_ => $A.messageError('复制失败'));
                        } else {
                            $A.messageWarning('不可复制的内容');
                        }
                        break;

                    case "forward":
                        this.onForward('open')
                        break;

                    case "withdraw":
                        this.onWithdraw()
                        break;

                    case "view":
                        this.onViewFile()
                        break;

                    case "down":
                        this.onDownFile()
                        break;

                    case "tag":
                        this.onTag()
                        break;

                    case "newTask":
                        Store.set('addTask', {
                            owner: [this.userId],
                            content: $A.formatMsgBasic(this.operateItem.msg.text)
                        });
                        break;

                    case "todo":
                        this.onTodo()
                        break;

                    case "emoji":
                        this.onEmoji(value)
                        break;
                }
            })
        },

        onReply() {
            const {tail} = this.scrollInfo()
            this.replyActiveId = this.operateItem.id
            this.replyActiveUpdate = false
            this.inputFocus()
            if (tail <= 10) {
                requestAnimationFrame(this.onToBottom)
            }
        },

        onUpdate() {
            this.onReply()
            if (this.operateItem.type === 'text') {
                this.replyActiveUpdate = true
                this.msgText = $A.formatMsgBasic(this.operateItem.msg.text)
            }
        },

        onCancelReply() {
            if (this.replyActiveUpdate) {
                this.replyActiveUpdate = false
                this.msgText = ''
            }
            this.replyActiveId = 0;
        },

        onWithdraw() {
            $A.modalConfirm({
                content: `确定撤回此信息吗？`,
                okText: '撤回',
                loading: true,
                onOk: () => {
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'dialog/msg/withdraw',
                            data: {
                                msg_id: this.operateItem.id
                            },
                        }).then(() => {
                            resolve("消息已撤回");
                            this.$store.dispatch("forgetDialogMsg", this.operateItem.id);
                        }).catch(({msg}) => {
                            reject(msg);
                        });
                    })
                }
            });
        },

        onViewReply(data) {
            if (this.operateVisible) {
                return
            }
            this.onPositionId(data.reply_id, data.msg_id)
        },

        onViewText({target}) {
            if (this.operateVisible) {
                return
            }
            switch (target.nodeName) {
                case "IMG":
                    if (target.classList.contains('browse')) {
                        this.onViewPicture(target.currentSrc);
                    } else {
                        this.$store.state.previewImageIndex = 0;
                        this.$store.state.previewImageList = $A.getTextImagesInfo(target.outerHTML);
                    }
                    break;

                case "SPAN":
                    if (target.classList.contains('mention') && target.classList.contains('task')) {
                        this.$store.dispatch("openTask", $A.runNum(target.getAttribute("data-id")));
                    }
                    break;
            }
        },

        onViewFile(data) {
            if (this.operateVisible) {
                return
            }
            if (!$A.isJson(data)) {
                data = this.operateItem
            }
            const {msg} = data;
            if (['jpg', 'jpeg', 'gif', 'png'].includes(msg.ext)) {
                this.onViewPicture(msg.path);
                return
            }
            const path = `/single/file/msg/${data.id}`;
            if (this.$Electron) {
                this.$Electron.sendMessage('windowRouter', {
                    name: `file-msg-${data.id}`,
                    path: path,
                    userAgent: "/hideenOfficeTitle/",
                    force: false,
                    config: {
                        title: `${msg.name} (${$A.bytesToSize(msg.size)})`,
                        titleFixed: true,
                        parent: null,
                        width: Math.min(window.screen.availWidth, 1440),
                        height: Math.min(window.screen.availHeight, 900),
                    },
                    webPreferences: {
                        nodeIntegrationInSubFrames: msg.ext === 'drawio'
                    },
                });
            } else if (this.$isEEUiApp) {
                $A.eeuiAppOpenPage({
                    pageType: 'app',
                    pageTitle: `${msg.name} (${$A.bytesToSize(msg.size)})`,
                    url: 'web.js',
                    params: {
                        titleFixed: true,
                        url: $A.rightDelete(window.location.href, window.location.hash) + `#${path}`
                    },
                });
            } else {
                window.open($A.apiUrl(`..${path}`))
            }
        },

        onViewPicture(currentUrl) {
            const data = $A.cloneJSON(this.dialogMsgs.filter(item => {
                if (item.dialog_id === this.dialogId) {
                    if (item.type === 'file') {
                        return ['jpg', 'jpeg', 'gif', 'png'].includes(item.msg.ext);
                    } else if (item.type === 'text') {
                        return item.msg.text.match(/<img\s+class="browse"[^>]*?>/);
                    }
                }
                return false;
            })).sort((a, b) => {
                return a.id - b.id;
            });
            //
            const list = [];
            data.some(({type, msg}) => {
                if (type === 'file') {
                    list.push({
                        src: msg.path,
                        width: msg.width,
                        height: msg.height,
                    })
                } else if (type === 'text') {
                    list.push(...$A.getTextImagesInfo(msg.text))
                }
            })
            //
            const index = list.findIndex(({src}) => src === currentUrl);
            if (index > -1) {
                this.$store.state.previewImageIndex = index;
                this.$store.state.previewImageList = list;
            } else {
                this.$store.state.previewImageIndex = 0;
                this.$store.state.previewImageList = [currentUrl];
            }
        },

        onDownFile(data) {
            if (this.operateVisible) {
                return
            }
            if (!$A.isJson(data)) {
                data = this.operateItem
            }
            $A.modalConfirm({
                title: '下载文件',
                content: `${data.msg.name} (${$A.bytesToSize(data.msg.size)})`,
                okText: '立即下载',
                onOk: () => {
                    this.$store.dispatch('downUrl', $A.apiUrl(`dialog/msg/download?msg_id=${data.id}`))
                }
            });
        },

        onReplyList(data) {
            if (this.operateVisible) {
                return
            }
            this.replyListId = data.msg_id
            this.replyListShow = true
        },

        onEmoji(data) {
            if (!$A.isJson(data)) {
                data = {
                    msg_id: this.operateItem.id,
                    symbol: data,
                }
            }
            this.$store.dispatch("setLoad", {
                key: `msg-${data.msg_id}`,
                delay: 600
            })
            this.$store.dispatch("call", {
                url: 'dialog/msg/emoji',
                data,
            }).then(({data}) => {
                const index = this.dialogMsgs.findIndex(item => item.id == data.id)
                if (index > -1) {
                    this.$store.dispatch("saveDialogMsg", data);
                } else if (this.todoViewTmp.id === data.id) {
                    this.todoViewTmp = Object.assign({}, this.todoViewTmp, data)
                }
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.$store.dispatch("cancelLoad", `msg-${data.msg_id}`)
            });
        },

        onShowEmojiUser(data) {
            if (this.operateVisible) {
                return
            }
            this.respondData = data
            this.respondShow = true
        },

        onTag() {
            if (this.operateVisible) {
                return
            }
            const data = {
                msg_id: this.operateItem.id,
            }
            //
            this.$store.dispatch("setLoad", {
                key: `msg-${data.msg_id}`,
                delay: 600
            })
            this.$store.dispatch("call", {
                url: 'dialog/msg/tag',
                data,
            }).then(({data}) => {
                this.tagOrTodoSuccess(data)
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.$store.dispatch("cancelLoad", `msg-${data.msg_id}`)
            });
        },

        onTodo(type) {
            if (this.operateVisible) {
                return
            }
            if (type === 'submit') {
                if (this.todoSettingData.type === 'user' && $A.arrayLength(this.todoSettingData.userids) === 0) {
                    $A.messageWarning("选择指定成员");
                    return
                }
                this.todoSettingLoad++
                this.onTodoSubmit(this.todoSettingData).then(msg => {
                    $A.messageSuccess(msg)
                    this.todoSettingShow = false
                }).catch($A.messageError).finally(_ => {
                    this.todoSettingLoad--
                })
            } else {
                this.todoSettingData = {
                    type: 'all',
                    userids: [],
                    msg_id: this.operateItem.id,
                }
                if (this.operateItem.todo) {
                    $A.modalConfirm({
                        content: "你确定取消待办吗？",
                        cancelText: '取消',
                        okText: '确定',
                        loading: true,
                        onOk: () => this.onTodoSubmit(this.todoSettingData)
                    });
                } else {
                    this.todoSettingShow = true
                }
            }
        },

        onTodoSubmit(data) {
            return new Promise((resolve, reject) => {
                this.$store.dispatch("setLoad", {
                    key: `msg-${data.msg_id}`,
                    delay: 600
                })
                this.$store.dispatch("call", {
                    url: 'dialog/msg/todo',
                    data,
                }).then(({data, msg}) => {
                    resolve(msg)
                    this.tagOrTodoSuccess(data)
                    this.onActive()
                }).catch(({msg}) => {
                    reject(msg);
                }).finally(_ => {
                    this.$store.dispatch("cancelLoad", `msg-${data.msg_id}`)
                });
            })
        },

        tagOrTodoSuccess(data) {
            this.$store.dispatch("saveDialogMsg", data.update);
            if (data.add) {
                this.$store.dispatch("saveDialogMsg", data.add);
                this.$store.dispatch("updateDialogLastMsg", data.add);
            }
        },
    }
}
</script>
