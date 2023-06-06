<template>
    <ETooltip
        v-if="user"
        class="common-avatar"
        :open-delay="openDelay"
        :disabled="$isEEUiApp || windowTouch || tooltipDisabled || isBot"
        :placement="tooltipPlacement">
        <div slot="content" class="common-avatar-transfer">
            <slot/>
            <p>{{$L('昵称')}}: {{user.nickname}}<em v-if="user.delete_at" class="deleted no-dark-content">{{$L('已删除')}}</em><em v-else-if="user.disable_at" class="disabled no-dark-content">{{$L('已离职')}}</em></p>
            <p class="department-name" :title="user.department_name || ''">{{$L('部门')}}: {{user.department_name || '-'}}</p>
            <p>{{$L('职位/职称')}}: {{user.profession || '-'}}</p>
            <p v-if="user.delete_at"><strong>{{$L('删除时间')}}: {{user.delete_at}}</strong></p>
            <p v-else-if="user.disable_at"><strong>{{$L('离职时间')}}: {{user.disable_at}}</strong></p>
            <slot name="end"/>
            <div v-if="showMenu" class="avatar-icons">
                <Icon type="ios-chatbubbles" @click="openDialog"/>
            </div>
        </div>
        <div class="avatar-wrapper" :class="{'avatar-pointer': clickOpenDialog}" @click="onClickOpen">
            <div v-if="showIcon" :class="boxClass" :style="boxStyle">
                <em :style="spotStyle"></em>
                <EAvatar v-if="showImg" ref="avatar" :class="{'avatar-default':isDefault}" :src="user.userimg" :size="avatarSize" :error="onError">
                    <span class="avatar-char" :style="spotStyle">{{nickname}}</span>
                </EAvatar>
                <EAvatar v-else :size="avatarSize" class="avatar-text">
                    <span class="avatar-char" :style="spotStyle">{{nickname}}</span>
                </EAvatar>
            </div>
            <div v-if="showName" class="avatar-name" :style="nameStyle">
                <div v-if="user.bot" class="taskfont bot">&#xe68c;</div>
                <span>{{nameText || user.nickname}}</span>
            </div>
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
            nameText: {
                type: String,
                default: null   // showName = true 时有效，留空就显示会员昵称
            },
            tooltipDisabled: {
                type: Boolean,
                default: false
            },
            showIconMenu: {
                type: Boolean,
                default: false
            },
            clickOpenDialog: {
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
            userResult: {
                type: Function,
                default: () => {
                }
            }
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
            this.$store.state.userAvatar[this._uid] = this.$props;
        },
        beforeDestroy() {
            if (this.subscribe) {
                this.subscribe.unsubscribe();
                this.subscribe = null;
            }
            if (this.$store.state.userAvatar[this._uid] !== undefined) {
                delete this.$store.state.userAvatar[this._uid];
            }
        },
        computed: {
            ...mapState(['userInfo', 'userOnline', 'cacheUserBasic']),

            boxClass() {
                return {
                    'avatar-box': true,
                    'online': this.userId === this.userid || this.user.online || this.isBot,
                    'disabled': this.user.disable_at,
                    'deleted': this.user.delete_at
                }
            },

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
                const {delete_at, disable_at} = this.user
                const styles = {}
                if (!showIcon) {
                    styles.marginLeft = 0
                }
                if (delete_at || disable_at) {
                    styles.opacity = 0.8
                    styles.textDecoration = "line-through"
                }
                return styles
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

            showMenu() {
                if (this.userId == this.userid) {
                    return false
                }
                if (this.user.delete_at || this.user.disable_at) {
                    return false
                }
                return this.showIconMenu
            },

            isDefault() {
                const {userimg} = this.user
                return $A.strExists(userimg, '/avatar');
            },

            isBot() {
                return !!(this.user && this.user.bot);
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
            },

            'user.online'(val) {
                if (val || this.userId === this.userid) {
                    this.$emit('update:online', true)
                } else {
                    const now = $A.Time()
                    const line = $A.Time(this.user.line_at)
                    const seconds = now - line
                    let stats = '最后在线于很久以前';
                    if (seconds < 60) {
                        stats = `最后在线于刚刚`
                    } else if (seconds < 3600) {
                        stats = `最后在线于 ${Math.floor(seconds / 60)} 分钟前`
                    } else if (seconds < 3600 * 6) {
                        stats = `最后在线于 ${Math.floor(seconds / 3600)} 小时前`
                    } else {
                        const nowYmd = $A.formatDate('Y-m-d', now)
                        const lineYmd = $A.formatDate('Y-m-d', line)
                        const lineHi = $A.formatDate('H:i', line)
                        if (nowYmd === lineYmd) {
                            stats = `最后在线于今天 ${lineHi}`
                        } else if ($A.formatDate('Y-m-d', now - 86400) === lineYmd) {
                            stats = `最后在线于昨天 ${lineHi}`
                        } else if (seconds < 3600 * 24 * 365) {
                            stats = `最后在线于 ${lineYmd}`
                        }
                    }
                    this.$emit('update:online', this.$L(stats))
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
                const tempUser = this.cacheUserBasic.find(({userid}) => userid == this.userid);
                if (tempUser) {
                    this.setUser(tempUser);
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
                this.userResult(info);
            },

            onClickOpen() {
                if (this.clickOpenDialog) {
                    this.openDialog()
                } else {
                    this.$emit('open-dialog', this.userid)
                }
            },

            openDialog() {
                this.$store.dispatch("openDialogUserid", this.userid).then(_ => {
                    this.goForward({name: 'manage-messenger'})
                }).catch(({msg}) => {
                    $A.modalError(msg)
                });
            },

            onError() {
                return true
            },
        }
    };
</script>
