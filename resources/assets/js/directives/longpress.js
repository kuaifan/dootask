const isSupportTouch = "ontouchend" in document;

// 长按或右键指令
const longpress = {
    bind: function (el, binding) {
        let delay = 500,
            mode = 'default',
            isCall = false,
            pressTimer = null,
            callback = binding.value;
        if ($A.isJson(binding.value)) {
            delay = binding.value.delay || 500;
            callback = binding.value.callback;
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
        el.addEventListener('touchend', el.__longpressCancel__)
        el.addEventListener('touchcancel', el.__longpressCancel__)
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
        el.removeEventListener('touchend', el.__longpressCancel__)
        el.removeEventListener('touchcancel', el.__longpressCancel__)
        delete el.__longpressStart__
        delete el.__longpressClick__
        delete el.__longpressCancel__
    }
}

export default longpress
