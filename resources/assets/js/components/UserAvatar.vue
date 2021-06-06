<template>
    <Tooltip v-if="user"
             class="common-avatar"
             :delay="600"
             :transfer="transfer">
        <div slot="content">
            <p>{{$L('昵称')}}: {{user.nickname}}</p>
            <p>{{$L('职位/职称')}}: {{user.profession || '-'}}</p>
        </div>
        <div class="avatar-wrapper">
            <div :class="['avatar-box', user.online ? 'online' : '']">
                <WAvatar v-if="showImg" :src="user.userimg" :size="size"/>
                <WAvatar v-else :size="size" class="avatar-text">{{nickname}}</WAvatar>
            </div>
            <div v-if="showName" class="avatar-name">{{user.nickname}}</div>
        </div>
    </Tooltip>
</template>

<script>
    import WAvatar from "./WAvatar";
    import {mapState} from "vuex";
    export default {
        name: 'UserAvatar',
        components: {WAvatar},
        props: {
            userid: {
                type: [String, Number],
                default: ''
            },
            size: {
                type: [String, Number],
                default: 'default'
            },
            showName: {
                type: Boolean,
                default: false
            },
            transfer: {
                type: Boolean,
                default: true
            },
        },
        data() {
            return {
                user: null
            }
        },
        mounted() {
            this.getData()
        },
        computed: {
            ...mapState(["userOnline"]),

            showImg() {
                const {userimg} = this.user
                if (!userimg) {
                    return false;
                }
                return !$A.rightExists(userimg, '/avatar.png');
            },

            nickname() {
                const {nickname} = this.user;
                if (!nickname) {
                    return "D";
                }
                let value = nickname.substring(0, 2);
                if (/^[\u4e00-\u9fa5]+$/.test(value)) {
                    value = value.substring(0, 1);
                }
                return value || 'D';
            }
        },
        watch: {
            userid() {
                this.getData()
            },

            userOnline(data) {
                if (this.user && data[this.user.userid]) {
                    this.$set(this.user, 'online', data[this.user.userid]);
                }
            }
        },
        methods: {
            getData() {
                if (!this.userid) {
                    return;
                }
                this.$store.commit('getUserBasic', {
                    userid: this.userid,
                    success: (user) => {
                        this.user = user;
                    }
                });
            }
        }
    };
</script>
