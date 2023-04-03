<template>
    <div class="markdown-body" v-html="html"></div>
</template>

<script>
import '../../../../sass/pages/components/dialog-markdown/markdown.less'
import MarkdownIt from 'markdown-it'
import mdKatex from '@traptitech/markdown-it-katex'
import mila from 'markdown-it-link-attributes'
import hljs from 'highlight.js'

export default {
    name: "DialogMarkdown",
    props: {
        text: {
            type: String,
            default: ''
        },
    },
    data() {
        return {
            mdi: null,
        }
    },

    mounted() {
        this.copyCodeBlock()
    },

    updated() {
        this.copyCodeBlock()
    },

    computed: {
        html() {
            const {text} = this
            if (this.mdi === null) {
                const {highlightBlock} = this
                this.mdi = new MarkdownIt({
                    linkify: true,
                    highlight(code, language) {
                        const validLang = !!(language && hljs.getLanguage(language))
                        if (validLang) {
                            const lang = language ?? ''
                            return highlightBlock(hljs.highlight(code, {language: lang}).value, lang)
                        }
                        return highlightBlock(hljs.highlightAuto(code).value, '')
                    },
                })
                this.mdi.use(mila, {attrs: {target: '_blank', rel: 'noopener'}})
                this.mdi.use(mdKatex, {blockClass: 'katexmath-block rounded-md p-[10px]', errorColor: ' #cc0000'})
            }
            return this.mdi.render(text)
        }
    },

    methods: {
        highlightBlock(str, lang = '') {
            return `<pre class="code-block-wrapper"><div class="code-block-header"><span class="code-block-header__lang">${lang}</span><span class="code-block-header__copy">${this.$L('复制代码')}</span></div><code class="hljs code-block-body ${lang}">${str}</code></pre>`
        },

        copyCodeBlock() {
            const codeBlockWrapper = this.$el.querySelectorAll('.code-block-wrapper')
            codeBlockWrapper.forEach((wrapper) => {
                const copyBtn = wrapper.querySelector('.code-block-header__copy')
                const codeBlock = wrapper.querySelector('.code-block-body')
                if (copyBtn && codeBlock && copyBtn.getAttribute("data-copy") !== "click") {
                    copyBtn.setAttribute("data-copy", "click")
                    copyBtn.addEventListener('click', () => {
                        if (navigator.clipboard?.writeText)
                            navigator.clipboard.writeText(codeBlock.textContent ?? '')
                        else
                            this.copyText({text: codeBlock.textContent ?? '', origin: true})
                    })
                }
            })
        },

        copyText(options) {
            const props = {origin: true, ...options}

            let input

            if (props.origin)
                input = document.createElement('textarea')
            else
                input = document.createElement('input')

            input.setAttribute('readonly', 'readonly')
            input.value = props.text
            document.body.appendChild(input)
            input.select()
            if (document.execCommand('copy'))
                document.execCommand('copy')
            document.body.removeChild(input)
        }
    }
}
</script>
