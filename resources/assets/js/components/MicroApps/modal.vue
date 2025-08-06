<template>
    <div v-transfer-dom :data-transfer="true">
        <div :class="className">
            <transition :name="transitions[0]">
                <div v-if="shouldRenderInDom" v-show="open" class="micro-modal-mask" @click="onClose(false)" :style="maskStyle"></div>
            </transition>
            <transition :name="transitions[1]">
                <div v-if="shouldRenderInDom" v-show="open" class="micro-modal-content" :style="contentStyle">
                    <!-- 工具栏（移动端） -->
                    <div v-if="capsuleMenuShow" class="micro-modal-cmask"></div>
                    <div class="micro-modal-capsule" :style="capsuleStyle">
                        <div class="micro-modal-capsule-item" @click="onCapsuleMore">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2 11C3.10457 11 4 10.1046 4 9C4 7.89543 3.10457 7 2 7C0.895431 7 0 7.89543 0 9C0 10.1046 0.895431 11 2 11Z" fill="currentColor"/>
                                <path d="M9 12C10.6569 12 12 10.6569 12 9C12 7.34315 10.6569 6 9 6C7.34315 6 6 7.34315 6 9C6 10.6569 7.34315 12 9 12Z" fill="currentColor"/>
                                <path d="M16 11C17.1046 11 18 10.1046 18 9C18 7.89543 17.1046 7 16 7C14.8954 7 14 7.89543 14 9C14 10.1046 14.8954 11 16 11Z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="micro-modal-capsule-line"></div>
                        <div class="micro-modal-capsule-item" @click="onClose(false)">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 16C12.866 16 16 12.866 16 9C16 5.13401 12.866 2 9 2C5.13401 2 2 5.13401 2 9C2 12.866 5.13401 16 9 16Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 12C10.6569 12 12 10.6569 12 9C12 7.34315 10.6569 6 9 6C7.34315 6 6 7.34315 6 9C6 10.6569 7.34315 12 9 12Z" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>

                    <!-- 工具栏（桌面端） -->
                    <div class="micro-modal-tools" :class="{expanded: $A.isMainElectron}">
                        <div class="tool-close" @click="onClose(true)">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 26 26">
                                <path d="M8.28596 6.51819C7.7978 6.03003 7.00634 6.03003 6.51819 6.51819C6.03003 7.00634 6.03003 7.7978 6.51819 8.28596L11.2322 13L6.51819 17.714C6.03003 18.2022 6.03003 18.9937 6.51819 19.4818C7.00634 19.97 7.7978 19.97 8.28596 19.4818L13 14.7678L17.714 19.4818C18.2022 19.97 18.9937 19.97 19.4818 19.4818C19.97 18.9937 19.97 18.2022 19.4818 17.714L14.7678 13L19.4818 8.28596C19.97 7.7978 19.97 7.00634 19.4818 6.51819C18.9937 6.03003 18.2022 6.03003 17.714 6.51819L13 11.2322L8.28596 6.51819Z" fill="currentColor"></path>
                            </svg>
                        </div>
                        <div class="tool-fullscreen" @click="$emit('on-popout-window', options.name)">
                            <svg viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
                                <path d="M682.666667 298.666667H170.666667c-47.061333 0-85.333333 38.272-85.333334 85.333333v426.666667c0 47.061333 38.272 85.333333 85.333334 85.333333h512c47.061333 0 85.333333-38.272 85.333333-85.333333V384c0-47.061333-38.272-85.333333-85.333333-85.333333zM170.666667 810.666667v-341.333334h512V384l0.085333 426.666667H170.666667z" fill="currentColor"></path>
                                <path d="M938.666667 213.333333c0-47.061333-38.272-85.333333-85.333334-85.333333H298.666667c-47.061333 0-85.333333 38.272-85.333334 85.333333h554.709334c46.976 0 85.162667 38.186667 85.290666 85.077334L853.418667 640H853.333333v85.333333c47.061333 0 85.333333-38.272 85.333334-85.333333V341.632L938.709333 341.333333V256L938.666667 255.573333V213.333333z" fill="currentColor"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- 窗口大小调整（桌面端） -->
                    <ResizeLine
                        class="micro-modal-resize"
                        v-model="dynamicSize"
                        placement="right"
                        :min="minSize"
                        :max="0"
                        :reverse="true"
                        :beforeResize="beforeResize"
                        @on-change="onChangeResize"/>

                    <!-- 窗口内容 -->
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
import { mapState } from "vuex";

