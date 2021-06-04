<template>
    <div ref="scrollerView" class="app-scroller" :class="[static ? 'app-scroller-static' : '']">
        <slot/>
    </div>
</template>

<style lang="scss" scoped>
.app-scroller {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    overflow-x: hidden;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
}

.app-scroller-static {
    position: static;
    flex: 1;
}
</style>
<script>
export default {
    name: 'ScrollerY',
    props: {
        static: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            scrollY: 0,
            scrollDiff: 0,
            scrollInfo: {},
        }
    },
    mounted() {
        this.$nextTick(() => {
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
        });
    },
    activated() {
        if (this.scrollY > 0) {
            this.$nextTick(() => {
                this.scrollTo(this.scrollY);
            });
        }
    },
    methods: {
        scrollTo(top, animate) {
            if (animate === false) {
                $A(this.$refs.scrollerView).stop().scrollTop(top);
            } else {
                $A(this.$refs.scrollerView).stop().animate({"scrollTop": top});
            }
        },
        scrollToBottom(animate) {
            this.scrollTo(this.$refs.scrollerView.scrollHeight, animate);
        },
        getScrollInfo() {
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
        }
    }
}
</script>
