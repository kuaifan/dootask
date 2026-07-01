<template>
    <div v-if="displayMode === 'chat'" v-transfer-dom :data-transfer="true">
        <transition name="fade">
            <div
                v-if="visible && isMobile"
                class="ai-assistant-chat-mask"
                :style="{zIndex: zIndex - 1}"></div>
        </transition>
        <transition name="fade">
            <div
                v-if="visible"
                ref="chatWindow"
                class="ai-assistant-chat"
                :class="{'is-fullscreen': effectiveFullscreen, 'is-mobile-fullscreen': isMobile}"
                :style="chatStyle">
                <div v-if="!isMobile" class="ai-assistant-fullscreen" @click="toggleFullscreen">
                    <svg v-if="isFullscreen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="4 10 10 10 10 4"/><polyline points="14 4 14 10 20 10"/>
                        <polyline points="10 20 10 14 4 14"/><polyline points="20 14 14 14 14 20"/>
                    </svg>
                    <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="5 9 5 5 9 5"/><polyline points="19 9 19 5 15 5"/>
                        <polyline points="5 15 5 19 9 19"/><polyline points="19 15 19 19 15 19"/>
                    </svg>
                </div>
                <Icon class="ai-assistant-close" type="ios-close" @click="onClose"/>
                <div
                    class="ai-assistant-drag-handle"
                    @dblclick="toggleFullscreen"
                    @mousedown.stop.prevent="onDragMouseDown">
                    <slot name="header"></slot>
                </div>
                <slot></slot>
                <!-- 调整大小的控制点 -->
                <template v-if="!effectiveFullscreen">
                    <div class="ai-assistant-resize-handle ai-assistant-resize-n" @mousedown.stop.prevent="onResizeMouseDown($event, 'n')"></div>
                    <div class="ai-assistant-resize-handle ai-assistant-resize-s" @mousedown.stop.prevent="onResizeMouseDown($event, 's')"></div>
                    <div class="ai-assistant-resize-handle ai-assistant-resize-e" @mousedown.stop.prevent="onResizeMouseDown($event, 'e')"></div>
                    <div class="ai-assistant-resize-handle ai-assistant-resize-w" @mousedown.stop.prevent="onResizeMouseDown($event, 'w')"></div>
                    <div class="ai-assistant-resize-handle ai-assistant-resize-ne" @mousedown.stop.prevent="onResizeMouseDown($event, 'ne')"></div>
                    <div class="ai-assistant-resize-handle ai-assistant-resize-nw" @mousedown.stop.prevent="onResizeMouseDown($event, 'nw')"></div>
                    <div class="ai-assistant-resize-handle ai-assistant-resize-se" @mousedown.stop.prevent="onResizeMouseDown($event, 'se')"></div>
                    <div class="ai-assistant-resize-handle ai-assistant-resize-sw" @mousedown.stop.prevent="onResizeMouseDown($event, 'sw')"></div>
                </template>
            </div>
        </transition>
        <!--窗口助理：空 Modal 进入全局模态栈，由 removeLast()（ESC/滑动返回/返回键/Electron 关窗）统一管理；mask=false 不阻挡背后操作-->
        <Modal
            v-model="visible"
            :mask="false"
            :mask-closable="false"
            :footer-hide="true"
            :transition-names="['', '']"
            :beforeClose="onAssistClose"
            class-name="ai-assistant-assist"/>
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
        },
        zIndex: {
            type: Number,
            default: 2000
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
            sizeCacheKey: 'aiAssistant.chatSize',
            // 窗口尺寸（用于计算位置）
            windowSize: {
                width: 460,
                height: 600,
            },
            // 用户自定义尺寸
            customSize: {
                width: null,
                height: null,
            },
            // 尺寸限制
            minSize: {
                width: 380,
                height: 400,
            },
            maxSize: {
                width: 800,
                height: 900,
            },
            record: {},
            // 调整大小相关
            resizing: false,
            resizeDirection: null,
            resizeRecord: {},
            // 全屏状态
            isFullscreen: false,
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
            return this.windowWidth;
        },

        clientHeight() {
            return this.windowHeight;
        },

        isMobile() {
            return this.windowWidth < 576;
        },

        effectiveFullscreen() {
            return this.isFullscreen || this.isMobile;
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
                    zIndex: this.zIndex,
                };
            }
            // 全屏时不应用自定义尺寸和位置
            if (this.effectiveFullscreen) {
                return {
                    zIndex: this.zIndex,
                };
            }
            const style = {
                left: `${this.left}px`,
                top: `${this.top}px`,
                zIndex: this.zIndex,
            };
            // 应用自定义尺寸
            if (this.customSize.width) {
                style.width = `${this.customSize.width}px`;
            }
            if (this.customSize.height) {
                style.height = `${this.customSize.height}px`;
            }
            return style;
        },
    },

    watch: {
        visible(val) {
            if (val && this.displayMode === 'chat') {
                this.$nextTick(() => {
                    this.updateWindowSize();
                });
            } else if (!val) {
                // 关闭时重置全屏状态
                this.isFullscreen = false;
            }
        },
        windowWidth() {
            this.onViewportChange();
        },
        windowHeight() {
            this.onViewportChange();
        },
    },

    mounted() {
        this.loadSizeAndPosition();
        document.addEventListener('keydown', this.onGlobalEscFullscreen, true);
    },

    beforeDestroy() {
        document.removeEventListener('keydown', this.onGlobalEscFullscreen, true);
        document.removeEventListener('mousemove', this.onDragMouseMove);
        document.removeEventListener('mouseup', this.onDragMouseUp);
        document.removeEventListener('mousemove', this.onResizeMouseMove);
        document.removeEventListener('mouseup', this.onResizeMouseUp);
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
         * 拖动：鼠标按下
         */
        onDragMouseDown(e) {
            // 只响应鼠标左键，全屏时禁用拖动
            if (e.button !== 0 || this.effectiveFullscreen) return;

            this.updateWindowSize();
            this.record = {
                offsetX: e.clientX - this.left,
                offsetY: e.clientY - this.top,
            };
            this.dragging = true;

            document.addEventListener('mousemove', this.onDragMouseMove);
            document.addEventListener('mouseup', this.onDragMouseUp);
            document.addEventListener('contextmenu', this.onContextMenu);
        },

        /**
         * 切换全屏
         */
        toggleFullscreen() {
            if (this.isMobile) return;
            this.isFullscreen = !this.isFullscreen;
        },

        /**
         * 右键菜单弹出时取消拖动/调整大小
         */
        onContextMenu() {
            if (this.dragging) {
                this.onDragMouseUp();
            }
            if (this.resizing) {
                this.onResizeMouseUp();
            }
        },

        /**
         * 拖动：鼠标移动
         */
        onDragMouseMove(e) {
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
         * 拖动：鼠标松开
         */
        onDragMouseUp() {
            document.removeEventListener('mousemove', this.onDragMouseMove);
            document.removeEventListener('mouseup', this.onDragMouseUp);
            document.removeEventListener('contextmenu', this.onContextMenu);

            this.savePosition();
            this.dragging = false;
        },

        /**
         * 调整大小：鼠标按下
         */
        onResizeMouseDown(e, direction) {
            if (e.button !== 0) return;

            this.updateWindowSize();
            this.resizeDirection = direction;
            this.resizeRecord = {
                startX: e.clientX,
                startY: e.clientY,
                startWidth: this.windowSize.width,
                startHeight: this.windowSize.height,
                startLeft: this.left,
                startTop: this.top,
            };
            this.resizing = true;

            document.addEventListener('mousemove', this.onResizeMouseMove);
            document.addEventListener('mouseup', this.onResizeMouseUp);
            document.addEventListener('contextmenu', this.onContextMenu);
        },

        /**
         * 调整大小：鼠标移动
         */
        onResizeMouseMove(e) {
            if (!this.resizing) return;

            const dir = this.resizeDirection;
            const deltaX = e.clientX - this.resizeRecord.startX;
            const deltaY = e.clientY - this.resizeRecord.startY;

            let newWidth = this.resizeRecord.startWidth;
            let newHeight = this.resizeRecord.startHeight;
            let newLeft = this.resizeRecord.startLeft;
            let newTop = this.resizeRecord.startTop;

            // 根据方向计算新尺寸
            if (dir.includes('e')) {
                newWidth = this.resizeRecord.startWidth + deltaX;
            }
            if (dir.includes('w')) {
                newWidth = this.resizeRecord.startWidth - deltaX;
                newLeft = this.resizeRecord.startLeft + deltaX;
            }
            if (dir.includes('s')) {
                newHeight = this.resizeRecord.startHeight + deltaY;
            }
            if (dir.includes('n')) {
                newHeight = this.resizeRecord.startHeight - deltaY;
                newTop = this.resizeRecord.startTop + deltaY;
            }

            // 限制最小/最大尺寸
            const minMargin = 12;
            const maxWidth = Math.min(this.maxSize.width, this.clientWidth - minMargin * 2);
            const maxHeight = Math.min(this.maxSize.height, this.clientHeight - minMargin * 2);

            newWidth = Math.max(this.minSize.width, Math.min(newWidth, maxWidth));
            newHeight = Math.max(this.minSize.height, Math.min(newHeight, maxHeight));

            // 如果是从左边或上边调整，需要修正位置
            if (dir.includes('w')) {
                const widthDiff = newWidth - this.resizeRecord.startWidth;
                newLeft = this.resizeRecord.startLeft - widthDiff;
            }
            if (dir.includes('n')) {
                const heightDiff = newHeight - this.resizeRecord.startHeight;
                newTop = this.resizeRecord.startTop - heightDiff;
            }

            // 边界限制位置
            newLeft = Math.max(minMargin, Math.min(newLeft, this.clientWidth - newWidth - minMargin));
            newTop = Math.max(minMargin, Math.min(newTop, this.clientHeight - newHeight - minMargin));

            // 更新尺寸
            this.customSize.width = newWidth;
            this.customSize.height = newHeight;
            this.windowSize.width = newWidth;
            this.windowSize.height = newHeight;

            // 更新位置
            this.updatePositionFromCoords(newLeft, newTop);
        },

        /**
         * 调整大小：鼠标松开
         */
        onResizeMouseUp() {
            document.removeEventListener('mousemove', this.onResizeMouseMove);
            document.removeEventListener('mouseup', this.onResizeMouseUp);
            document.removeEventListener('contextmenu', this.onContextMenu);

            this.saveSize();
            this.savePosition();
            this.resizing = false;
            this.resizeDirection = null;
        },

        /**
         * 先加载尺寸，再加载位置（确保位置计算时使用正确的尺寸）
         */
        async loadSizeAndPosition() {
            await this.loadSize();
            await this.loadPosition();
        },

        /**
         * 加载保存的尺寸
         */
        async loadSize() {
            try {
                const saved = await $A.IDBString(this.sizeCacheKey);
                if (saved) {
                    const size = JSON.parse(saved);
                    if (size && typeof size.width === 'number' && typeof size.height === 'number') {
                        this.customSize = {
                            width: Math.max(this.minSize.width, Math.min(size.width, this.maxSize.width)),
                            height: Math.max(this.minSize.height, Math.min(size.height, this.maxSize.height)),
                        };
                        this.windowSize.width = this.customSize.width;
                        this.windowSize.height = this.customSize.height;
                    }
                }
            } catch (e) {
                // ignore
            }
        },

        /**
         * 保存尺寸
         */
        saveSize() {
            if (this.customSize.width && this.customSize.height) {
                $A.IDBSave(this.sizeCacheKey, JSON.stringify(this.customSize));
            }
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
         * 视口尺寸变化
         */
        onViewportChange() {
            this.constrainSizeToScreen();
            this.checkBounds();
        },

        /**
         * 限制尺寸不超出屏幕
         */
        constrainSizeToScreen() {
            const minMargin = 12;
            const maxWidth = this.clientWidth - minMargin * 2;
            const maxHeight = this.clientHeight - minMargin * 2;

            if (this.customSize.width && this.customSize.width > maxWidth) {
                this.customSize.width = Math.max(this.minSize.width, maxWidth);
                this.windowSize.width = this.customSize.width;
            }
            if (this.customSize.height && this.customSize.height > maxHeight) {
                this.customSize.height = Math.max(this.minSize.height, maxHeight);
                this.windowSize.height = this.customSize.height;
            }
        },

        /**
         * 全局 ESC 捕获：仅在 chat 浮窗处于全屏时介入。
         * AI 浮窗 z-index 恒为最高，但其助理 Modal 未必是模态栈顶（modalIndex 最大），
         * 故不能依赖 ViewUI 冒泡 ESC。这里在捕获阶段抢先拦截并退出全屏。
         */
        onGlobalEscFullscreen(e) {
            if (e.key !== 'Escape' && e.keyCode !== 27) {
                return;
            }
            if (this.displayMode === 'chat' && this.visible && this.isFullscreen) {
                e.stopImmediatePropagation();
                e.preventDefault();
                this.isFullscreen = false;
            }
        },

        /**
         * 窗口助理被全局返回机制（removeLast）弹出时触发：转而关闭 chat 浮窗。
         * 返回一个不 resolve 的 Promise，关闭由 visible 变为 false 后自动驱动（与微应用助理一致）。
         */
        onAssistClose() {
            return new Promise(() => {
                this.onClose()
            });
        },

        /**
         * 点击 AI 回复内链接导航前的处理（统一策略）：
         * - modal 模式：一律关闭（居中弹窗会挡住目标）
         * - chat 模式 + 移动端：关闭（全屏会挡住目标）
         * - chat 模式 + 桌面端全屏：退出全屏，保留浮窗以便继续对话
         * - chat 模式 + 桌面端非全屏：保持打开
         */
        prepareForNavigate() {
            if (this.displayMode !== 'chat') {
                this.$emit('input', false);
                return;
            }
            if (this.isMobile) {
                this.$emit('input', false);
            } else if (this.isFullscreen) {
                this.isFullscreen = false;
            }
        },

        /**
         * 关闭：直接关闭浮窗（不区分显示模式），由外部控制 visible 变为 false
         */
        onClose() {
            this.$emit('input', false);
        },
    }
};
</script>
