<template>
    <div class="project-dialog">
        <DialogWrapper class="project-dialog-wrapper">
            <div slot="head">
                <div class="dialog-user">
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
                <div class="dialog-title">
                    <h2>{{$L('群聊')}}</h2>
                </div>
            </div>
        </DialogWrapper>
    </div>
</template>

<style lang="scss" scoped>
:global {
    .project-dialog {
        display: flex;
        flex-direction: column;
        background-color: #ffffff;
        z-index: 1;
        .project-dialog-wrapper {
            flex: 1;
            height: 0;
            .dialog-user {
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
        }
    }
}
</style>

<script>
import {mapState} from "vuex";
import DialogWrapper from "./DialogWrapper";

export default {
    name: "ProjectDialog",
    components: {DialogWrapper},
    data() {
        return {
            memberShowAll: false,
        }
    },

    computed: {
        ...mapState(['projectDetail', 'projectChatShow']),
    },

    watch: {
        projectDetail() {
            this.getMsg()
        },
        projectChatShow() {
            this.getMsg()
        }
    },

    methods: {
        getMsg() {
            if (this.projectChatShow && this.projectDetail.dialog_id) {
                this.$store.commit('getDialogMsgList', this.projectDetail.dialog_id);
            }
        }
    }
}
</script>
