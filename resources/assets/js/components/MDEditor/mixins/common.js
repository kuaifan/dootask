import {saveFile} from '../utils';
import defaultTools from '../config/tools';

export default {
    name: 'markdown',
    props: {
        value: {
            type: [String, Number],
            default: ''
        },
        theme: {// 默认主题
            type: String,
            default: 'light'
        },
        width: {// 初始化宽度
            type: [Number, String],
            default: 'auto'
        },
        height: {// 初始化高度
            type: [Number, String],
            default: 600
        },
        toolbars: {// 工具栏
            type: Object,
            default() {
                return {};
            }
        },
        bordered: {//是否有边框
            type: Boolean,
            default: true
        },
        autoSave: {// 是否自动保存
            type: Boolean,
            default: false
        },
        interval: {// 自动保存间隔 mm
            type: Number,
            default: 10000
        },
        exportFileName: {// 默认导出文件名称
            type: String,
            default: 'unnamed'
        },
        markedOptions: {//marked.js配置项
            type: Object,
            default() {
                return {};
            }
        },
        copyCode: {// 复制代码
            type: Boolean,
            default: true
        },
        copyBtnText: {// 复制代码按钮文字
            type: String,
            default: '复制代码'
        },
        isPreview: {//是否是预览模式
            type: Boolean,
            default: false
        },
        isCustomFullscreen: {//是否全部（自定义参数）
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            currentValue: '', // 输入框内容
            timeoutId: null,
            indexLenth: 1,
            html: '',// 预览的html
            preview: false, // 是否是预览状态
            split: true, //分屏显示
            fullscreen: false, // 是否是全屏
            scrollSide: '', // 哪个半栏在滑动
            lastInsert: '', //最后一次插入的内容
            timerId: null, // 定时器id
            themeName: '',
            themeSlideDown: false,
            imgSlideDown: false,
            imgs: [],
            scrolling: true, // 同步滚动
            editorScrollHeight: 0,
            previewImgModal: false,
            previewImgSrc: '',
            previewImgMode: '',

            tocTrigger: false,
            tocShow: false,
            tocAction: '',
            tocLists: [],
        };
    },
    computed: {
        tools() {
            const {toolbars = {}} = this;
            return {
                ...defaultTools,
                ...toolbars
            };
        }
    },
    methods: {
        insertLink() {// 插入链接
            this.insertContent('\n[link](href)');
        },
        insertImage() {// 插入图片
            this.insertContent('\n![image](imgUrl)');
        },
        insertTable() {// 插入表格
            this.insertContent(
                '\nheader 1 | header 2\n---|---\nrow 1 col 1 | row 1 col 2\nrow 2 col 1 | row 2 col 2\n\n'
            );
        },
        handleSave() {// 保存操作
            const {currentValue, themeName, html} = this;
            this.$emit('on-save', {
                theme: themeName,
                value: currentValue,
                html
            });
        },
        toggleSlideDown() {// 显示主题选项
            this.slideDown = !this.slideDown;
        },
        setThemes(name) {// 设置主题
            this.themeName = name;
            this.themeSlideDown = false;
            this.$emit('on-theme-change', name);
        },
        exportFile() {// 导出为.md格式
            saveFile(this.currentValue, this.exportFileName + '.md');
        },
        importFile(e) {// 导入本地文件
            const file = e.target.files[0];
            if (!file) {
                return;
            }
            const {type} = file;
            if (!['text/markdown', 'text/src'].includes(type)) {
                return;
            }
            const reader = new FileReader();
            reader.readAsText(file, {
                encoding: 'utf-8'
            });
            reader.onload = () => {
                this.currentValue = reader.result;
                e.target.value = '';
                if (this.pro) {// 专业版，手动set value
                    this.editor.setOption('value', this.currentValue);
                }
            };
            reader.onerror = err => {
                console.error(err);
            }
        },
        handlePaste(_, e) {// 粘贴图片
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
                    this.$emit('on-upload-image', file);
                    e.preventDefault();
                }
            }
        },
        mousescrollSide(side) {// 设置究竟是哪个半边在主动滑动
            this.scrollSide = side;
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
        },
        chooseImage() {// 选择图片
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = ()=>{
               const files = input.files;
               if(files[0]){
                   this.$emit('on-upload-image', files[0]);
                   input.value = '';
               }
            }
            input.click();
        },
        addCopyListener() {// 监听复制操作
            setTimeout(() => {
                const btns = document.querySelectorAll(
                    '.code-block .copy-code'
                );
                this.btns = btns;
                for (let i = 0, len = btns.length; i < len; i++) {
                    btns[i].onclick = () => {
                        const code = btns[i].parentNode.querySelectorAll('pre')[0].innerText;
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
        /**
         * 自定义事件
         * @param act
         */
        onCustom(act) {
            this.$emit('on-custom', act);
        }
    },
    destroyed() {// 销毁时清除定时器
        clearInterval(this.timerId);
    }
};
