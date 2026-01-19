<template>
    <transition name="fade">
        <div
            v-if="visible"
            class="ai-float-button-wrapper"
            :class="wrapperClass"
            :style="wrapperStyle"
            @mouseenter="onMouseEnter"
            @mouseleave="onMouseLeave">
            <div
                ref="floatBtn"
                class="ai-float-button"
                :class="btnClass"
                :style="btnStyle"
                @mousedown.stop.prevent="onMouseDown">
                <!-- 完整图标 -->
                <svg class="ai-float-button-icon no-dark-content" viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
                    <path d="M385.80516777 713.87417358c-12.76971517 0-24.13100586-7.79328205-28.82575409-19.62404756l-48.91927648-123.9413531c-18.40341303-46.75969229-55.77360888-84.0359932-102.53330118-102.53330117l-123.94135309-48.91927649c-11.83076552-4.69474822-19.62404757-16.05603892-19.62404757-28.8257541s7.79328205-24.13100586 19.62404757-28.82575407l123.94135309-48.91927649c46.75969229-18.40341303 84.0359932-55.77360888 102.53330118-102.53330119l48.91927648-123.94135308c4.69474822-11.83076552 16.05603892-19.62404757 28.8257541-19.62404757s24.13100586 7.79328205 28.82575408 19.62404757l48.91927648 123.94135308c18.40341303 46.75969229 55.77360888 84.0359932 102.53330118 102.53330119l123.94135309 48.91927649c11.83076552 4.69474822 19.62404757 16.05603892 19.62404757 28.82575407 0 12.76971517-7.79328205 24.13100586-19.62404757 28.8257541l-123.94135309 48.91927649c-46.75969229 18.40341303-84.0359932 55.77360888-102.53330118 102.53330117l-48.91927648 123.9413531c-4.69474822 11.83076552-16.14993388 19.62404757-28.82575408 19.62404756zM177.45224165 390.12433614l50.89107073 20.0935224c62.62794129 24.69437565 112.67395736 74.74039171 137.368333 137.36833299l20.09352239 50.89107073 20.0935224-50.89107073c24.69437565-62.62794129 74.74039171-112.67395736 137.368333-137.36833299l50.89107072-20.0935224-50.89107073-20.09352239c-62.62794129-24.69437565-112.67395736-74.74039171-137.36833299-137.36833301l-20.09352239-50.89107074-20.0935224 50.89107074c-24.69437565 62.62794129-74.74039171 112.67395736-137.368333 137.36833301l-50.89107073 20.09352239zM771.33789183 957.62550131c-12.76971517 0-24.13100586-7.79328205-28.82575409-19.62404758l-26.6661699-67.6043744c-8.63833672-21.87752672-26.10280012-39.34199011-47.98032684-47.98032684l-67.60437441-26.6661699c-11.83076552-4.69474822-19.62404757-16.05603892-19.62404757-28.82575409s7.79328205-24.13100586 19.62404757-28.82575409l67.60437441-26.6661699c21.87752672-8.63833672 39.34199011-26.10280012 47.98032684-47.98032685l26.6661699-67.6043744c4.69474822-11.83076552 16.05603892-19.62404757 28.82575409-19.62404757s24.13100586 7.79328205 28.82575409 19.62404757l26.66616991 67.6043744c8.63833672 21.87752672 26.10280012 39.34199011 47.98032684 47.98032685l67.6043744 26.6661699c11.83076552 4.69474822 19.62404757 16.05603892 19.62404757 28.82575409s-7.79328205 24.13100586-19.62404757 28.82575409l-67.6043744 26.6661699c-21.87752672 8.63833672-39.34199011 26.10280012-47.98032684 47.98032684l-26.66616991 67.6043744c-4.69474822 11.83076552-16.14993388 19.62404757-28.82575409 19.62404758z m-75.58544639-190.70067281c33.61439727 14.83540438 60.75004201 41.87715415 75.49155143 75.49155143 14.83540438-33.61439727 41.87715415-60.75004201 75.49155142-75.49155143-33.61439727-14.83540438-60.75004201-41.87715415-75.49155142-75.49155143-14.74150942 33.61439727-41.87715415 60.75004201-75.49155143 75.49155143z"/>
                </svg>
            </div>
        </div>
    </transition>
</template>

