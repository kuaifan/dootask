/**
 * Color utilities for parsing and inverting colors.
 */

export function colorReverse(colorValue) {
    const rgba = parseToRgba(colorValue);
    if (!rgba) {
        return colorValue;
    }
    const inverted = {
        r: 255 - rgba.r,
        g: 255 - rgba.g,
        b: 255 - rgba.b,
        a: typeof rgba.a === 'number' ? rgba.a : 1
    };
    return rgbaToHex(inverted);
}

export function parseToRgba(colorValue) {
    if (!colorValue || typeof colorValue !== 'string') {
        return null;
    }
    const value = colorValue.trim();
    return parseHex(value) || parseRgb(value) || parseHsl(value);
}

export function parseHex(value) {
    const hex = value.replace(/^#/, '').toLowerCase();
    if (![3, 4, 6, 8].includes(hex.length) || /[^a-f0-9]/.test(hex)) {
        return null;
    }
    const normalized = hex.length === 3 || hex.length === 4
        ? hex.split('').map(char => char + char).join('')
        : hex;
    const hasAlpha = normalized.length === 8;
    const r = parseInt(normalized.slice(0, 2), 16);
    const g = parseInt(normalized.slice(2, 4), 16);
    const b = parseInt(normalized.slice(4, 6), 16);
    const a = hasAlpha ? parseInt(normalized.slice(6, 8), 16) / 255 : 1;
    return {r, g, b, a};
}

export function parseRgb(value) {
    const match = value.match(/rgba?\(\s*([\d.]+%?)\s*,\s*([\d.]+%?)\s*,\s*([\d.]+%?)(?:\s*,\s*([\d.]+%?))?\s*\)/i);
    if (!match) {
        return null;
    }
    const channels = match.slice(1, 4).map(item => normalizeChannel(item));
    if (channels.some(item => item === null)) {
        return null;
    }
    const alpha = typeof match[4] !== 'undefined' ? normalizeAlpha(match[4]) : 1;
    return {r: channels[0], g: channels[1], b: channels[2], a: alpha};
}

export function parseHsl(value) {
    const match = value.match(/hsla?\(\s*([\d.]+)\s*,\s*([\d.]+)%\s*,\s*([\d.]+)%(?:\s*,\s*([\d.]+%?))?\s*\)/i);
    if (!match) {
        return null;
    }
    const h = ((parseFloat(match[1]) % 360) + 360) % 360;
    const s = parseFloat(match[2]) / 100;
    const l = parseFloat(match[3]) / 100;
    const alpha = typeof match[4] !== 'undefined' ? normalizeAlpha(match[4]) : 1;
    const {r, g, b} = hslToRgb(h, s, l);
    return {r, g, b, a: alpha};
}

export function normalizeChannel(value) {
    if (value.indexOf('%') > -1) {
        const num = parseFloat(value);
        if (isNaN(num)) {
            return null;
        }
        return Math.round(Math.max(0, Math.min(100, num)) * 2.55);
    }
    const num = parseFloat(value);
    if (isNaN(num)) {
        return null;
    }
    return Math.round(Math.max(0, Math.min(255, num)));
}

export function normalizeAlpha(value) {
    if (typeof value === 'undefined') {
        return 1;
    }
    if (value.indexOf('%') > -1) {
        const num = parseFloat(value);
        if (isNaN(num)) {
            return 1;
        }
        return Math.max(0, Math.min(100, num)) / 100;
    }
    const num = parseFloat(value);
    if (isNaN(num)) {
        return 1;
    }
    return Math.max(0, Math.min(1, num));
}

export function hslToRgb(h, s, l) {
    const c = (1 - Math.abs(2 * l - 1)) * s;
    const x = c * (1 - Math.abs((h / 60) % 2 - 1));
    const m = l - c / 2;
    let rPrime = 0;
    let gPrime = 0;
    let bPrime = 0;

    if (h < 60) {
        rPrime = c;
        gPrime = x;
    } else if (h < 120) {
        rPrime = x;
        gPrime = c;
    } else if (h < 180) {
        gPrime = c;
        bPrime = x;
    } else if (h < 240) {
        gPrime = x;
        bPrime = c;
    } else if (h < 300) {
        rPrime = x;
        bPrime = c;
    } else {
        rPrime = c;
        bPrime = x;
    }

    return {
        r: Math.round((rPrime + m) * 255),
        g: Math.round((gPrime + m) * 255),
        b: Math.round((bPrime + m) * 255)
    };
}

export function rgbaToHex({r, g, b, a = 1}) {
    const toHex = (value) => {
        const clamped = Math.max(0, Math.min(255, Math.round(value)));
        return clamped.toString(16).padStart(2, '0');
    };
    const alpha = Math.max(0, Math.min(1, a));
    const alphaHex = alpha < 1 ? toHex(alpha * 255) : '';
    return `#${toHex(r)}${toHex(g)}${toHex(b)}${alphaHex}`;
}
