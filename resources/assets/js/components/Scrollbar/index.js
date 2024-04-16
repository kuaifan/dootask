import {supportsTouch, toInt} from "./lib/util";
import * as CSS from './lib/css';

export default {
    name: 'Scrollbar',
    props: {
        tag: {
            type: String,
            default: 'div'
        },
        className: {
            type: String,
            default: ''
        },
        enableX: {
            type: Boolean,
            default: false
        },
        enableY: {
            type: Boolean,
            default: true
        },
        hideBar: {
            type: Boolean,
            default: false
        },
        minSize: {
            type: Number,
            default: 20
        },
        touchContentBlur: {
            type: Boolean,
            default: true
        },
    },
    data() {
        return {
            isReady: false,

            scrollingX: false,
            scrollingY: false,

            moveingX: false,
            moveingY: false,

            containerWidth: null,
            containerHeight: null,

            contentWidth: null,
            contentHeight: null,
            contentOverflow: {
                x: null,
                y: null,
            },

            thumbYHeight: null,
            thumbYTop: null,
            thumbXWidth: null,
            thumbXLeft: null,

            lastScrollTop: 0,
            lastScrollLeft: 0,

            timeouts: {},
        }
    },
    computed: {
        containerClass() {
            const classList = ['scrollbar-container'];
            if (supportsTouch) {
                classList.push('scrollbar-touch')
            } else {
                classList.push('scrollbar-desktop')
            }
            if (this.contentWidth > this.containerWidth && this.contentOverflow.x !== 'hidden' && this.enableX) {
                classList.push('scrollbar-active-x')
            }
            if (this.contentHeight > this.containerHeight && this.contentOverflow.y !== 'hidden' && this.enableY) {
                classList.push('scrollbar-active-y')
            }
            if (this.scrollingX) {
                classList.push('scrollbar-scrolling-x')
            }
            if (this.scrollingY) {
                classList.push('scrollbar-scrolling-y')
            }
            if (this.moveingX) {
                classList.push('scrollbar-moveing-x')
            }
            if (this.moveingY) {
                classList.push('scrollbar-moveing-y')
            }
            if (this.hideBar || !this.isReady) {
                classList.push('scrollbar-hidebar')
            }
            return classList
        },
        contentClass({className, enableX, enableY}) {
            const classList = ['scrollbar-content'];
            if (className) {
                classList.push(className)
            }
            if (!enableX) {
                classList.push('scrollbar-disable-x')
            }
            if (!enableY) {
                classList.push('scrollbar-disable-y')
            }
            return classList
        }
    },
    mounted() {
        this.$nextTick(() => {
            this.updateBase()
        });
    },
    updated() {
        this.$nextTick(() => {
            this.updateGeometry(false);
        });
    },
    methods: {
        /**
         * 滚动区域信息
         * @returns {{scale: number, scrollY: *, scrollE: number}}
         */
        scrollInfo() {
            const scroller = $A(this.$refs.content);
            const wInnerH = Math.round(scroller.innerHeight());
            const wScrollY = scroller.scrollTop();
            const bScrollH = this.$refs.content.scrollHeight;
            return {
                scale: wScrollY / (bScrollH - wInnerH),     //已滚动比例
                scrollY: wScrollY,                          //滚动的距离
                scrollE: bScrollH - wInnerH - wScrollY,     //与底部距离
            }
        },

        /**
         * 滚动区域元素
         * @returns {Vue | Element | (Vue | Element)[]}
         */
        scrollElement() {
            return this.$refs.content;
        },

        /**
         * 从滚动区域获取指定元素
         * @param el
         * @returns {*}
         */
        querySelector(el) {
            return this.$refs.content && this.$refs.content.querySelector(el)
        },

        /**
         * 更新基础信息
         */
        updateBase() {
            if (supportsTouch) {
                return;
            }
            const containerStyles = CSS.get(this.$refs.container);
            const contentStyles = CSS.get(this.$refs.content);

            CSS.set(this.$refs.trackX, {
                left: toInt(containerStyles.paddingLeft) + toInt(contentStyles.marginLeft),
                right: toInt(containerStyles.paddingRight) + toInt(contentStyles.marginRight),
                bottom: toInt(containerStyles.paddingBottom) + toInt(contentStyles.marginBottom),
            });
            CSS.set(this.$refs.trackY, {
                top: toInt(containerStyles.paddingTop) + toInt(contentStyles.marginTop),
                bottom: toInt(containerStyles.paddingBottom) + toInt(contentStyles.marginBottom),
                right: toInt(containerStyles.paddingRight) + toInt(contentStyles.marginRight),
            });

            this.contentOverflow = {
                x: contentStyles.overflowX,
                y: contentStyles.overflowY,
            }
        },

        /**
         * 更新滚动条
         * @param scrolling 是否正在滚动
         */
        updateGeometry(scrolling) {
            if (supportsTouch) {
                return;
            }

            const element = this.$refs.content;
            if (!element) {
                return;
            }

            const scrollTop = Math.floor(element.scrollTop);
            const rect = element.getBoundingClientRect();

            this.containerWidth = Math.round(rect.width);
            this.containerHeight = Math.round(rect.height);
            this.contentWidth = element.scrollWidth;
            this.contentHeight = element.scrollHeight;

            this.thumbXWidth = Math.max(toInt((this.containerWidth * this.containerWidth) / this.contentWidth), this.minSize);
            this.thumbXLeft = toInt((element.scrollLeft * (this.containerWidth - this.thumbXWidth)) / (this.contentWidth - this.containerWidth));
            this.thumbYHeight = Math.max(toInt((this.containerHeight * this.containerHeight) / this.contentHeight), this.minSize);
            this.thumbYTop = toInt((scrollTop * (this.containerHeight - this.thumbYHeight)) / (this.contentHeight - this.containerHeight));

            CSS.set(this.$refs.thumbX, {
                left: this.thumbXLeft,
                width: this.thumbXWidth,
            });
            CSS.set(this.$refs.thumbY, {
                top: this.thumbYTop,
                height: this.thumbYHeight,
            });

            if (scrolling) {
                this.scrollingX = this.lastScrollLeft !== element.scrollLeft;
                this.scrollingY = this.lastScrollTop !== element.scrollTop;

                this.lastScrollTop = element.scrollTop;
                this.lastScrollLeft = element.scrollLeft;

                this.timeouts['scroll'] && clearTimeout(this.timeouts['scroll']);
                this.timeouts['scroll'] = setTimeout(() => {
                    this.scrollingX = false;
                    this.scrollingY = false;
                }, 1000)
            }
        },

        /**
         * 鼠标移入事件（单次）
         */
        onContainerMouseMove() {
            if (this.windowTouch) {
                return;
            }
            setTimeout(() => {
                if (this.isReady) {
                    return
                }
                this.updateGeometry(true);
                this.isReady = true
            }, 300)
        },

        /**
         * 内容区域触摸开始事件
         * @param e
         */
        onContentTouchStart(e) {
            if (!this.touchContentBlur) {
                return;
            }
            const focusedElement = document.activeElement;
            if (focusedElement) {
                focusedElement.blur();
            }
        },

        /**
         * 滚动区域滚动事件
         * @param e
         */
        onContentScroll(e) {
            this.updateGeometry(true);
            this.$emit('on-scroll', e);
            this.isReady = true
        },

        /**
         * 内容区域鼠标进入事件
         */
        onContentMouseenter() {
            this.updateBase();
            this.updateGeometry(false);
        },

        /**
         * 轨道区域(X)鼠标按下事件
         * @param e
         */
        onTrackXMouseDown(e) {
            if (supportsTouch) {
                return;
            }
            const element = this.$refs.content;
            const rect = this.$refs.trackX.getBoundingClientRect();

            const positionLeft = e.pageX - window.scrollX - rect.left;
            const direction = positionLeft > this.thumbXLeft ? 1 : -1;

            element.scrollLeft += direction * this.containerWidth;
            this.updateGeometry(true);

            e.stopPropagation();
        },

        /**
         * 轨道区域(Y)鼠标按下事件
         * @param e
         */
        onTrackYMouseDown(e) {
            if (supportsTouch) {
                return;
            }
            const element = this.$refs.content;
            const rect = this.$refs.trackY.getBoundingClientRect();

            const positionTop = e.pageY - window.scrollY - rect.top;
            const direction = positionTop > this.thumbYTop ? 1 : -1;

            element.scrollTop += direction * this.containerHeight;
            this.updateGeometry(true);

            e.stopPropagation();
        },

        /**
         * 滚动条(X)鼠标按下事件
         * @param e
         */
        onThumbXMouseDown(e) {
            if (supportsTouch) {
                return;
            }
            const element = this.$refs.content;
            const rect = element.getBoundingClientRect();
            const scrollLeft = element.scrollLeft;
            const pageX = e.pageX - window.scrollX;

            const mouseMoveHandler = (e) => {
                const diff = e.pageX - pageX;
                element.scrollLeft = scrollLeft + diff * this.contentWidth / rect.width;
            };

            const mouseUpHandler = () => {
                this.timeouts['moveX'] = setTimeout(() => {
                    this.moveingX = false;
                }, 100);
                document.removeEventListener('mousemove', mouseMoveHandler);
                document.removeEventListener('mouseup', mouseUpHandler);
            };
            this.moveingX = true;
            this.timeouts['moveX'] && clearTimeout(this.timeouts['moveX']);

            document.addEventListener('mousemove', mouseMoveHandler);
            document.addEventListener('mouseup', mouseUpHandler);

            e.preventDefault();
            e.stopPropagation();
        },

        /**
         * 滚动条(Y)鼠标按下事件
         * @param e
         */
        onThumbYMouseDown(e) {
            if (supportsTouch) {
                return;
            }
            const element = this.$refs.content;
            const rect = element.getBoundingClientRect();
            const scrollTop = element.scrollTop;
            const pageY = e.pageY - window.scrollY;

            const mouseMoveHandler = (e) => {
                const diff = e.pageY - pageY;
                element.scrollTop = scrollTop + diff * this.contentHeight / rect.height;
            };

            const mouseUpHandler = () => {
                this.timeouts['moveY'] = setTimeout(() => {
                    this.moveingY = false;
                }, 100);
                document.removeEventListener('mousemove', mouseMoveHandler);
                document.removeEventListener('mouseup', mouseUpHandler);
            };
            this.moveingY = true;
            this.timeouts['moveY'] && clearTimeout(this.timeouts['moveY']);

            document.addEventListener('mousemove', mouseMoveHandler);
            document.addEventListener('mouseup', mouseUpHandler);

            e.preventDefault();
            e.stopPropagation();
        }
    },
    render(h) {
        return h('div', {
            ref: 'container',
            class: this.containerClass,
            on: {
                '~mousemove': this.onContainerMouseMove,
            }
        }, [
            h(this.tag, {
                ref: 'content',
                class: this.contentClass,
                on: {
                    touchstart: this.onContentTouchStart,
                    scroll: this.onContentScroll,
                    mouseenter: this.onContentMouseenter,
                }
            }, this.$slots.default),
            h('div', {
                ref: 'trackX',
                class: 'scrollbar-track-x',
                on: {
                    mousedown: this.onTrackXMouseDown
                }
            }, [
                h('div', {
                    ref: 'thumbX',
                    class: 'scrollbar-thumb-x',
                    on: {
                        mousedown: this.onThumbXMouseDown
                    }
                }),
            ]),
            h('div', {
                ref: 'trackY',
                class: 'scrollbar-track-y',
                on: {
                    mousedown: this.onTrackYMouseDown
                }
            }, [
                h('div', {
                    ref: 'thumbY',
                    class: 'scrollbar-thumb-y',
                    on: {
                        mousedown: this.onThumbYMouseDown
                    }
                })
            ]),
        ])
    }
}
