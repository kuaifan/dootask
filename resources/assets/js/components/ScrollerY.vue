<template>
    <div ref="scrollerView" class="app-scroller-y" :class="[static ? 'static' : '']">
        <slot/>
        <div ref="bottom" class="app-scroller-bottom"></div>
    </div>
</template>

<script>
export default {
    name: 'ScrollerY',
    props: {
        static: {
            type: Boolean,
            default: false
        },
        autoBottom: {
            type: Boolean,
            default: false
        },
        autoRecovery: {
            type: Boolean,
            default: true
        },
        autoRecoveryAnimate: {
            type: Boolean,
            default: false
        },
    },

    data() {
        return {
            scrollY: 0,
            scrollDiff: 0,
            autoInterval: null,
        }
    },

    mounted() {
        this.openInterval()
        this.$nextTick(this.initScroll);
    },

    activated() {
        this.openInterval()
        this.recoveryScroll()
    },

    destroyed() {
        this.closeInterval()
    },

    deactivated() {
        this.closeInterval()
    },

    methods: {
        initScroll() {
            this.autoToBottom();
            let scrollListener = typeof this.$listeners['on-scroll'] === "function";
            let scrollerView = $A(this.$refs.scrollerView);
            scrollerView.scroll(() => {
                let wInnerH = Math.round(scrollerView.innerHeight());
                let wScrollY = scrollerView.scrollTop();
                let bScrollH = this.$refs.scrollerView.scrollHeight;
                this.scrollY = wScrollY;
                if (scrollListener) {
                    let direction = 'static';
                    let directionreal = 'static';
                    if (this.scrollDiff - wScrollY > 50) {
                        this.scrollDiff = wScrollY;
                        direction = 'down';
                    } else if (this.scrollDiff - wScrollY < -100) {
                        this.scrollDiff = wScrollY;
                        direction = 'up';
                    }
                    if (this.scrollDiff - wScrollY > 1) {
                        this.scrollDiff = wScrollY;
                        directionreal = 'down';
                    } else if (this.scrollDiff - wScrollY < -1) {
                        this.scrollDiff = wScrollY;
                        directionreal = 'up';
                    }
                    this.$emit('on-scroll', {
                        scale: wScrollY / (bScrollH - wInnerH),     //已滚动比例
                        scrollY: wScrollY,                          //滚动的距离
                        scrollE: bScrollH - wInnerH - wScrollY,     //与底部距离
                        direction: direction,                       //滚动方向
                        directionreal: directionreal,               //滚动方向（即时）
                    });
                }
            });
        },

        recoveryScroll() {
            if (this.autoRecovery && (this.scrollY > 0 || this.autoBottom)) {
                this.$nextTick(() => {
                    if (this.autoBottom) {
                        this.autoToBottom();
                    } else {
                        this.scrollTo(this.scrollY, this.autoRecoveryAnimate);
                    }
                });
            }
        },

        openInterval() {
            this.autoToBottom();
            this.autoInterval && clearInterval(this.autoInterval);
            this.autoInterval = setInterval(this.autoToBottom, 300)
        },

        closeInterval() {
            clearInterval(this.autoInterval);
            this.autoInterval = null;
        },

        scrollTo(top, animate) {
            if (animate === false) {
                $A(this.$refs.scrollerView).stop().scrollTop(top);
            } else {
                $A(this.$refs.scrollerView).stop().animate({"scrollTop": top});
            }
        },

        autoToBottom() {
            if (this.autoBottom) {
                $A.scrollToView(this.$refs.bottom, {
                    behavior: 'instant',
                    inline: 'end',
                })
            }
        },

        scrollInfo() {
            let scrollerView = $A(this.$refs.scrollerView);
            let wInnerH = Math.round(scrollerView.innerHeight());
            let wScrollY = scrollerView.scrollTop();
            let bScrollH = this.$refs.scrollerView.scrollHeight;
            this.scrollY = wScrollY;
            return {
                scale: wScrollY / (bScrollH - wInnerH),     //已滚动比例
                scrollY: wScrollY,                          //滚动的距离
                scrollE: bScrollH - wInnerH - wScrollY,     //与底部距离
            }
        },

        querySelector(el) {
            return this.$refs.scrollerView && this.$refs.scrollerView.querySelector(el)
        },
    }
}
</script>
