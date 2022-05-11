<template>
    <div class="project-dialog">
        <DialogWrapper :dialog-id="projectData.dialog_id" class="project-dialog-wrapper">
            <div slot="head">
                <div class="dialog-user">
                    <div class="member-head">
                        <div class="member-title">{{$L('项目成员')}}<span @click="memberShowAll=!memberShowAll">({{projectData.project_user.length}})</span></div>
                        <div class="member-close" @click="onClose">
                            <Icon type="ios-close"/>
                        </div>
                    </div>
                    <ul :class="['member-list', memberShowAll ? 'member-all' : '']">
                        <li v-for="item in projectData.project_user">
                            <UserAvatar :userid="item.userid" :size="36"/>
                        </li>
                    </ul>
                </div>
                <div class="dialog-nav">
                    <div class="dialog-title">
                        <h2>{{$L('群聊')}}</h2>
                    </div>
                </div>
            </div>
        </DialogWrapper>
    </div>
</template>

<script>
import {mapGetters} from "vuex";
import {Store} from 'le5le-store';
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
        ...mapGetters(['projectData'])
    },

    methods: {
        onClose() {
            this.$store.dispatch('toggleProjectParameter', 'chat');
        }
    }
}
</script>
