<template>
    <div class="dialog-view" :class="viewClass" :data-id="msgData.id">
        <!--昵称-->
        <div v-if="dialogType === 'group'" class="dialog-username" @pointerdown="handleOperation($event, 'mention')">
            <UserAvatar :userid="msgData.userid" :show-icon="false" :show-name="true" :name-text="msgData.userid === -1 ? (msgData.msg.nickname || '') : ''" :click-open-detail="msgData.userid !== -1"/>
        </div>

        <div
            class="dialog-head"
            :class="headClass"
            @click="handleClick"
            @pointerdown="handleOperation($event, 'operateMsg')">
            <!--回复-->
            <div v-if="!hideReply && msgData.reply_id && showReplyData(msgData.msg.reply_data)" class="dialog-reply no-dark-content" :class="replyClass" @click="viewReply">
                <div class="reply-avatar">
                    <UserAvatar :userid="msgData.msg.reply_data.userid" :show-icon="false" :show-name="true" :name-text="msgData.msg.reply_data.userid === -1 ? ((msgData.msg.reply_data.msg && msgData.msg.reply_data.msg.nickname) || '') : ''"/>
                </div>
                <div class="reply-desc" v-html="$A.getMsgSimpleDesc(msgData.msg.reply_data, 'image-preview')"></div>
            </div>
            <!--转发-->
            <div v-if="!hideForward && msgData.forward_id && showForwardData(msgData.msg.forward_data)" class="dialog-reply no-dark-content" @click="msgData.msg.forward_data.userid !== -1 && openDialog(msgData.msg.forward_data.userid)">
                <div class="reply-avatar">
                    <UserAvatar :userid="msgData.msg.forward_data.userid" :show-icon="false" :show-name="true"/>
                </div>
            </div>
            <!--详情-->
            <div ref="content" class="dialog-content" :class="contentClass">
                <!--文本-->
                <TextMsg v-if="msgData.type === 'text'" :msgId="msgData.id" :msg="msgData.msg" :createdAt="msgData.created_at" @viewText="viewText"/>
                <!--长文本-->
                <LongTextMsg v-else-if="msgData.type === 'longtext'" :msgId="msgData.id" :msg="msgData.msg" @viewText="viewText" @downFile="downFile"/>
                <!--文件-->
                <FileMsg v-else-if="msgData.type === 'file'" :msg="msgData.msg" @viewFile="viewFile" @downFile="downFile"/>
                <!--录音-->
                <RecordMsg v-else-if="msgData.type === 'record'" :msgId="msgData.id" :msg="msgData.msg" @viewText="viewText" @playRecord="playRecord"/>
                <!--位置-->
                <LocationMsg v-else-if="msgData.type === 'location'" :msg="msgData.msg"/>
                <!--会议-->
                <MeetingMsg v-else-if="msgData.type === 'meeting'" :msg="msgData.msg" @openMeeting="openMeeting"/>
                <!--接龙-->
                <WordChainMsg v-else-if="msgData.type === 'word-chain'" :msg="msgData.msg" :msgId="msgData.id" :unfoldWordChainData="unfoldWordChainData" @unfoldWordChain="unfoldWordChain(msgData)" @onWordChain="onWordChain"/>
                <!--投票-->
                <VoteMsg v-else-if="msgData.type === 'vote'" :msg="msgData.msg" :voteData="voteData" @onVote="onVote($event, msgData)"/>
                <!--合并转发-->
                <MergeForwardMsg v-else-if="msgData.type === 'merge-forward'" :msg="msgData.msg" @on-view-detail="onMergeForwardDetail"/>
                <!--模板-->
                <TemplateMsg v-else-if="msgData.type === 'template'" :msg="msgData.msg" @viewText="viewText"/>
                <!--等待-->
                <LoadMsg v-else-if="isLoading" :error="msgData.error"/>
                <!--未知-->
                <UnknownMsg v-else/>
            </div>
            <!--emoji-->
            <ul v-if="$A.arrayLength(msgData.emoji) > 0" class="dialog-emoji">
                <li
                    v-for="(item, index) in msgData.emoji"
                    :key="index"
                    :class="{hasme: item.userids.includes(userId)}">
                    <div class="emoji-symbol no-dark-content" @click="onEmoji(item.symbol)">{{item.symbol}}</div>
                    <div class="emoji-users" @click="onShowEmojiUser(item)">
                        <ul>
                            <template v-for="(uitem, uindex) in sortEmojiUser(item.userids)">
                                <li v-if="uindex < emojiUsersNum" :key="`emoji-user-li-${uindex}-${uitem}`">
                                    <UserAvatar :userid="uitem" show-name :show-icon="false"/>
                                </li>
                                <li v-else-if="uindex == emojiUsersNum" :key="`emoji-user-more-${uindex}`">+{{item.userids.length - emojiUsersNum}}位</li>
                            </template>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>

        <div class="dialog-foot">
            <!--回复数-->
            <div v-if="!hideReply && msgData.reply_num > 0" class="reply" @click="replyList">
                <i class="taskfont">&#xe6eb;</i>
                {{msgData.reply_num}}条回复
            </div>
            <!--标注-->
            <div v-if="msgData.tag" class="tag" @click="openTag">
                <i class="taskfont">&#xe61e;</i>
            </div>
            <!--待办-->
            <div v-if="msgData.todo" class="todo" :class="{'todo_done': msgData.todo_done}" @click="openTodo">
                <EPopover
                    v-model="todoShow"
                    ref="todo"
                    popper-class="dialog-wrapper-read-poptip dialog-wrapper-todo-poptip"
                    :placement="isRightMsg ? 'bottom-end' : 'bottom-start'">
                    <div class="read-poptip-content" :class="{'is-tab': todoNarrow}">
                        <!-- 窄屏 tab 头：不平分，标签靠左，添加仅待办显示 -->
                        <div v-if="todoNarrow" class="todo-tabbar">
                            <span class="todo-tab" :class="{on: todoTab === 'undone'}" @click.stop="todoTab = 'undone'">{{ $L('待办') }} <em>{{ todoUndoneList.length }}</em></span>
                            <span class="todo-tab" :class="{on: todoTab === 'done'}" @click.stop="todoTab = 'done'">{{ $L('完成') }} <em>{{ todoDoneList.length }}</em></span>
                            <span class="space"></span>
                            <Button v-if="todoTab === 'undone'" type="primary" size="small" @click.stop="handleTodoAdd">{{ $L('添加') }}</Button>
                        </div>
                        <!-- 完成 -->
                        <Scrollbar v-if="!todoNarrow || todoTab === 'done'" class-name="read">
                            <div v-if="!todoNarrow" class="read-title">
                                <em>{{ todoDoneList.length }}</em>
                                {{ $L('完成') }}
                            </div>
                            <ul v-if="todoDoneList.length">
                                <li v-for="item in todoDoneList" :key="`todo-done-${item.userid}`">
                                    <UserAvatar :userid="item.userid" :size="26" showName/>
                                </li>
                            </ul>
                            <div v-else class="read-empty">
                                <Icon type="ios-checkmark-circle-outline"/>
                                <p>{{ $L('暂无完成') }}</p>
                            </div>
                        </Scrollbar>
                        <!-- 待办 -->
                        <Scrollbar v-if="!todoNarrow || todoTab === 'undone'" class-name="unread">
                            <div v-if="!todoNarrow" class="read-title">
                                <em>{{ todoUndoneList.length }}</em>
                                {{ $L('待办') }}
                                <span class="space"></span>
                                <Button type="primary" size="small" @click.stop="handleTodoAdd">{{ $L('添加') }}</Button>
                            </div>
                            <ul v-if="todoUndoneList.length">
                                <li v-for="item in todoUndoneList" :key="`todo-undone-${item.userid}`">
                                    <UserAvatar :userid="item.userid" :size="26" showName/>
                                    <span class="todo-remind" @click.stop>
                                        <DatePicker
                                            :open="todoRemindOpenUserid === item.userid"
                                            :value="todoRemindOpenUserid === item.userid ? todoRemindEditing : item.remind_at"
                                            type="datetime"
                                            format="yyyy-MM-dd HH:mm"
                                            :editable="false"
                                            transfer
                                            @on-change="val => todoRemindEditing = val"
                                            @on-ok="confirmTodoRemind(item)"
                                            @on-clear="cancelTodoRemind(item)"
                                            @on-open-change="v => { if (!v && todoRemindOpenUserid === item.userid) todoRemindOpenUserid = 0 }">
                                            <span v-if="item.remind_at" class="todo-remind-time" @click.stop="openTodoRemind(item)">
                                                <Icon type="ios-alarm-outline"/>
                                                {{ todoRemindFormat(item.remind_at) }}
                                            </span>
                                            <Icon v-else type="ios-clock-outline" class="todo-remind-add" @click.stop="openTodoRemind(item)"/>
                                        </DatePicker>
                                    </span>
                                </li>
                            </ul>
                            <div v-else class="read-empty">
                                <Icon type="ios-list-box-outline"/>
                                <p>{{ $L('暂无待办') }}</p>
                            </div>
                        </Scrollbar>
                    </div>
                    <div slot="reference" class="popover-reference"></div>
                </EPopover>
                <Loading v-if="todoLoad > 0"/>
                <i v-else class="taskfont">&#xe7b7;</i>
            </div>
            <!--编辑-->
            <div v-if="msgData.modify" class="modify">
                <i class="taskfont">&#xe779;</i>
            </div>
            <!--错误/等待/时间/阅读-->
            <div v-if="msgData.error === true" class="error" @click="onError">
                <Icon type="ios-alert" />
            </div>
            <Loading v-else-if="isLoading" :delay="300"/>
            <template v-else>
                <!--时间-->
                <div v-if="timeShow" class="time" @click="timeShow=false">{{msgData.created_at}}</div>
                <div v-else class="time" :title="msgData.created_at" @click="timeShow=true">{{$A.timeFormat(msgData.created_at)}}</div>
                <!--阅读-->
                <template v-if="!hidePercentage">
                    <div v-if="dialogType === 'group'" class="percent" @click="openReadPercentage">
                        <EPopover
                            v-model="percentageShow"
                            ref="percent"
                            popper-class="dialog-wrapper-read-poptip"
                            :placement="isRightMsg ? 'bottom-end' : 'bottom-start'">
                            <div class="read-poptip-content">
                                <Scrollbar class-name="read">
                                    <div class="read-title">
                                        <em>{{ readList.length }}</em>
                                        {{ $L('已读') }}
                                    </div>
                                    <ul>
                                        <li v-for="item in readList" :key="`read-${item.userid}`">
                                            <UserAvatar :userid="item.userid" :size="26" showName/>
                                        </li>
                                    </ul>
                                </Scrollbar>
                                <Scrollbar class-name="unread">
                                    <div class="read-title">
                                        <em>{{ unreadList.length }}</em>
                                        {{ $L('未读') }}
                                    </div>
                                    <ul>
                                        <li v-for="item in unreadList" :key="`unread-${item.userid}`">
                                            <UserAvatar :userid="item.userid" :size="26" showName/>
                                        </li>
                                    </ul>
                                </Scrollbar>
                            </div>
                            <div slot="reference" class="popover-reference"></div>
                        </EPopover>
                        <Loading v-if="percentageLoad > 0"/>
                        <WCircle v-else :percent="msgData.percentage" :size="14"/>
                    </div>
                    <Icon v-else-if="msgData.percentage === 100" class="done" type="md-done-all"/>
                    <Icon v-else class="done" type="md-checkmark"/>
                </template>
            </template>
        </div>
    </div>
