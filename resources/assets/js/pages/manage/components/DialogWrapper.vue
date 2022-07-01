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
                        <i class="taskfont">&#xe72d;</i>
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
                                <UserAvatar :online.sync="dialogData.online_state" :userid="dialogData.dialog_user.userid" :size="42"/>
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
                            <template v-if="dialogData.type === 'group'">
                                <div v-if="dialogData.group_type === 'project'" class="sub-title pointer" @click="openProject">
                                    {{$L('项目聊天室')}} {{$L('打开项目管理')}}
                                </div>
                                <div v-else-if="dialogData.group_type === 'task'" class="sub-title pointer" @click="openTask">
                                    {{$L('任务聊天室')}} {{$L('查看任务详情')}}
                                </div>
                            </template>
                            <div v-else-if="dialogData.type === 'user'" :class="['sub-title', dialogData.online_state === true ? 'online' : 'offline']">
                                {{$L(dialogData.online_state === true ? '在线' : dialogData.online_state)}}
                            </div>
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
                <transition name="fade">
                    <div v-if="dialogMsgList.length > 10 && msgTags.length > 1 && windowScrollY === 0" class="nav-tags scrollbar-hidden">
                        <ul>
                            <li
                                v-for="item in msgTags"
                                :key="item.type"
                                :class="{
                                    [`tag-${item.type||'msg'}`]: true,
                                    active: msgType === item.type,
                                }"
                                @click="msgType=item.type">
                                <i></i>
                                <span>{{$L(item.label)}}</span>
                            </li>
                        </ul>
                    </div>
                </transition>
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
            :extra-props="{dialogData, operateVisible, operateItem, hidePercentage: isMyDialog, hideReply: msgId > 0}"
            :estimate-size="78"
            :keeps="70"
            @scroll="onScroll"
            @range="onRange"
            @totop="onPrevPage"

            @on-longpress="onLongpress"
            @on-view-reply="onViewReply"
            @on-view-text="onViewText"
            @on-view-file="onViewFile"
            @on-down-file="onDownFile"
            @on-reply-list="onReplyList"
            @on-emoji="onEmoji">
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
            <ChatInput
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
                            <li @click="onOperate('forward')">
                                <i class="taskfont">&#xe638;</i>
                                <span>{{ $L('转发') }}</span>
                            </li>
                            <template v-if="operateHasText">
                                <li @click="onOperate('copy')">
                                    <i class="taskfont">&#xe77f;</i>
                                    <span>{{ $L('复制') }}</span>
                                </li>
                                <li @click="onOperate('newTask')">
                                    <i class="taskfont">&#xe7b8;</i>
                                    <span>{{ $L('新任务') }}</span>
                                </li>
                            </template>
                            <template v-if="operateItem.userid == userId">
                                <li @click="onOperate('withdraw')">
                                    <i class="taskfont">&#xe637;</i>
                                    <span>{{ $L('撤回') }}</span>
                                </li>
                            </template>
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
                <FormItem prop="userids" :label="$L('转发给')">
                    <UserInput v-model="forwardData.userids" :multiple-max="20" :placeholder="$L('选择转发成员')"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="forwardShow=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="forwardLoad" @click="onForward('submit')">{{$L('转发')}}</Button>
            </div>
        </Modal>

        <!--群设置-->
        <DrawerOverlay
            v-model="groupInfoShow"
            placement="right"
            :size="400">
            <DialogGroupInfo v-if="groupInfoShow" :dialogId="dialogId"/>
        </DrawerOverlay>

        <!--回复列表-->
        <DrawerOverlay
            v-model="replyListShow"
            placement="right"
            class-name="dialog-wrapper-reply-list"
            :size="500">
            <DialogWrapper
                v-if="replyListShow && replyListItem"
                :dialogId="dialogId"
                :msgId="replyListId"
                class="reply-list">
                <div slot="head" class="dialog-scroller">
                    <DialogItem
                        :source="replyListItem"
                        @on-view-text="onViewText"
                        @on-view-file="onViewFile"
                        @on-emoji="onEmoji"
                        hidePercentage
                        hideReply
                        isReply/>
                </div>
            </DialogWrapper>
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
import ChatInput from "./ChatInput";

import VirtualList from 'vue-virtual-scroll-list-hi'
import {Store} from "le5le-store";

