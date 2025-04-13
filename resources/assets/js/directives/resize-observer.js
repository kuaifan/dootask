/**
 * 尺寸变化监听指令
 *
 * 用法示例：
 * 1. 简单监听: v-resize-observer="handleResize"
 * 2. 配置选项: v-resize-observer="{ handler: handleResize, throttle: 200 }"
 *
 * @param {Function|Object} binding.value - 处理函数或配置选项
 * @param {Function} binding.value.handler - 处理回调函数
 * @param {Number} binding.value.throttle - 节流时间(毫秒)，默认：200
 */

import {throttle} from 'lodash';

export default {
    inserted(el, binding) {
        const handler = typeof binding.value === 'function'
            ? binding.value
            : binding.value?.handler;

        if (typeof handler !== 'function') {
            // console.warn('[v-resize-observer] 需要提供一个函数作为处理回调');
            return;
        }

        const throttleTime = typeof binding.value === 'object'
            ? binding.value.throttle || 200
            : 200;

        el._resizeHandler = throttle(entries => {
            const entry = entries[0];
            // 传递尺寸信息给回调函数
            handler({
                width: entry.contentRect.width,
                height: entry.contentRect.height,
                entry: entry
            });
        }, throttleTime);

        el._resizeObserver = new ResizeObserver(el._resizeHandler);
        el._resizeObserver.observe(el);
    },

    unbind(el) {
        if (el._resizeObserver) {
            el._resizeObserver.disconnect();
            el._resizeObserver = null;
        }
        if (el._resizeHandler) {
            el._resizeHandler.cancel && el._resizeHandler.cancel();
            el._resizeHandler = null;
        }
    }
};