</template>

<script>
import WCircle from "../../../../components/WCircle";
import {mapGetters, mapState} from "vuex";

import TextMsg from "./text.vue";
import LongTextMsg from "./longtext.vue";
import FileMsg from "./file.vue";
import RecordMsg from "./record.vue";
import LocationMsg from "./location.vue";
import MeetingMsg from "./meet.vue";
import WordChainMsg from "./word-chain.vue";
import VoteMsg from "./vote.vue";
import TemplateMsg from "./template";
import MergeForwardMsg from "./merge-forward.vue";
import LoadMsg from "./load.vue";
import UnknownMsg from "./unknown.vue";
import emitter from "../../../../store/events";

// 模块级别的正则表达式常量，所有组件实例共享
const REGEX_CACHE = Object.freeze({
    emoticon: /^<img\s+class="emoticon"[^>]*?>$/,
    threeEmoji: /^\s*<p>\s*([\uD800-\uDBFF][\uDC00-\uDFFF]){3}\s*<\/p>\s*$/,
    twoEmoji: /^\s*<p>\s*([\uD800-\uDBFF][\uDC00-\uDFFF]){2}\s*<\/p>\s*$/,
    oneEmoji: /^\s*<p>\s*[\uD800-\uDBFF][\uDC00-\uDFFF]\s*<\/p>\s*$/,
    emojiRange: /^\s*<p>\s*([\uD800-\uDBFF][\uDC00-\uDFFF]){1,3}\s*<\/p>\s*$/
});

