<template>
    <div class="dialog-view" :class="viewClass" :data-id="msgData.id">
        <!--昵称-->
        <div v-if="dialogType === 'group'" class="dialog-username">
            <UserAvatar :userid="msgData.userid" :show-icon="false" :show-name="true" click-open-dialog/>
        </div>

        <div
            class="dialog-head"
            :class="headClass"
            @click="handleClick"
            v-longpress="{callback: handleLongpress, delay: 300}">
            <!--回复-->
            <div v-if="!hideReply && msgData.reply_id && showReplyData(msgData.msg.reply_data)" class="dialog-reply no-dark-content" @click="viewReply">
                <div class="reply-avatar">
                    <UserAvatar :userid="msgData.msg.reply_data.userid" :show-icon="false" :show-name="true"/>
                </div>
                <div class="reply-desc" v-html="$A.getMsgSimpleDesc(msgData.msg.reply_data, 'image-preview')"></div>
            </div>
            <!--转发-->
            <div v-if="!hideForward && msgData.forward_id && showForwardData(msgData.msg.forward_data)" class="dialog-reply no-dark-content" @click="openDialog(msgData.msg.forward_data.userid)">
                <div class="reply-avatar">
                    <UserAvatar :userid="msgData.msg.forward_data.userid" :show-icon="false" :show-name="true"/>
                </div>
            </div>
            <!--详情-->
            <div ref="content" class="dialog-content" :class="contentClass">
                <!--文本-->
                <TextMsg v-if="msgData.type === 'text'" :msgId="msgData.id" :msg="msgData.msg" @viewText="viewText"/>
                <!--文件-->
                <FileMsg v-else-if="msgData.type === 'file'" :msg="msgData.msg" @viewFile="viewFile" @downFile="downFile"/>
                <!--录音-->
                <RecordMsg v-else-if="msgData.type === 'record'" :msgId="msgData.id" :msg="msgData.msg" @playRecord="playRecord"/>
                <!--位置-->
                <LocationMsg v-else-if="msgData.type === 'location'" :msg="msgData.msg"/>
                <!--会议-->
                <MeetingMsg v-else-if="msgData.type === 'meeting'" :msg="msgData.msg" @openMeeting="openMeeting"/>
                <!--接龙-->
                <WordChainMsg v-else-if="msgData.type === 'word-chain'" :msg="msgData.msg" :msgId="msgData.id" :unfoldWordChainData="unfoldWordChainData" @unfoldWordChain="unfoldWordChain(msgData)" @onWordChain="onWordChain"/>
                <!--投票-->
                <VoteMsg v-else-if="msgData.type === 'vote'" :msg="msgData.msg" :voteData="voteData" @onVote="onVote($event, msgData)"/>
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
                            <template v-for="(uitem, uindex) in item.userids">
                                <li v-if="uindex < emojiUsersNum" :class="{bold:uitem==userId}"><UserAvatar :userid="uitem" show-name :show-icon="false"/></li>
                                <li v-else-if="uindex == emojiUsersNum">+{{item.userids.length - emojiUsersNum}}位</li>
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
            <div v-if="msgData.tag" class="tag">
                <i class="taskfont">&#xe61e;</i>
            </div>
            <!--待办-->
            <div v-if="msgData.todo" class="todo" @click="openTodo">
                <EPopover
                    v-model="todoShow"
                    ref="todo"
                    popper-class="dialog-wrapper-read-poptip"
                    :placement="isRightMsg ? 'bottom-end' : 'bottom-start'">
                    <div class="read-poptip-content">
                        <Scrollbar class-name="read">
                            <div class="read-title">
                                <em>{{ todoDoneList.length }}</em>
                                {{ $L('完成') }}
                            </div>
                            <ul>
                                <li v-for="item in todoDoneList">
                                    <UserAvatar :userid="item.userid" :size="26" showName/>
                                </li>
                            </ul>
                        </Scrollbar>
                        <Scrollbar class-name="unread">
                            <div class="read-title">
                                <em>{{ todoUndoneList.length }}</em>
                                {{ $L('待办') }}
                                <span class="space"></span>
                                <Button type="primary" size="small" @click="handleTodoAdd">{{ $L('添加') }}</Button>
                            </div>
                            <ul>
                                <li v-for="item in todoUndoneList">
                                    <UserAvatar :userid="item.userid" :size="26" showName/>
                                </li>
                            </ul>
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
                    <div v-if="msgData.send > 1 || dialogType === 'group'" class="percent" @click="openReadPercentage">
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
                                        <li v-for="item in readList">
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
                                        <li v-for="item in unreadList">
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
import {Store} from "le5le-store";
import longpress from "../../../../directives/longpress";

