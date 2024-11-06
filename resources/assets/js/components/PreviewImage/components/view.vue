<template>
    <div ref="view" class="common-preview-image">
        <div class="common-preview-view no-dark-content">
            <template v-if="!isSingle">
                <div class="preview-view-prev" :class="{ 'is-disabled': !infinite && isFirst }" @click="prev">
                    <i class="taskfont">&#xe72d;</i>
                </div>
                <div class="preview-view-next" :class="{ 'is-disabled': !infinite && isLast }" @click="next">
                    <i class="taskfont">&#xe733;</i>
                </div>
            </template>
            <div class="preview-view-actions">
                <div class="actions-inner">
                    <i class="taskfont" @click="handleActions('zoomOut')">&#xe7a2;</i>
                    <i class="taskfont" @click="handleActions('zoomIn')">&#xe79f;</i>
                    <i class="actions-divider"></i>
                    <i class="taskfont" @click="toggleMode" v-html="mode.icon"></i>
                    <i class="actions-divider"></i>
                    <i class="taskfont" @click="handleActions('anticlocelise')">&#xe7a7;</i>
                    <i class="taskfont" @click="handleActions('clocelise')">&#xe7a6;</i>
                </div>
            </div>
            <div class="preview-view-canvas">
                <img
                    v-for="(url, i) in urlList"
                    v-if="i === index"
                    ref="img"
                    class="preview-view-img"
                    :key="i"
                    :src="currentImg"
                    :style="imgStyle"
                    @load="handleImgLoad"
                    @error="handleImgError"
                    @mousedown="handleMouseDown">
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.common-preview-image {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    top: 0;
    backdrop-filter: blur(4px);

    .common-preview-view {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        top: 0;
        background: rgba(0, 0, 0, .8);

        .preview-view-prev,
        .preview-view-next {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            opacity: .8;
            cursor: pointer;
            background-color: #606266;
            border-color: #fff;
            height: 44px;
            width: 44px;
            overflow: hidden;
            position: absolute;
            top: 50%;
            z-index: 2;
            transform: translateY(-50%);

            @media (max-width: 640px) {
                display: none;
            }

            &.is-disabled {
                cursor: no-drop;

                > i {
                    opacity: 0.8;
                }
            }

            > i {
                color: #fff;
                font-size: 24px;
            }
        }

        .preview-view-prev {
            left: 40px;
        }

        .preview-view-next {
            right: 40px;
        }

        .preview-view-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: .8;
            background-color: #606266;
            border-color: #fff;
            border-radius: 22px;
            bottom: 30px;
            height: 44px;
            position: absolute;
            left: 50%;
            z-index: 2;
            padding: 0 23px;
            transform: translateX(-50%);
            width: 282px;
            user-select: none;

            .actions-inner {
                align-items: center;
                color: #fff;
                cursor: default;
                display: flex;
                height: 100%;
                justify-content: space-around;
                text-align: justify;
                width: 100%;

                > i {
                    cursor: pointer;
                    font-size: 23px;
                }
            }
        }

        .preview-view-canvas {
            align-items: center;
            display: flex;
            height: 100%;
            justify-content: center;
            width: 100%;
            user-select: none;
        }
    }
}
</style>

<script>
import {isFirefox, rafThrottle} from "element-sea/src/utils/util";
import {off, on} from "element-sea/src/utils/dom";

const Mode = {
    CONTAIN: {
        name: 'contain',
        icon: '&#xe79e;'
    },
    ORIGINAL: {
        name: 'original',
        icon: '&#xe79d;'
    }
};
const mousewheelEventName = isFirefox() ? 'DOMMouseScroll' : 'mousewheel';


