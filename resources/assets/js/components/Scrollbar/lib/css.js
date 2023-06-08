export function get(element) {
    if (element) {
        return getComputedStyle(element);
    }
    return {};
}

export function set(element, obj) {
    if (element) {
        for (const key in obj) {
            let val = obj[key];
            if (typeof val === 'number') {
                val = `${val}px`;
            }
            element.style[key] = val;
        }
    }
    return element;
}
