<template>
    <div v-transfer-dom :data-transfer="true">
        <div :class="className">
            <transition :name="transitions[0]">
                <div v-if="value" class="micro-modal-mask" @click="onClose" :style="maskStyle"></div>
            </transition>
            <transition :name="transitions[1]">
                <div v-if="value" class="micro-modal-content" :style="contentStyle">
                    <div class="micro-modal-close" @click="onClose">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26 26" fill="none" role="img" class="icon fill-current">
                            <path d="M8.28596 6.51819C7.7978 6.03003 7.00634 6.03003 6.51819 6.51819C6.03003 7.00634 6.03003 7.7978 6.51819 8.28596L11.2322 13L6.51819 17.714C6.03003 18.2022 6.03003 18.9937 6.51819 19.4818C7.00634 19.97 7.7978 19.97 8.28596 19.4818L13 14.7678L17.714 19.4818C18.2022 19.97 18.9937 19.97 19.4818 19.4818C19.97 18.9937 19.97 18.2022 19.4818 17.714L14.7678 13L19.4818 8.28596C19.97 7.7978 19.97 7.00634 19.4818 6.51819C18.9937 6.03003 18.2022 6.03003 17.714 6.51819L13 11.2322L8.28596 6.51819Z" fill="currentColor"></path>
                        </svg>
                    </div>
                    <ResizeLine
                        class="micro-modal-resize"
                        v-model="dynamicSize"
                        placement="right"
                        :min="minSize"
                        :max="0"
                        :reverse="true"
                        :beforeResize="beforeResize"
                        @on-change="onChangeResize"/>
                    <div ref="body" class="micro-modal-body" :style="bodyStyle">
                        <slot></slot>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>

<script>
import TransferDom from "../../directives/transfer-dom";
import ResizeLine from "../ResizeLine.vue";

export default {
    name: 'MicroModal',
    components: {ResizeLine},
    directives: {TransferDom},
    props: {
        value: {
            type: Boolean,
            default: false
        },
        size: {
            type: Number,
            default: 300
        },
        minSize: {
            type: Number,
            default: 300
        },
        background: {
            default: null
        },
        transparent: {
            type: Boolean,
            default: false
        },
        autoDarkTheme: {
            type: Boolean,
            default: true
        },
        beforeClose: Function
    },
    data() {
        return {
            dynamicSize: 0,
            zIndex: 1000
        }
    },
    computed: {
        className({value, transparent, autoDarkTheme}) {
            return {
                'micro-modal': true,
                'micro-modal-hidden': !value,
                'no-dark-content': !autoDarkTheme,
                'transparent-mode': transparent
            }
        },
        transitions({transparent}) {
            if (transparent) {
                return ['', '']
            }
            return ['micro-modal-fade', 'micro-modal-slide']
        },
        bodyStyle() {
            const styleObject = {}
            if ($A.isJson(this.background)) {
                styleObject.background = this.background
            } else if (this.background) {
                styleObject.backgroundColor = this.background;
            }
            return styleObject;
        },
        maskStyle({zIndex}) {
            return {zIndex}
        },
        contentStyle({dynamicSize, zIndex}) {
            const width = dynamicSize <= 100 ? `${dynamicSize}%` : `${dynamicSize}px`
            return {width, zIndex}
        },
    },
    watch: {
        value: {
            handler(val) {
                if (val) {
                    this.zIndex = typeof window.modalTransferIndex === 'number' ? window.modalTransferIndex++ : 1000;
                }
            },
            immediate: true
        },
        size: {
            handler(val) {
                this.dynamicSize = parseInt(val);
            },
            immediate: true
        }
    },
    methods: {
        beforeResize() {
            return new Promise(resolve => {
                if (this.dynamicSize <= 100) {
                    this.updateSize();
                }
                resolve()
            })
        },

        onChangeResize({event}) {
            if (event === 'up') {
                this.updateSize();
            }
        },

        updateSize() {
            if (this.$refs.body) {
                this.dynamicSize = this.$refs.body.clientWidth;
            }
        },

        onClose() {
            if (!this.beforeClose) {
                return this.handleClose();
            }
            const before = this.beforeClose();
            if (before && before.then) {
                before.then(() => {
                    this.handleClose();
                });
            } else {
                this.handleClose();
            }
        },

        handleClose() {
            this.$emit('input', false)
        }
    }
}
</script>

