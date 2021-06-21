<template>
    <div class="project-dialog">
        <DialogWrapper class="project-dialog-wrapper">
            <div slot="head">
                <div class="dialog-user">
                    <div class="member-head">
                        <div class="member-title">{{$L('项目成员')}}<span>({{projectData.project_user.length}})</span></div>
                        <div class="member-view-all" @click="memberShowAll=!memberShowAll">{{$L('查看所有')}}</div>
                    </div>
                    <ul :class="['member-list', memberShowAll ? 'member-all' : '']">
                        <li v-for="item in projectData.project_user">
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
import {mapGetters, mapState} from "vuex";
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
        ...mapState(['projectChatShow']),

        ...mapGetters(['projectData'])
    },

    watch: {
        'projectData.dialog_id' () {
            this.getMsgList()
        },
        projectChatShow: {
            handler() {
                this.getMsgList()
            },
            immediate: true
        }
    },

    methods: {
        getMsgList() {
            if (this.projectChatShow && this.projectData.dialog_id) {
                this.$store.dispatch("getDialogMsgList", this.projectData.dialog_id);
            }
        }
    }
}
</script>
