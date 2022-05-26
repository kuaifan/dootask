const isSupportTouch = "ontouchend" in document;
export default {
    bind (el, binding) {
        if (!isSupportTouch) {
            return
        }
        let timer = 0;
        el.__touchLongpressDown__ = e => {
            timer = setTimeout(_ => {
                timer = 0
                if (binding.expression) {
                    binding.value(e, el)
                }
            }, 600)
        };
        el.__touchLongpressMove__ = _ => {
            if (timer) {
                clearTimeout(timer)
                timer = 0
            }
        };
        el.__touchLongpressUp__ = _ => {
            if (timer) {
                clearTimeout(timer)
                timer = 0
            }
        };
        el.addEventListener('touchstart', el.__touchLongpressDown__);
        el.addEventListener('touchmove', el.__touchLongpressMove__);
        el.addEventListener('touchend', el.__touchLongpressUp__);
    },
    update () {

    },
    unbind (el) {
        if (!isSupportTouch) {
            return
        }
        el.removeEventListener('touchstart', el.__touchLongpressDown__);
        el.removeEventListener('touchmove', el.__touchLongpressMove__);
        el.removeEventListener('touchend', el.__touchLongpressUp__);
        delete el.__touchLongpressDown__;
        delete el.__touchLongpressMove__;
        delete el.__touchLongpressUp__;
    }
};
