import Codemirror from '../../assets/js/codemirror';
import '../../assets/js/codemirror/mode/xml';
import '../../assets/js/codemirror/mode/css';
import '../../assets/js/codemirror/mode/javascript';
import '../../assets/js/codemirror/mode/markdown';
import codemirrorConfig from '../../assets/js/codemirror/config';
import '../../assets/js/codemirror/styles/codemirror.css';
import common from '../../mixins/common';
import marked from '../../config/marked';
import tocObj from "../../assets/js/marked/createToc";

export default {
    name: 'markdown-pro',
    mixins: [common],
    data() {
        return {
            pro: true,
            editor: null, // 编辑器实例
            lastPos: '' // 光标最后所在位置,
        };
    },

    mounted() {
        tocObj.reset()
        this.init();
        this.createEditor();
    },
    methods: {
        init() {// 初始化
            this.currentValue = this.value;
            this.themeName = this.theme;
            this.preview = this.isPreview;
            this.currentValue = this.value;
            if (this.isPreview) {
                return;
            }
            setTimeout(() => {
                if (this.autoSave) {
                    this.timerId = setInterval(() => {
                        this.handleSave();
                    }, this.interval);
                }
            }, 20);
        },
        createEditor() {// 初始化左侧编辑器
            this.editor = new Codemirror(this.$refs.codemirror, {
                value: this.currentValue,
                onload: (data) => {
                    const {doc: {height = 0}} = data;
                    this.editorScrollHeight = height;
                },
                ...codemirrorConfig
            });

            this.addEditorLintener();
            this.$emit('on-ready', {
                vm: this,
                insertContent: this.insertContent
            })
        },
        addEditorLintener() {//绑定监听事件
            const editor = this.editor;
            editor.on('change', data => {
                this.lastPos = editor.getCursor();
                this.currentValue = editor.getValue();
                const {
                    doc: {height}
                } = data;
                this.editorScrollHeight = height;
            });
            editor.on('scroll', this.markdownScroll);
            editor.on('paste', this.handlePaste);
            editor.on('keydown', (data, e) => {
                if (e.keyCode === 83) {
                    if (e.metaKey || e.ctrlKey) {
                        e.preventDefault();
                        this.handleSave();
                    }
                } else if (e.keyCode === 13) {
                    this.listerenKeyupEnter(e);
                } else if (e.keyCode === 8) {
                    this.listerenDelete(data);
                }
            });
            editor.on('focus', () => {
                this.lastPos = editor.getCursor();
            });
        },
        insertContent(str) {// 插入文本
            this.editor.replaceSelection(str);
            this.lastInsert = str.replace(/\n/g, '');
        },
        setCursor(line = 0, ch = 0) {// 设置焦点
            const {editor} = this;
            editor.setCursor(line, ch);
            editor.focus();
        },

        insertStrong() {// 粗体
            const {editor, lastPos = {}} = this;
            const {line = 0, ch = 0} = lastPos;
            const selection = editor.getSelection();
            if (selection) {
                this.insertContent('**' + selection + '**');
            } else {
                this.insertContent('****');
                this.setCursor(line, ch + 2);
            }
        },
        insertItalic() {// 斜体
            const {editor, lastPos = {}} = this;
            const {line = 0, ch = 0} = lastPos;
            const selection = editor.getSelection();
            if (selection) {
                this.insertContent('*' + selection + '*');
            } else {
                this.insertContent('**');
                this.setCursor(line, ch + 1);
            }
        },
        insertUnderline() {// 下划线
            const {editor, lastPos = {}} = this;
            const {line = 0, ch = 0} = lastPos;
            const selection = editor.getSelection();
            if (selection) {
                this.insertContent('<u>' + selection + '</u>');
            } else {
                this.insertContent('<u></u>');
                this.setCursor(line, ch + 3);
            }
        },
        insertOverline() {// 删除线
            const {editor, lastPos = {}} = this;
            const {line = 0, ch = 0} = lastPos;
            const selection = editor.getSelection();
            if (selection) {
                this.insertContent('~~' + selection + '~~');
            } else {
                this.insertContent('~~~~');
                this.setCursor(line, ch + 2);
            }
        },
        insertTitle(level) {// 插入标题
            const titles = {
                1: '#  ',
                2: '##  ',
                3: '###  ',
                4: '####  ',
                5: '#####  ',
                6: '######  '
            };
            const {editor, lastPos = {}} = this;
            const {line} = lastPos;
            const selection = editor.getSelection();
            if (selection) {
                this.insertContent('\n' + titles[level] + selection + '\n');
            } else {
                const title = titles[level];
                if (editor.isClean()) {
                    this.insertContent(title);
                    this.setCursor(0, title.length);
                } else {
                    this.insertContent('\n' + title);
                    this.setCursor(line + 1, title.length);
                }
            }
        },
        insertLine() {// 插入分割线
            const {editor} = this;
            if (editor.isClean()) {
                this.insertContent('----\n');
            } else {
                this.insertContent('\n\n----\n');
            }
        },
        insertQuote() {// 引用
            const {editor, lastPos = {}} = this;
            const {line = 0} = lastPos;
            const selection = editor.getSelection();
            if (selection) {
                this.insertContent('\n>  ' + selection + '\n\n');
            } else {
                if (editor.isClean()) {
                    this.insertContent('>  ');
                    this.setCursor(0, 3);
                } else {
                    this.insertContent('\n>  ');
                    this.setCursor(line + 1, 3);
                }
            }
        },
        insertUl() {// 无序列表
            const {editor, lastPos = {}} = this;
            const {line = 0, ch = 0} = lastPos;
            const selection = editor.getSelection();
            if (selection) {
                this.insertContent('\n-  ' + selection + '\n\n');
            } else {
                if (editor.isClean() || ch === 0) {
                    this.insertContent('-  ');
                    this.setCursor(line, 3);
                } else {
                    this.insertContent('\n-  ');
                    this.setCursor(line + 1, 3);
                }
            }
        },
        insertOl() {// 有序列表
            const {editor, lastPos = {}} = this;
            const {line = 0, ch = 0} = lastPos;
            const selection = editor.getSelection();
            if (selection) {
                this.insertContent('\n1.  ' + selection + '\n\n');
            } else {
                if (editor.isClean() || ch === 0) {
                    this.insertContent('1.  ');
                    this.setCursor(line, 4);
                } else {
                    this.insertContent('\n1.  ');
                    this.setCursor(line + 1, 4);
                }
            }
        },
        insertCode() {// 插入code
            const {editor, lastPos = {}} = this;
            const {line} = lastPos;
            const selection = editor.getSelection();
            if (selection) {
                this.insertContent('\n```\n' + selection + '\n```\n');
            } else {
                if (editor.isClean()) {
                    this.insertContent('```\n\n```');
                    this.setCursor(1, 0);
                } else {
                    this.insertContent('\n```\n\n```');
                    this.setCursor(line + 2, 0);
                }
            }
        },
        insertFinished() {// 已完成列表
            const {editor, lastPos = {}} = this;
            const {line = 0, ch = 0} = lastPos;
            const selection = editor.getSelection();
            if (selection) {
                this.insertContent('\n- [x] ' + selection + '\n\n');
            } else {
                if (editor.isClean() || ch === 0) {
                    this.insertContent('- [x] ');
                    this.setCursor(line, 6);
                } else {
                    this.insertContent('\n- [x] ');
                    this.setCursor(line + 1, 6);
                }
            }
        },
        insertNotFinished() {// 未完成列表
            const {editor, lastPos = {}} = this;
            const {line = 0, ch = 0} = lastPos;
            const selection = editor.getSelection();
            if (selection) {
                this.insertContent('\n- [ ] ' + selection + '\n\n');
            } else {
                if (editor.isClean() || ch === 0) {
                    this.insertContent('- [ ] ');
                    this.setCursor(line, 6);
                } else {
                    this.insertContent('\n- [ ] ');
                    this.setCursor(line + 1, 6);
                }
            }
        },
        listerenKeyupEnter(e) {// 回车事件
            const {lastInsert} = this;
            if (lastInsert) {
                const list = ['-', '- [ ]', '- [x]'];
                if (list.includes(lastInsert.trim())) {
                    e.preventDefault();
                    this.insertContent('\n' + lastInsert);
                } else if (/^\d+\.$/.test(lastInsert.trim())) {
                    e.preventDefault();
                    this.insertContent(
                        '\n' + (parseInt(lastInsert, 0) + 1) + '.  '
                    );
                }
            }
        },
        listerenDelete() {// 删除 backup
            setTimeout(() => {
                const {editor} = this;
                if (!editor.isClean()) {
                    const value = editor.getValue();
                    if (value.split('\n').pop() === '') {
                        this.lastInsert = '';
                    }
                }
            }, 20);
        },
        onDelete() {// 删除时,以回车为界分割，如果数组最后一个元素为''时，将行一次插入的共嗯那个置为空，避免回车时再次插入
            const lines = this.currentValue.split('\n');
            if (lines[lines.length - 1] === '') {
                this.lastInsert = '';
            }
        },
        markdownScroll(data = {}) {//编辑器区域滚动
            if (this.scrolling && this.scrollSide === 'left') {
                const {
                    doc: {height, scrollTop}
                } = data;
                const preview = this.$refs.preview;
                const contentHeight = preview.offsetHeight;
                const previewScrollHeight = preview.scrollHeight;
                preview.scrollTop = parseInt(
                    (scrollTop * (previewScrollHeight - contentHeight)) /
                    (height - contentHeight),
                    0
                );
            }
        },
        previewScroll() {//预览内容区域滚动
            if (this.scrolling && this.scrollSide === 'right') {
                const preview = this.$refs.preview;
                const contentHeight = preview.offsetHeight;
                const previewScrollHeight = preview.scrollHeight;
                const previewScrollTop = preview.scrollTop;
                const scrollTop = parseInt((previewScrollTop * (this.editorScrollHeight - contentHeight)) / (previewScrollHeight - contentHeight), 0);
                this.editor.scrollTo(0, scrollTop);
                //
                const container = $A(this.$refs.preview);
                let inner, topId;
                container.find("h1,h2,h3,h4,h5").each((index, item) => {
                    inner = $A(item);
                    if (inner.offset().top - container.offset().top >= 0 && (topId = inner.attr("toc-id"))) {
                        this.tocAction = topId;
                        return false;
                    }
                });
            }
        },
        redo() {
            const {editor} = this;
            editor.redo();
            setTimeout(() => {
                editor.refresh();
            }, 20);
        },
        tocLevel(l, lists) {
            let minLevel = 9999;
            if (typeof lists === "undefined") {
                lists = this.tocLists;
            }
            lists.forEach(({level}) => {
                minLevel = Math.min(minLevel, level)
            });
            if (minLevel === 9999) {
                return l;
            }
            return l - (minLevel - 1);
        },
        tocClick(item) {
            this.tocAction = item.anchor;
            const container = $A(this.$refs.preview);
            const inner = container.find("h" + item.level + '[toc-id="' + this.tocAction + '"]');
            if (inner) {
                container.animate({
                    scrollTop: inner.offset().top - container.offset().top + container.scrollTop()
                });
            }
        }
    },
    watch: {
        currentValue() {
            clearTimeout(this.timeoutId);
            this.timeoutId = setTimeout(() => {
                const {currentValue} = this;
                let html = marked(currentValue, {
                    sanitize: false,
                    ...this.markedOptions
                }).replace(/href="/gi, 'target="_blank" href="');
                if (this.copyCode && html !== '') {
                    html = html.replace(/<pre>/g, '<div class="code-block"><span class="copy-code">' + this.copyBtnText + '</span><pre>').replace(/<\/pre>/g, '</pre></div>')
                }
                if (/\[\[TOC\]\]/.test(html)) {
                    let string = '';
                    tocObj.tocItems.forEach((item) => {
                        string+= `<li class="toc-anchor-item" onclick="_goTocAction(this, '${item.level}', '${item.anchor}')"><span class="toc-link-${this.tocLevel(item.level, tocObj.tocItems)}" title="${item.text}">${item.text}</span></li>`;
                    });
                    html = html.replace(/\[\[TOC\]\]/g, `<ul class="toc-anchor">${string}</ul>`)
                }
                this.html = html;

                //toc
                this.tocLists = tocObj.tocItems;
                if (!this.tocTrigger) {
                    this.tocShow = this.tocLists.length > 1
                }
                tocObj.reset()

                this.addImageClickListener();
                this.addCopyListener();
                this.$emit('input', currentValue);
            }, 30);
        },
        value() {
            const {value, currentValue} = this;
            if (currentValue !== value) {// 由于用户输入而造成的value变化，不对editor设置值
                this.currentValue = value;
                this.editor.setOption('value', value);
            }
        }
    }
};
