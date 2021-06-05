<template>
    <div
        v-if="$store.state.projectChatShow"
        class="project-message"
        @drop.prevent="chatPasteDrag($event, 'drag')"
        @dragover.prevent="chatDragOver(true)"
        @dragleave.prevent="chatDragOver(false)">
        <div class="group-member">
            <div class="member-head">
                <div class="member-title">{{$L('项目成员')}}<span>({{projectDetail.project_user.length}})</span></div>
                <div class="member-view-all" @click="memberShowAll=!memberShowAll">{{$L('查看所有')}}</div>
            </div>
            <ul :class="['member-list', memberShowAll ? 'member-all' : '']">
                <li v-for="item in projectDetail.project_user">
                    <UserAvatar :userid="item.userid" :size="36"/>
                </li>
            </ul>
        </div>
        <div class="group-title">{{$L('群聊')}}</div>
        <ScrollerY ref="groupChat" class="group-chat message-scroller" @on-scroll="chatScroll">
            <div ref="manageList" class="message-list">
                <ul>
                    <li v-if="dialogLoad > 0" class="loading"><Loading/></li>
                    <li v-else-if="dialogList.length === 0" class="nothing">{{$L('暂无消息')}}</li>
                    <li v-for="(item, key) in dialogList" :key="key" :class="{self:item.userid == userId}">
                        <div class="message-avatar">
                            <UserAvatar :userid="item.userid" :size="30"/>
                        </div>
                        <MessageView :msg-data="item" dialog-type="group"/>
                    </li>
                    <li ref="bottom" class="bottom"></li>
                </ul>
            </div>
        </ScrollerY>
        <div :class="['group-footer', msgNew > 0 ? 'newmsg' : '']">
            <div class="group-newmsg" @click="goNewBottom">{{$L('有' + msgNew + '条新消息')}}</div>
            <DragInput class="group-input" v-model="msgText" type="textarea" :rows="1" :autosize="{ minRows: 1, maxRows: 3 }" :maxlength="255" @on-keydown="chatKeydown" @on-input-paste="pasteDrag" :placeholder="$L('输入消息...')" />
            <MessageUpload
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

<style lang="scss">
:global {
    .project-message {
        .group-footer {
            .group-input {
                background-color: #F4F5F7;
                padding: 10px 12px;
                border-radius: 10px;
                .ivu-input {
                    border: 0;
                    resize: none;
                    background-color: transparent;
                    &:focus {
                        box-shadow: none;
                    }
                }
            }
        }
    }
}
</style>
<style lang="scss" scoped>
:global {
    .project-message {
        display: flex;
        flex-direction: column;
        background-color: #ffffff;
        z-index: 1;
        .group-member {
            margin-top: 36px;
            padding: 0 32px;
            .member-head {
                display: flex;
                align-items: center;
                .member-title {
                    flex: 1;
                    font-size: 18px;
                    font-weight: 600;
                    > span {
                        padding-left: 6px;
                        color: #2d8cf0;
                    }
                }
                .member-view-all {
                    color: #999;
                    font-size: 13px;
                    cursor: pointer;
                    &:hover {
                        color: #777;
                    }
                }
            }
            .member-list {
                display: flex;
                align-items: center;
                margin-top: 14px;
                overflow: auto;
                > li {
                    position: relative;
                    list-style: none;
                    margin-right: 14px;
                    margin-bottom: 8px;
                }
                &.member-all {
                    display: block;
                    > li {
                        display: inline-block;
                    }
                }
            }
        }
        .group-title {
            padding: 0 32px;
            margin-top: 20px;
            font-size: 18px;
            font-weight: 600;
        }
        .group-chat {
            flex: 1;
            padding: 0 32px;
            margin-top: 18px;
        }
        .group-footer {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            padding: 0 28px;
            margin-bottom: 20px;
            .group-newmsg {
                display: none;
                height: 30px;
                line-height: 30px;
                color: #ffffff;
                font-size: 12px;
                background-color: rgba(0, 0, 0, 0.6);
                padding: 0 12px;
                margin-bottom: 20px;
                margin-right: 10px;
                border-radius: 16px;
                cursor: pointer;
                z-index: 2;;
            }
            .chat-upload {
                display: none;
                width: 0;
                height: 0;
                overflow: hidden;
            }
            &.newmsg {
                margin-top: -50px;
                .group-newmsg {
                    display: block;
                }
            }
        }
        .drag-over {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 3;
            background-color: rgba(255, 255, 255, 0.78);
            display: flex;
            align-items: center;
            justify-content: center;
            &:before {
                content: "";
                position: absolute;
                top: 16px;
                left: 16px;
                right: 16px;
                bottom: 16px;
                border: 2px dashed #7b7b7b;
                border-radius: 12px;
            }
            .drag-text {
                padding: 12px;
                font-size: 18px;
                color: #666666;
            }
        }
    }
}
</style>

