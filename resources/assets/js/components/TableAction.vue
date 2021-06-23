<template>
    <div class="td-action" :style="tdStyle">
        <div ref="action" @mouseenter="handleIn" class="td-action-container" v-resize="onResize"><slot></slot></div>
    </div>
</template>

<script>
import VueResizeObserver from "vue-resize-observer";
import Vue from 'vue'
Vue.use(VueResizeObserver);

    export default {
        name: 'TableAction',
        props: {
            column: {
                type: Object,
                default: () => {
                    return {};
                }
            },
            minWidth: {
                type: Number,
                default: 80
            },
            align: {
                type: String,
                default: ''
            },
        },
        data() {
            return {
                width: 0,
                height: 0,
            }
        },
        mounted() {
            this.onUpdate();
        },
        activated() {
            this.onUpdate();
        },
        beforeUpdate() {
            this.onUpdate();
        },
        computed: {
            tdStyle() {
                const style = {};
                const {align} = this;
                if (['left', 'center', 'right'].includes(align.toLowerCase())) {
                    style.textAlign = align;
                }
                return style;
            }
        },
        methods: {
            handleIn() {
                if (this.$refs.action.offsetWidth != this.width) {
                    this.onUpdate();
                }
            },
            onUpdate() {
                this.onResize({
                    width: this.$refs.action.offsetWidth,
                    height: this.$refs.action.offsetHeight,
                })
            },
            onResize({ width, height }) {
                $A(".ivu-table-column-" + this.column.__id).each((index, el) => {
                    let action = $A(el).find(".td-action-container")
                    if (action.length > 0) {
                        width = Math.max(width, action[0].offsetWidth)
                        height = Math.max(height, action[0].offsetHeight)
                    }
                });
                this.width = width;
                this.height = height;
                let newWidth = Math.max(this.minWidth, this.width + 26);
                if (this.column.minWidth) {
                    newWidth = Math.max(this.column.minWidth, newWidth);
                }
                if (this.column.maxWidth) {
                    newWidth = Math.min(this.column.maxWidth, newWidth);
                }
                newWidth != this.column.width && this.$set(this.column, 'width', newWidth)
            }
        }
    }
</script>
