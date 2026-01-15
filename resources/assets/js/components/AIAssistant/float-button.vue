<template>
    <transition name="fade">
        <div
            v-if="visible"
            ref="floatBtn"
            class="ai-float-button no-dark-content"
            :style="btnStyle"
            @mousedown.stop.prevent="onMouseDown">
            <svg viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg">
                <path d="M385.80516777 713.87417358c-12.76971517 0-24.13100586-7.79328205-28.82575409-19.62404756l-48.91927648-123.9413531c-18.40341303-46.75969229-55.77360888-84.0359932-102.53330118-102.53330117l-123.94135309-48.91927649c-11.83076552-4.69474822-19.62404757-16.05603892-19.62404757-28.8257541s7.79328205-24.13100586 19.62404757-28.82575407l123.94135309-48.91927649c46.75969229-18.40341303 84.0359932-55.77360888 102.53330118-102.53330119l48.91927648-123.94135308c4.69474822-11.83076552 16.05603892-19.62404757 28.8257541-19.62404757s24.13100586 7.79328205 28.82575408 19.62404757l48.91927648 123.94135308c18.40341303 46.75969229 55.77360888 84.0359932 102.53330118 102.53330119l123.94135309 48.91927649c11.83076552 4.69474822 19.62404757 16.05603892 19.62404757 28.82575407 0 12.76971517-7.79328205 24.13100586-19.62404757 28.8257541l-123.94135309 48.91927649c-46.75969229 18.40341303-84.0359932 55.77360888-102.53330118 102.53330117l-48.91927648 123.9413531c-4.69474822 11.83076552-16.14993388 19.62404757-28.82575408 19.62404756zM177.45224165 390.12433614l50.89107073 20.0935224c62.62794129 24.69437565 112.67395736 74.74039171 137.368333 137.36833299l20.09352239 50.89107073 20.0935224-50.89107073c24.69437565-62.62794129 74.74039171-112.67395736 137.368333-137.36833299l50.89107072-20.0935224-50.89107073-20.09352239c-62.62794129-24.69437565-112.67395736-74.74039171-137.36833299-137.36833301l-20.09352239-50.89107074-20.0935224 50.89107074c-24.69437565 62.62794129-74.74039171 112.67395736-137.368333 137.36833301l-50.89107073 20.09352239zM771.33789183 957.62550131c-12.76971517 0-24.13100586-7.79328205-28.82575409-19.62404758l-26.6661699-67.6043744c-8.63833672-21.87752672-26.10280012-39.34199011-47.98032684-47.98032684l-67.60437441-26.6661699c-11.83076552-4.69474822-19.62404757-16.05603892-19.62404757-28.82575409s7.79328205-24.13100586 19.62404757-28.82575409l67.60437441-26.6661699c21.87752672-8.63833672 39.34199011-26.10280012 47.98032684-47.98032685l26.6661699-67.6043744c4.69474822-11.83076552 16.05603892-19.62404757 28.82575409-19.62404757s24.13100586 7.79328205 28.82575409 19.62404757l26.66616991 67.6043744c8.63833672 21.87752672 26.10280012 39.34199011 47.98032684 47.98032685l67.6043744 26.6661699c11.83076552 4.69474822 19.62404757 16.05603892 19.62404757 28.82575409s-7.79328205 24.13100586-19.62404757 28.82575409l-67.6043744 26.6661699c-21.87752672 8.63833672-39.34199011 26.10280012-47.98032684 47.98032684l-26.66616991 67.6043744c-4.69474822 11.83076552-16.14993388 19.62404757-28.82575409 19.62404758z m-75.58544639-190.70067281c33.61439727 14.83540438 60.75004201 41.87715415 75.49155143 75.49155143 14.83540438-33.61439727 41.87715415-60.75004201 75.49155142-75.49155143-33.61439727-14.83540438-60.75004201-41.87715415-75.49155142-75.49155143-14.74150942 33.61439727-41.87715415 60.75004201-75.49155143 75.49155143z"/>
            </svg>
        </div>
    </transition>
</template>

<script>
import emitter from "../../store/events";
import {withLanguagePreferencePrompt} from "../../utils/ai";
import {getPageContext} from "./page-context";

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
            },
            dragging: false,
            positionLoaded: false,
            cacheKey: 'aiAssistant.floatButtonPosition',
            btnSize: 44,
            record: {},
        };
    },

    computed: {
        visible() {
            return this.userId > 0 &&
                this.positionLoaded &&
                !this.windowPortrait &&
                this.routeName !== 'login' &&
                !this.$parent?.showModal;
        },

        // 计算实际的 left 值
        left() {
            if (this.position.fromRight) {
                return this.clientWidth - this.btnSize - this.position.x;
            }
            return this.position.x;
        },

        // 计算实际的 top 值
        top() {
            if (this.position.fromBottom) {
                return this.clientHeight - this.btnSize - this.position.y;
            }
            return this.position.y;
        },

        btnStyle() {
            return {
                left: `${this.left}px`,
                top: `${this.top}px`,
                width: `${this.btnSize}px`,
                height: `${this.btnSize}px`,
            };
        },

        clientWidth() {
            return this.windowWidth || document.documentElement.clientWidth;
        },

        clientHeight() {
            return this.windowHeight || document.documentElement.clientHeight;
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
            const centerX = left + this.btnSize / 2;
            const centerY = top + this.btnSize / 2;

            // 判断在哪个半区
            const fromRight = centerX >= this.clientWidth / 2;
            const fromBottom = centerY >= this.clientHeight / 2;

            // 计算距离
            const x = fromRight ? (this.clientWidth - this.btnSize - left) : left;
            const y = fromBottom ? (this.clientHeight - this.btnSize - top) : top;

            this.position = {x, y, fromRight, fromBottom};
        },

        /**
         * 鼠标按下
         */
        onMouseDown(e) {
            // 只响应鼠标左键
            if (e.button !== 0) return;

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

            const moveDistance = Math.abs(this.left - this.record.startLeft) + Math.abs(this.top - this.record.startTop);
            const duration = Date.now() - this.record.time;

            this.savePosition();
            this.dragging = false;

            // 判断是否为点击（移动距离小于5px 或 按下时间小于200ms）
            if (moveDistance < 5 || duration < 200) {
                this.onClick();
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
            emitter.emit('openAIAssistant', {
                displayMode: 'chat',
                sessionKey: 'global',
                resumeSession: 300,
                showApplyButton: false,
                onBeforeSend: this.handleBeforeSend,
            });
        },

        /**
         * 处理发送前的上下文准备
         * 在发送时动态获取当前页面上下文，确保上下文与用户当前所在页面一致
         * @param context - 对话历史上下文
         * @returns {(string|*)[][]}
         */
        handleBeforeSend(context = []) {
            const {systemPrompt} = getPageContext(this.$store);

            const prepared = [
                ['system', withLanguagePreferencePrompt(systemPrompt)],
            ];

            if (context.length > 0) {
                prepared.push(...context);
            }

            return prepared;
        }
    },
};
</script>

<style lang="scss" scoped>
.ai-float-button {
    position: fixed;
    z-index: 1000;
    border-radius: 50%;
    background: #8bcf70;
    box-shadow: 0 4px 12px lch(77 53.3 131.54 / 0.4);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s, box-shadow 0.2s;
    user-select: none;

    &:hover {
        transform: scale(1.08);
        box-shadow: 0 6px 16px lch(77 53.3 131.54 / 0.5);
    }

    &:active {
        transform: scale(0.95);
    }

    svg {
        width: 24px;
        height: 24px;
        fill: #fff;
    }
}
</style>
