export function extractPlainText(content) {
    if (!content) {
        return '';
    }
    const value = typeof content === 'string' ? content : JSON.stringify(content);
    if (typeof window === 'undefined' || !window.document) {
        return value.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
    }
    const div = document.createElement('div');
    div.innerHTML = value;
    return (div.textContent || div.innerText || '').replace(/\s+/g, ' ').trim();
}

