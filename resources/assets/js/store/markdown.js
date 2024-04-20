import MarkdownIt from "markdown-it";
import hljs from "highlight.js";
import mila from "markdown-it-link-attributes";
import mdKatex from "@traptitech/markdown-it-katex";

/**
 * Markdown
 */
const MarkdownUtils = {
    mdi: null,
    mds: null,
    formatMsg: (text) => {
        const array = text.match(/<img\s+[^>]*?>/g);
        if (array) {
            array.some(res => {
                text = text.replace(res, `<div class="no-size-image-box">${res}</div>`);
            })
        }
        return text
    },
    highlightBlock: (str, lang = '') => {
        return `<pre class="code-block-wrapper"><div class="code-block-header"><span class="code-block-header__lang">${lang}</span><span class="code-block-header__copy">${$A.L('复制代码')}</span></div><code class="hljs code-block-body ${lang}">${str}</code></pre>`
    },
}

export function MarkdownConver(text) {
    if (text === '...') {
        return '<div class="input-blink"></div>'
    }
    if (MarkdownUtils.mdi === null) {
        MarkdownUtils.mdi = new MarkdownIt({
            linkify: true,
            highlight(code, language) {
                const validLang = !!(language && hljs.getLanguage(language))
                if (validLang) {
                    const lang = language ?? ''
                    return MarkdownUtils.highlightBlock(hljs.highlight(code, {language: lang}).value, lang)
                }
                return MarkdownUtils.highlightBlock(hljs.highlightAuto(code).value, '')
            },
        })
        MarkdownUtils.mdi.use(mila, {attrs: {target: '_blank', rel: 'noopener'}})
        MarkdownUtils.mdi.use(mdKatex, {blockClass: 'katexmath-block rounded-md p-[10px]', errorColor: ' #cc0000'})
    }
    return MarkdownUtils.formatMsg(MarkdownUtils.mdi.render(text))
}

export function MarkdownPreview(text) {
    if (MarkdownUtils.mds === null) {
        MarkdownUtils.mds = MarkdownIt()
    }
    return MarkdownUtils.mds.render(text)
}

export function isMarkdownFormat(html) {
    if (html === '') {
        return false
    }
    const tmp = html.replace(/<p>/g, '\n').replace(/(^|\s+)```([\s\S]*)```/gm, '')
    if (/<\/(strong|s|em|u|ol|ul|li|blockquote|pre|img|a)>/i.test(tmp)) {
        return false
    }
    if (/<span[^>]+?class="mention"[^>]*?>/i.test(tmp)) {
        return false
    }
    //
    const el = document.createElement('div')
    el.style.position = 'fixed'
    el.style.top = '0'
    el.style.left = '0'
    el.style.width = '10px'
    el.style.height = '10px'
    el.style.overflow = 'hidden'
    el.style.zIndex = '-9999'
    el.style.opacity = '0'
    el.innerHTML = html
    document.body.appendChild(el)
    const text = el.innerText
    document.body.removeChild(el)
    //
    if (
        /(^|\s+)#+\s(.*)$/m.test(text)              // 标题
        || /(^|\s+)\*\*(.*)\*\*/m.test(text)        // 粗体
        || /(^|\s+)__(.*)__/m.test(text)            // 粗体
        || /(^|\s+)\*(.*)\*/m.test(text)            // 斜体
        || /(^|\s+)_(.*)_/m.test(text)              // 斜体
        || /(^|\s+)~~(.*)~~/m.test(text)            // 删除线
        || /(^|\s+)\[(.*?)\]\((.*?)\)/m.test(text)  // 链接
        || /(^|\s+)!\[(.*?)\]\((.*?)\)/m.test(text) // 图片
        || /(^|\s+)`(.*?)`/m.test(text)             // 行内代码
        || /(^|\s+)```([\s\S]*?)```/m.test(text)    // 代码块
    ) {
        return true
    }
    return false
}
