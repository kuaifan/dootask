<template>
    <div
        v-if="user"
        class="avatar-wrapper common-avatar"
        :class="{'avatar-pointer': clickOpenDialog}"
        @click="onClickOpen">
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
</template>

<script>
import {Store} from "le5le-store";
import mixin from './mixin';

export default {
    name: 'UserAvatar',
    mixins: [ mixin ],
    data() {
        return {
            user: null,
            subscribe: null
        }
    },
    mounted() {
        this.getData();
        //
        this.subscribe = Store.subscribe('userActive', ({type, data}) => {
            if (data.userid == this.userid) {
                if (type === 'line') {
                    this.user && this.$set(this.user, 'online', data.online);
                } else {
                    this.setUser(data)
                }
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
        boxClass() {
            return {
                'avatar-box': true,
                'online': this.$store.state.userId === this.userid || this.user.online || this.isBot,
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
            this.getData();
        }
    },
    methods: {
        getData() {
            if (!this.$store.state.userId) {
                return;
            }
            const tempUser = this.$store.state.cacheUserBasic.find(({userid}) => userid == this.userid);
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

            if (typeof this.userResult === "function") {
                this.userResult(info);
            }
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
}
</script>
