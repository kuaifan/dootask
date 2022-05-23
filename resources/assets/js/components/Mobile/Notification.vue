<template>
    <transition v-if="show && userid > 0" name="mobile-notify">
        <div class="mobile-notification" :class="{show}" @click.stop="onClick">
            <UserAvatar :userid="userid" :size="40" show-name/>
            <div class="notification-desc">{{desc}}</div>
        </div>
    </transition>
</template>

<script>
export default {
    name: "MobileNotification",
    data() {
        return {
            userid: 0,
            desc: '',
            duration: 6000,
            callback: null,

            show: false,
            timer: null,
        };
    },

    beforeDestroy() {
        this.timer && clearTimeout(this.timer);
        this.show = false;
    },

    methods: {
        open(config) {
            if (!$A.isJson(config)) {
                return;
            }
            this.userid = config.userid || 0;
            this.desc = config.desc || "";
            this.duration = typeof config.duration === "number" ? config.duration : 6000;
            this.callback = typeof config.callback === "function" ? config.callback : null;
            this.show = true;
            this.timer && clearTimeout(this.timer);
            if (this.duration > 0) {
                this.timer = setTimeout(this.close, this.duration)
            }
            if (this.$isEEUiApp) {
                const webview = requireModuleJs("webview");
                webview && webview.sendMessage({
                    action: 'setVibrate',
                    time: 1000
                });
            }
        },

        close() {
            this.show = false;
        },

        onClick() {
            this.close();
            if (typeof this.callback === "function") {
                this.callback();
            }
        }
    },
};
</script>
