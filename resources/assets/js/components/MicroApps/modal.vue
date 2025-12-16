<template>
    <div v-transfer-dom :data-transfer="true">
        <div :class="className" :style="wrapStyle">
            <transition :name="transitions[0]">
                <div v-if="shouldRenderInDom" v-show="open" class="micro-modal-mask" :style="maskStyle"></div>
            </transition>
            <transition :name="transitions[1]">
                <div v-if="shouldRenderInDom" v-show="open" class="micro-modal-content" :style="contentStyle">
                    <!-- 胶囊工具栏 -->
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
                        <div class="micro-modal-capsule-item" @click="attemptClose">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 16C12.866 16 16 12.866 16 9C16 5.13401 12.866 2 9 2C5.13401 2 2 5.13401 2 9C2 12.866 5.13401 16 9 16Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M9 12C10.6569 12 12 10.6569 12 9C12 7.34315 10.6569 6 9 6C7.34315 6 6 7.34315 6 9C6 10.6569 7.34315 12 9 12Z" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>

                    <!-- 窗口大小调整 -->
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
import { mapState } from 'vuex';
import TransferDom from "../../directives/transfer-dom";
import ResizeLine from "../ResizeLine.vue";
import { colorReverse } from "../../utils/color";

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
        windowType: {
            type: String,
            default: 'embed',
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
        ...mapState(['themeName']),
        shouldRenderInDom() {
            return this.open || !!this.options.keep_alive;
        },
        className() {
            return {
                'micro-modal': true,
                'micro-modal-hidden': !this.open,
                'no-dark-content': !this.options.auto_dark_theme,
                'transparent-mode': !!this.options.transparent,
                [`${this.windowType}-window`]: true,
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
            if (this.options.background) {
                const colors = `${this.options.background}|`.split('|');
                styleObject.background = (this.themeName === 'dark' ? colorReverse(colors[1]) : null) || colors[0];
            }
            return styleObject;
        },
        wrapStyle({zIndex}) {
            return {zIndex}
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
                if (!this.getCapsuleVisible(capsule.visible)) {
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
            const customMenu = [];
            const {capsule} = this.options;
            if ($A.isJson(capsule) && $A.isArray(capsule.more_menus)) {
                capsule.more_menus.forEach(item => {
                    if (item.label && item.value) {
                        customMenu.push(item);
                    }
                });
            }
            const systemMenu = [
                {label: this.$L('重启应用'), value: 'restart'},
                {label: this.$L('关闭应用'), value: 'destroy'},
            ];
            if ($A.isMainElectron) {
                systemMenu.unshift({label: this.$L('新窗口打开'), value: 'popout'})
            }
            if (customMenu.length > 0) {
                systemMenu[0].divided = true;
            }
            this.$store.commit('menu/operation', {
                event,
                list: [...customMenu, ...systemMenu],
                size: 'large',
                onVisibleChange: (visible) => {
                    this.capsuleMenuShow = visible;
                },
                onUpdate: (value) => {
                    this.$emit('on-capsule-more', this.options.name, value);
                }
            })
        },

        attemptClose() {
            if (!this.beforeClose) {
                return this.handleClose();
            }
            const before = this.beforeClose(this.options.name);
            if (before && before.then) {
                before.then(() => {
                    this.handleClose();
                });
            } else {
                this.handleClose();
            }
        },

        handleClose() {
            this.$emit('on-confirm-close', this.options.name);
        },

        getCapsuleVisible(visible) {
            if (typeof visible === 'boolean') {
                return visible
            }
            if ($A.isJson(visible)) {
                const defaultVisible = typeof visible.default === 'boolean' ? visible.default : true
                if (this.windowType === 'popout') {
                    return typeof visible.popout === 'boolean' ? visible.popout : defaultVisible
                }
                if (this.windowType === 'embed') {
                    return typeof visible.embed === 'boolean' ? visible.embed : defaultVisible
                }
                return defaultVisible
            }
            return true
        }
    }
}
</script>

<style lang="scss">
.micro-modal {
    position: fixed;
    inset: 0;
    will-change: auto;

    --modal-mask-bg: rgba(0, 0, 0, .4);
    --modal-resize-display: block;
    --modal-content-left: auto;
    --modal-content-min-width: auto;
    --modal-content-max-width: 100%;
    --modal-body-margin: 0;
    --modal-body-border-radius: 0;
    --modal-body-background-color: #ffffff;
    --modal-dark-filter: none;
    --modal-slide-transform: translate(15%, 0);

    --modal-capsule-bgcolor: rgba(255, 255, 255, 0.6);
    --modal-capsule-bor-color: rgba(229, 230, 235, 0.6);
    --modal-capsule-hov-bgcolor: rgba(255, 255, 255, 0.9);
    --modal-capsule-hov-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    --modal-capsule-line-color: rgba(229, 230, 235, 0.8);

    // 透明模式
    &.transparent-mode {
        --modal-mask-bg: transparent;
        --modal-resize-display: none;
        --modal-content-left: 0;
        --modal-content-min-width: 100%;
        --modal-body-margin: 0;
        --modal-body-border-radius: 0;
        --modal-body-background-color: transparent;
    }

    // 移动端适配（全屏）
    @media (width < 768px) {
        --modal-mask-bg: transparent;
        --modal-resize-display: none;
        --modal-content-left: 0;
        --modal-content-min-width: 100%;
        --modal-body-margin: 0;
        --modal-body-border-radius: 0;
        --modal-slide-transform: translate(0, 15%);
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
        filter: var(--modal-dark-filter);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--modal-mask-bg);
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
        margin-top: var(--modal-body-margin);
        margin-right: var(--modal-body-margin);
        transform: translateY(var(--status-bar-height, 0));
        display: flex;
        align-items: center;
        background: var(--modal-capsule-bgcolor);
        border: 1px solid var(--modal-capsule-bor-color);
        border-radius: 16px;
        transition: box-shadow 0.2s, background 0.2s, top 0.2s, right 0.2s;
        will-change: box-shadow, background, top, right;

        &:hover {
            background: var(--modal-capsule-hov-bgcolor);
            box-shadow: var(--modal-capsule-hov-shadow);
        }

        &-line {
            width: 1px;
            height: 16px;
            background: var(--modal-capsule-line-color);
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

    &-resize {
        display: var(--modal-resize-display);
        position: absolute;
        top: 0;
        left: var(--modal-body-margin);
        bottom: 0;
        z-index: 1;
        width: 5px;
    }

    &-content {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: var(--modal-content-left);
        display: flex;
        flex-direction: column;
        height: 100%;
        min-width: var(--modal-content-min-width);
        max-width: var(--modal-content-max-width);
    }

    &-body {
        flex: 1;
        min-height: 0;
        margin: var(--modal-body-margin);
        border-radius: var(--modal-body-border-radius);
        background-color: var(--modal-body-background-color);
        position: relative;
    }

    &-fade {
        &-enter-active,
        &-leave-active {
            transition: opacity .5s cubic-bezier(0.32, 0.72, 0, 1);
        }

        &-enter,
        &-leave-to {
            opacity: 0;
        }
    }

    &-slide {
        &-enter-active,
        &-leave-active {
            transition: transform .3s cubic-bezier(0.32, 0.72, 0, 1), opacity .3s cubic-bezier(0.32, 0.72, 0, 1);
        }

        &-enter,
        &-leave-to {
            transform: var(--modal-slide-transform);
            opacity: 0;
        }
    }
}

// 横屏模式适配
body.window-landscape {
    .micro-modal {
        --modal-content-max-width: calc(100% - 80px);
    }
    &.transparent-mode {
        --modal-content-max-width: 100%;
    }
    @media (width < 768px) {
        --modal-content-max-width: 100%;
    }
}

// 深色模式适配
body.dark-mode-reverse {
    .micro-modal {
        &:not(.transparent-mode) {
            --modal-mask-bg: rgba(230, 230, 230, 0.6);
            --modal-capsule-bgcolor: rgba(210, 210, 210, 0.6);
            --modal-capsule-bor-color: rgba(210, 210, 210, 0.3);
            --modal-capsule-hov-bgcolor: rgba(210, 210, 210, 0.8);
            --modal-capsule-hov-shadow: 0 4px 16px rgba(180, 180, 180, 0.2);
            --modal-capsule-line-color: rgba(180, 180, 180, 0.6);

            &.no-dark-content {
                --modal-dark-filter: invert(100%) hue-rotate(180deg) contrast(100%);
                --modal-body-background-color: #000000;
            }
        }
        &.popout-window {
            &.no-dark-content {
                --modal-dark-filter: invert(100%) hue-rotate(180deg) contrast(100%);
                --modal-body-background-color: #000000;
            }
        }
    }
}
</style>
