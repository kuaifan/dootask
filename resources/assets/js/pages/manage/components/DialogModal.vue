<template>
    <Modal
        :value="show"
        :mask="false"
        :mask-closable="false"
        :footer-hide="true"
        :transition-names="['mobile-dialog', '']"
        :beforeClose="onBeforeClose"
        class-name="dialog-modal"
        fullscreen>
        <DialogWrapper v-if="windowSmall && dialogId > 0" :dialogId="dialogId" :beforeBack="onBeforeClose"/>
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

    computed: {
        ...mapState(['dialogId']),

        show() {
            return this.dialogId > 0 && this.windowSmall
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