<style lang="scss">
.micro-modal {
    width: 100vw;
    height: 100vh;
    will-change: auto;

    // 透明模式
    &.transparent-mode {
        --modal-mask-bg: transparent;
        --modal-close-display: none;
        --modal-resize-display: none;
        --modal-content-left: 0;
        --modal-content-min-width: 100%;
        --modal-content-max-width: 100%;
        --modal-body-border-radius: 0;
        --modal-body-background-color: transparent;
    }

    // 小屏幕适配
    @media (max-width: 500px) {
        --modal-mask-bg: transparent;
        --modal-close-display: none;
        --modal-resize-display: none;
        --modal-content-left: 0;
        --modal-content-min-width: 100%;
        --modal-content-max-width: 100%;
        --modal-body-border-radius: 0;
        --modal-slide-transform: translate(0, 15%) scale(0.98);
    }

    &-hidden {
        pointer-events: none;
        animation: fade-hide-zindex 0s forwards;
        animation-delay: 300ms;

        @keyframes fade-hide-zindex {
            to {
                width: 0;
                height: 0;
                overflow: hidden;
                visibility: hidden;
            }
        }
    }

    &-mask {
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        background-color: var(--modal-mask-bg, rgba(0, 0, 0, .4));
    }

    &-close {
        position: absolute;
        top: var(--status-bar-height, 0);
        left: -40px;
        z-index: 1;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: var(--modal-close-display, flex);
        align-items: center;
        justify-content: center;
        color: var(--modal-close-color, #ffffff);
        cursor: pointer;

        > svg {
            width: 24px;
            height: 24px;
            transition: transform 0.3s ease-in-out;
        }

        &:hover {
            > svg {
                transform: rotate(-90deg);
            }
        }
    }

    &-resize {
        display: var(--modal-resize-display, 'block');
        position: absolute;
        top: 0;
        bottom: 0;
        left: -3px;
        z-index: 1;
        width: 3px;
    }

    &-content {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: var(--modal-content-left, auto);
        display: flex;
        flex-direction: column;
        height: 100%;
        min-width: var(--modal-content-min-width, auto);
        max-width: var(--modal-content-max-width, calc(100% - 40px));
    }

    &-body {
        flex: 1;
        height: 0;
        overflow: hidden;
        border-radius: var(--modal-body-border-radius, 18px 0 0 18px);
        background-color: var(--modal-body-background-color, #ffffff);
        position: relative;
    }

    &-fade {
        &-enter-active {
            transition: all .2s ease;
        }

        &-leave-active {
            transition: all .2s ease;
        }

        &-enter,
        &-leave-to {
            opacity: 0;
        }
    }

    &-slide {
        &-enter-active {
            transition: all .2s ease;
        }

        &-leave-active {
            transition: all .2s ease;
        }

        &-enter,
        &-leave-to {
            transform: var(--modal-slide-transform, translate(15%, 0) scale(0.98));
            opacity: 0;
        }
    }
}

// 深色模式适配
body.dark-mode-reverse {
    .micro-modal {
        &:not(.transparent-mode) {
            &:not(.no-dark-content) {
                --modal-mask-bg: rgba(230, 230, 230, 0.6);
                --modal-close-color: #323232;
            }
            &.no-dark-content {
                --modal-mask-bg: rgba(20, 20, 20, 0.6);
                --modal-body-background-color: #000000;
            }
        }
    }
}
</style>
