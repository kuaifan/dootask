<template>
    <Modal
        ref="modal"
        v-model="show"
        :closable="escClosable"
        :mask="finalMask"
        :mask-closable="maskClosable"
        :footer-hide="true"
        :fullscreen="true"
        :class-name="finalClassName"
        :transition-names="finalTransitionNames"
        :before-close="beforeClose">
        <DrawerOverlayView
            :placement="finalPlacement"
            :size="finalSize"
            :minSize="finalMinSize"
            :resize="finalResize"
            @on-close="close">
            <template v-if="$slots.title" #title>
                <slot name="title"></slot>
            </template>
            <template v-if="$slots.more" #more>
                <slot name="more"></slot>
            </template>
            <template #default>
                <slot></slot>
            </template>
        </DrawerOverlayView>
    </Modal>
</template>

<script>
import DrawerOverlayView from "./view";

export default {
    name: 'DrawerOverlay',
    components: {DrawerOverlayView},
    props: {
        value: {
            type: Boolean,
            default: false
        },
        // 是否显示遮罩，留空自动判断
        mask: {
            default: null
        },
        // 允许点击遮罩关闭
        maskClosable: {
            type: Boolean,
            default: true
        },
        // 允许按下 ESC 键关闭
        escClosable: {
            type: Boolean,
            default: true
        },
        // 是否全屏，留空自动判断屏幕宽度小于 768px 时自动变为全屏模式
        fullscreen: {
            default: null
        },
        // 抽屉放置位置，可选 'right' 或 'bottom'
        // 在全屏模式下无效，强制为 'bottom'
        placement: {
            validator(value) {
                return ['right', 'bottom'].includes(value)
            },
            default: 'bottom'
        },
        // 抽屉的大小，可以是百分比或像素值，默认 100%
        // 在全屏模式下无效
        size: {
            type: [Number, String],
            default: "100%"
        },
        // 抽屉的最小大小，单位为像素，默认 300px
        // 在全屏模式下无效
        minSize: {
            type: Number,
            default: 300
        },
        // 允许调整大小，默认为 true
        // 在全屏模式下无效
        resize: {
            type: Boolean,
            default: true
        },
        // 自定义类名
        className: {
            type: String
        },
        // 拦截关闭事件的函数
        beforeClose: Function
    },
    data() {
        return {
            show: this.value,
        }
    },
    watch: {
        value(v) {
            this.show = v;
        },
        show(v) {
            this.value !== v && this.$emit("input", v)
        },
    },
    computed: {
        finalFullscreen() {
            if (typeof this.fullscreen === 'boolean') {
                return this.fullscreen
            }
            return this.windowWidth < 768
        },
        finalMask() {
            if (typeof this.mask === 'boolean') {
                return this.mask
            }
            return !this.finalFullscreen
        },
        finalClassName() {
            const array = [
                "common-drawer",
                `drawer-${this.finalPlacement}`
            ];
            if (this.finalFullscreen) {
                array.push("drawer-fullscreen")
            }
            if (this.className) {
                array.push(this.className)
            }
            return array.join(" ");
        },
        finalTransitionNames() {
            return [`drawer-animation-${this.finalPlacement}`, 'drawer-animation-fade']
        },
        finalPlacement() {
            return this.finalFullscreen ? 'bottom' : this.placement
        },
        finalSize() {
            if (this.finalFullscreen) {
                return "100%"
            }
            return this.size
        },
        finalMinSize() {
            if (this.finalFullscreen) {
                return 0
            }
            return this.minSize
        },
        finalResize() {
            if (this.finalFullscreen) {
                return false
            }
            return this.resize
        }
    },
    methods: {
        close() {
            this.$refs.modal.close();
        }
    }
};
</script>
