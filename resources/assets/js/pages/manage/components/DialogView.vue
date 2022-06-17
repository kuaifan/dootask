<template>
    <div class="dialog-view" :class="viewClass" :data-id="msgData.id">
        <!--昵称-->
        <div v-if="dialogType === 'group'" class="dialog-username">
            <UserAvatar :userid="msgData.userid" :show-icon="false" :show-name="true" :tooltip-disabled="true"/>
        </div>

        <div
            class="dialog-head"
            :class="headClass"
            v-longpress="{callback: handleLongpress, delay: 300}">
            <!--回复-->
            <div v-if="replyData" class="dialog-reply no-dark-content" @click="viewReply">
                <UserAvatar :userid="replyData.userid" :show-icon="false" :show-name="true" :tooltip-disabled="true"/>
                <div class="reply-desc">{{formatMsgDesc(replyData)}}</div>
            </div>
            <!--详情-->
            <div class="dialog-content" :class="contentClass">
                <!--文本-->
                <div v-if="msgData.type === 'text'" class="content-text no-dark-content">
                    <pre @click="viewText" v-html="textMsg(msgData.msg.text)"></pre>
                </div>
                <!--文件-->
                <div v-else-if="msgData.type === 'file'" :class="`content-file ${msgData.msg.type}`">
                    <div class="dialog-file">
                        <img v-if="msgData.msg.type === 'img'" class="file-img" :style="imageStyle(msgData.msg)" :src="msgData.msg.thumb" @click="viewFile"/>
                        <div v-else class="file-box">
                            <img class="file-thumb" :src="msgData.msg.thumb"/>
                            <div class="file-info">
                                <div class="file-name">{{msgData.msg.name}}</div>
                                <div class="file-size">{{$A.bytesToSize(msgData.msg.size)}}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--录音-->
                <div v-else-if="msgData.type === 'record'" class="content-record no-dark-content">
                    <div class="dialog-record" :class="{playing: audioPlaying === msgData.msg.path}" :style="recordStyle(msgData.msg)" @click="playRecord">
                        <div class="record-time">{{recordDuration(msgData.msg.duration)}}</div>
                        <div class="record-icon taskfont"></div>
                    </div>
                </div>
                <!--会议-->
                <div v-else-if="msgData.type === 'meeting'" class="content-meeting no-dark-content">
                    <ul class="dialog-meeting">
                        <li>
                            <em>{{$L('会议主题')}}</em>
                            {{msgData.msg.name}}
                        </li>
                        <li>
                            <em>{{$L('会议创建人')}}</em>
                            <UserAvatar :userid="msgData.msg.userid" :show-icon="false" :show-name="true" tooltip-disabled/>
                        </li>
                        <li>
                            <em>{{$L('频道ID')}}</em>
                            {{msgData.msg.meetingid.replace(/^(.{3})(.{3})(.*)$/, '$1 $2 $3')}}
                        </li>
                        <li class="meeting-operation" @click="openMeeting">
                            {{$L('点击加入会议')}}
                            <i class="taskfont">&#xe68b;</i>
                        </li>
                    </ul>
                </div>
                <!--等待-->
                <div v-else-if="msgData.type === 'loading'" class="content-loading">
                    <Loading/>
                </div>
                <!--未知-->
                <div v-else class="content-unknown">{{$L("未知的消息类型")}}</div>
            </div>
            <!--emoji-->
            <ul v-if="$A.arrayLength(msgData.emoji) > 0" class="dialog-emoji">
                <li
                    v-for="(item, index) in msgData.emoji"
                    :key="index"
                    :class="{hasme: item.userids.includes(userId)}"
                    @click="onEmoji(item.symbol)">
                    <div class="emoji-symbol no-dark-content">{{item.symbol}}</div>
                    <div class="emoji-num">{{item.userids.length}}</div>
                </li>
            </ul>
        </div>

        <!--等待/时间/阅读-->
        <div v-if="isLoading" class="dialog-foot"><Loading/></div>
        <div v-else class="dialog-foot">
            <!--时间-->
            <div v-if="timeShow" class="time" @click="timeShow=false">{{msgData.created_at}}</div>
            <div v-else class="time" :title="msgData.created_at" @click="timeShow=true">{{$A.formatTime(msgData.created_at)}}</div>
            <!--阅读-->
            <template v-if="!hidePercentage">
                <div v-if="msgData.send > 1 || dialogType === 'group'" class="percent" @click="openReadPercentage">
                    <EPopover
                        v-model="popperShow"
                        ref="percent"
                        popper-class="dialog-wrapper-read-poptip"
                        placement="left-end">
                        <div class="read-poptip-content">
                            <ul class="read scrollbar-overlay">
                                <li class="read-title"><em>{{ readList.length }}</em>{{ $L('已读') }}</li>
                                <li v-for="item in readList">
                                    <UserAvatar :userid="item.userid" :size="26" showName/>
                                </li>
                            </ul>
                            <ul class="unread scrollbar-overlay">
                                <li class="read-title"><em>{{ unreadList.length }}</em>{{ $L('未读') }}</li>
                                <li v-for="item in unreadList">
                                    <UserAvatar :userid="item.userid" :size="26" showName/>
                                </li>
                            </ul>
                        </div>
                        <div slot="reference"></div>
                    </EPopover>
                    <Loading v-if="popperLoad > 0"/>
                    <WCircle v-else :percent="msgData.percentage" :size="14"/>
                </div>
                <Icon v-else-if="msgData.percentage === 100" class="done" type="md-done-all"/>
                <Icon v-else class="done" type="md-checkmark"/>
            </template>
        </div>
    </div>
