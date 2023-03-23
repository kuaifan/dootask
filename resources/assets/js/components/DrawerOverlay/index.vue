<template>
    <Modal
        ref="modal"
        v-model="show"
        :closable="escClosable"
        :mask-closable="maskClosable"
        :footer-hide="true"
        :transition-names="[$A.isAndroid() ? '' : `drawer-slide-${placement}`, '']"
        :beforeClose="beforeClose"
        fullscreen
        :class-name="modalClass">
        <DrawerOverlayView
            :placement="placement"
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
        }
    },
    watch: {
        value(v) {
            this.show = v;
        },
        show(v) {
            this.value !== v && this.$emit("input", v)
        }
    },
    computed: {
        modalClass() {
            if (this.className) {
                return `common-drawer-overlay ${this.className} ${this.placement}`
            } else {
                return `common-drawer-overlay ${this.placement}`
            }
        }
    },
    methods: {
        onClose() {
            this.$refs.modal.close();
        }
    }
};
</script>