export default {
    props: {
        urlList: {
            type: Array,
            default: () => []
        },
        initialIndex: {
            type: Number,
            default: 0
        },
        infinite: {
            type: Boolean,
            default: false
        },
        onSwitch: {
            type: Function,
            default: () => {
            }
        },
    },
    data() {
        return {
            index: this.initialIndex,
            loading: false,
            mode: Mode.CONTAIN,
            transform: {
                scale: 1,
                deg: 0,
                offsetX: 0,
                offsetY: 0,
                enableTransition: false
            }
        }
    },
    mounted() {
        this.deviceSupportInstall();
        this.$refs.view.focus();
    },
    beforeDestroy() {
        this.deviceSupportUninstall();
    },
    computed: {
        isSingle() {
            return this.urlList.length <= 1;
        },
        isFirst() {
            return this.index === 0;
        },
        isLast() {
            return this.index === this.urlList.length - 1;
        },
        currentImg() {
            let item = this.urlList[this.index];
            if ($A.isJson(item)) {
                item = item.src;
            }
            return item;
        },
        imgStyle() {
            const {scale, deg, offsetX, offsetY, enableTransition} = this.transform;
            const style = {
                transform: `scale(${scale}) rotate(${deg}deg)`,
                transition: enableTransition ? 'transform .3s' : '',
                'margin-left': `${offsetX}px`,
                'margin-top': `${offsetY}px`
            };
            if (this.mode === Mode.CONTAIN) {
                style.maxWidth = style.maxHeight = '100%';
            }
            return style;
        },
    },
    watch: {
        index: {
            handler: function (val) {
                this.reset();
                this.onSwitch(val);
            }
        },
        initialIndex(index) {
            this.index = index
        },
        currentImg() {
            this.$nextTick(_ => {
                const $img = this.$refs.img[0];
                if (!$img.complete) {
                    this.loading = true;
                }
            });
        }
    },
    methods: {
        deviceSupportInstall() {
            this._keyDownHandler = e => {
                e.stopPropagation();
                const keyCode = e.keyCode;
                switch (keyCode) {
                    // SPACE
                    case 32:
                        this.toggleMode();
                        break;
                    // LEFT_ARROW
                    case 37:
                        this.prev();
                        break;
                    // UP_ARROW
                    case 38:
                        this.handleActions('zoomIn');
                        break;
                    // RIGHT_ARROW
                    case 39:
                        this.next();
                        break;
                    // DOWN_ARROW
                    case 40:
                        this.handleActions('zoomOut');
                        break;
                }
            };
            this._mouseWheelHandler = rafThrottle(e => {
                const delta = e.wheelDelta ? e.wheelDelta : -e.detail;
                if (delta > 0) {
                    this.handleActions('zoomIn', {
                        zoomRate: 0.015,
                        enableTransition: false
                    });
                } else {
                    this.handleActions('zoomOut', {
                        zoomRate: 0.015,
                        enableTransition: false
                    });
                }
            });
            on(document, 'keydown', this._keyDownHandler);
            on(document, mousewheelEventName, this._mouseWheelHandler);
        },
        deviceSupportUninstall() {
            off(document, 'keydown', this._keyDownHandler);
            off(document, mousewheelEventName, this._mouseWheelHandler);
            this._keyDownHandler = null;
            this._mouseWheelHandler = null;
        },
        handleImgLoad(e) {
            this.loading = false;
        },
        handleImgError(e) {
            this.loading = false;
            e.target.alt = '加载失败';
        },
        handleMouseDown(e) {
            if (this.loading || e.button !== 0) return;

            const {offsetX, offsetY} = this.transform;
            const startX = e.pageX;
            const startY = e.pageY;
            this._dragHandler = rafThrottle(ev => {
                this.transform.offsetX = offsetX + ev.pageX - startX;
                this.transform.offsetY = offsetY + ev.pageY - startY;
            });
            on(document, 'mousemove', this._dragHandler);
            on(document, 'mouseup', ev => {
                off(document, 'mousemove', this._dragHandler);
            });

            e.preventDefault();
        },
        reset() {
            this.transform = {
                scale: 1,
                deg: 0,
                offsetX: 0,
                offsetY: 0,
                enableTransition: false
            };
        },
        toggleMode() {
            if (this.loading) return;

            const modeNames = Object.keys(Mode);
            const modeValues = Object.values(Mode);
            const index = modeValues.indexOf(this.mode);
            const nextIndex = (index + 1) % modeNames.length;
            this.mode = Mode[modeNames[nextIndex]];
            this.reset();
        },
        prev() {
            if (this.isFirst && !this.infinite) return;
            const len = this.urlList.length;
            this.index = (this.index - 1 + len) % len;
        },
        next() {
            if (this.isLast && !this.infinite) return;
            const len = this.urlList.length;
            this.index = (this.index + 1) % len;
        },
        handleActions(action, options = {}) {
            if (this.loading) return;
            const {zoomRate, rotateDeg, enableTransition} = {
                zoomRate: 0.2,
                rotateDeg: 90,
                enableTransition: true,
                ...options
            };
            const {transform} = this;
            switch (action) {
                case 'zoomOut':
                    if (transform.scale > 0.2) {
                        transform.scale = parseFloat((transform.scale - zoomRate).toFixed(3));
                    }
                    break;
                case 'zoomIn':
                    transform.scale = parseFloat((transform.scale + zoomRate).toFixed(3));
                    break;
                case 'clocelise':
                    transform.deg += rotateDeg;
                    break;
                case 'anticlocelise':
                    transform.deg -= rotateDeg;
                    break;
            }
            transform.enableTransition = enableTransition;
        }
    }
};
</script>
