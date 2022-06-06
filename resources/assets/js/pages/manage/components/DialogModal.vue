<template>
    <Modal
        :value="visible"
        :mask="false"
        :mask-closable="false"
        :footer-hide="true"
        :transition-names="['', '']"
        :beforeClose="onBeforeClose"
        class-name="dialog-modal"
        fullscreen>
        <transition name="mobile-dialog">
            <DialogWrapper v-if="windowSmall && dialogId > 0" :dialogId="dialogId" :beforeBack="onBeforeClose"/>
        </transition>
    </Modal>
</template>

<style lang="scss">
body {
    .ivu-modal-wrap {
        &.dialog-modal {
            overflow: hidden;

            .ivu-modal {
                margin: 0;
                padding: 0;

                .ivu-modal-content {
                    background: transparent;

                    .ivu-modal-close {
                        display: none;
                    }

                    .ivu-modal-body {
                        padding: 0;
                        display: flex;
                        flex-direction: column;
                        overflow: hidden;
                    }
                }
            }
        }
    }
}
</style>

<script>
import {mapState} from "vuex";
import DialogWrapper from "./DialogWrapper";

export default {
    name: "DialogModal",
    components: {DialogWrapper},

    data() {
        return {
            timer: null,
            visible: false,
        }
    },

    computed: {
        ...mapState(['dialogId']),

        show() {
            return this.dialogId > 0 && this.windowSmall
        }
    },

    watch: {
        show(v) {
            this.timer && clearTimeout(this.timer);
            if (v) {
                this.visible = true;
            } else {
                this.timer = setTimeout(_ => {
                    this.visible = false;
                }, 300);
            }
        }
    },

    methods: {
        onBeforeClose() {
            return new Promise(_ => {
                this.$store.dispatch("openDialog", 0)
            })
        },
    }
}
</script>
