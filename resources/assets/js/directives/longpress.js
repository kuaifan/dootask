const isSupportTouch = "ontouchend" in document;

// 长按或右键指令
const longpress = {
    bind: function (el, binding) {
        let mode = 'default',
            isCall = false,
            pressTimer = null,
            delay = 500, // 延迟时间，长按多久触发（毫秒）
            touchend = null, // 触摸结束回调
            callback = binding.value, // 回调函数：第一个参数是事件对象（点到的对象），第二个参数是元素对象（注册绑定的对象）
            preventEndEvent = false; // 是否触摸结束阻止（如果触发了callback）
        if ($A.isJson(binding.value)) {
            delay = binding.value.delay || 500;
            touchend = typeof binding.value.touchend === 'function' ? binding.value.touchend : touchend;
            callback = typeof binding.value.callback === 'function' ? binding.value.callback : callback;
            preventEndEvent = binding.value.preventEndEvent || false;
        }
        if (typeof callback !== 'function') {
            throw 'callback must be a function'
        }

        // 菜单键（右键）
        el.__longpressContextmenu__ = (e) => {
            e.preventDefault()
            e.stopPropagation()
            if (mode === 'default') {
                callback(e, el)
            }
        }
        el.addEventListener('contextmenu', el.__longpressContextmenu__);

        // 不支持touch
        if (!isSupportTouch) {
            return
        }

        // 创建计时器（ 500秒后执行函数 ）
        el.__longpressStart__ = (e) => {
            if (e.type === 'click' && e.button !== 0) {
                return
            }
            mode = 'touch'
            isCall = false
            if (pressTimer === null) {
                pressTimer = setTimeout(() => {
                    if (mode === 'touch') {
                        isCall = true
                        callback(e.touches[0], el)
                    }
                }, delay)
            }
        }

        // 取消计时器
        el.__longpressCancel__ = (e) => {
            if (pressTimer !== null) {
                clearTimeout(pressTimer)
                pressTimer = null
            }
            mode = 'default'
        }

        // 触摸结束
        el.__longpressEnd__ = (e) => {
            if (typeof touchend === 'function') {
                touchend(e, isCall)
            }
            if (isCall && preventEndEvent) {
                e.preventDefault()
                e.stopPropagation()
            }
            el.__longpressCancel__(e)
        }

        // 点击拦截
        el.__longpressClick__ = (e) => {
            if (isCall) {
                e.preventDefault()
                e.stopPropagation()
            }
            el.__longpressCancel__(e)
        }

        // 添加事件监听器
        el.addEventListener('touchstart', el.__longpressStart__)
        el.addEventListener('click', el.__longpressClick__)
        el.addEventListener('touchmove', el.__longpressCancel__)
        el.addEventListener('touchcancel', el.__longpressCancel__)
        el.addEventListener('touchend', el.__longpressEnd__)
    },
    // 指令与元素解绑的时候，移除事件绑定
    unbind(el) {
        el.removeEventListener('contextmenu', el.__longpressContextmenu__)
        delete el.__longpressContextmenu__
        if (!isSupportTouch) {
            return
        }
        el.removeEventListener('touchstart', el.__longpressStart__)
        el.removeEventListener('click', el.__longpressClick__)
        el.removeEventListener('touchmove', el.__longpressCancel__)
        el.removeEventListener('touchcancel', el.__longpressCancel__)
        el.removeEventListener('touchend', el.__longpressEnd__)
        delete el.__longpressStart__
        delete el.__longpressClick__
        delete el.__longpressCancel__
        delete el.__longpressEnd__
    }
}

export default longpress