<script>
import DragInput from "../../../components/DragInput";
import ScrollerY from "../../../components/ScrollerY";
import {mapState} from "vuex";
import MessageView from "./message-view";
import MessageUpload from "./message-upload";

export default {
    name: "ProjectMessage",
    components: {MessageUpload, MessageView, ScrollerY, DragInput},
    data() {
        return {
            autoBottom: true,
            autoInterval: null,

            memberShowAll: false,

            dialogId: 0,
            dialogDrag: false,

            msgText: '',
            msgLength: 0,
            msgNew: 0,
        }
    },

    mounted() {
        this.goBottom();
        this.autoInterval = setInterval(this.goBottom, 200)
    },

    beforeDestroy() {
        clearInterval(this.autoInterval)
    },

    computed: {
        ...mapState(['userId', 'projectDetail', 'projectMsgUnread', 'dialogLoad', 'dialogList']),
    },

    watch: {
        projectDetail(detail) {
            this.dialogId = detail.dialog_id;
        },

        dialogId(id) {
            this.$store.commit('getDialogMsg', id);
        },

        dialogList(list) {
            if (!this.autoBottom) {
                let length = list.length - this.msgLength;
                if (length > 0) {
                    this.msgNew+= length;
                }
            }
            this.msgLength = list.length;
        }
    },

    methods: {
        sendMsg() {
            let tempId = $A.randomString(16);
            this.dialogList.push({
                id: tempId,
                type: 'text',
                userid: this.userId,
                msg: {
                    text: this.msgText,
                },
            });
            this.goBottom();
            //
            $A.apiAjax({
                url: 'dialog/msg/sendtext',
                data: {
                    dialog_id: this.projectDetail.dialog_id,
                    text: this.msgText,
                },
                error:() => {
                    this.$store.commit('spliceDialogMsg', {id: tempId});
                },
                success: ({ret, data, msg}) => {
                    if (ret !== 1) {
                        $A.modalWarning({
                            title: '发送失败',
                            content: msg
                        });
                    }
                    this.$store.commit('spliceDialogMsg', {
                        id: tempId,
                        data: ret === 1 ? data : null
                    });
                }
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

        chatDragOver(show) {
            let random = (this.__dialogDrag = $A.randomString(8));
            if (!show) {
                setTimeout(() => {
                    if (random === this.__dialogDrag) {
                        this.dialogDrag = show;
                    }
                }, 150);
            } else {
                this.dialogDrag = show;
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
                    this.dialogList.push({
                        id: file.tempId,
                        type: 'loading',
                        userid: this.userId,
                        msg: { },
                    });
                    break;

                case 'error':
                    this.$store.commit('spliceDialogMsg', {id: file.tempId});
                    break;

                case 'success':
                    this.$store.commit('spliceDialogMsg', {id: file.tempId, data: file.data});
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
            if (this.autoBottom && this.$refs.bottom) {
                this.$refs.bottom.scrollIntoView(false);
            }
        },

        goNewBottom() {
            this.msgNew = 0;
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