import TextMsg from "./text.vue";
import FileMsg from "./file.vue";
import RecordMsg from "./record.vue";
import LocationMsg from "./location.vue";
import MeetingMsg from "./meet.vue";
import WordChainMsg from "./word-chain.vue";
import VoteMsg from "./vote.vue";
import TemplateMsg from "./template";
import LoadMsg from "./load.vue";
import UnknownMsg from "./unknown.vue";

export default {
    name: "DialogView",
    components: {
        UnknownMsg,
        LoadMsg,
        TemplateMsg,
        VoteMsg,
        WordChainMsg,
        MeetingMsg,
        LocationMsg,
        RecordMsg,
        TextMsg,
        FileMsg,
        WCircle
    },
    directives: {longpress},
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

        viewClass() {
            const {msgData, operateAction, operateEnter, pointerMouse} = this;
            const array = [];
            if (msgData.type) {
                array.push(msgData.type)
            }
            if (operateAction) {
                array.push('operate-action')
                if (operateEnter) {
                    array.push('pointer-mouse')
                }
            }
            if (pointerMouse && array.indexOf('pointer-mouse') === -1) {
                array.push('pointer-mouse')
            }
            return array
        },

        readList() {
            return this.percentageList.filter(({read_at}) => read_at)
        },

        unreadList() {
            return this.percentageList.filter(({read_at}) => !read_at)
        },

        todoDoneList() {
            return this.todoList.filter(({done_at}) => done_at)
        },

        todoUndoneList() {
            return this.todoList.filter(({done_at}) => !done_at)
        },

        headClass() {
            const {id, reply_id, type, msg, emoji, dot} = this.msgData;
            const array = [];
            if (dot && !this.dotClicks.includes(id)) {
                array.push('dot')
            }
            if (reply_id === 0 && $A.arrayLength(emoji) === 0) {
                if (type === 'text') {
                    if (/^<img\s+class="emoticon"[^>]*?>$/.test(msg.text)
                        || /^\s*<p>\s*([\uD800-\uDBFF][\uDC00-\uDFFF]){1,3}\s*<\/p>\s*$/.test(msg.text)) {
                        array.push('transparent')
                    }
                }
            }
            return array;
        },

        contentClass() {
            const {type, msg} = this.msgData;
            const classArray = [];
            if (type === 'text') {
                if (/^<img\s+class="emoticon"[^>]*?>$/.test(msg.text)) {
                    classArray.push('an-emoticon')
                } else if (/^\s*<p>\s*([\uD800-\uDBFF][\uDC00-\uDFFF]){3}\s*<\/p>\s*$/.test(msg.text)) {
                    classArray.push('three-emoji')
                } else if (/^\s*<p>\s*([\uD800-\uDBFF][\uDC00-\uDFFF]){2}\s*<\/p>\s*$/.test(msg.text)) {
                    classArray.push('two-emoji')
                } else if (/^\s*<p>\s*[\uD800-\uDBFF][\uDC00-\uDFFF]\s*<\/p>\s*$/.test(msg.text)) {
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
        handleLongpress(event, el) {
            this.$emit("on-longpress", {event, el, msgData: this.msgData})
        },

        handleClick() {
            if (this.msgData.dot) {
                this.dotClicks.push(this.msgData.id);
                this.$store.dispatch("dialogMsgDot", this.msgData);
            }
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
                    this.todoShow = true
                }, 100)
            });
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
            Store.set('addMeeting', {
                type: 'join',
                name: this.msgData.msg.name,
                meetingid: this.msgData.msg.meetingid,
                meetingdisabled: true,
            });
        },

        openDialog(userid) {
            this.$store.dispatch("openDialogUserid", userid).then(_ => {
                this.goForward({name: 'manage-messenger'})
            }).catch(({msg}) => {
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
