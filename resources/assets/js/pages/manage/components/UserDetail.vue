<template>
    <ModalAlive
        v-model="showModal"
        class-name="common-user-detail-modal"
        :fullscreen="isFullscreen"
        :mask-closable="false"
        :footer-hide="true"
        width="600">
            <div class="user-detail-body">
                <UserAvatar
                    :userid="userData.userid"
                    :size="120"
                    :show-state-dot="false"
                    @open-dialog="onOpenAvatar"/>
                <ul>
                    <li>
                        <h1>{{userData.nickname}}</h1>
                        <em v-if="userData.delete_at" class="deleted no-dark-content">{{$L('已删除')}}</em>
                        <em v-else-if="userData.disable_at" class="disabled no-dark-content">{{$L('已离职')}}</em>
                    </li>
                    <template v-if="!userData.bot">
                        <li class="department-name">
                            <span>{{$L('部门')}}: </span>
                            {{userData.department_name || '-'}}
                        </li>
                        <li>
                            <span>{{$L('职位/职称')}}: </span>
                            {{userData.profession || '-'}}
                        </li>
                        <li v-if="userData.delete_at">
                            <strong><span>{{$L('删除时间')}}: </span>{{userData.delete_at}}</strong>
                        </li>
                        <li v-else-if="userData.disable_at">
                            <strong><span>{{$L('离职时间')}}: </span>{{userData.disable_at}}</strong>
                        </li>
                        <li>
                            <span>{{$L('最后在线')}}: </span>
                            {{userData.line_at || '-'}}
                        </li>
                    </template>
                </ul>
                <Button icon="md-chatbubbles" :disabled="userData.delete_at" @click="onOpenDialog">{{ $L('开始聊天') }}</Button>
            </div>
    </ModalAlive>
</template>

<script>
import emitter from "../../../store/events";
import {mapState} from "vuex";

export default {
    name: 'UserDetail',

    data() {
        return {
            userData: {
                userid: 0
            },

            showModal: false,
        }
    },

    mounted() {
        emitter.on('openUser', this.onShow);
    },

    beforeDestroy() {
        emitter.off('openUser', this.onShow);
    },

    watch: {
        ...mapState(['cacheUserBasic'])
    },

    computed: {
        isFullscreen({windowWidth}) {
            return windowWidth < 576
        },
    },

    methods: {
        onShow(userid) {
            if (!/^\d+$/.test(userid)) {
                return
            }
            this.$store.dispatch("showSpinner", 600)
            this.$store.dispatch('getUserData', userid).then(user => {
                this.userData = user;
                this.showModal = true
            }).finally(_ => {
                this.$store.dispatch("hiddenSpinner")
            });
        },

        onHide() {
            this.showModal = false
        },

        onOpenAvatar() {
            this.$store.dispatch("previewImage", this.userData.userimg)
        },

        onOpenDialog() {
            this.$store.dispatch("openDialogUserid", this.userData.userid).then(_ => {
                this.onHide()
            }).catch(({msg}) => {
                $A.modalError(msg)
            });
        }
    }
};
</script>
