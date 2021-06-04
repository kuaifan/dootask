<template>
    <div v-if="$store.state.projectChatShow" class="project-message">
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
        <ScrollerY ref="groupChat" class="group-chat message-scroller" @on-scroll="groupChatScroll">
            <div ref="manageList" class="message-list">
                <ul>
                    <li v-if="dialogLoad > 0" class="loading"><Loading/></li>
                    <li v-else-if="dialogList.length === 0" class="nothing">{{$L('暂无消息')}}</li>
                    <li v-for="(item, key) in dialogList" :key="key" :class="{self:item.userid == userId}">
                        <div class="message-avatar">
                            <UserAvatar :userid="item.userid" :size="30"/>
                        </div>
                        <MessageView :msg-data="item"/>
                    </li>
                </ul>
            </div>
        </ScrollerY>
        <div class="group-footer">
            <DragInput class="group-input" v-model="msgText" type="textarea" :rows="1" :autosize="{ minRows: 1, maxRows: 3 }" :maxlength="255" @on-keydown="groupKeydown" @on-input-paste="groupPasteDrag" :placeholder="$L('输入消息...')" />
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
            padding: 0 28px;
            margin-bottom: 20px;
        }
    }
}
</style>

<script>
import DragInput from "../../../components/DragInput";
import ScrollerY from "../../../components/ScrollerY";
import {mapState} from "vuex";
import MessageView from "./message-view";

export default {
    name: "ProjectMessage",
    components: {MessageView, ScrollerY, DragInput},
    data() {
        return {
            autoBottom: true,
            memberShowAll: false,

            dialogId: 0,

            msgText: '',
        }
    },

    mounted() {
        this.groupChatGoAuto();
        this.groupChatGoBottom();
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
        }
    },

    methods: {
        sendMsg() {
            let mid = $A.randomString(16);
            this.dialogList.push({
                id: mid,
                userid: this.userId,
                type: 'text',
                msg: {
                    text: this.msgText,
                },
            });
            this.groupChatGoBottom(true);
            //
            $A.apiAjax({
                url: 'dialog/msg/sendtext',
                data: {
                    dialog_id: this.projectDetail.dialog_id,
                    text: this.msgText,
                },
                error:() => {
                    this.dialogList = this.dialogList.filter(({id}) => id != mid);
                },
                success: ({ret, data, msg}) => {
                    if (ret === 1) {
                        if (!this.dialogList.find(({id}) => id == data.id)) {
                            let index = this.dialogList.findIndex(({id}) => id == mid);
                            if (index > -1) this.dialogList.splice(index, 1, data);
                            return;
                        }
                    }
                    this.dialogList = this.dialogList.filter(({id}) => id != mid);
                }
            });
            //
            this.msgText = '';
        },

        groupKeydown(e) {
            if (e.keyCode === 13) {
                if (e.shiftKey) {
                    return;
                }
                e.preventDefault();
                this.sendMsg();
            }
        },

        groupPasteDrag(e, type) {
            const files = type === 'drag' ? e.dataTransfer.files : e.clipboardData.files;
            const postFiles = Array.prototype.slice.call(files);
            if (postFiles.length > 0) {
                e.preventDefault();
                postFiles.forEach((file) => {
                    // 上传文件
                });
            }
        },

        groupChatScroll(res) {
            if (res.directionreal === 'up') {
                if (res.scrollE < 10) {
                    this.autoBottom = true;
                }
            } else if (res.directionreal === 'down') {
                this.autoBottom = false;
            }
        },

        groupChatGoAuto() {
            clearTimeout(this.groupChatGoTimeout);
            this.groupChatGoTimeout = setTimeout(() => {
                if (this.autoBottom) {
                    this.groupChatGoBottom(true);
                }
                this.groupChatGoAuto();
            }, 1000);
        },

        groupChatGoBottom(animation = false) {
            this.$nextTick(() => {
                if (typeof this.$refs.groupChat !== "undefined") {
                    if (this.$refs.groupChat.getScrollInfo().scrollE > 0) {
                        this.$refs.groupChat.scrollTo(this.$refs.manageList.clientHeight, animation);
                    }
                    this.autoBottom = true;
                }
            });
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
