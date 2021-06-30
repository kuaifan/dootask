import marked from '../../config/marked';
import common from '../../mixins/common';

export default {
    name: 'markdown-simple',
    mixins: [common],
    data() {
        return {
            textareaHeight: this.height
        };
    },
    mounted() {
        this.init();
    },
    methods: {
        init() {
            this.currentValue = this.value;
            this.themeName = this.theme;
            this.preview = this.isPreview;
            if (this.isPreview) {
                return;
            }
            setTimeout(() => {
                const textarea = this.$refs.textarea;
                textarea.focus();
                textarea.addEventListener('keydown', e => {
                    if (e.keyCode === 83) {
                        if (e.metaKey || e.ctrlKey) {
                            e.preventDefault();
                            this.handleSave();
                        }
                    }
                });
                textarea.addEventListener('paste', this.handlePaste);
                if (this.autoSave) {
                    this.timerId = setInterval(() => {
                        this.handleSave();
                    }, this.interval);
                }
                this.$emit('on-ready',{
                    vm:this,
                    insertContent:this.insertContent
                })
            }, 20);
        },

        insertContent(initStr) {
            // 插入文本
            this.lastInsert = initStr;
            const point = this.getCursortPosition();
            const lastChart = this.currentValue.substring(point - 1, point);
            const lastFourCharts = this.currentValue.substring(
                point - 4,
                point
            );
            if (
                lastChart !== '\n' &&
                this.currentValue !== '' &&
                lastFourCharts !== '    '
            ) {
                const str = '\n' + initStr;
                this.insertAfterText(str);
            } else {
                this.insertAfterText(initStr);
            }
        },
        getCursortPosition() {
            // 获取光标位置
            const textDom = this.$refs.textarea;
            let cursorPos = 0;
            if (document.selection) {
                textDom.focus();
                let selectRange = document.selection.createRange();
                selectRange.moveStart('character', -this.currentValue.length);
                cursorPos = selectRange.text.length;
            } else if (
                textDom.selectionStart ||
                parseInt(textDom.selectionStart, 0) === 0
            ) {
                cursorPos = textDom.selectionStart;
            }
            return cursorPos;
        },
        insertAfterText(value) {
            // 插入文本
            const textDom = this.$refs.textarea;
            let selectRange;
            if (document.selection) {
                textDom.focus();
                selectRange = document.selection.createRange();
                selectRange.text = value;
                textDom.focus();
            } else if (
                textDom.selectionStart ||
                parseInt(textDom.selectionStart, 0) === 0
            ) {
                const startPos = textDom.selectionStart;
                const endPos = textDom.selectionEnd;
                const scrollTop = textDom.scrollTop;
                textDom.value =
                    textDom.value.substring(0, startPos) +
                    value +
                    textDom.value.substring(endPos, textDom.value.length);
                textDom.focus();
                textDom.selectionStart = startPos + value.length;
                textDom.selectionEnd = startPos + value.length;
                textDom.scrollTop = scrollTop;
            } else {
                textDom.value += value;
                textDom.focus();
            }
            this.$set(this, 'currentValue', textDom.value);
        },
        setCaretPosition(position) {
            // 设置光标位置
            const textDom = this.$refs.textarea;
            if (textDom.setSelectionRange) {
                textDom.focus();
                textDom.setSelectionRange(position, position);
            } else if (textDom.createTextRange) {
                let range = textDom.createTextRange();
                range.collapse(true);
                range.moveEnd('character', position);
                range.moveStart('character', position);
                range.select();
            }
        },
        insertQuote() {
            // 引用
            this.insertContent('\n>  ');
        },
        insertUl() {
            // 无需列表
            this.insertContent('-  ');
        },
        insertOl() {
            // 有序列表
            this.insertContent('1. ');
        },
        insertFinished() {
            // 已完成列表
            this.insertContent('- [x]  ');
        },
        insertNotFinished() {
            // 未完成列表
            this.insertContent('- [ ]  ');
        },
        insertCode() {
            // 插入code
            const point = this.getCursortPosition();
            const lastChart = this.currentValue.substring(point - 1, point);
            this.insertContent('\n```\n\n```');
            if (lastChart !== '\n' && this.currentValue !== '') {
                this.setCaretPosition(point + 5);
            } else {
                this.setCaretPosition(point + 5);
            }
        },
        insertStrong() {
            // 粗体
            const point = this.getCursortPosition();
            const lastChart = this.currentValue.substring(point - 1, point);
            this.insertContent('****');
            if (lastChart !== '\n' && this.currentValue !== '') {
                this.setCaretPosition(point + 2);
            } else {
                this.setCaretPosition(point + 2);
            }
        },
        insertItalic() {
            // 斜体
            const point = this.getCursortPosition();
            const lastChart = this.currentValue.substring(point - 1, point);
            this.insertContent('**');
            if (lastChart !== '\n' && this.currentValue !== '') {
                this.setCaretPosition(point + 1);
            } else {
                this.setCaretPosition(point + 1);
            }
        },
        insertBg() {
            // 背景色
            const point = this.getCursortPosition();
            const lastChart = this.currentValue.substring(point - 1, point);
            this.insertContent('====');
            if (lastChart !== '\n' && this.currentValue !== '') {
                this.setCaretPosition(point + 5);
            } else {
                this.setCaretPosition(point + 5);
            }
        },
        insertUnderline() {
            // 下划线
            const point = this.getCursortPosition();
            const lastChart = this.currentValue.substring(point - 1, point);
            this.insertContent('<u></u>');
            if (lastChart !== '\n' && this.currentValue !== '') {
                this.setCaretPosition(point + 3);
            } else {
                this.setCaretPosition(point + 5);
            }
        },
        insertOverline() {
            // overline
            const point = this.getCursortPosition();
            const lastChart = this.currentValue.substring(point - 1, point);
            this.insertContent('~~~~');
            if (lastChart !== '\n' && this.currentValue !== '') {
                this.setCaretPosition(point + 2);
            } else {
                this.setCaretPosition(point + 2);
            }
        },
        insertTitle(level) {
            // 插入标题
            const titleLevel = {
                1: '#  ',
                2: '##  ',
                3: '###  ',
                4: '####  ',
                5: '#####  ',
                6: '######  '
            };
            this.insertContent(titleLevel[level]);
        },
        tab(e) {
            // 屏蔽teatarea tab默认事件
            this.insertContent('    ', this);
            if (e.preventDefault) {
                e.preventDefault();
            } else {
                e.returnValue = false;
            }
        },
        insertLine() {
            // 插入分割线
            this.insertContent('\n----\n');
        },
        enter() {
            // 回车事件
            const {lastInsert} = this;
            const list = ['-  ', '1. ', '- [ ]  ', '- [x]  '];
            if (list.includes(lastInsert)) {
                this.insertContent(lastInsert);
            }
        },
        onDelete() {
            // 删除时,以回车为界分割，如果数组最后一个元素为''时，将行一次插入的共嗯那个置为空，避免回车时再次插入
            const lines = this.currentValue.split('\n');
            if (lines[lines.length - 1] === '') {
                this.lastInsert = '';
            }
        },
        handlePaste(e) {
            // 粘贴图片
            const {clipboardData = {}} = e;
            const {types = [], items} = clipboardData;
            let item = null;
            for (let i = 0; i < types.length; i++) {
                if (types[i] === 'Files') {
                    item = items[i];
                    break;
                }
            }
            if (item) {
                const file = item.getAsFile();
                if (/image/gi.test(file.type)) {
                    this.$emit('on-paste-image', file);
                    e.preventDefault();
                }
            }
        },
        markdownScroll() {
            const {scrolling} = this;
            if (!scrolling) {
                return;
            }
            if (this.scroll === 'left') {
                const markdownEditor = this.$refs.markdownEditor;
                const preview = this.$refs.preview;
                const contentHeight = preview.offsetHeight;
                const markdownScrollHeight = markdownEditor.scrollHeight;
                const markdownScrollTop = markdownEditor.scrollTop;
                const previewScrollHeight = preview.scrollHeight;
                preview.scrollTop = parseInt(
                    (markdownScrollTop /
                        (markdownScrollHeight - contentHeight)) *
                    (previewScrollHeight - contentHeight),
                    0
                );
            }
        },
        previewScroll() {
            const {scrolling} = this;
            if (!scrolling) {
                return;
            }
            if (this.scroll === 'right') {
                const markdownEditor = this.$refs.markdownEditor;
                const preview = this.$refs.preview;
                const contentHeight = preview.offsetHeight;
                const markdownScrollHeight = markdownEditor.scrollHeight;
                const previewScrollHeight = preview.scrollHeight;
                const previewScrollTop = preview.scrollTop;
                markdownEditor.scrollTop = parseInt(
                    (previewScrollTop / (previewScrollHeight - contentHeight)) *
                    (markdownScrollHeight - contentHeight),
                    0
                );
            }
        },
        mousescrollSide(side) {
            // 设置究竟是哪个半边在主动滑动
            this.scroll = side;
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
                    html = html.replace(/<pre>/g, '<div class="code-block"><span class="copy-code">'+this.copyBtnText+'</span><pre>').replace(/<\/pre>/g, '</pre></div>');
                }
                this.html = html;
                this.indexLenth = this.currentValue.split('\n').length;
                this.textareaHeight = this.indexLenth * 22;
                this.addImageClickListener();
                this.addCopyListener();
                this.$emit('input', currentValue);
            }, 30);
        },
        value() {
            if (this.currentValue !== this.value) {
                this.currentValue = this.value;
            }
        }
    }
};
