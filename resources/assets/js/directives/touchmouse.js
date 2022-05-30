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
        if (isSupportTouch) {
            el.addEventListener('touchstart', el.__touchMouseDown__);
            el.addEventListener('touchmove', el.__touchMouseMove__);
            el.addEventListener('touchend', el.__touchMouseUp__);
        } else {
            el.addEventListener('mousedown', el.__touchMouseDown__);
            document.addEventListener('mousemove', el.__touchMouseMove__);
            document.addEventListener('mouseup', el.__touchMouseUp__);
        }
    },
    update () {

    },
    unbind (el) {
        if (isSupportTouch) {
            el.removeEventListener('touchstart', el.__touchMouseDown__);
            el.removeEventListener('touchmove', el.__touchMouseMove__);
            el.removeEventListener('touchend', el.__touchMouseUp__);
        } else {
            el.removeEventListener('mousedown', el.__touchMouseDown__);
            document.removeEventListener('mousemove', el.__touchMouseMove__);
            document.removeEventListener('mouseup', el.__touchMouseUp__);
        }
        delete el.__touchMouseDown__;
        delete el.__touchMouseMove__;
        delete el.__touchMouseUp__;
    }
};
