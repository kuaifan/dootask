<template>
    <Modal
        :value="previewImageList.length > 0"
        :mask="false"
        :mask-closable="false"
        :footer-hide="true"
        :transition-names="['', '']"
        fullscreen
        @on-visible-change="visibleChange"
        class-name="common-preview-image">
        <PreviewImageView v-if="previewImageList.length > 0" :initial-index="previewImageIndex" :url-list="previewImageList" infinite/>
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
import {mapState} from "vuex";

export default {
    name: 'PreviewImage',
    components: {PreviewImageView},
    computed: {
        ...mapState([
            'previewImageIndex',
            'previewImageList',
        ]),
    },
    methods: {
        visibleChange(val) {
            if (!val) {
                this.close()
            }
        },
        close() {
            this.$store.state.previewImageIndex = 0;
            this.$store.state.previewImageList = [];
        },
    }
};
</script>
