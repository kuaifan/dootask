const isSupportTouch = "ontouchend" in document;
export default {
    bind (el, binding) {
        let isTouch = false;
        el.__touchEvent__ = {
            start: e => {
                e.preventDefault();
                isTouch = true;
                binding.value("down", e);
            },
            move: e => {
                if (isTouch) {
                    binding.value("move", e);
                }
            },
            end: _ => {
                if (isTouch) {
                    isTouch = false;
                    binding.value("up");
                }
            },
            click: e => {
                binding.value("click", e);
            }
        };
        if (isSupportTouch) {
            el.addEventListener('touchstart', el.__touchEvent__.start);
            el.addEventListener('touchmove', el.__touchEvent__.move);
            el.addEventListener('touchend', el.__touchEvent__.end);
        } else {
            el.addEventListener('mousedown', el.__touchEvent__.start, { passive: false });
            document.addEventListener('mousemove', el.__touchEvent__.move);
            document.addEventListener('mouseup', el.__touchEvent__.end);
        }
        el.addEventListener('click', el.__touchEvent__.click);
    },
    update () {

    },
    unbind (el) {
        if (isSupportTouch) {
            el.removeEventListener('touchstart', el.__touchEvent__.start);
            el.removeEventListener('touchmove', el.__touchEvent__.move);
            el.removeEventListener('touchend', el.__touchEvent__.end);
        } else {
            el.removeEventListener('mousedown', el.__touchEvent__.start);
            document.removeEventListener('mousemove', el.__touchEvent__.move);
            document.removeEventListener('mouseup', el.__touchEvent__.end);
        }
        el.removeEventListener('click', el.__touchEvent__.click);
        delete el.__touchEvent__;
    }
};