</template>

<script>
import WCircle from "../../../components/WCircle";
import {mapState} from "vuex";
import {Store} from "le5le-store";
import longpress from "../../../directives/longpress";
import {textMsgFormat, msgSimpleDesc} from "../../../functions/utils";

export default {
    name: "DialogView",
    components: {WCircle},
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
        operateVisible: {
            type: Boolean,
            default: false
        },
        operateAction: {
            type: Boolean,
            default: false
        },
    },

    data() {
        return {
            popperLoad: 0,
            popperShow: false,
            timeShow: false,
            operateEnter: false,
            allList: [],
        }
    },

    beforeDestroy() {
        Store.set('audioSubscribe', this.msgData.id);
    },

    computed: {
        ...mapState(['loads', 'dialogMsgs', 'dialogReplys', 'audioPlaying', 'windowActive']),

        isLoading() {
            if (!this.msgData.created_at) {
                return true;
            }
            const load = this.loads.find(({key}) => key === `msg-${this.msgData.id}`);
            return load && load.num > 0
        },

        viewClass() {
            const {msgData, replyData, operateAction, operateEnter} = this;
            const array = [];
            if (msgData.type) {
                array.push(msgData.type)
            }
            if (replyData) {
                array.push('reply-view')
            }
            if (operateAction) {
                array.push('operate-action')
                if (operateEnter) {
                    array.push('operate-enter')
                }
            }
            return array
        },

        readList() {
            return this.allList.filter(({read_at}) => read_at)
        },

        unreadList() {
            return this.allList.filter(({read_at}) => !read_at)
        },

        headClass() {
            const {reply_id, type, msg, emoji} = this.msgData;
            const array = [];
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
        },

        replyData() {
            const {reply_id} = this.msgData;
            if (reply_id > 0) {
                let data = this.dialogMsgs.find(item => item.id === reply_id)
                if (data) {
                    return data;
                }
                data = this.dialogReplys.find(item => item.id === reply_id)
                if (data) {
                    return data;
                }
                this.$store.dispatch("getDialogReply", reply_id)
            }
            return null;
        }
    },

    watch: {
        msgData: {
            handler() {
                this.msgRead();
            },
            immediate: true,
        },
        windowActive(active) {
            if (active) {
                this.msgRead();
            }
        },
        operateAction(val) {
            this.operateEnter = false;
            if (val) {
                setTimeout(_ => {
                    this.operateEnter = true;
                }, 500)
            }
        }
    },

    methods: {
        handleLongpress(event, el) {
            this.$emit("on-longpress", {event, el, msgData: this.msgData})
        },

        msgRead() {
            if (!this.windowActive) {
                return;
            }
            this.$store.dispatch("dialogMsgRead", this.msgData);
        },

        openReadPercentage() {
            if (this.popperLoad > 0) {
                return;
            }
            if (this.popperShow) {
                this.popperShow = false;
                return;
            }
            this.popperLoad++;
            this.$store.dispatch("call", {
                url: 'dialog/msg/readlist',
                data: {
                    msg_id: this.msgData.id,
                },
            }).then(({data}) => {
                this.allList = data;
            }).catch(() => {
                this.allList = [];
            }).finally(_ => {
                setTimeout(() => {
                    this.popperLoad--;
                    this.popperShow = true
                }, 100)
            });
        },

        textMsg(text) {
            return textMsgFormat(text, this.userId);
        },

        recordStyle(info) {
            const {duration} = info;
            const width = 50 + Math.min(180, Math.floor(duration / 150));
            return {
                width: width + 'px',
            };
        },

        recordDuration(duration) {
            const minute = Math.floor(duration / 60000),
                seconds = Math.floor(duration / 1000) % 60;
            if (minute > 0) {
                return `${minute}:${seconds}″`
            }
            return `${Math.max(1, seconds)}″`
        },

        imageStyle(info) {
            const {width, height} = info;
            if (width && height) {
                let maxW = 220,
                    maxH = 220,
                    tempW = width,
                    tempH = height;
                if (width > maxW || height > maxH) {
                    if (width > height) {
                        tempW = maxW;
                        tempH = height * (maxW / width);
                    } else {
                        tempW = width * (maxH / height);
                        tempH = maxH;
                    }
                }
                return {
                    width: tempW + 'px',
                    height: tempH + 'px',
                };
            }
            return {};
        },

        formatMsgDesc(data) {
            return msgSimpleDesc(data)
        },

        playRecord() {
            if (this.operateVisible) {
                return
            }
            Store.set('audioSubscribe', {
                id: this.msgData.id,
                src: this.msgData.msg.path,
            });
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

        viewReply() {
            this.$emit("on-view-reply", {
                msg_id: this.msgData.id,
                reply_id: this.replyData.id
            })
        },

        viewText(e) {
            this.$emit("on-view-text", e)
        },

        viewFile(e) {
            this.$emit("on-view-file", e)
        },

        onEmoji(emoji) {
            this.$emit("on-emoji", emoji)
        },
    }
}
</script>
