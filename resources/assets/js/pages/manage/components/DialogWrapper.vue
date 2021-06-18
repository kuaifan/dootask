<template>
    <div
        class="dialog-wrapper"
        @drop.prevent="chatPasteDrag($event, 'drag')"
        @dragover.prevent="chatDragOver(true, $event)"
        @dragleave.prevent="chatDragOver(false, $event)">
        <slot name="head">
            <div class="dialog-title">
                <div class="main-title">
                    <h2>{{dialogDetail.name}}</h2>
                    <em v-if="peopleNum > 0">({{peopleNum}})</em>
                </div>
                <template v-if="dialogDetail.type === 'group'">
                    <div v-if="dialogDetail.group_type === 'project'" class="sub-title pointer" @click="openProject">
                        {{$L('项目聊天室')}} {{$L('打开项目管理')}}
                    </div>
                    <div v-else-if="dialogDetail.group_type === 'task'" class="sub-title pointer" @click="openTask">
                        {{$L('任务聊天室')}} {{$L('查看任务详情')}}
                    </div>
                </template>
            </div>
        </slot>
        <ScrollerY
            ref="scroller"
            class="dialog-scroller overlay-y"
            :auto-bottom="autoBottom"
            @on-scroll="chatScroll"
            static>
            <div ref="manageList" class="dialog-list">
                <ul>
                    <li v-if="dialogMsgHasMorePages" class="history" @click="loadNextPage">{{$L('加载历史消息')}}</li>
                    <li v-else-if="dialogMsgLoad > 0 && dialogMsgList.length === 0" class="loading"><Loading/></li>
                    <li v-else-if="dialogMsgList.length === 0" class="nothing">{{$L('暂无消息')}}</li>
                    <li
                        v-for="item in dialogMsgLists"
                        :id="'view_' + item.id"
                        :key="item.id"
                        :class="{self:item.userid == userId, 'history-tip': topId == item.id}">
                        <em v-if="topId == item.id" class="history-text">{{$L('历史消息')}}</em>
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
            <DragInput
                ref="input"
                v-model="msgText"
                class="dialog-input"
                type="textarea"
                :rows="1"
                :autosize="{ minRows: 1, maxRows: 3 }"
                :maxlength="255"
                @on-focus="onEventFocus"
                @on-blur="onEventblur"
                @on-keydown="chatKeydown"
                @on-input-paste="pasteDrag"
                :placeholder="$L('输入消息...')" />
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
            msgNew: 0,
            topId: 0,
        }
    },

    destroyed() {
        this.$store.state.dialogId = 0;
    },

    deactivated() {
        this.$store.state.dialogId = 0;
    },

    computed: {
        ...mapState([
            'userId',
            'dialogId',
            'dialogDetail',
            'dialogMsgLoad',
            'dialogMsgPush',
            'dialogMsgList',
            'dialogMsgHasMorePages',
        ]),

        dialogMsgLists() {
            const {dialogMsgList} = this;
            return dialogMsgList.sort((a, b) => {
                return a.id - b.id;
            });
        },

        peopleNum() {
            return this.dialogDetail.type === 'group' ? $A.runNum(this.dialogDetail.people) : 0;
        }
    },

    watch: {
        dialogMsgPush() {
            if (this.autoBottom) {
                this.$nextTick(this.goBottom);
            } else {
                this.msgNew++;
            }
        },

        dialogId() {
            this.msgNew = 0;
            this.topId = -1;
        }
    },

    methods: {
        sendMsg(text) {
            if (typeof text === "string" && text) {
                this.msgText = text;
                this.$refs.input.focus();
            }
            if (!this.msgText) {
                return;
            }
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
            }).then(({data}) => {
                this.$store.dispatch("dialogMsgUpdate", {id: tempId, data});
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.$store.dispatch("dialogMsgUpdate", {id: tempId});
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
                    this.$store.dispatch("dialogMsgUpdate", {id: file.tempId});
                    break;

                case 'success':
                    this.$store.dispatch("dialogMsgUpdate", {id: file.tempId, data: file.data});
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

        onEventFocus(e) {
            this.$emit("on-focus", e)
        },

        onEventblur(e) {
            this.$emit("on-blur", e)
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

        openProject() {
            if (!this.dialogDetail.group_info) {
                return;
            }
            this.goForward({path: '/manage/project/' + this.dialogDetail.group_info.id});
        },

        openTask() {
            if (!this.dialogDetail.group_info) {
                return;
            }
            this.$store.dispatch("openTask", {
                id: this.dialogDetail.group_info.id,
                dialog_id: this.dialogDetail.id
            });
        },

        loadNextPage() {
            let topId = this.dialogMsgLists[0].id;
            this.$store.dispatch('getDialogMsgListNextPage').then(() => {
                this.$nextTick(() => {
                    this.topId = topId;
                    let dom = document.getElementById("view_" + topId);
                    if (dom) {
                        try {
                            dom.scrollIntoView(true);
                        } catch (e) {
                            scrollIntoView(dom, {
                                behavior: 'instant',
                                inline: 'start',
                            })
                        }
                    }
                });
            });
        }
    }
}
</script>
