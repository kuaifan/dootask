<template>
    <div v-if="displayMode === 'chat'" v-transfer-dom :data-transfer="true">
        <transition name="fade">
            <div
                v-if="visible"
                ref="chatWindow"
                class="ai-assistant-chat"
                :style="chatStyle">
                <Icon class="ai-assistant-close" type="ios-close" @click="onClose"/>
                <div
                    class="ai-assistant-drag-handle"
                    @mousedown.stop.prevent="onMouseDown">
                    <slot name="header"></slot>
                </div>
                <slot></slot>
            </div>
        </transition>
    </div>
    <Modal
        v-else
        v-model="visible"
        :width="shouldCreateNewSession ? '440px' : '600px'"
        :mask-closable="false"
        :footer-hide="true"
        class-name="ai-assistant-modal">
        <template #header>
            <slot name="header"></slot>
        </template>
        <slot></slot>
    </Modal>
</template>

<script>
import TransferDom from "../../directives/transfer-dom";

export default {
    name: 'AssistantModal',
    directives: {TransferDom},

    props: {
        value: {
            type: Boolean,
            default: false
        },
        displayMode: {
            type: String,
            default: 'modal'
        },
        shouldCreateNewSession: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            // 位置存储：只保存两个距离（水平一个、垂直一个）
            position: {
                x: 24,              // 水平距离值
                y: 24,              // 垂直距离值
                fromRight: true,    // true: 距右边, false: 距左边
                fromBottom: true,   // true: 距底部, false: 距顶部
            },
            dragging: false,
            positionLoaded: false,
            cacheKey: 'aiAssistant.chatPosition',
            windowSize: {
                width: 460,
                height: 640,
            },
            record: {},
        };
    },

    computed: {
        visible: {
            get() {
                return this.value;
            },
            set(val) {
                this.$emit('input', val);
            }
        },

        clientWidth() {
            return this.windowWidth || document.documentElement.clientWidth;
        },

        clientHeight() {
            return this.windowHeight || document.documentElement.clientHeight;
        },

        // 计算实际的 left 值
        left() {
            if (this.position.fromRight) {
                return this.clientWidth - this.windowSize.width - this.position.x;
            }
            return this.position.x;
        },

        // 计算实际的 top 值
        top() {
            if (this.position.fromBottom) {
                return this.clientHeight - this.windowSize.height - this.position.y;
            }
            return this.position.y;
        },

        chatStyle() {
            if (!this.positionLoaded) {
                return {
                    opacity: 0,
                };
            }
            return {
                left: `${this.left}px`,
                top: `${this.top}px`,
            };
        },
    },

    watch: {
        visible(val) {
            if (val && this.displayMode === 'chat') {
                this.$nextTick(() => {
                    this.updateWindowSize();
                });
            }
        },
    },

    mounted() {
        this.loadPosition();
        window.addEventListener('resize', this.onResize);
    },

    beforeDestroy() {
        window.removeEventListener('resize', this.onResize);
        document.removeEventListener('mousemove', this.onMouseMove);
        document.removeEventListener('mouseup', this.onMouseUp);
        document.removeEventListener('contextmenu', this.onContextMenu);
    },

    methods: {
        /**
         * 更新窗口实际尺寸
         */
        updateWindowSize() {
            const el = this.$refs.chatWindow;
            if (el) {
                this.windowSize = {
                    width: el.offsetWidth,
                    height: el.offsetHeight,
                };
            }
        },

        /**
         * 加载保存的位置
         */
        async loadPosition() {
            try {
                const saved = await $A.IDBString(this.cacheKey);
                if (saved) {
                    const pos = JSON.parse(saved);
                    if (pos && typeof pos.x === 'number' && typeof pos.y === 'number') {
                        this.position = pos;
                        this.$nextTick(() => {
                            this.checkBounds();
                            this.positionLoaded = true;
                        });
                        return;
                    }
                }
            } catch (e) {
                // ignore
            }
            // 默认位置：右下角
            this.position = {x: 24, y: 24, fromRight: true, fromBottom: true};
            this.positionLoaded = true;
        },

        /**
         * 保存位置
         */
        savePosition() {
            $A.IDBSave(this.cacheKey, JSON.stringify(this.position));
        },

        /**
         * 根据当前 left/top 更新 position 对象
         */
        updatePositionFromCoords(left, top) {
            const centerX = left + this.windowSize.width / 2;
            const centerY = top + this.windowSize.height / 2;

            // 判断在哪个半区
            const fromRight = centerX >= this.clientWidth / 2;
            const fromBottom = centerY >= this.clientHeight / 2;

            // 计算距离
            const x = fromRight ? (this.clientWidth - this.windowSize.width - left) : left;
            const y = fromBottom ? (this.clientHeight - this.windowSize.height - top) : top;

            this.position = {x, y, fromRight, fromBottom};
        },

        /**
         * 鼠标按下
         */
        onMouseDown(e) {
            // 只响应鼠标左键
            if (e.button !== 0) return;

            this.updateWindowSize();
            this.record = {
                time: Date.now(),
                startLeft: this.left,
                startTop: this.top,
                offsetX: e.clientX - this.left,
                offsetY: e.clientY - this.top,
            };
            this.dragging = true;

            document.addEventListener('mousemove', this.onMouseMove);
            document.addEventListener('mouseup', this.onMouseUp);
            document.addEventListener('contextmenu', this.onContextMenu);
        },

        /**
         * 右键菜单弹出时取消拖动
         */
        onContextMenu() {
            if (this.dragging) {
                this.onMouseUp();
            }
        },

        /**
         * 鼠标移动
         */
        onMouseMove(e) {
            if (!this.dragging) return;

            const minMargin = 12;
            let newLeft = e.clientX - this.record.offsetX;
            let newTop = e.clientY - this.record.offsetY;

            // 边界限制（最小边距12px）
            newLeft = Math.max(minMargin, Math.min(newLeft, this.clientWidth - this.windowSize.width - minMargin));
            newTop = Math.max(minMargin, Math.min(newTop, this.clientHeight - this.windowSize.height - minMargin));

            this.updatePositionFromCoords(newLeft, newTop);
        },

        /**
         * 鼠标松开
         */
        onMouseUp() {
            document.removeEventListener('mousemove', this.onMouseMove);
            document.removeEventListener('mouseup', this.onMouseUp);
            document.removeEventListener('contextmenu', this.onContextMenu);

            this.savePosition();
            this.dragging = false;
        },

        /**
         * 检查边界（仅在加载和窗口变化时调用）
         */
        checkBounds() {
            const minMargin = 12;
            // 确保距离在有效范围内（最小12px，最大不超出屏幕）
            const maxX = this.clientWidth - this.windowSize.width - minMargin;
            const maxY = this.clientHeight - this.windowSize.height - minMargin;
            this.position.x = Math.max(minMargin, Math.min(this.position.x, maxX));
            this.position.y = Math.max(minMargin, Math.min(this.position.y, maxY));
        },

        /**
         * 窗口大小改变
         */
        onResize() {
            this.$nextTick(() => {
                this.updateWindowSize();
                this.checkBounds();
            });
        },

        onClose() {
            this.$emit('input', false);
        }
    }
};
</script>
