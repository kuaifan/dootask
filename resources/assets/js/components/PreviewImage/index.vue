<template>
    <Modal
        v-model="show"
        :mask="false"
        :mask-closable="false"
        :footer-hide="true"
        :transition-names="['', '']"
        :class-name="viewMode === 'desktop' ? 'common-preview-image-view' : 'common-preview-image-swipe'"
        fullscreen>
        <template v-if="list.length > 0">
            <PreviewImageView v-if="viewMode === 'desktop'" :initial-index="index" :url-list="list" infinite/>
            <PreviewImageSwipe v-if="viewMode === 'mobile'" :initial-index="index" :url-list="list" @on-destroy="show=false"/>
        </template>
    </Modal>
</template>

<style lang="scss">
body {
    .ivu-modal-wrap {
        &.common-preview-image-view {
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

                        @media (max-width: 640px) {
                            right: 24px;
                            top: 24px;
                        }

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
        &.common-preview-image-swipe {
            display: none;
        }
    }
}
</style>

<script>
const PreviewImageView = () => import('./components/view');
const PreviewImageSwipe = () => import('./components/swipe');

export default {
    name: 'PreviewImage',
    components: {PreviewImageSwipe, PreviewImageView},
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
        },
        mode: {
            type: String,
            default: null
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
    },
    computed: {
        viewMode() {
            if (this.mode) {
                return this.mode
            }
            return this.windowTouch ? 'mobile' : 'desktop'
        }
    }
};
</script>
