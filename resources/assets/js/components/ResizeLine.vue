<template>
    <div class="component-resize-line" :class="[resizing ? 'resizing' : '', placement]" @mousedown.left.stop.prevent="resizeDown"></div>
</template>
<script>
    export default {
        name: 'ResizeLine',

        props: {
            value: {
            },
            min: {
                type: Number,
                default: 100,
            },
            max: {
                type: Number,
                default: 600,
            },
            placement: {
                validator (value) {
                    return ['right', 'bottom'].includes(value)
                },
                default: 'bottom'
            },
            reverse: {
                type: Boolean,
                default: false
            },
            beforeResize: Function
        },

        data() {
            return {
                resizing: false,

                mouseX: 0,
                mouseY: 0,

                offset: {},

                tmpSize: undefined,
            }
        },

        mounted() {
            document.documentElement.addEventListener('mousemove', this.handleMove, true);
            document.documentElement.addEventListener('mouseup', this.handleUp, true);
        },

        methods: {
            resizeDown(e) {
                if (!this.beforeResize) {
                    this.handleDown(e)
                    return
                }
                const before = this.beforeResize();
                if (before && before.then) {
                    before.then(_ => {
                        this.handleDown(e)
                    });
                } else {
                    this.handleDown(e);
                }
            },

            handleDown(e) {
                this.mouseX = e.pageX || e.clientX + document.documentElement.scrollLeft;
                this.mouseY = e.pageY || e.clientY + document.documentElement.scrollTop;
                this.offset = {
                    left: e.target.offsetLeft,
                    top: e.target.offsetTop,
                };
                this.resizing = true;
                if (typeof this.value === 'number') {
                    this.tmpSize = this.value;
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
                if (typeof this.tmpSize === 'number') {
                    let value;
                    if (this.placement == 'bottom') {
                        value = this.reverse ? (this.tmpSize - diffY) : (this.tmpSize + diffY);
                    } else {
                        value = this.reverse ? (this.tmpSize - diffX) : (this.tmpSize + diffX);
                    }
                    if (this.min > 0) {
                        value = Math.max(this.min, value);
                    }
                    if (this.max > 0) {
                        value = Math.min(this.max, value);
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
                if (!this.resizing) {
                    return;
                }
                this.resizing = false;
                this.tmpSize = undefined;
                this.$emit('on-change', {
                    event: 'up',
                });
            },
        },
    }
</script>
