<template>
    <div ref="preview" class="markdown-preview-warp">
        <div
            :class="`markdown-preview ${'markdown-theme-' + theme}`"
            v-html="html"
        ></div>
        <!-- 预览图片-->
        <div :class="['preview-img', previewImgModal ? 'active' : '']">
            <span
                class="close icon-close iconfont"
                @click="previewImgModal = false"
            ></span>
            <img :src="previewImgSrc" :class="[previewImgMode]" alt=""/>
        </div>
    </div>
</template>

<script>
    import marked from '../../config/marked';
    import tocObj from "../../assets/js/marked/createToc";

    export default {
        name: 'markdown-preview',
        props: {
            initialValue: {
                // 初始化内容
                type: String,
                default: ''
            },
            markedOptions: {
                type: Object,
                default: () => ({})
            },
            theme: {
                type: String,
                default: 'light'
            },
            copyCode: {// 复制代码
                type: Boolean,
                default: true
            },
            copyBtnText: {// 复制代码按钮文字
                type: String,
                default: '复制代码'
            }
        },
        data() {
            return {
                html: '',
                previewImgModal: false,
                previewImgSrc: '',
                previewImgMode: ''
            };
        },
        mounted() {
            this.translateMarkdown();
        },
        methods: {
            tocLevel(l, lists) {
                let minLevel = 9999;
                lists.forEach(({level}) => {
                    minLevel = Math.min(minLevel, level)
                });
                if (minLevel === 9999) {
                    return l;
                }
                return l - (minLevel - 1);
            },
            translateMarkdown() {
                let html = marked(this.initialValue, {
                    sanitize: false,
                    ...this.markedOptions
                }).replace(/href="/gi, 'target="_blank" href="');
                if (this.copyCode) {
                    html = html.replace(/<pre>/g, '<div class="code-block"><span class="copy-code">' + this.copyBtnText + '</span><pre>').replace(/<\/pre>/g, '</pre></div>');
                }
                if (/\[\[TOC\]\]/.test(html)) {
                    let string = '';
                    tocObj.tocItems.forEach((item) => {
                        string+= `<li class="toc-anchor-item" onclick="_goTocAction(this, '${item.level}', '${item.anchor}')"><span class="toc-link-${this.tocLevel(item.level, tocObj.tocItems)}" title="${item.text}">${item.text}</span></li>`;
                    });
                    html = html.replace(/\[\[TOC\]\]/g, `<ul class="toc-anchor">${string}</ul>`)
                }
                this.html = html;

                this.addCopyListener();
                this.addImageClickListener();
            },
            addCopyListener() {// 监听复制操作
                setTimeout(() => {
                    const btns = document.querySelectorAll(
                        '.code-block .copy-code'
                    );
                    this.btns = btns;
                    for (let i = 0, len = btns.length; i < len; i++) {
                        btns[i].onclick = () => {
                            const code = btns[i].parentNode.querySelectorAll(
                                'pre'
                            )[0].innerText;
                            const aux = document.createElement('input');
                            aux.setAttribute('value', code);
                            document.body.appendChild(aux);
                            aux.select();
                            document.execCommand('copy');
                            document.body.removeChild(aux);
                            this.$emit('on-copy', code);
                        };
                    }
                }, 600);
            },
            addImageClickListener() {// 监听查看大图
                const {imgs = []} = this;
                if (imgs.length > 0) {
                    for (let i = 0, len = imgs.length; i < len; i++) {
                        imgs[i].onclick = null;
                    }
                }
                setTimeout(() => {
                    if (!this.$refs.preview) {
                        return;
                    }
                    this.imgs = this.$refs.preview.querySelectorAll('img');
                    for (let i = 0, len = this.imgs.length; i < len; i++) {
                        this.imgs[i].onclick = () => {
                            const src = this.imgs[i].getAttribute('src');
                            this.previewImage(src);
                        };
                    }
                }, 600);
            },
            previewImage(src) {// 预览图片
                const img = new Image();
                img.src = src;
                img.onload = () => {
                    const width = img.naturalWidth;
                    const height = img.naturalHeight;
                    if (height / width > 1.4) {
                        this.previewImgMode = 'horizontal';
                    } else {
                        this.previewImgMode = 'vertical';
                    }
                    this.previewImgSrc = src;
                    this.previewImgModal = true;
                };
            }
        },
        watch: {
            initialValue() {
                this.translateMarkdown();
            }
        }
    };
</script>

<style scoped lang="less">
    @import "../../assets/font/iconfont.css";
    @import "../../assets/css/theme";
    @import "../../assets/css/index";
    @import "../../assets/css/light";
    @import "../../assets/css/dark";
    @import "../../assets/css/one-dark";
    @import "../../assets/css/github";
</style>
