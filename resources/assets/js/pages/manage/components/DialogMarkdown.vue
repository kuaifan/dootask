<template>
    <div @click="onCLick" class="markdown-body" v-html="html"></div>
</template>

<script>
import '../../../../sass/pages/components/dialog-markdown/markdown.less'
import {MarkdownConver} from "../../../store/markdown";

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
        html({text}) {
            return MarkdownConver(text)
        }
    },

    methods: {
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
                            this.copyContent({text: codeBlock.textContent ?? '', origin: true})
                    })
                }
            })
        },

        copyContent(options) {
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
        },

        onCLick(e) {
            this.$emit('click', e)
        }
    }
}
</script>
