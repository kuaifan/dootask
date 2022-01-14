<template>
    <div class="component-resize-line" :class="{resizing}" @mousedown.left.stop.prevent="resizeDown"></div>
</template>
<style lang="scss" scoped>
    .component-resize-line {
        cursor: col-resize;
        @media (max-width: 768px) {
            display: none;
        }
        &.resizing {
            &:after {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 99999;
                cursor: col-resize;
            }
        }
    }
</style>
<script>
    export default {
        name: 'ResizeLine',

        props: {
            value: {
            },
            minWidth: {
                type: Number,
                default: 100,
            },
            maxWidth: {
                type: Number,
                default: 600,
            },
            reverse: {
                type: Boolean,
                default: false
            }
        },

        data() {
            return {
                resizing: false,

                mouseX: 0,
                mouseY: 0,

                offset: {},

                tmpWidth: undefined,
            }
        },

        mounted() {
            document.documentElement.addEventListener('mousemove', this.handleMove, true);
            document.documentElement.addEventListener('mouseup', this.handleUp, true);
        },

        methods: {
            resizeDown(e) {
                this.mouseX = e.pageX || e.clientX + document.documentElement.scrollLeft;
                this.mouseY = e.pageY || e.clientY + document.documentElement.scrollTop;
                this.offset = {
                    left: e.target.offsetLeft,
                    top: e.target.offsetTop,
                };
                this.resizing = true;
                if (typeof this.value === 'number') {
                    this.tmpWidth = this.value;
                }
                this.$emit('on-change', {
                    event: 'down',
                });
            },
            handleMove(e) {
                if (!this.resizing) {
                    return;
                }
                let diffX = (e.pageX || e.clientX + document.documentElement.scrollLeft) - this.mouseX;
                let diffY = (e.pageY || e.clientY + document.documentElement.scrollTop) - this.mouseY;
                if (typeof this.tmpWidth === 'number') {
                    let value = this.reverse ? (this.tmpWidth - diffX) : (this.tmpWidth + diffX);
                    if (this.minWidth > 0) {
                        value = Math.max(this.minWidth, value);
                    }
                    if (this.maxWidth > 0) {
                        value = Math.min(this.maxWidth, value);
                    }
                    this.$emit("input", value);
                }
                this.$emit('on-change', {
                    event: 'move',
                    diff: {
                        x: diffX,
                        y: diffY,
                    },
                    offset: this.offset,
                });
            },
            handleUp() {
                this.resizing = false;
                this.tmpWidth = undefined;
                this.$emit('on-change', {
                    event: 'up',
                });
            },
        },
    }
</script>
