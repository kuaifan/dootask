<template>
    <div class="mobile-back">
        <div v-show="windowScrollY > 0" ref="bar" class="back-bar"></div>
        <div v-if="show" class="back-semicircle" :style="style"></div>
    </div>
</template>

<script>
import {mapState} from "vuex";

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
        this.$refs.bar.addEventListener('touchmove', this.barListener)
        document.addEventListener('touchstart', this.touchstart)
        document.addEventListener('touchmove', this.touchmove)
        document.addEventListener('touchend', this.touchend)
    },

    beforeDestroy() {
        this.$refs.bar.removeEventListener('touchmove', this.barListener)
        document.removeEventListener('touchstart', this.touchstart)
        document.removeEventListener('touchmove', this.touchmove)
        document.removeEventListener('touchend', this.touchend)
    },

    computed: {
        ...mapState(['windowScrollY']),

        style() {
            return {
                top: (this.y + this.windowScrollY) + 'px',
                left: this.x > 20 ? 0 : '-50px',
            }
        },
    },

    watch: {
        show(state) {
            if (state) {
                document.body.classList.add("touch-back");
            } else {
                document.body.classList.remove("touch-back");
            }
            this.$store.state.touchBackInProgress = state;
        }
    },

    methods: {
        getXY(event) {
            let touch = event.touches[0]
            this.x = touch.clientX
            this.y = touch.clientY
        },

        barListener(event) {
            event.preventDefault()
        },

        touchstart(event) {
            this.getXY(event)
            // 判断是否是边缘滑动
            this.show = this.canBack() && this.x < 20;
        },

        touchmove(event) {
            if (this.show) {
                this.getXY(event)
            }
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
                $A.eeuiAppSetPageBackPressed({
                    pageName: 'firstPage',
                }, _ => {
                    if (this.canBack()) {
                        this.onBack();
                    } else {
                        $A.eeuiAppGoDesktop()
                    }
                });
            }
        }
    },
};
</script>