export default {
    name: "DialogView",
    components: {
        UnknownMsg,
        LoadMsg,
        MergeForwardMsg,
        TemplateMsg,
        VoteMsg,
        WordChainMsg,
        MeetingMsg,
        LocationMsg,
        RecordMsg,
        LongTextMsg,
        TextMsg,
        FileMsg,
        WCircle
    },
    props: {
        msgData: {
            type: Object,
            default: () => {
                return {};
            }
        },
        dialogType: {
            type: String,
            default: ''
        },
        hidePercentage: {
            type: Boolean,
            default: false
        },
        hideReply: {
            type: Boolean,
            default: false
        },
        hideForward: {
            type: Boolean,
            default: false
        },
        operateVisible: {
            type: Boolean,
            default: false
        },
        operateAction: {
            type: Boolean,
            default: false
        },
        pointerMouse: {
            type: Boolean,
            default: false
        },
        isRightMsg: {
            type: Boolean,
            default: false
        },
    },

    data() {
        return {
            timeShow: false,
            operateEnter: false,

            percentageLoad: 0,
            percentageShow: false,
            percentageList: [],

            todoLoad: 0,
            todoShow: false,
            todoList: [],
            todoTab: 'undone',

            todoRemindOpenUserid: 0,
            todoRemindEditing: '',

            emojiUsersNum: 5,

            voteData: {},
            dotClicks: [],
            unfoldWordChainData: [],
        }
    },

    mounted() {
        this.emojiUsersNum = Math.min(6, Math.max(2, Math.floor((this.windowWidth - 180) / 52)))
        if (Object.keys(this.voteData).length === 0) {
            this.voteData = JSON.parse(window.localStorage.getItem(`__cache:vote__`)) || {};
        }
        if (this.unfoldWordChainData.length === 0) {
            this.unfoldWordChainData = JSON.parse(window.localStorage.getItem(`__cache:unfoldWordChain__`)) || [];
        }
    },

    beforeDestroy() {
        this.$store.dispatch("audioStop", this.msgData.msg?.path)
    },

    computed: {
        ...mapState(['loads']),
        ...mapGetters(['isLoad']),

        isLoading() {
            if (!this.msgData.created_at) {
                return true;
            }
            return this.isLoad(`msg-${this.msgData.id}`)
        },

        readList({userId}) {
            return this.percentageList.filter(({userid, read_at}) => userid != userId && read_at)
        },

        unreadList({userId}) {
            return this.percentageList.filter(({userid, read_at}) => userid != userId && !read_at)
        },

        todoDoneList() {
            return this.todoList.filter(({done_at}) => done_at)
        },

        todoUndoneList() {
            return this.todoList.filter(({done_at}) => !done_at)
        },

        todoNarrow() {
            return this.windowWidth <= 500;
        },

        viewClass() {
            const {msgData} = this;
            const classArray = [];
            if (msgData.type) {
                classArray.push(msgData.type)
            }
            return classArray
        },

        headClass() {
            const {msgData, operateAction} = this;
            const {id, reply_id, type, msg, emoji, dot} = msgData;
            const classArray = [];
            if (operateAction) {
                classArray.push('operating')
            }
            if (dot && !this.dotClicks.includes(id)) {
                classArray.push('dot')
            }
            if (reply_id === 0 && $A.arrayLength(emoji) === 0) {
                if (type === 'text') {
                    if (REGEX_CACHE.emoticon.test(msg.text)
                        || REGEX_CACHE.emojiRange.test(msg.text)) {
                        classArray.push('transparent')
                    }
                }
            }
            return classArray;
        },

        replyClass() {
            const classArray = [];
            if (this.operateEnter || this.pointerMouse) {
                classArray.push('user-select-auto')
            }
            return classArray;
        },

        contentClass() {
            const {type, msg} = this.msgData;
            const classArray = [];

            if (this.operateEnter || this.pointerMouse) {
                classArray.push('user-select-auto')
            }

            if (type === 'text' && msg?.text) {
                const text = msg.text;

                if (REGEX_CACHE.emoticon.test(text)) {
                    classArray.push('an-emoticon')
                } else if (REGEX_CACHE.threeEmoji.test(text)) {
                    classArray.push('three-emoji')
                } else if (REGEX_CACHE.twoEmoji.test(text)) {
                    classArray.push('two-emoji')
                } else if (REGEX_CACHE.oneEmoji.test(text)) {
                    classArray.push('an-emoji')
                }
            }

            return classArray;
        }
    },

    watch: {
        operateAction(val) {
            this.operateEnter = false;
            if (val) {
                setTimeout(_ => this.operateEnter = true, 500)
            }
        },
        voteData: {
            handler(val) {
                const voteData = JSON.parse(window.localStorage.getItem('__cache:vote__')) || {}
                for (const key in val) {
                    voteData[key] = val[key];
                }
                if (Object.keys(voteData).length > 0) {
                    window.localStorage.setItem('__cache:vote__', JSON.stringify(voteData))
                }
            },
            deep: true
        }
    },

    methods: {
        handleOperation({currentTarget}, type) {
            this.$store.commit("longpress/set", {
                type,
                data: this.msgData,
                element: currentTarget
            })
        },

        handleClick() {
            if (this.msgData.dot) {
                this.dotClicks.push(this.msgData.id);
                this.$store.dispatch("dialogMsgDot", this.msgData);
            }
        },

        openTag() {
            if (!this.msgData.tag) {
                return
            }
            this.$store.dispatch("showSpinner", 600)
            this.$store.dispatch("getUserData", this.msgData.tag).then(user => {
                $A.messageInfo(`标注人员：${user.nickname} (ID: ${user.userid})`)
            }).catch(_ => {
                $A.messageError('标注人员不存在')
            }).finally(_ => {
                this.$store.dispatch("hiddenSpinner")
            });
        },

        openTodo() {
            if (this.todoLoad > 0) {
                return;
            }
            if (this.todoShow) {
                this.todoShow = false;
                return;
            }
            this.todoLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/msg/todolist',
                data: {
                    msg_id: this.msgData.id,
                },
            }).then(({data}) => {
                this.todoList = data;
            }).catch(() => {
                this.todoList = [];
            }).finally(_ => {
                setTimeout(() => {
                    this.todoLoad--;
                    this.todoTab = 'undone';
                    this.todoShow = true
                }, 100)
            });
        },

        // 提醒时间展示格式（remind_at 已是服务器时间字符串，直接格式化，不做时区换算）
        todoRemindFormat(val) {
            return val ? $A.dayjs(val).format("MM-DD HH:mm") : ''
        },

        // 打开某条待办的提醒时间选择器
        openTodoRemind(item) {
            this.todoRemindEditing = item.remind_at || ''
            this.todoRemindOpenUserid = item.userid
        },

        // 用户点击 OK 确认后提交
        confirmTodoRemind(item) {
            this.todoRemindOpenUserid = 0
            this.setTodoRemind(item, this.todoRemindEditing)
        },

        // 用户点击选择器内「清空」→ 二次确认后取消该成员的提醒时间（无时间则仅关闭，不发请求）
        cancelTodoRemind(item) {
            this.todoRemindOpenUserid = 0
            if (!item.remind_at) {
                return
            }
            $A.modalConfirm({
                title: '取消提醒',
                content: '确定取消该成员的提醒时间吗？',
                onOk: () => {
                    this.setTodoRemind(item, '')
                }
            })
        },

        // 设置/修改/取消某成员待办的提醒时间（val 为空=取消）
        setTodoRemind(item, val) {
            if (item._remindLoading) {
                return
            }
            this.$set(item, '_remindLoading', true)
            const remind_at = val ? $A.dayjs(val).second(0).format("YYYY-MM-DD HH:mm:ss") : ''
            this.$store.dispatch("call", {
                method: 'post',
                url: 'dialog/msg/todoremind',
                data: {
                    msg_id: this.msgData.id,
                    userids: [item.userid],
                    remind_at,
                },
            }).then(({msg}) => {
                this.$set(item, 'remind_at', remind_at || null)
                $A.messageSuccess(msg)
            }).catch(({msg}) => {
                $A.messageError(msg)
            }).finally(() => {
                this.$set(item, '_remindLoading', false)
            })
        },

        handleTodoAdd() {
            this.$refs.todo.doClose();
            this.$emit("on-other", {
                event: 'todoAdd',
                data: {
                    msg_id: this.msgData.id,
                    userids: this.todoList.map(({userid}) => userid)
                }
            })
        },

        openReadPercentage() {
            if (this.percentageLoad > 0) {
                return;
            }
            if (this.percentageShow) {
                this.percentageShow = false;
                return;
            }
            this.percentageLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/msg/readlist',
                data: {
                    msg_id: this.msgData.id,
                },
            }).then(({data}) => {
                this.percentageList = data;
            }).catch(() => {
                this.percentageList = [];
            }).finally(_ => {
                setTimeout(() => {
                    this.percentageLoad--;
                    this.percentageShow = true
                }, 100)
            });
        },

        playRecord() {
            if (this.operateVisible) {
                return
            }
            if (!this.msgData.created_at) {
                return;
            }
            this.$store.dispatch("audioPlay", this.msgData.msg.path)
        },

        openMeeting() {
            if (this.operateVisible) {
                return
            }
            emitter.emit('addMeeting', {
                type: 'join',
                name: this.msgData.msg.name,
                meetingid: this.msgData.msg.meetingid,
                meetingdisabled: true,
            });
        },

        openDialog(userid) {
            this.$store.dispatch("openDialogUserid", userid).catch(({msg}) => {
                $A.modalError(msg)
            });
        },

        showReplyData(data) {
            if (!$A.isJson(data)) {
                return false
            }
            return data.userid
        },

        showForwardData(data) {
            if (!$A.isJson(data)) {
                return false
            }
            return data.show && data.userid
        },

        viewReply() {
            this.$emit("on-view-reply", {
                msg_id: this.msgData.id,
                reply_id: this.msgData.reply_id
            })
        },

        viewText(e) {
            this.$emit("on-view-text", e, this.$refs.content)
        },

        viewFile() {
            if (!this.msgData.created_at) {
                return;
            }
            this.$emit("on-view-file", this.msgData)
        },

        downFile() {
            if (!this.msgData.created_at) {
                return;
            }
            this.$emit("on-down-file", this.msgData)
        },

        replyList() {
            this.$emit("on-reply-list", {
                msg_id: this.msgData.id,
            })
        },

        onError() {
            this.$emit("on-error", this.msgData)
        },

        onEmoji(symbol) {
            this.$emit("on-emoji", {
                msg_id: this.msgData.id,
                symbol
            })
        },

        onShowEmojiUser(item) {
            this.$emit("on-show-emoji-user", item)
        },

        onMergeForwardDetail(msg) {
            this.$emit("on-merge-forward-detail", {msgId: this.msgData.id, msgData: msg})
        },

        sortEmojiUser(useris) {
            const myList = useris.filter(item => item == this.userId);
            const otherList = useris.filter(item => item != this.userId);
            return myList.concat(otherList);
        },

        unfoldWordChain(msg) {
            if (this.unfoldWordChainData.indexOf(msg.id) == -1) {
                const data = JSON.parse(window.localStorage.getItem('__cache:unfoldWordChain__')) || [];
                data.push(msg.id);
                window.localStorage.setItem('__cache:unfoldWordChain__', JSON.stringify(data));
                this.unfoldWordChainData.push(msg.id);
            }
        },

        onWordChain() {
            this.$store.state.dialogDroupWordChain = {
                type: 'participate',
                dialog_id: this.msgData.dialog_id,
                msgData: this.msgData,
            }
        },

        onVote(type, msgData) {
            if (type != 'vote') {
                $A.modalConfirm({
                    content: type == 'finish' ? '确定结束投票？' : '再次发送投票？',
                    cancelText: '取消',
                    okText: '确定',
                    onOk: () => {
                        this.submitVote(type, msgData);
                    }
                });
                return;
            }
            this.submitVote(type, msgData);
        },

        submitVote(type, msgData) {
            this.$set(msgData.msg, '_loadIng', 1)
            this.$store.dispatch("call", {
                url: 'dialog/msg/vote',
                method: 'post',
                data: {
                    dialog_id: msgData.dialog_id,
                    uuid: msgData.msg.uuid,
                    vote: this.voteData[msgData.msg.uuid] || [],
                    type: type
                }
            }).then(({ data }) => {
                if (type == 'again') {
                    $A.messageSuccess("已发送");
                }
                data.forEach(d => {
                    this.$store.dispatch("saveDialogMsg", d);
                });
            }).catch(({ msg }) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.$set(msgData.msg, '_loadIng', 0)
            });
        },
    }
}
</script>
