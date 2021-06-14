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

    mounted() {
        this.getMsg();
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
                this.$store.dispatch("getDialogMsgList", this.projectDetail.dialog_id);
            }
        }
    }
}
</script>
