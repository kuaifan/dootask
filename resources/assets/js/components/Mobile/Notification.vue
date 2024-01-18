<template>
    <transition v-if="show && userid > 0" name="mobile-notify">
        <div
            class="mobile-notification"
            :class="{show}"
            :style="notifyStyle"
            @click.stop="onClick"
            @touchstart="onTouchstart"
            @touchmove="onTouchmove">
            <UserAvatar :userid="userid" :size="40" show-name :name-text="title"/>
            <div class="notification-desc no-dark-content">{{desc}}</div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "MobileNotification",
    data() {
        return {
            userid: 0,
            title: '',
            desc: '',
            duration: 6000,
            callback: null,

            show: false,
            timer: null,

            startY: 0,
        };
    },

    beforeDestroy() {
        this.timer && clearTimeout(this.timer);
        this.show = false;
    },

    computed: {
        notifyStyle() {
            return {
                marginTop: this.$store.state.windowScrollY + 'px',
            };
        },
    },

    methods: {
        open(config) {
            if (!$A.isJson(config)) {
                return;
            }
            this.userid = config.userid || 0;
            this.title = config.title || "";
            this.desc = config.desc || "";
            this.duration = typeof config.duration === "number" ? config.duration : 6000;
            this.callback = typeof config.callback === "function" ? config.callback : null;
            this.show = true;
            this.timer && clearTimeout(this.timer);
            if (this.duration > 0) {
                this.timer = setTimeout(this.close, this.duration)
            }
            $A.eeuiAppSendMessage({
                action: 'setVibrate',
            });
        },

        close() {
            this.show = false;
        },

        onClick() {
            this.close();
            if (typeof this.callback === "function") {
                this.callback();
            }
        },

        onTouchstart(e) {
            this.startY = e.touches[0].clientY;
        },

        onTouchmove(e) {
            if (this.startY > 0 && this.startY - e.touches[0].clientY > 10) {
                this.startY = 0;
                this.close();
            }
        },
    },
};
</script>
