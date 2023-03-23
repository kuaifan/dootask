<template>
    <div ref="floatDrag" :style="dragStyle" @mousedown.stop.prevent="mouseDown">
        <slot></slot>
    </div>
</template>

<script>
export default {
    name: "DragBallComponent",
    props: {
        id: {
            type: String,
            default: ""
        },
        distanceRight: {
            type: Number,
            default: 0
        },
        distanceBottom: {
            type: Number,
            default: 100
        },
        isScrollHidden: {
            type: Boolean,
            default: false
        },
        isCanDraggable: {
            type: Boolean,
            default: true
        },
        zIndex: {
            type: Number,
            default: 50
        }
    },

    data() {
        return {
            clientWidth: null,
            clientHeight: null,
            top: 0,
            left: 0,
            timer: null,
            currentTop: 0,
            isMoving: false,
            record: {}
        };
    },

    created() {
        this.clientWidth = document.documentElement.clientWidth;
        this.clientHeight = document.documentElement.clientHeight;
    },

    mounted() {
        if (this.id) {
            if (!$A.isJson(window._DragBallComponent)) {
                window._DragBallComponent = {};
            }
        }
        if (this.isCanDraggable) {
            this.$nextTick(() => {
                if (this.id && $A.isJson(window._DragBallComponent[this.id])) {
                    this.left = window._DragBallComponent[this.id].left
                    this.top = window._DragBallComponent[this.id].top
                } else {
                    this.left = this.clientWidth - this.floatDrag.offsetWidth - this.distanceRight;
                    this.top = this.clientHeight - this.floatDrag.offsetHeight - this.distanceBottom;
                }
                this.initDraggable();
            });
        }
        this.isScrollHidden && window.addEventListener("scroll", this.handleScroll);
        window.addEventListener("resize", this.handleResize);
    },

    beforeDestroy() {
        if (this.id) {
            window._DragBallComponent[this.id] = {
                left: this.left,
                top: this.top
            };
        }
        window.removeEventListener("scroll", this.handleScroll);
        window.removeEventListener("resize", this.handleResize);
    },

    computed: {
        dragStyle() {
            return {
                left: this.left + 'px',
                top: this.top + 'px',
                zIndex: this.zIndex,
                position: 'fixed',
            }
        },

        floatDrag () {
            return this.$refs.floatDrag
        }
    },

    methods: {
        /**
         * 设置滚动监听（设置滚动时隐藏悬浮按钮，停止时显示）
         */
        handleScroll() {
            this.timer && clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.handleScrollEnd();
            }, 200);
            this.currentTop = document.documentElement.scrollTop || document.body.scrollTop;
            if (this.left > this.clientWidth / 2) {
                this.left = this.clientWidth + this.floatDrag.offsetWidth;
            } else {
                this.left = -this.floatDrag.offsetWidth;
            }
        },

        /**
         * 滚动结束
         */
        handleScrollEnd() {
            let scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
            if (scrollTop === this.currentTop) {
                if (this.left > this.clientWidth / 2) {
                    this.left = this.clientWidth - this.floatDrag.offsetWidth;
                } else {
                    this.left = 0;
                }
                clearTimeout(this.timer);
            }
        },

        /**
         * 窗口resize监听
         */
        handleResize() {
            this.clientWidth = document.documentElement.clientWidth;
            this.clientHeight = document.documentElement.clientHeight;
            this.$nextTick(this.checkDraggablePosition);
        },

        /**
         * 初始化draggable
         */
        initDraggable() {
            this.floatDrag.addEventListener("touchstart", this.toucheStart);
            this.floatDrag.addEventListener("touchmove", this.touchMove);
            this.floatDrag.addEventListener("touchend", this.touchEnd);
        },

        mouseDown(e) {
            this.record = {
                time: new Date().getTime(),
                top: this.floatDrag.offsetTop,
                left: this.floatDrag.offsetLeft,
                x: e.clientX - this.floatDrag.offsetLeft,
                y: e.clientY - this.floatDrag.offsetTop,
            }
            this.floatDrag.style.transition = "none";
            this.canClick = false;
            //
            document.onmousemove = (e) => {
                let left = e.clientX - this.record.x;
                let top = e.clientY - this.record.y;
                if (left < 0) {
                    left = 0
                } else if (left > (window.innerWidth - this.floatDrag.offsetWidth)) {
                    left = window.innerWidth - this.floatDrag.offsetWidth
                }
                if (top < 0) {
                    top = 0
                } else if (top > (window.innerHeight - this.floatDrag.offsetHeight)) {
                    top = window.innerHeight - this.floatDrag.offsetHeight
                }
                this.left = left;
                this.top = top;
            };
            document.onmouseup = () => {
                document.onmousemove = null;
                document.onmouseup = null;
                this.checkDraggablePosition();
                this.floatDrag.style.transition = "all 0.3s";
                // 点击事件
                if ((Math.abs(this.record.top - this.floatDrag.offsetTop) < 5 && Math.abs(this.record.left - this.floatDrag.offsetLeft) < 5) || new Date().getTime() - this.record.time < 200) {
                    this.$emit("on-click")
                }
            }
        },

        toucheStart() {
            this.canClick = false;
            this.floatDrag.style.transition = "none";
        },

        touchMove(e) {
            this.canClick = true;
            if (e.targetTouches.length === 1) {
                // 单指拖动
                let touch = event.targetTouches[0];
                let left = touch.clientX - this.floatDrag.offsetWidth / 2;
                let top = touch.clientY - this.floatDrag.offsetHeight / 2;
                if (left < 0) {
                    left = 0
                } else if (left > (window.innerWidth - this.floatDrag.offsetWidth)) {
                    left = window.innerWidth - this.floatDrag.offsetWidth
                }
                if (top < 0) {
                    top = 0
                } else if (top > (window.innerHeight - this.floatDrag.offsetHeight)) {
                    top = window.innerHeight - this.floatDrag.offsetHeight
                }
                this.left = left;
                this.top = top;
            }
        },

        touchEnd() {
            if (!this.canClick) return;
            this.floatDrag.style.transition = "all 0.3s";
            this.checkDraggablePosition();
        },

        /**
         * 判断元素显示位置（在窗口改变和move end时调用）
         */
        checkDraggablePosition() {
            if (this.left + this.floatDrag.offsetWidth / 2 >= this.clientWidth / 2) {
                this.left = this.clientWidth - this.floatDrag.offsetWidth;
            } else {
                this.left = 0;
            }
            if (this.top < 0) {
                this.top = 0;
            }
            if (this.top + this.floatDrag.offsetHeight >= this.clientHeight) {
                this.top = this.clientHeight - this.floatDrag.offsetHeight;
            }
        }
    },
};
</script>
