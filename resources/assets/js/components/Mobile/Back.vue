<template>
    <div class="mobile-back" :style="style"></div>
</template>

<script>
export default {
    name: "MobileBack",
    props: {
        showTabbar: {
            type: Boolean,
            default: false
        },
    },

    data() {
        return {
            show: false,
            x: 0,
            y: 0
        };
    },

    created() {
        this.appAndroidEvents()
    },

    mounted() {
        window.addEventListener('touchstart', this.touchstart)
        window.addEventListener('touchmove', this.touchmove)
        window.addEventListener('touchend', this.touchend)
    },

    beforeDestroy() {
        window.removeEventListener('touchstart', this.touchstart)
        window.removeEventListener('touchmove', this.touchmove)
        window.removeEventListener('touchend', this.touchend)
    },

    computed: {
        style() {
            return {
                top: this.y + 'px',
                left: this.x > 30 && this.show ? 0 : '-50px',
            }
        },
    },

    methods: {
        getXY(event) {
            let touch = event.touches[0]
            this.x = touch.clientX
            this.y = touch.clientY
        },

        touchstart(event) {
            this.getXY(event)
            // 判断是否是边缘滑动
            this.show = this.canBack() && this.x < 30;
        },

        touchmove(event) {
            this.getXY(event)
        },

        touchend() {
            // 判断停止时的位置偏移
            if (this.x > 90 && this.show) {
                this.onBack();
            }
            this.x = 0
            this.show = false
        },

        canBack() {
            if (!this.showTabbar) {
                return true;
            }
            return this.$Modal.visibles().length > 0;
        },

        onBack() {
            if (this.$Modal.removeLast()) {
                return;
            }
            this.goBack();
        },

        appAndroidEvents() {
            if (this.$isEEUiApp && $A.isAndroid()) {
                const eeui = requireModuleJs("eeui");
                eeui.setPageBackPressed({
                    pageName: 'firstPage',
                }, _ => {
                    if (this.canBack()) {
                        this.onBack();
                    } else {
                        eeui.goDesktop()
                    }
                });
            }
        }
    },
};
</script>
