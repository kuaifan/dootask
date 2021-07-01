<template>
    <div
        class="dialog-wrapper"
        @drop.prevent="chatPasteDrag($event, 'drag')"
        @dragover.prevent="chatDragOver(true, $event)"
        @dragleave.prevent="chatDragOver(false, $event)">
        <slot name="head">
            <div class="dialog-title">
                <div class="main-title">
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
                    <li v-if="dialogData.hasMorePages" class="history" @click="loadNextPage">{{$L('加载历史消息')}}</li>
                    <li v-else-if="dialogData.loading > 0 && dialogMsgList.length === 0" class="loading"><Loading/></li>
                    <li v-else-if="dialogMsgList.length === 0" class="nothing">{{$L('暂无消息')}}</li>
                    <li
                        v-for="item in dialogMsgList"
                        :id="'view_' + item.id"
                        :key="item.id"
                        :class="{self:item.userid == userId, 'history-tip': topId == item.id}">
                        <em v-if="topId == item.id" class="history-text">{{$L('历史消息')}}</em>
                        <div class="dialog-avatar">
                            <UserAvatar :userid="item.userid" :tooltip-disabled="item.userid == userId" :size="30"/>
                        </div>
                        <DialogView :msg-data="item" :dialog-type="dialogData.type"/>
                    </li>
                    <li
                        v-for="item in tempMsgList"
                        :id="'tmp_' + item.id"
                        :key="'tmp_' + item.id"
                        :class="{self:item.userid == userId}">
                        <div class="dialog-avatar">
                            <UserAvatar :userid="item.userid" :tooltip-disabled="item.userid == userId" :size="30"/>
                        </div>
                        <DialogView :msg-data="item" :dialog-type="dialogData.type"/>
                    </li>
                </ul>
            </div>
        </ScrollerY>
        <div :class="['dialog-footer', msgNew > 0 && dialogMsgList.length > 0 ? 'newmsg' : '']" @click="onActive">
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
                :dialog-id="dialogId"
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
    props: {
        dialogId: {
            type: Number,
            default: 0
        },
    },

    data() {
        return {
            autoBottom: true,
            autoInterval: null,

            dialogDrag: false,

            msgText: '',
            msgNew: 0,
            topId: 0,

            tempMsgs: []
        }
    },

    computed: {
        ...mapState([
            'userId',
            'dialogs',
            'dialogMsgs',
            'dialogMsgPush',
        ]),

        dialogData() {
            return this.dialogs.find(({id}) => id == this.dialogId) || {};
        },

        dialogMsgList() {
            if (!this.dialogId) {
                return [];
            }
            return $A.cloneJSON(this.dialogMsgs.filter(({dialog_id}) => {
                return dialog_id == this.dialogId;
            })).sort((a, b) => {
                return a.id - b.id;
            });
        },

        tempMsgList() {
            if (!this.dialogId) {
                return [];
            }
            return $A.cloneJSON(this.tempMsgs.filter(({dialog_id}) => {
                return dialog_id == this.dialogId;
            }));
        },

        peopleNum() {
            return this.dialogData.type === 'group' ? $A.runNum(this.dialogData.people) : 0;
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

        dialogId: {
            handler(id) {
                if (id) {
                    this.autoBottom = true;
                    this.msgNew = 0;
                    this.topId = -1;
                    this.$store.dispatch("getDialogMsgs", id);
                }
            },
            immediate: true
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
            this.tempMsgs.push({
                id: tempId,
                dialog_id: this.dialogData.id,
                type: 'text',
                userid: this.userId,
                msg: {
                    text: this.msgText,
                },
            });
            this.autoBottom = true;
            this.onActive();
            //
            this.$store.dispatch("call", {
                url: 'dialog/msg/sendtext',
                data: {
                    dialog_id: this.dialogId,
                    text: this.msgText,
                },
            }).then(({data}) => {
                this.tempMsgs = this.tempMsgs.filter(({id}) => id != tempId)
                this.sendSuccess(data);
            }).catch(({msg}) => {
                $A.modalError(msg);
                this.tempMsgs = this.tempMsgs.filter(({id}) => id != tempId)
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
                    this.tempMsgs.push({
                        id: file.tempId,
                        dialog_id: this.dialogData.id,
                        type: 'loading',
                        userid: this.userId,
                        msg: { },
                    });
                    this.autoBottom = true;
                    this.onActive();
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
            this.$store.dispatch("saveDialogMsg", data);
            this.$store.dispatch("increaseTaskMsgNum", this.dialogId);
            this.$store.dispatch("moveDialogTop", this.dialogId);
            this.$store.dispatch("updateDialogLastMsg", data);
            this.onActive();
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
            if (res.scale === 1) {
                this.autoBottom = true;
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

        onActive() {
            this.$emit("on-active");
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
            if (!this.dialogData.group_info) {
                return;
            }
            this.goForward({path: '/manage/project/' + this.dialogData.group_info.id});
        },

        openTask() {
            if (!this.dialogData.group_info) {
                return;
            }
            this.$store.dispatch("openTask", this.dialogData.group_info.id);
        },

        loadNextPage() {
            let topId = this.dialogMsgList[0].id;
            this.$store.dispatch('getDialogMsgNextPage', this.dialogId).then(() => {
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
