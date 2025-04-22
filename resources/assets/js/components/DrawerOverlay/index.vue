<template>
    <Modal
        ref="modal"
        v-model="show"
        :closable="escClosable"
        :mask="!isFullscreen"
        :mask-closable="maskClosable"
        :footer-hide="true"
        :transition-names="[$A.isAndroid() ? '' : `drawer-slide-${transitionName}`, '']"
        :beforeClose="beforeClose"
        fullscreen
        :class-name="className">
        <div v-if="isFullscreen" class="overlay-body">
            <slot/>
        </div>
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
        drawerClass: {
            type: String
        },
        modalClass: {
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
        className() {
            const array = []
            if (this.isFullscreen) {
                array.push("common-drawer-modal")
                if (this.modalClass) {
                    array.push(this.modalClass)
                }
            } else {
                array.push("common-drawer-overlay")
                if (this.drawerClass) {
                    array.push(this.drawerClass)
                }
                array.push(this.transitionName)
            }
            return array.join(" ");
        },
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
