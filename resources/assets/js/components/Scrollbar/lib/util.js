export function toInt(x) {
    return parseInt(x, 10) || 0;
}

export const supportsTouch = typeof window !== 'undefined' &&
    ('ontouchstart' in window ||
        ('maxTouchPoints' in window.navigator &&
            window.navigator.maxTouchPoints > 0) ||
        (window.DocumentTouch && document instanceof window.DocumentTouch));
