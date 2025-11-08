import html2md from 'html-to-md'

const cutText = (text, limit = 60, ellipsis = '...') => {
    const value = (text || '').trim();
    if (!value) {
        return '';
    }
    const units = Array.from(value);
    if (units.length <= limit) {
        return value;
    }
    return units.slice(0, limit).join('') + ellipsis;
}

const extractPlainText = (content, cutLength = null, convertHtmlToMarkdownMode = false) => {
    if (!content) {
        return '';
    }
    const value = typeof content === 'string' ? content : JSON.stringify(content);
    if (convertHtmlToMarkdownMode) {
        const newValue = html2md(value).trim();
        return cutLength ? cutText(newValue, cutLength) : newValue;
    }
    if (typeof window === 'undefined' || !window.document) {
        const newValue = value.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        return cutLength ? cutText(newValue, cutLength) : newValue;
    }
    const div = document.createElement('div');
    div.innerHTML = value;
    const newValue = (div.textContent || div.innerText || '').replace(/\s+/g, ' ').trim();
    return cutLength ? cutText(newValue, cutLength) : newValue;
}

export {cutText, extractPlainText}
