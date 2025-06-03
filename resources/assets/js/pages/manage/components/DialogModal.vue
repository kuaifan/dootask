<template>
    <Modal
        ref="modal"
        :value="show"
        :styles="modalStyles"
        :mask-closable="false"
        :footer-hide="true"
        :mask="!windowPortrait"
        :fullscreen="windowPortrait"
        :transition-names="transitionNames"
        :class-name="className"
        :beforeClose="onBeforeClose">
        <DialogWrapper
            v-if="show"
            ref="dialogWrapper"
            :dialogId="dialogId"
            :style="dialogStyles"
            :beforeBack="onBeforeClose"
            location="modal"/>
    </Modal>
</template>

<script>
import {mapState} from "vuex";
import DialogWrapper from "./DialogWrapper";
import emitter from "../../../store/events";

export default {
    name: "DialogModal",
    components: {DialogWrapper},

    data() {
        return {
            show: false,
            timer: null,
            closIng: false,
        }
    },

    mounted() {
        emitter.on('handleMoveTop', this.handleMoveTop);
    },

    beforeDestroy() {
        emitter.off('handleMoveTop', this.handleMoveTop);
    },

    computed: {
        ...mapState(['dialogId', 'windowOrientation']),

        modalStyles() {
            if (this.windowPortrait) {
                return {}
            }
            return {
                width: '90%',
                maxWidth: '720px'
            }
        },

        dialogStyles() {
            if (this.windowPortrait) {
                return {}
            }
            const height = Math.min(1100, this.windowHeight)
            const factor = height > 900 ? 200 : 70;
            return {
                height: '600px',
                maxHeight: (height - factor - 30) + 'px'
            }
        },

        transitionNames() {
            return this.windowPortrait ? ['mobile-dialog', ''] : ['ease', 'fade']
        },

        className() {
            const cls = ['dialog-modal', `dialog-${this.windowOrientation}`]
            if (this.closIng > 0) {
                cls.push('dialog-closing')
            }
            return cls.join(' ')
        }
    },

    watch: {
        dialogId() {
            this.handleShow()
        },

        windowPortrait() {
            this.handleShow()
        },

        show(v) {
            this.$store.state.dialogModalShow = v;
            $A.eeuiAppSetScrollDisabled(v && this.windowPortrait)
        }
    },

    methods: {
        onBeforeClose() {
            if (this.$refs.dialogWrapper) {
                this.$refs.dialogWrapper.operateVisible = false
            }
            return new Promise(async _ => {
                this.closIng++
                await this.$store.dispatch("openDialog", 0)
                await new Promise(resolve => setTimeout(resolve, 300))
                this.closIng--
            })
        },
        handleShow() {
            this.show = this.dialogId > 0 && (this.windowPortrait || this.routeName !== 'manage-messenger')
        },
        handleMoveTop(type) {
            type === 'dialogModal' && this.$refs.modal?.handleMoveTop();
        }
    }
}
</script>
