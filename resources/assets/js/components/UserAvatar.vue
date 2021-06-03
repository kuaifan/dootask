<template>
    <Tooltip v-if="user"
             :class="['common-avatar', user.online ? 'online' : '']"
             :delay="600"
             :transfer="transfer">
        <div slot="content">
            <p>{{$L('昵称')}}: {{user.nickname}}</p>
            <p>{{$L('职位/职称')}}: {{user.profession || '-'}}</p>
        </div>
        <Avatar v-if="showImg" :src="user.userimg" :size="size"/>
        <Avatar v-else :size="size" class="common-avatar-text">{{nickname}}</Avatar>
    </Tooltip>
</template>

<script>
    export default {
        name: 'UserAvatar',
        props: {
            userid: {
                type: [String, Number],
                default: ''
            },
            size: {
                type: [String, Number],
                default: 'default'
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
