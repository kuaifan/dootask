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
                    string = string.replace(/(https*:\/\/)((\w|=|\?|\.|\/|&|-|:|\+|%|;|#)+)/g, "<a href=\"$1$2\" target=\"_blank\">$1$2</a>")
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
    },

    /**
     * 获取文本消息图片
     * @param text
     * @returns {*[]}
     */
    textImagesInfo(text) {
        const baseUrl = $A.apiUrl('../');
        const array = text.match(new RegExp(`<img[^>]*?>`, "g"));
        const list = [];
        if (array) {
            const srcReg = new RegExp("src=([\"'])([^'\"]*)\\1"),
                widthReg = new RegExp("(original-)?width=\"(\\d+)\""),
                heightReg = new RegExp("(original-)?height=\"(\\d+)\"")
            array.some(res => {
                const srcMatch = res.match(srcReg),
                    widthMatch = res.match(widthReg),
                    heightMatch = res.match(heightReg);
                if (srcMatch) {
                    list.push({
                        src: srcMatch[2].replace(/\{\{RemoteURL\}\}/g, baseUrl),
                        width: widthMatch ? widthMatch[2] : -1,
                        height: heightMatch ? heightMatch[2] : -1,
                    })
                }
            })
        }
        return list;
    },

    /**
     * 消息简单描述
     * @param data
     * @returns {string|*}
     */
    msgSimpleDesc(data) {
        if ($A.isJson(data)) {
            switch (data.type) {
                case 'text':
                    return $A.getMsgTextPreview(data.msg.text)
                case 'record':
                    return `[${$A.L('语音')}]`
                case 'meeting':
                    return `[${$A.L('会议')}] ${data.msg.name}`
                case 'file':
                    if (data.msg.type == 'img') {
                        return `[${$A.L('图片')}]`
                    }
                    return `[${$A.L('文件')}] ${data.msg.name}`
                default:
                    return `[${$A.L('未知的消息')}]`
            }
        }
        return '';
    },

    /**
     * 阻止滑动穿透
     * @param el
     */
    scrollPreventThrough(el) {
        if (!el) {
            return;
        }
        if (el.getAttribute("data-prevent-through") === "yes") {
            return;
        }
        el.setAttribute("data-prevent-through", "yes")
        //
        let targetY = null;
        el.addEventListener('touchstart', function (e) {
            targetY = Math.floor(e.targetTouches[0].clientY);
        });
        el.addEventListener('touchmove', function (e) {
            // 检测可滚动区域的滚动事件，如果滑到了顶部或底部，阻止默认事件
            let NewTargetY = Math.floor(e.targetTouches[0].clientY),    //本次移动时鼠标的位置，用于计算
                sTop = el.scrollTop,        //当前滚动的距离
                sH = el.scrollHeight,       //可滚动区域的高度
                lyBoxH = el.clientHeight;   //可视区域的高度
            if (sTop <= 0 && NewTargetY - targetY > 0) {
                // 下拉页面到顶
                e.preventDefault();
            } else if (sTop >= sH - lyBoxH && NewTargetY - targetY < 0) {
                // 上翻页面到底
                e.preventDefault();
            }
        }, false);
    },
}
