<template>
    <Modal
        ref="modal"
        v-model="show"
        :closable="escClosable"
        :mask-closable="maskClosable"
        :footer-hide="true"
        :transition-names="[$A.isAndroid() ? '' : `drawer-slide-${transitionName}`, '']"
        :beforeClose="beforeClose"
        fullscreen
        :class-name="modalClass">
        <slot v-if="isFullscreen" />
        <DrawerOverlayView v-else
            :placement="transitionName"
            :size="size"
            :minSize="minSize"
            :resize="resize"
            @on-close="onClose">
            <slot/>
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
        maskClosable: {
            type: Boolean,
            default: true
        },
        escClosable: {
            type: Boolean,
            default: true
        },
        placement: {
            validator (value) {
                return ['right', 'bottom'].includes(value)
            },
            default: 'bottom'
        },
        size: {
            type: [Number, String],
            default: "100%"
        },
        minSize: {
            type: Number,
            default: 300
        },
        resize: {
            type: Boolean,
            default: true
        },
        className: {
            type: String
        },
        beforeClose: Function
    },
    data() {
        return {
            show: this.value,
            isFullscreen: false
        }
    },
    watch: {
        value(v) {
            this.show = v;
        },
        show(v) {
            this.value !== v && this.$emit("input", v)
        },
        windowWidth(val){
            this.isFullscreen = val < 500 && this.placement != 'bottom'
        }
    },
    computed: {
        transitionName(){
            return this.isFullscreen ? 'bottom' : this.placement
        },
        modalClass() {
            if(this.isFullscreen){
                return "common-drawer-modal"
            }
            if (this.className) {
                return `common-drawer-overlay ${this.className} ${this.transitionName}`
            } else {
                return `common-drawer-overlay ${this.transitionName}`
            }
        }
    },
    mounted() {
        this.isFullscreen = this.windowWidth < 500  && this.placement != 'bottom'
    },
    methods: {
        onClose() {
            this.$refs.modal.close();
        }
    }
};
</script>
