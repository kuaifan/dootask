// 滚动超出隐藏
const scrollActive = {
    bind: function (el, binding) {
        let element = binding.value;
        let activeClass = 'active';
        let inactiveClass = 'inactive';
        el.__vueScrollActiveMain__ = null;
        if ($A.isJson(binding.value)) {
            if (binding.value.main) {
                el.__vueScrollActiveMain__ = binding.value.main
            }
            if (binding.value.element) {
                element = binding.value.element;
            }
            if (binding.value.activeClass) {
                activeClass = binding.value.activeClass;
            }
            if (binding.value.inactiveClass) {
                inactiveClass = binding.value.inactiveClass;
            }
        }
        if (!element) {
            return;
        }
        const main = el.__vueScrollActiveMain__ ? el.querySelector(el.__vueScrollActiveMain__) : el;
        //
        el.__vueScrollActiveListener__ = (e) => {
            const containerRect = e.target.getBoundingClientRect()
            const items = e.target.querySelectorAll(element)
            items.forEach((item, index) => {
                const itemRect = item.getBoundingClientRect()
                if (
                    itemRect.top < containerRect.bottom && itemRect.bottom > containerRect.top &&
                    itemRect.left < containerRect.right && itemRect.right > containerRect.left
                ) {
                    activeClass && item.classList.add(activeClass)
                    inactiveClass && item.classList.remove(inactiveClass)
                } else {
                    activeClass && item.classList.remove(activeClass)
                    inactiveClass && item.classList.add(inactiveClass)
                }
            })
        }
        main.addEventListener("scroll", el.__vueScrollActiveListener__);
        //
        let initialHeight = 0;
        let initialWidth = 0;
        el.__vueScrollActiveObserver__ = new MutationObserver(function (mutations) {
            // 遍历每个发生变化的 mutation
            mutations.forEach(function (mutation) {
                if (mutation.type === "childList") {
                    const childElements = main.querySelectorAll(element)
                    if (childElements.length === 0) {
                        return;
                    }
                    const lastSpecifiedElement = childElements[childElements.length - 1].getBoundingClientRect();
                    const lastWidth = lastSpecifiedElement.right;
                    const lastHeight = lastSpecifiedElement.bottom;
                    if (lastHeight != initialHeight || lastWidth != initialWidth) {
                        initialHeight = lastHeight;
                        initialWidth = lastWidth;
                        el.__vueScrollActiveListener__({target: el});
                    }
                }
            });
        });
        el.__vueScrollActiveObserver__.observe(main, { childList: true, subtree: true });
    },

    // 指令与元素解绑的时候，移除事件绑定
    unbind(el) {
        const main = el.__vueScrollActiveMain__ ? el.querySelector(el.__vueScrollActiveMain__) : el;
        main.removeEventListener('scroll', el.__vueScrollActiveListener__);
        delete el.__vueScrollActiveListener__;
        //
        el.__vueScrollActiveObserver__.disconnect();
        delete el.__vueScrollActiveObserver__;
    }
}

export default scrollActive
