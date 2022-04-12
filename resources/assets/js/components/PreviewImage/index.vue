<template>
    <Modal
        v-model="show"
        :mask="false"
        :mask-closable="false"
        :footer-hide="true"
        :transition-names="['', '']"
        fullscreen
        class-name="common-preview-image">
        <PreviewImageView v-if="list.length > 0" :initial-index="index" :url-list="list" infinite/>
    </Modal>
</template>

<style lang="scss">
body {
    .ivu-modal-wrap {
        &.common-preview-image {
            .ivu-modal {
                margin: 0;
                padding: 0;

                .ivu-modal-content {
                    background: transparent;

                    .ivu-modal-close {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 50%;
                        background-color: #606266;
                        font-size: 24px;
                        height: 40px;
                        right: 40px;
                        top: 40px;
                        width: 40px;

                        .ivu-icon-ios-close {
                            top: 0;
                            right: 0;
                            font-size: 40px;
                            color: #fff;
                        }
                    }

                    .ivu-modal-body {
                        padding: 0;
                    }
                }
            }
        }
    }
}
</style>

<script>
import PreviewImageView from "./view";

export default {
    name: 'PreviewImage',
    components: {PreviewImageView},
    props: {
        value: {
            type: Boolean,
            default: false
        },
        index: {
            type: Number,
            default: 0
        },
        list: {
            type: Array,
            default: () => {
                return [];
            }
        }
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
    }
};
</script>
