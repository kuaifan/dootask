<template>
    <div class="mobile-back" :style="style"></div>
</template>

<script>
export default {
    name: "MobileBack",
    props: {
        disabled: {
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
                left: !this.disabled && this.x > 30 && this.show ? 0 : '-50px',
            }
        },
    },

    methods: {
        // 获取坐标
        getXY(event) {
            let touch = event.touches[0]
            this.x = touch.clientX
            this.y = touch.clientY
        },
        touchstart(event) {
            this.getXY(event)
            // 判断是否是边缘滑动
            this.show = !this.disabled && this.x < 30;
        },
        touchmove(event) {
            this.getXY(event)
        },
        touchend() {
            // 判断停止时的位置偏移
            if (this.x > 90 && this.show) {
                this.goBack();
            }
            this.x = 0
            this.show = false
        },
    },
};
</script>
