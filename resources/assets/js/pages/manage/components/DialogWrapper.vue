<template>
    <div
        class="dialog-wrapper"
        @drop.prevent="chatPasteDrag($event, 'drag')"
        @dragover.prevent="chatDragOver(true, $event)"
        @dragleave.prevent="chatDragOver(false, $event)">
        <slot name="head">
            <div class="dialog-title">
                <h2>{{dialogDetail.name}}</h2>
                <em v-if="peopleNum > 0">({{peopleNum}})</em>
            </div>
        </slot>
        <ScrollerY
            ref="scroller"
            class="dialog-scroller"
            :auto-bottom="autoBottom"
            @on-scroll="chatScroll"
            static>
            <div ref="manageList" class="dialog-list">
                <ul>
                    <li v-if="dialogMsgLoad > 0" class="loading"><Loading/></li>
                    <li v-else-if="dialogMsgList.length === 0" class="nothing">{{$L('暂无消息')}}</li>
                    <li v-for="(item, key) in dialogMsgList" :key="key" :class="{self:item.userid == userId}">
                        <div class="dialog-avatar">
                            <UserAvatar :userid="item.userid" :tooltip-disabled="item.userid == userId" :size="30"/>
                        </div>
                        <DialogView :msg-data="item" :dialog-type="dialogDetail.type"/>
                    </li>
                </ul>
            </div>
        </ScrollerY>
        <div :class="['dialog-footer', msgNew > 0 && dialogMsgList.length > 0 ? 'newmsg' : '']">
            <div class="dialog-newmsg" @click="goNewBottom">{{$L('有' + msgNew + '条新消息')}}</div>
            <DragInput class="dialog-input" v-model="msgText" type="textarea" :rows="1" :autosize="{ minRows: 1, maxRows: 3 }" :maxlength="255" @on-keydown="chatKeydown" @on-input-paste="pasteDrag" :placeholder="$L('输入消息...')" />
            <DialogUpload
                ref="chatUpload"
                class="chat-upload"
                @on-progress="chatFile('progress', $event)"
                @on-success="chatFile('success', $event)"
                @on-error="chatFile('error', $event)"/>
        </div>
        <div v-if="dialogDrag" class="drag-over" @click="dialogDrag=false">
            <div class="drag-text">{{$L('拖动到这里发送')}}</div>
        </div>
    </div>
</template>

<script>
import DragInput from "../../../components/DragInput";
import ScrollerY from "../../../components/ScrollerY";
import {mapState} from "vuex";
import DialogView from "./DialogView";
import DialogUpload from "./DialogUpload";

export default {
    name: "DialogWrapper",
    components: {DialogUpload, DialogView, ScrollerY, DragInput},
    data() {
        return {
            autoBottom: true,
            autoInterval: null,

            memberShowAll: false,

            dialogDrag: false,

            msgText: '',
            msgLength: 0,
            msgNew: 0,
        }
    },

    destroyed() {
        this.$store.state.dialogId = 0;
    },

    deactivated() {
        this.$store.state.dialogId = 0;
    },

    computed: {
        ...mapState(['userId', 'dialogId', 'dialogDetail', 'dialogMsgLoad', 'dialogMsgList']),

        peopleNum() {
            return this.dialogDetail.type === 'group' ? $A.runNum(this.dialogDetail.people) : 0;
        }
    },

    watch: {
        dialogMsgList(list) {
            if (!this.autoBottom) {
                let length = list.length - this.msgLength;
                if (length > 0) {
                    this.msgNew+= length;
                }
            } else {
                this.$nextTick(this.goBottom);
            }
            this.msgLength = list.length;
        },

        dialogId() {
            this.msgNew = 0;
        }
    },

    methods: {
        sendMsg() {
            let tempId = $A.randomString(16);
            this.dialogMsgList.push({
                id: tempId,
                type: 'text',
                userid: this.userId,
                msg: {
                    text: this.msgText,
                },
            });
            this.autoBottom = true;
            //
            this.$store.dispatch("call", {
                url: 'dialog/msg/sendtext',
                data: {
                    dialog_id: this.dialogId,
                    text: this.msgText,
                },
            }).then((data, msg) => {
                this.$store.dispatch('dialogMsgSplice', {id: tempId, data});
            }).catch((data, msg) => {
                $A.modalWarning({
                    title: '发送失败',
                    content: msg
                });
                this.$store.dispatch('dialogMsgSplice', {id: tempId});
            });
            //
            this.msgText = '';
        },

        chatKeydown(e) {
            if (e.keyCode === 13) {
                if (e.shiftKey) {
                    return;
                }
                e.preventDefault();
                this.sendMsg();
            }
        },

        pasteDrag(e, type) {
            const files = type === 'drag' ? e.dataTransfer.files : e.clipboardData.files;
            const postFiles = Array.prototype.slice.call(files);
            if (postFiles.length > 0) {
                e.preventDefault();
                postFiles.forEach((file) => {
                    this.$refs.chatUpload.upload(file);
                });
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

        chatPasteDrag(e, type) {
            this.dialogDrag = false;
            const files = type === 'drag' ? e.dataTransfer.files : e.clipboardData.files;
            const postFiles = Array.prototype.slice.call(files);
            if (postFiles.length > 0) {
                e.preventDefault();
                postFiles.forEach((file) => {
                    this.$refs.chatUpload.upload(file);
                });
            }
        },

        chatFile(type, file) {
            switch (type) {
                case 'progress':
                    this.dialogMsgList.push({
                        id: file.tempId,
                        type: 'loading',
                        userid: this.userId,
                        msg: { },
                    });
                    break;

                case 'error':
                    this.$store.dispatch('dialogMsgSplice', {id: file.tempId});
                    break;

                case 'success':
                    this.$store.dispatch('dialogMsgSplice', {id: file.tempId, data: file.data});
                    break;
            }
        },

        chatScroll(res) {
            switch (res.directionreal) {
                case 'up':
                    if (res.scrollE < 10) {
                        this.autoBottom = true;
                    }
                    break;
                case 'down':
                    this.autoBottom = false;
                    break;
            }
        },

        goBottom() {
            if (this.autoBottom) {
                this.msgNew = 0;
                this.$refs.scroller.autoToBottom();
            }
        },

        goNewBottom() {
            this.autoBottom = true;
            this.goBottom();
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
    }
}
</script>
