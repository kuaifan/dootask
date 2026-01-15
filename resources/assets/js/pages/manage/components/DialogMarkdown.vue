<template>
    <div @click="onCLick" class="markdown-body" v-html="html"></div>
</template>

<script>
import '../../../../sass/pages/components/dialog-markdown/markdown.less'
import {MarkdownConver} from "../../../utils/markdown";

export default {
    name: "DialogMarkdown",
    props: {
        text: {
            type: String,
            default: ''
        },
        // 导航前回调（如关闭弹窗）
        beforeNavigate: {
            type: Function,
            default: null
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
            const target = e.target;
            if (target.tagName === 'A') {
                const href = target.getAttribute('href');
                if (href && href.startsWith('dootask://')) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.handleDooTaskLink(href);
                    return;
                }
            }
            this.$emit('click', e)
        },

        /**
         * 处理 dootask:// 协议链接
         * 格式: dootask://type/id 或 dootask://type/id1/id2
         * 文件链接支持: dootask://file/123 (数字ID) 或 dootask://file/OSwxLHY3ZlN2R245 (base64编码)
         */
        handleDooTaskLink(href) {
            const match = href.match(/^dootask:\/\/(\w+)\/([^/]+)(?:\/(\d+))?$/);
            if (!match) {
                return;
            }

            const [, type, id, id2] = match;
            const isNumericId = /^\d+$/.test(id);
            const numId = isNumericId ? parseInt(id, 10) : null;
            const numId2 = id2 ? parseInt(id2, 10) : null;

            switch (type) {
                case 'task':
                    this.$store.dispatch('openTask', { id: (numId2 && numId2 > 0) ? numId2 : numId });
                    break;

                case 'project':
                    this.beforeNavigate?.();
                    this.goForward({ name: 'manage-project', params: { projectId: numId } });
                    break;

                case 'file':
                    if (isNumericId) {
                        // 数字ID：跳转到文件列表并高亮
                        this.beforeNavigate?.();
                        this.goForward({ name: 'manage-file', params: { folderId: 0, fileId: null, shakeId: numId } });
                        this.$store.state.fileShakeId = numId;
                        setTimeout(() => {
                            this.$store.state.fileShakeId = 0;
                        }, 600);
                    } else {
                        // 非数字ID（如base64编码）：打开新窗口预览
                        window.open($A.mainUrl('single/file/' + id));
                    }
                    break;

                case 'contact':
                    this.$store.dispatch('openDialogUserid', numId).catch(({ msg }) => {
                        $A.modalError(msg);
                    });
                    break;

                case 'message':
                    this.$store.dispatch('openDialog', numId).then(() => {
                        if (numId2) {
                            this.$store.state.dialogSearchMsgId = numId2;
                        }
                    }).catch(({ msg }) => {
                        $A.modalError(msg);
                    });
                    break;
            }
        }
    }
}
</script>
