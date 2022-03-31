<template>
    <ETooltip
        v-if="user"
        class="common-avatar"
        :open-delay="openDelay"
        :disabled="tooltipDisabled"
        :placement="tooltipPlacement">
        <div slot="content" class="common-avatar-transfer">
            <slot/>
            <p>{{$L('昵称')}}: {{user.nickname}}</p>
            <p>{{$L('职位/职称')}}: {{user.profession || '-'}}</p>
            <div v-if="userId != userid && showIconMenu" class="avatar-icons">
                <Icon type="ios-chatbubbles" @click="openDialog"/>
            </div>
        </div>
        <div class="avatar-wrapper">
            <div v-if="showIcon" :class="['avatar-box', userId === userid || user.online ? 'online' : '']" :style="boxStyle">
                <em :style="spotStyle"></em>
                <EAvatar v-if="showImg" ref="avatar" :class="{'avatar-default':isDefault}" :src="user.userimg" :size="avatarSize" :error="onError">
                    <span class="avatar-char" :style="spotStyle">{{nickname}}</span>
                </EAvatar>
                <EAvatar v-else :size="avatarSize" class="avatar-text">
                    <span class="avatar-char" :style="spotStyle">{{nickname}}</span>
                </EAvatar>
            </div>
            <div v-if="showName" class="avatar-name" :style="nameStyle">{{user.nickname}}</div>
        </div>
    </ETooltip>
</template>

<script>
    import {mapState} from "vuex";
    import {Store} from 'le5le-store';

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
            showIcon: {
                type: Boolean,
                default: true
            },
            showName: {
                type: Boolean,
                default: false
            },
            tooltipDisabled: {
                type: Boolean,
                default: false
            },
            showIconMenu: {
                type: Boolean,
                default: false
            },
            tooltipPlacement: {
                type: String,
                default: 'bottom'
            },
            borderWitdh: {
                type: Number,
                default: 0
            },
            borderColor: {
                type: String,
                default: ''
            },
            openDelay: {
                type: Number,
                default: 600
            },
        },
        data() {
            return {
                user: null,
                subscribe: null
            }
        },
        mounted() {
            this.getData();
            //
            this.subscribe = Store.subscribe('cacheUserActive', (data) => {
                if (data.userid == this.userid) {
                    this.setUser(data)
                }
            });
        },
        beforeDestroy() {
            if (this.subscribe) {
                this.subscribe.unsubscribe();
                this.subscribe = null;
            }
        },
        computed: {
            ...mapState(["userId", "userInfo", "userOnline"]),

            boxStyle() {
                const style = {};
                const {borderWitdh, borderColor} = this
                if (borderWitdh > 0) {
                    style.border = borderWitdh + "px solid " + (borderColor || "#ffffff");
                }
                return style;
            },

            spotStyle() {
                let {borderWitdh, size} = this
                if (size === 'default') size = 32;
                if (borderWitdh > 0) size-= borderWitdh;
                if (size == 32) {
                    return {}
                }
                return {
                    'transform': 'scale(' + Math.min(1.25, size / 32) + ')',
                }
            },

            nameStyle() {
                const {showIcon} = this;
                if (!showIcon) {
                    return {
                        paddingLeft: 0
                    }
                } else {
                    return {}
                }
            },

            avatarSize() {
                let {borderWitdh, size} = this
                if (size === 'default') size = 32;
                if (borderWitdh > 0) {
                    return size - borderWitdh * 2;
                } else {
                    return size;
                }
            },

            showImg() {
                const {userimg} = this.user
                if (!userimg) {
                    return false;
                }
                return !$A.rightExists(userimg, '/avatar.png');
            },

            isDefault() {
                const {userimg} = this.user
                return $A.strExists(userimg, '/avatar/default_');
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

            userInfo(info) {
                if (info.userid == this.userid) {
                    this.setUser(info);
                }
            },

            userOnline(data) {
                if (this.user && typeof data[this.user.userid] !== "undefined") {
                    this.$set(this.user, 'online', data[this.user.userid]);
                }
            }
        },
        methods: {
            getData() {
                if (!this.userid) {
                    return;
                }
                if (this.userid == this.userInfo.userid) {
                    this.setUser(this.userInfo);
                    return;
                }
                this.$store.dispatch("getUserBasic", {userid: this.userid});
            },

            setUser(info) {
                try {
                    if (this.user && this.user.userimg != info.userimg && this.$refs.avatar) {
                        this.$refs.avatar.$data.isImageExist = true;
                    }
                } catch (e) {
                    //
                }
                this.user = info;
            },

            onError() {
                return true
            },

            openDialog() {
                this.goForward({name: 'manage-messenger'});
                this.$store.dispatch("openDialogUserid", this.userid).catch(() => {})
            }
        }
    };
</script>
