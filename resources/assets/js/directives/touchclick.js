const isSupportTouch = "ontouchend" in document;
export default {
    bind (el, binding) {
        if (isSupportTouch) {
            const touchData = {
                move: false,
                time: 0,
                x: 0,
                y: 0,
            };
            el.__touchEvent__ = {
                start: e => {
                    e.preventDefault();
                    touchData.move = false;
                    touchData.time = new Date().getTime();
                    touchData.x = e.touches ? e.touches[0].clientX : e.clientX;
                    touchData.y = e.touches ? e.touches[0].clientY : e.clientY;
                },
                move: e => {
                    if (touchData.time > 0) {
                        const x = e.touches ? e.touches[0].clientX : e.clientX;
                        const y = e.touches ? e.touches[0].clientY : e.clientY;
                        if (Math.abs(x - touchData.x) > 5 || Math.abs(y - touchData.y) > 5) {
                            touchData.move = true;
                        }
                    }
                },
                end: _ => {
                    if (touchData.time > 0) {
                        if (!touchData.move && new Date().getTime() - touchData.time < 300) {
                            binding.value();
                        }
                        touchData.time = 0;
                    }
                }
            };
            el.addEventListener('touchstart', el.__touchEvent__.start);
            el.addEventListener('touchmove', el.__touchEvent__.move);
            el.addEventListener('touchend', el.__touchEvent__.end);
        } else {
            el.__clickEvent__ = e => {
                e.preventDefault();
                binding.value();
            };
            el.addEventListener('click', el.__clickEvent__);
        }
    },
    update () {

    },
    unbind (el) {
        if (isSupportTouch) {
            el.removeEventListener('touchstart', el.__touchEvent__.start);
            el.removeEventListener('touchmove', el.__touchEvent__.move);
            el.removeEventListener('touchend', el.__touchEvent__.end);
            delete el.__touchEvent__;
        } else {
            el.removeEventListener('click', el.__clickEvent__);
            delete el.__clickEvent__;
        }
    }
};