<script>
import {mapState} from "vuex";
import emitter from "../../store/events";
import {withLanguagePreferencePrompt} from "../../utils/ai";
import {getPageContext, getSceneKey} from "./page-context";
import {createOperationModule} from "./operation-module";

export default {
    name: 'AIAssistantFloatButton',

    data() {
        return {
            // 位置存储：只保存两个距离（水平一个、垂直一个）
            position: {
                x: 24,          // 水平距离值
                y: 24,          // 垂直距离值
                fromRight: true,  // true: 距右边, false: 距左边
                fromBottom: true, // true: 距底部, false: 距顶部
                collapsed: false, // 是否收起到边缘
            },
            dragging: false,
            positionLoaded: false,
            cacheKey: 'aiAssistant.floatButtonPosition',
            btnSize: 44,
            collapsedHeight: 48,  // 收起时的高度
            collapseThreshold: 12, // 触发收起的边缘距离阈值
            collapseDelay: 1000,   // 收起延迟（毫秒）
            collapseTimer: null,   // 收起定时器
            record: {},
            // 前端操作模块
            operationModule: null,
            operationSessionId: null,
        };
    },

    computed: {
        ...mapState(['microAppsIds']),

        aiInstalled() {
            return this.microAppsIds?.includes('ai');
        },

        visible() {
            return this.aiInstalled &&
                this.userId > 0 &&
                this.positionLoaded &&
                !this.windowPortrait &&
                this.routeName !== 'login' &&
                !this.$parent?.showModal;
        },

        collapsed() {
            return this.position.collapsed;
        },

        clientWidth() {
            return this.windowWidth || document.documentElement.clientWidth;
        },

        clientHeight() {
            return this.windowHeight || document.documentElement.clientHeight;
        },

        // wrapper 宽度：包含按钮所有可能位置（边缘到 position.x 的距离）
        wrapperWidth() {
            return this.btnSize + this.position.x;
        },

        // wrapper 左边位置
        wrapperLeft() {
            if (this.position.fromRight) {
                // 右侧：从 (屏幕宽 - wrapper宽度) 开始
                return this.clientWidth - this.wrapperWidth;
            }
            // 左侧：从 0 开始
            return 0;
        },

        wrapperTop() {
            // 基于按钮中心计算，确保收起/展开时中心位置不变
            const centerY = this.position.fromBottom
                ? this.clientHeight - this.btnSize / 2 - this.position.y
                : this.position.y + this.btnSize / 2;
            return centerY - this.wrapperHeight / 2;
        },

        wrapperClass() {
            return {
                'is-left': !this.position.fromRight,
                'is-right': this.position.fromRight,
                'is-dragging': this.dragging,
                'is-collapsed': this.collapsed,
            };
        },

        wrapperHeight() {
            return this.collapsed ? this.collapsedHeight : this.btnSize;
        },

        wrapperStyle() {
            return {
                left: `${this.wrapperLeft}px`,
                top: `${this.wrapperTop}px`,
                width: `${this.wrapperWidth}px`,
                height: `${this.wrapperHeight}px`,
                zIndex: this.$parent?.topZIndex || 2000,
            };
        },

        btnClass() {
            return {
                'is-collapsed': this.collapsed,
            };
        },

        btnStyle() {
            // 收起时按钮在边缘（不偏移），展开时向内偏移 position.x
            if (this.collapsed) {
                return { transform: 'translateX(0)' };
            }
            // 右侧向左偏移（负值），左侧向右偏移（正值）
            const offset = this.position.fromRight ? -this.position.x : this.position.x;
            return { transform: `translateX(${offset}px)` };
        },
    },

    mounted() {
        this.loadPosition();
        window.addEventListener('resize', this.onResize);
        emitter.on('openAIAssistantGlobal', this.onClick);
        emitter.on('aiAssistantClosed', this.onAssistantClosed);
        this.initOperationModule();
    },

    beforeDestroy() {
        window.removeEventListener('resize', this.onResize);
        emitter.off('openAIAssistantGlobal', this.onClick);
        emitter.off('aiAssistantClosed', this.onAssistantClosed);
        document.removeEventListener('mousemove', this.onMouseMove);
        document.removeEventListener('mouseup', this.onMouseUp);
        document.removeEventListener('contextmenu', this.onContextMenu);
        this.clearCollapseTimer();
        this.destroyOperationModule();
    },

    methods: {
        /**
         * 加载保存的位置
         */
        async loadPosition() {
            try {
                const saved = await $A.IDBString(this.cacheKey);
                if (saved) {
                    const pos = JSON.parse(saved);
                    if (pos && typeof pos.x === 'number' && typeof pos.y === 'number') {
                        // 兼容旧格式，确保有 collapsed 字段
                        this.position = {...pos, collapsed: pos.collapsed ?? false};
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
            this.position = {x: 24, y: 24, fromRight: true, fromBottom: true, collapsed: false};
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
            const centerX = left + this.btnSize / 2;
            const centerY = top + this.btnSize / 2;

            // 判断在哪个半区
            const fromRight = centerX >= this.clientWidth / 2;
            const fromBottom = centerY >= this.clientHeight / 2;

            // 计算距离
            const x = fromRight ? (this.clientWidth - this.btnSize - left) : left;
            const y = fromBottom ? (this.clientHeight - this.btnSize - top) : top;

            this.position = {x, y, fromRight, fromBottom, collapsed: this.position.collapsed};
        },

        /**
         * 鼠标按下
         */
        onMouseDown(e) {
            // 只响应鼠标左键
            if (e.button !== 0) return;

            // 收起状态下点击直接打开 AI 助手
            if (this.collapsed) {
                this.onClick();
                return;
            }

            // 按钮的实际视觉位置
            const btnRect = this.$refs.floatBtn.getBoundingClientRect();
            this.record = {
                time: Date.now(),
                startLeft: btnRect.left,
                startTop: btnRect.top,
                offsetX: e.clientX - btnRect.left,
                offsetY: e.clientY - btnRect.top,
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
            newLeft = Math.max(minMargin, Math.min(newLeft, this.clientWidth - this.btnSize - minMargin));
            newTop = Math.max(minMargin, Math.min(newTop, this.clientHeight - this.btnSize - minMargin));

            this.updatePositionFromCoords(newLeft, newTop);
        },

        /**
         * 鼠标松开
         */
        onMouseUp() {
            document.removeEventListener('mousemove', this.onMouseMove);
            document.removeEventListener('mouseup', this.onMouseUp);
            document.removeEventListener('contextmenu', this.onContextMenu);

            const btnRect = this.$refs.floatBtn.getBoundingClientRect();
            const moveDistance = Math.abs(btnRect.left - this.record.startLeft) + Math.abs(btnRect.top - this.record.startTop);
            const duration = Date.now() - this.record.time;

            this.savePosition();
            this.dragging = false;

            // 判断是否为点击（移动距离小于5px 且 按下时间小于200ms）
            if (moveDistance < 5 && duration < 200) {
                this.onClick();
            }

            // 检测是否靠近左右边缘，延迟收起
            this.scheduleCollapse();
        },

        /**
         * 鼠标进入
         */
        onMouseEnter() {
            this.clearCollapseTimer();
            // 如果是收起状态，展开
            if (this.collapsed) {
                this.position.collapsed = false;
                this.savePosition();
            }
        },

        /**
         * 鼠标离开
         */
        onMouseLeave() {
            // 拖拽期间不处理
            if (this.dragging) return;
            // 延迟检测是否需要收起
            this.scheduleCollapse();
        },

        /**
         * 计划收起（延迟执行）
         */
        scheduleCollapse() {
            this.clearCollapseTimer();
            // 检测是否靠近边缘
            if (this.position.x <= this.collapseThreshold) {
                this.collapseTimer = setTimeout(() => {
                    this.position.collapsed = true;
                    this.savePosition();
                }, this.collapseDelay);
            }
        },

        /**
         * 清除收起定时器
         */
        clearCollapseTimer() {
            if (this.collapseTimer) {
                clearTimeout(this.collapseTimer);
                this.collapseTimer = null;
            }
        },

        /**
         * 检查边界（仅在加载和窗口变化时调用）
         */
        checkBounds() {
            const minMargin = 12;
            // 确保距离在有效范围内（最小12px，最大不超出屏幕）
            const maxX = this.clientWidth - this.btnSize - minMargin;
            const maxY = this.clientHeight - this.btnSize - minMargin;
            this.position.x = Math.max(minMargin, Math.min(this.position.x, maxX));
            this.position.y = Math.max(minMargin, Math.min(this.position.y, maxY));
        },

        /**
         * 窗口大小改变
         */
        onResize() {
            this.$nextTick(() => {
                this.checkBounds();
            });
        },

        /**
         * 点击按钮
         */
        onClick() {
            const routeParams = this.$route?.params || {};
            const sceneKey = getSceneKey(this.$store, routeParams);

            // 启用前端操作模块
            this.enableOperationModule();

            emitter.emit('openAIAssistant', {
                displayMode: 'chat',
                sessionKey: 'global',
                sceneKey,
                resumeSession: 86400,
                showApplyButton: false,
                onBeforeSend: this.handleBeforeSend,
            });
        },

        /**
         * AI 助手关闭事件
         */
        onAssistantClosed() {
            this.disableOperationModule();
        },

        /**
         * 处理发送前的上下文准备
         * 在发送时动态获取当前页面上下文，确保上下文与用户当前所在页面一致
         * @param context - 对话历史上下文
         * @returns {(string|*)[][]}
         */
        handleBeforeSend(context = []) {
            const routeParams = this.$route?.params || {};
            const {systemPrompt} = getPageContext(this.$store, routeParams);

            // 添加操作会话信息
            let operationContext = '';
            if (this.operationSessionId) {
                operationContext = `\n\n前端操作会话已建立，session_id: ${this.operationSessionId}。你可以使用 get_page_context、execute_action、execute_element_action 工具直接操作用户的页面。`;
            }

            const prepared = [
                ['system', withLanguagePreferencePrompt(systemPrompt + operationContext)],
            ];

            if (context.length > 0) {
                prepared.push(...context);
            }

            return prepared;
        },

        /**
         * 初始化操作模块
         */
        initOperationModule() {
            if (this.operationModule) {
                return;
            }

            this.operationModule = createOperationModule({
                store: this.$store,
                router: this.$router,
                onSessionReady: (sessionId) => {
                    this.operationSessionId = sessionId;
                },
                onSessionLost: () => {
                    this.operationSessionId = null;
                },
            });
        },

        /**
         * 启用操作模块
         */
        enableOperationModule() {
            if (this.operationModule) {
                this.operationModule.enable();
            }
        },

        /**
         * 禁用操作模块
         */
        disableOperationModule() {
            if (this.operationModule) {
                this.operationModule.disable();
                this.operationSessionId = null;
            }
        },

        /**
         * 销毁操作模块
         */
        destroyOperationModule() {
            if (this.operationModule) {
                this.operationModule.disable();
                this.operationModule = null;
                this.operationSessionId = null;
            }
        },
    },
};
</script>

<style lang="scss">
$btn-size: 44px;
$collapsed-width: 12px;
$collapsed-height: 48px;

.ai-float-button-wrapper {
    position: fixed;
    display: flex;
    align-items: center;

    // 右侧：按钮默认在右边（屏幕边缘），展开时通过 transform 向左移动
    &.is-right {
        justify-content: flex-end;
    }

    // 左侧：按钮默认在左边（屏幕边缘），展开时通过 transform 向右移动
    &.is-left {
        justify-content: flex-start;
    }

    &.is-dragging {
        .ai-float-button {
            transition: none !important;
        }
    }
}

.ai-float-button {
    width: $btn-size;
    height: $btn-size;
    border-radius: 50%;
    background: #8bcf70;
    box-shadow: 0 4px 12px lch(77 53.3 131.54 / 0.4);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.25s ease-out, box-shadow 0.2s, width 0.25s ease-out, height 0.25s ease-out, border-radius 0.25s ease-out;
    user-select: none;
    flex-shrink: 0;

    &:not(.is-collapsed):hover {
        box-shadow: 0 6px 16px lch(77 53.3 131.54 / 0.5);
    }

    .ai-float-button-icon {
        width: 24px;
        height: 24px;
        fill: #fff;
    }

    // 收起状态
    &.is-collapsed {
        width: $collapsed-width;
        height: $collapsed-height;
        box-shadow: 0 2px 8px lch(77 53.3 131.54 / 0.3);

        .ai-float-button-icon {
            display: none;
        }
    }

    .is-left &.is-collapsed {
        border-radius: 0 6px 6px 0;
    }

    .is-right &.is-collapsed {
        border-radius: 6px 0 0 6px;
    }
}

body.dark-mode-reverse {
    .ai-float-button {
        box-shadow: none;
        &:hover {
            box-shadow: none;
        }
    }
}
</style>
