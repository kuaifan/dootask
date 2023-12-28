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
        return '<p><span class="input-blink"></span>&nbsp;</p>'
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
