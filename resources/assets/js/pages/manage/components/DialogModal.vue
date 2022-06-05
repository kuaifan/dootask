<template>
    <Modal
        v-model="show"
        :mask="false"
        :mask-closable="false"
        :footer-hide="true"
        :transition-names="['mobile-dialog', '']"
        :beforeClose="onBeforeClose"
        class-name="dialog-modal"
        fullscreen>
        <transition name="mobile-dialog">
            <DialogWrapper v-if="dialogModalId > 0" :dialogId="dialogModalId" :beforeBack="onBeforeClose"/>
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
            show: false
        }
    },

    computed: {
        ...mapState(['dialogModalId'])
    },

    watch: {
        dialogModalId: {
            handler(id) {
                this.show = id > 0;
            },
            immediate: true
        }
    },

    methods: {
        onBeforeClose() {
            return new Promise(_ => {
                this.$store.state.dialogModalId = 0;
            })
        },
    }
}
</script>
