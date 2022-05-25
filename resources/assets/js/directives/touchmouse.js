const isSupportTouch = "ontouchend" in document;
export default {
    bind (el, binding) {
        let isMove = false;
        el.__touchMouseDown__ = e => {
            isMove = false;
            e.preventDefault();
        };
        el.__touchMouseMove__ = _ => {
            isMove = true;
        };
        el.__touchMouseUp__ = e => {
            if (isMove) {
                return;
            }
            if (binding.expression) {
                binding.value(e);
            }
        };
        el.addEventListener(isSupportTouch ? 'touchstart' : 'mousedown', el.__touchMouseDown__);
        el.addEventListener(isSupportTouch ? 'touchmove' : 'mousemove', el.__touchMouseMove__);
        el.addEventListener(isSupportTouch ? 'touchend' : 'mouseup', el.__touchMouseUp__);
    },
    update () {

    },
    unbind (el) {
        el.removeEventListener(isSupportTouch ? 'touchstart' : 'mousedown', el.__touchMouseDown__);
        el.removeEventListener(isSupportTouch ? 'touchmove' : 'mousemove', el.__touchMouseMove__);
        el.removeEventListener(isSupportTouch ? 'touchend' : 'mouseup', el.__touchMouseUp__);
        delete el.__touchMouseDown__;
        delete el.__touchMouseMove__;
        delete el.__touchMouseUp__;
    }
};
