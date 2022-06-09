module.exports = {
    /**
     * 消息格式化处理
     * @param text
     * @param userid
     * @returns {string|*}
     */
    textMsgFormat(text, userid) {
        if (!text) {
            return ""
        }
        const atReg = new RegExp(`<span class="mention user" data-id="${userid}">`, "g")
        text = text.trim().replace(/(\n\x20*){3,}/g, "\n\n");
        text = text.replace(/&nbsp;/g, ' ')
        text = text.replace(/<p><\/p>/g, '<p><br/></p>')
        text = text.replace(/\{\{RemoteURL\}\}/g, $A.apiUrl('../'))
        text = text.replace(atReg, `<span class="mention me" data-id="${userid}">`)
        // 处理内容连接
        if (/https*:\/\//.test(text)) {
            text = text.split(/(<[^>]*>)/g).map(string => {
                if (string && !/<[^>]*>/.test(string)) {
                    string = string.replace(/(https*:\/\/)((\w|=|\?|\.|\/|&|-|:|\+|%|;)+)/g, "<a href=\"$1$2\" target=\"_blank\">$1$2</a>")
                }
                return string;
            }).join("")
        }
        // 处理图片显示尺寸
        const array = text.match(/<img\s+[^>]*?>/g);
        if (array) {
            const widthReg = new RegExp("width=\"(\\d+)\""),
                heightReg = new RegExp("height=\"(\\d+)\"")
            array.some(res => {
                const widthMatch = res.match(widthReg),
                    heightMatch = res.match(heightReg);
                if (widthMatch && heightMatch) {
                    const width = parseInt(widthMatch[1]),
                        height = parseInt(heightMatch[1]),
                        maxSize = res.indexOf("emoticon") > -1 ? 150 : 220;
                    const scale = $A.scaleToScale(width, height, maxSize, maxSize);
                    const value = res
                        .replace(widthReg, `original-width="${width}" width="${scale.width}"`)
                        .replace(heightReg, `original-height="${height}" height="${scale.height}"`)
                    text = text.replace(res, value)
                }
            })
        }
        return text;
    }
}
