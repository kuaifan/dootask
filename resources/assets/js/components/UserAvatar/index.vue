<template>
    <div
        v-if="user"
        class="avatar-wrapper common-avatar"
        :class="{'avatar-pointer': clickOpenDetail}"
        @click="onClickOpen">
        <div
            v-if="showIcon"
            :class="boxClass"
            :style="boxStyle"
            :title="showName ? undefined : user.nickname">
            <em v-if="showStateDot && !user.disable_at" :style="spotStyle"></em>
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
import mixin from './mixin';
import emitter from "../../store/events";

export default {
    name: 'UserAvatar',
    mixins: [ mixin ],
    data() {
        return {
            user: null,
        }
    },
    mounted() {
        this.getData();
        //
        emitter.on('userActive', this.userActive);
        this.$store.state.userAvatar[this._uid] = this.$props;
    },
    beforeDestroy() {
        emitter.off('userActive', this.userActive);
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
            const {borderWidth, borderColor} = this
            if (borderWidth > 0) {
                style.border = borderWidth + "px solid " + (borderColor || "#ffffff");
            }
            return style;
        },

        spotStyle() {
            let {borderWidth, size} = this
            if (size === 'default') size = 32;
            if (borderWidth > 0) size-= borderWidth;
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
            }
            return styles
        },

        avatarSize() {
            let {borderWidth, size} = this
            if (size === 'default') size = 32;
            if (borderWidth > 0) {
                return size - borderWidth * 2;
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
        userActive({type, data}) {
            if (data.userid == this.userid) {
                if (type === 'line') {
                    this.user && this.$set(this.user, 'online', data.online);
                } else {
                    this.setUser(data)
                }
            }
        },

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
            if (this.clickOpenDetail) {
                emitter.emit('openUser', this.userid);
            } else {
                this.$emit('on-click', this.userid)
            }
        },

        openDialog() {
            this.$store.dispatch("openDialogUserid", this.userid).catch(({msg}) => {
                $A.modalError(msg)
            });
        },

        onError() {
            return true
        },
    }
}
</script>