export default {
    name: "DialogWrapper",
    components: {
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

            replyListShow: false,
            replyListId: 0,

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
            'taskId',
            'dialogSearchMsgId',
            'dialogMsgs',
            'dialogMsgTransfer',
            'cacheDialogs',
            'wsOpenNum',
            'touchBackInProgress',
            'windowActive',
        ]),

        ...mapGetters(['isLoad']),

        isReady() {
            return this.dialogId > 0 && this.dialogData.id > 0
        },

        dialogData() {
            return this.cacheDialogs.find(({id}) => id == this.dialogId) || {};
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
            const dialogMsgList = this.dialogMsgList.filter(item => this.msgFilter(item)).sort((a, b) => {
                return a.id - b.id;
            })
            const tempMsgList = this.tempMsgList.filter(item => this.msgFilter(item))
            if (tempMsgList.length > 0) {
                const array = [];
                array.push(...dialogMsgList);
                array.push(...tempMsgList)
                return array;
            }
            return dialogMsgList;
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
                {icon: '&#xe6eb;', type: '', label: '消息'},
            ];
            if (this.dialogData.has_tag) {
                array.push({icon: '&#xe61e;', type: 'tag', label: '标注'})
            }
            if (this.dialogData.has_image) {
                array.push({icon: '&#xe7bc;', type: 'image', label: '图片'})
            }
            if (this.dialogData.has_file) {
                array.push({icon: '&#xe7c0;', type: 'file', label: '文件'})
            }
            if (this.dialogData.has_link) {
                array.push({icon: '&#xe786;', type: 'link', label: '链接'})
            }
            return array
        },

        wrapperClass() {
            if (['ready', 'ing'].includes(this.recordState)) {
                return ['record-ready']
            }
            return null
        },

        scrollerClass() {
            return !this.$slots.head && this.msgTags.length > 1 && this.windowScrollY === 0 ? 'default-header' : null
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

        replyId() {
            return parseInt(this.msgId > 0 ? this.msgId : this.replyActiveId)
        },

        replyItem() {
            return this.replyId ? this.dialogMsgs.find(({id}) => id === this.replyId) : null
        },

        replyListItem() {
            return this.replyListId ? this.dialogMsgs.find(item => item.id == this.replyListId) : null
        }
    },

    watch: {
        dialogId: {
            handler(dialog_id) {
                if (dialog_id) {
                    this.msgNew = 0;
                    //
                    if (this.allMsgList.length > 0) {
                        this.allMsgs = this.allMsgList;
                        requestAnimationFrame(this.onToBottom);
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

        msgType(type) {
            this.onToBottom()
            if (type) {
                this.$store.dispatch("getDialogMsgs", {
                    dialog_id: this.dialogId,
                    msg_id: this.msgId,
                    msg_type: this.msgType,
                }).catch(_ => {});
            }
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
            if (num <= 1) return
            this.$store.dispatch("getDialogMsgs", {
                dialog_id: this.dialogId,
                msg_id: this.msgId,
                msg_type: this.msgType,
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
                    requestAnimationFrame(this.onToBottom)
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
            this.msgType = '';
            this.onToBottom();
            this.onActive();
            //
            let tempId = $A.randomString(16);
            let tempMsg = {
                id: tempId,
                dialog_id: this.dialogData.id,
                reply_id: this.replyId,
                reply_data: this.replyItem,
                type: 'text',
                userid: this.userId,
                msg: {
                    text: $A.stringLength(msgText) > 2000 ? '' : msgText,
                },
            };
            if (msgText.length > 2000) {
                tempMsg.type = 'loading';
                tempMsg.msg = { };
            }
            this.tempMsgs.push(tempMsg);
            //
            this.$store.dispatch("call", {
                url: 'dialog/msg/sendtext',
                data: {
                    dialog_id: this.dialogId,
                    reply_id: this.replyId,
                    text: msgText,
                },
                method: 'post'
            }).then(({data}) => {
                this.tempMsgs = this.tempMsgs.filter(({id}) => id != tempId)
                this.sendSuccess(data);
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.tempMsgs = this.tempMsgs.filter(({id}) => id != tempId)
            });
        },

        /**
         * 发送录音
         * @param msg {base64, duration}
         */
        sendRecord(msg) {
            this.msgType = '';
            this.onToBottom();
            this.onActive();
            //
            let tempId = $A.randomString(16);
            this.tempMsgs.push({
                id: tempId,
                dialog_id: this.dialogData.id,
                reply_id: this.replyId,
                reply_data: this.replyItem,
                type: 'loading',
                userid: this.userId,
                msg,
            });
            //
            this.$store.dispatch("call", {
                url: 'dialog/msg/sendrecord',
                data: Object.assign(msg, {
                    dialog_id: this.dialogId,
                    reply_id: this.replyId,
                }),
                method: 'post'
            }).then(({data}) => {
                this.tempMsgs = this.tempMsgs.filter(({id}) => id != tempId)
                this.sendSuccess(data);
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.tempMsgs = this.tempMsgs.filter(({id}) => id != tempId)
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
            if (position_id === 0) {
                return
            }
            const index = this.allMsgs.findIndex(item => item.id === position_id)
            if (index > -1) {
                this.onToIndex(index)
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
                    }
                    if (msg_id > 0) {
                        this.$store.dispatch("cancelLoad", `msg-${msg_id}`)
                    }
                    this.preventToBottom = false;
                })
            }
        },

        itemClassAdd(index) {
            return index === this.replyActiveIndex ? 'dialog-shake' : '';
        },

        inputFocus() {
            this.$nextTick(_ => {
                this.$refs.input.focus()
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
                    this.onToBottom();
                    this.onActive();
                    //
                    this.tempMsgs.push({
                        id: file.tempId,
                        dialog_id: this.dialogData.id,
                        reply_id: this.replyId,
                        type: 'loading',
                        userid: this.userId,
                        msg: { },
                    });
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
                data.some(item => {
                    this.sendSuccess(item)
                })
                return;
            }
            this.$store.dispatch("saveDialogMsg", data);
            this.$store.dispatch("increaseTaskMsgNum", this.dialogId);
            this.$store.dispatch("increaseMsgReplyNum", data.reply_id);
            this.$store.dispatch("updateDialogLastMsg", data);
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
            if (['image', 'file'].includes(e)) {
                this.$refs.chatUpload.handleClick()
            }
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
                prev_id: this.prevId
            }).then(({data}) => {
                const ids = data.list.map(item => item.id)
                this.$nextTick(() => {
                    const scroller = this.$refs.scroller
                    const offset = ids.reduce((previousValue, currentId) => {
                        const previousSize = typeof previousValue === "object" ? previousValue.size : scroller.getSize(previousValue)
                        return {size: previousSize + scroller.getSize(currentId)}
                    })
                    let size = scroller.getOffset() + offset.size;
                    if (this.prevId === 0) {
                        size -= 36
                    }
                    this.onToOffset(size);
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
                    userids: [],
                    msg_id: this.operateItem.id
                };
                this.forwardShow = true;
            } else if (type === 'submit') {
                if ($A.arrayLength(this.forwardData.userids) === 0) {
                    $A.messageWarning("请选择转发成员");
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
                    case "copy":
                        if (this.operateHasText) {
                            this.$copyText(this.operateItem.msg.text.replace(/<[^>]+>/g, "")).then(_ => {
                                $A.messageSuccess('复制成功');
                            }).catch(_ => {
                                $A.messageError('复制失败');
                            });
                        } else {
                            $A.messageWarning('不可复制的内容');
                        }
                        break;

                    case "newTask":
                        if (this.operateHasText) {
                            Store.set('addTask', {
                                owner: [this.userId],
                                name: this.operateItem.msg.text.replace(/<[^>]+>/g, "")
                            });
                        }
                        break;

                    case "reply":
                        this.onReply()
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

                    case "emoji":
                        this.onEmoji(value)
                        break;

                    case "tag":
                        this.onTag()
                        break;
                }
            })
        },

        onReply() {
            const {tail} = this.scrollInfo()
            this.replyActiveId = this.operateItem.id
            this.inputFocus()
            if (tail <= 10) {
                requestAnimationFrame(this.onToBottom)
            }
        },

        onCancelReply() {
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
                this.$store.dispatch("saveDialogMsg", data);
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.$store.dispatch("cancelLoad", `msg-${data.msg_id}`)
            });
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
                this.$store.dispatch("saveDialogMsg", data.update);
                if (data.add) {
                    this.$store.dispatch("saveDialogMsg", data.add);
                    this.$store.dispatch("updateDialogLastMsg", data.add);
                }
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.$store.dispatch("cancelLoad", `msg-${data.msg_id}`)
            });
        }
    }
}
</script>
