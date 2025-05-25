/**
 * Emoji正则表达式
 * @type {RegExp}
 */
const emojiRegex = /(?:[\u2700-\u27bf]|(?:\ud83c[\udde6-\uddff]){2}|[\ud800-\udbff][\udc00-\udfff]|[\u0023-\u0039]\ufe0f?\u20e3|\u3299|\u3297|\u303d|\u3030|\u24c2|\ud83c[\udd70-\udd71]|\ud83c[\udd7e-\udd7f]|\ud83c\udd8e|\ud83c[\udd91-\udd9a]|\ud83c[\udde6-\uddff]|\ud83c[\ude01-\ude02]|\ud83c\ude1a|\ud83c\ude2f|\ud83c[\ude32-\ude3a]|\ud83c[\ude50-\ude51]|\u203c|\u2049|[\u25aa-\u25ab]|\u25b6|\u25c0|[\u25fb-\u25fe]|\u00a9|\u00ae|\u2122|\u2139|\ud83c\udc04|[\u2600-\u26FF]|\u2b05|\u2b06|\u2b07|\u2b1b|\u2b1c|\u2b50|\u2b55|\u231a|\u231b|\u2328|\u23cf|[\u23e9-\u23f3]|[\u23f8-\u23fa]|\ud83c\udccf|\u2934|\u2935|[\u2190-\u21ff])/g;

/**
 * 检查文本是否包含emoji
 * @param {string} text - 要检查的文本
 * @returns {boolean} - 是否包含emoji
 */
const containsEmoji = (text) => {
    emojiRegex.lastIndex = 0;
    return emojiRegex.test(text);
}

/**
 * 将文本中的emoji转换成HTML标签
 * @param {string} text - 输入文本
 * @param {string} className - 添加给emoji的类名，默认为'emoji-original'
 * @param {string} tagName - 包裹emoji的标签名，默认为'span'
 * @returns {string} - 转换后的HTML字符串
 *
 * 示例:
 * transformEmojiToHtml("我❤️你", "heart", "span")
 * // 返回: "我<span class=\"heart\">❤️</span>你"
 */
export default function transformEmojiToHtml(text, className = 'emoji-original', tagName = 'span') {
    // 参数验证
    if (typeof text !== 'string') return '';
    if (!className || typeof className !== 'string') className = 'emoji-original';
    if (!tagName || typeof tagName !== 'string') tagName = 'span';

    // 快速检查是否包含emoji
    if (!containsEmoji(text)) return text;

    // 重置正则索引并准备替换
    emojiRegex.lastIndex = 0;
    let result = '';
    let lastIndex = 0;
    let match;

    // 逐个匹配emoji并替换
    while ((match = emojiRegex.exec(text)) !== null) {
        // 添加emoji前的文本
        if (match.index > lastIndex) {
            result += text.substring(lastIndex, match.index);
        }

        // 添加包装后的emoji
        result += `<${tagName} class="${className}">${match[0]}</${tagName}>`;

        lastIndex = emojiRegex.lastIndex;
    }

    // 添加剩余文本
    if (lastIndex < text.length) {
        result += text.substring(lastIndex);
    }

    return result;
}
