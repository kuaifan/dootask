<template>
    <div v-if="$store.state.projectChatShow" class="project-message">
        <div class="group-member">
            <div class="member-head">
                <div class="member-title">Member<span>(25)</span></div>
                <div class="member-view-all">View All</div>
            </div>
            <ul class="member-list">
                <li class="online"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li class="online"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li class="online"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li class="online"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
                <li><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></li>
            </ul>
        </div>
        <div class="group-title">Group Chat</div>
        <ScrollerY ref="groupChat" class="group-chat" @on-scroll="groupChatScroll">
            <div ref="manageList" class="message-list">
                <ul>
                    <li>
                        <div class="message-avatar online"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></div>
                        <div class="message-item">
                            <div class="message-text">Selamat pagi, Mas!</div>
                            <div class="message-time">08:00 AM</div>
                        </div>
                    </li>
                    <li class="self">
                        <div class="message-avatar"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></div>
                        <div class="message-item">
                            <div class="message-text">Selamat pagi, Mas!</div>
                            <div class="message-time">08:00 AM</div>
                        </div>
                    </li>
                    <li>
                        <div class="message-avatar offline"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></div>
                        <div class="message-item">
                            <div class="message-text">Pagi Mas Piko, Langsung saja Ada apa Gerangan mas?</div>
                            <div class="message-time">08:00 AM</div>
                        </div>
                    </li>
                    <li class="self">
                        <div class="message-avatar"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></div>
                        <div class="message-item">
                            <div class="message-text">Pagi Mas Piko, Langsung saja Ada apa Gerangan mas?</div>
                            <div class="message-time">08:00 AM</div>
                        </div>
                    </li>
                    <li>
                        <div class="message-avatar online"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></div>
                        <div class="message-item">
                            <div class="message-text">Selamat pagi, Mas!</div>
                            <div class="message-time">08:00 AM</div>
                        </div>
                    </li>
                    <li class="self">
                        <div class="message-avatar"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></div>
                        <div class="message-item">
                            <div class="message-text">Selamat pagi, Mas!</div>
                            <div class="message-time">08:00 AM</div>
                        </div>
                    </li>
                    <li>
                        <div class="message-avatar offline"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></div>
                        <div class="message-item">
                            <div class="message-text">Pagi Mas Piko, Langsung saja Ada apa Gerangan mas?</div>
                            <div class="message-time">08:00 AM</div>
                        </div>
                    </li>
                    <li class="self">
                        <div class="message-avatar"><Avatar src="https://i.loli.net/2017/08/21/599a521472424.jpg" /></div>
                        <div class="message-item">
                            <div class="message-text">Pagi Mas Piko, Langsung saja Ada apa Gerangan mas?</div>
                            <div class="message-time">08:00 AM</div>
                        </div>
                    </li>
                </ul>
            </div>
        </ScrollerY>
        <div class="group-footer">
            <DragInput class="group-input" v-model="groupText" type="textarea" :rows="1" :autosize="{ minRows: 1, maxRows: 3 }" :maxlength="255" @on-keydown="groupKeydown" @on-input-paste="groupPasteDrag" :placeholder="$L('输入消息...')" />
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
                    .ivu-avatar {
                        width: 36px;
                        height: 36px;
                    }
                    &:before {
                        content: "";
                        position: absolute;
                        right: 0;
                        bottom: 0;
                        width: 9px;
                        height: 9px;
                        border-radius: 50%;
                        background-color: #ff0000;
                        border: 1px solid #ffffff;
                        z-index: 1;
                    }
                    &.online {
                        &:before {
                            background-color: #509E76;
                        }
                    }
                }
            }
        }
        .group-title {
            padding: 0 32px;
            margin-top: 28px;
            font-size: 18px;
            font-weight: 600;
        }
        .group-chat {
            flex: 1;
            padding: 0 32px;
            margin-top: 18px;
            overflow: auto;
            .message-list {
                > ul {
                    > li {
                        display: flex;
                        flex-direction: row;
                        align-items: flex-end;
                        list-style: none;
                        margin-bottom: 16px;
                        .message-avatar {
                            position: relative;
                            margin-bottom: 20px;
                            .ivu-avatar {
                                width: 28px;
                                height: 28px;
                                flex-shrink: 0;
                            }
                            &.online,
                            &.offline {
                                &:before {
                                    content: "";
                                    position: absolute;
                                    transform: scale(0.8);
                                    right: 0;
                                    bottom: 0;
                                    width: 9px;
                                    height: 9px;
                                    border-radius: 50%;
                                    background-color: #ff0000;
                                    border: 1px solid #ffffff;
                                    z-index: 1;
                                }
                            }
                            &.online {
                                &:before {
                                    background-color: #509E76;
                                }
                            }
                        }
                        .message-item {
                            max-width: 70%;
                            display: flex;
                            flex-direction: column;
                            align-items: flex-start;
                            margin: 0 0 0 8px;
                            .message-text {
                                color: #333333;
                                background-color: #F4F5F7;
                                padding: 8px;
                                border-radius: 6px 6px 6px 0;
                            }
                            .message-time {
                                color: #bbbbbb;
                                font-size: 12px;
                                padding-top: 3px;
                            }
                        }
                        &.self {
                            flex-direction: row-reverse;
                            .message-item {
                                align-items: flex-end;
                                margin: 0 8px 0 0;
                                .message-text {
                                    color: #ffffff;
                                    background-color: #2d8cf0;
                                    border-radius: 6px 6px 0 6px;
                                }
                            }
                        }
                    }
                }
            }
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

export default {
    name: "ProjectMessage",
    components: {ScrollerY, DragInput},
    data() {
        return {
            groupText: '',
            autoBottom: true
        }
    },
    mounted() {
        this.groupChatGoAuto();
        this.groupChatGoBottom();
    },
    methods: {
        groupKeydown() {

        },
        groupPasteDrag() {

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
                    this.groupChatGoBottom();
                }
                this.groupChatGoAuto();
            }, 1000);
        },
        groupChatGoBottom(animation = false) {
            this.$nextTick(() => {
                if (typeof this.$refs.groupChat !== "undefined") {
                    this.$refs.groupChat.scrollTo(this.$refs.manageList.clientHeight, animation);
                    this.autoBottom = true;
                }
            });
        },
    }
}
</script>
