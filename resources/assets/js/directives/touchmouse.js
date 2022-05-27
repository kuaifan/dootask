const isSupportTouch = "ontouchend" in document;
export default {
    bind (el, binding) {
        let isTouch = false;
        el.__touchMouseDown__ = e => {
            e.preventDefault();
            isTouch = true;
            binding.value("down", e);
        };
        el.__touchMouseMove__ = e => {
            if (isTouch) {
                binding.value("move", e);
            }
        };
        el.__touchMouseUp__ = _ => {
            if (isTouch) {
                isTouch = false;
                binding.value("up");
            }
        };
        el.addEventListener(isSupportTouch ? 'touchstart' : 'mousedown', el.__touchMouseDown__);
        document.addEventListener(isSupportTouch ? 'touchmove' : 'mousemove', el.__touchMouseMove__);
        document.addEventListener(isSupportTouch ? 'touchend' : 'mouseup', el.__touchMouseUp__);
    },
    update () {

    },
    unbind (el) {
        el.removeEventListener(isSupportTouch ? 'touchstart' : 'mousedown', el.__touchMouseDown__);
        document.removeEventListener(isSupportTouch ? 'touchmove' : 'mousemove', el.__touchMouseMove__);
        document.removeEventListener(isSupportTouch ? 'touchend' : 'mouseup', el.__touchMouseUp__);
        delete el.__touchMouseDown__;
        delete el.__touchMouseMove__;
        delete el.__touchMouseUp__;
    }
};