export default {
    name: 'MicroModal',
    components: {ResizeLine},
    directives: {TransferDom},
    props: {
        open: {
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
        options: {
            type: Object,
            default: () => ({})
        },
        beforeClose: Function
    },
    data() {
        return {
            dynamicSize: 0,
            zIndex: 1000,
            capsuleMenuShow: false,
        }
    },
    computed: {
        ...mapState(['windowIsMobileLayout']),
        shouldRenderInDom() {
            return this.open || !!this.options.keep_alive;
        },
        className() {
            return {
                'micro-modal': true,
                'micro-modal-hidden': !this.open,
                'no-dark-content': !this.options.auto_dark_theme,
                'transparent-mode': !!this.options.transparent,
                'capsule-mode': this.windowIsMobileLayout,
            }
        },
        transitions() {
            if (!!this.options.transparent) {
                return ['', '']
            }
            return ['micro-modal-fade', 'micro-modal-slide']
        },
        bodyStyle() {
            const styleObject = {}
            if ($A.isJson(this.options.background)) {
                styleObject.background = this.options.background
            } else if (this.options.background) {
                styleObject.backgroundColor = this.options.background;
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
        capsuleStyle() {
            const styleObject = {
                zIndex: this.zIndex + 1000
            }
            const {capsule} = this.options
            if ($A.isJson(capsule)) {
                if (capsule.visible === false) {
                    styleObject.display = 'none';
                }
                if (typeof capsule.top === 'number') {
                    styleObject.top = `${capsule.top}px`;
                }
                if (typeof capsule.right === 'number') {
                    styleObject.right = `${capsule.right}px`;
                }
            }
            return styleObject
        },
    },
    watch: {
        open: {
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

        onCapsuleMore(event) {
            const list = [];
            const {capsule} = this.options;
            if ($A.isJson(capsule) && $A.isArray(capsule.more_menus)) {
                capsule.more_menus.forEach(item => {
                    if (item.label && item.value) {
                        list.push(item);
                    }
                });
            }
            list.push(...[
                {label: this.$L('重启应用'), value: 'restart', divided: list.length > 0},
                {label: this.$L('关闭应用'), value: 'close'},
            ])
            this.$store.commit('menu/operation', {
                event,
                list,
                size: 'large',
                onVisibleChange: (visible) => {
                    this.capsuleMenuShow = visible;
                },
                onUpdate: (value) => {
                    if (value === 'close') {
                        this.onClose(true);
                    } else {
                        this.$emit('on-capsule-more', this.options.name, value);
                    }
                }
            })
        },

        onClose(auto = false) {
            if (!this.beforeClose) {
                return this.handleClose();
            }
            const before = this.beforeClose(this.options.name, auto);
            if (before && before.then) {
                before.then(() => {
                    this.handleClose();
                });
            } else {
                this.handleClose();
            }
        },

        handleClose() {
            this.$emit('on-close', this.options.name);
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
        --modal-tool-display: none;
        --modal-resize-display: none;
        --modal-content-left: 0;
        --modal-content-min-width: 100%;
        --modal-content-max-width: 100%;
        --modal-body-border-radius: 0;
        --modal-body-background-color: transparent;
    }

    // 胶囊模式
    &.capsule-mode {
        --modal-tool-display: none;
        --modal-capsule-display: flex;
    }

    // 移动端适配
    @media (max-width: 768px) {
        --modal-mask-bg: transparent;
        --modal-tool-display: none;
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
        filter: var(--modal-dark-filter, none);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--modal-mask-bg, rgba(0, 0, 0, .4));
    }

    &-cmask {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1;
        background-color: transparent;
    }

    &-capsule {
        filter: var(--modal-dark-filter, none);
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 2;
        transform: translateY(var(--status-bar-height, 0));
        display: var(--modal-capsule-display, none);
        align-items: center;
        background: rgba(255, 255, 255, 0.6);
        border: 1px solid rgba(229, 230, 235, 0.6);
        border-radius: 16px;
        transition: box-shadow 0.2s, background 0.2s;

        &:hover {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        &-line {
            width: 1px;
            height: 16px;
            background: rgba(229, 230, 235, 0.6);
        }

        &-item {
            width: 42px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;

            &:hover {
                svg {
                    color: #84C56A;
                }
            }

            svg {
                width: 20px;
                height: 20px;
                color: #303133;
                transition: color 0.2s;
                pointer-events: none;
            }
        }
    }

    &-tools {
        filter: var(--modal-dark-filter, none);
        position: absolute;
        top: var(--status-bar-height, 0);
        left: -40px;
        z-index: 2;
        min-width: 40px;
        min-height: 40px;
        display: var(--modal-tool-display, black);
        overflow: hidden;

        > div {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--modal-tool-color, #ffffff);
            cursor: pointer;

            &:hover {
                > svg {
                    transform: rotate(-90deg);
                }
            }

            > svg {
                width: 24px;
                height: 24px;
                transition: transform 0.2s ease-in-out;
            }

            + div {
                display: none;
            }

            &.tool-fullscreen {
                > svg {
                    width: 20px;
                    height: 20px;
                }
            }
        }

        &.expanded {
            height: 100%;

            &:hover {
                > div {
                    + div {
                        opacity: 1;
                    }
                }
            }

            > div {
                &:hover {
                    > svg {
                        transform: none;
                        opacity: 0.9;
                    }
                }

                + div {
                    display: flex;
                    opacity: 0;
                    transition: opacity 0.2s ease-in-out;
                }
            }
        }
    }

    &-resize {
        display: var(--modal-resize-display, 'block');
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 1;
        width: 5px;
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
            --modal-mask-bg: rgba(230, 230, 230, 0.6);
            --modal-tool-color: #000000;

            &.no-dark-content {
                --modal-dark-filter: invert(100%) hue-rotate(180deg) contrast(100%);
                --modal-body-background-color: #000000;
            }
        }
    }
}
</style>
