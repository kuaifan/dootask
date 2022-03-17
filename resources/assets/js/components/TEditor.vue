<template>
    <div class="teditor-wrapper">
        <div class="teditor-box" :class="[!inline && spinShow ? 'teditor-loadstyle' : 'teditor-loadedstyle']">
            <template v-if="inline">
                <div ref="myTextarea" :id="id" v-html="spinShow ? '' : content"></div>
                <Icon v-if="spinShow" type="ios-loading" :size="18" class="icon-loading icon-inline"></Icon>
            </template>
            <template v-else>
                <textarea ref="myTextarea" :id="id">{{ content }}</textarea>
                <Spin fix v-if="spinShow">
                    <Icon type="ios-loading" :size="18" class="icon-loading"></Icon>
                    <div>{{$L('加载组件中...')}}</div>
                </Spin>
            </template>
            <ImgUpload
                ref="myUpload"
                class="upload-control"
                type="callback"
                :uploadIng.sync="uploadIng"
                @on-callback="editorImage"
                num="50"/>
            <Upload
                name="files"
                ref="fileUpload"
                class="upload-control"
                :action="actionUrl"
                :headers="headers"
                multiple
                :format="uploadFormat"
                :show-upload-list="false"
                :max-size="maxSize"
                :on-progress="handleProgress"
                :on-success="handleSuccess"
                :on-error="handleError"
                :on-format-error="handleFormatError"
                :on-exceeded-size="handleMaxSize"
                :before-upload="handleBeforeUpload"/>
        </div>
        <Spin fix v-if="uploadIng > 0">
            <Icon type="ios-loading" class="icon-loading"></Icon>
            <div>{{$L('正在上传文件...')}}</div>
        </Spin>
        <Modal v-model="transfer" class="teditor-transfer" @on-visible-change="transferChange" footer-hide fullscreen transfer>
            <div slot="close">
                <Button type="primary" size="small">{{$L('完成')}}</Button>
            </div>
            <div class="teditor-transfer-body">
                <textarea :id="'T_' + id">{{ content }}</textarea>
            </div>
            <Spin fix v-if="uploadIng > 0">
                <Icon type="ios-loading" class="icon-loading"></Icon>
                <div>{{$L('正在上传文件...')}}</div>
            </Spin>
        </Modal>
    </div>
</template>

<script>
    import tinymce from 'tinymce/tinymce';
    import ImgUpload from "./ImgUpload";
    import {mapState} from "vuex";

    export default {
        name: 'TEditor',
        components: {ImgUpload},
        props: {
            id: {
                type: String,
                default: () => {
                    return  "tinymce_" + Math.round(Math.random() * 10000);
                }
            },
            value: {
                default: ''
            },
            height: {
                default: 360,
            },
            minHeight: {
                type: Number,
                default: 0,
            },
            htmlClass: {
                default: '',
                type: String
            },
            plugins: {
                type: Array,
                default: () => {
                    return [
                        'advlist autolink lists link image charmap print preview hr anchor pagebreak',
                        'searchreplace visualblocks visualchars code',
                        'insertdatetime media nonbreaking save table directionality',
                        'emoticons paste codesample'
                    ];
                }
            },
            toolbar: {
                type: String,
                default: ' undo redo | styleselect | uploadImages | uploadFiles | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist outdent indent | link image emoticons media codesample | preview screenload',
            },
            options: {
                type: Object,
                default: () => {
                    return {};
                }
            },
            optionFull: {
                type: Object,
                default: () => {
                    return {};
                }
            },
            inline: {
                type: Boolean,
                default: false
            },
            readOnly: {
                type: Boolean,
                default: false
            },
            autoSize: {
                type: Boolean,
                default: false
            },
            placeholder: {
                type: String,
                default: ''
            },
            placeholderFull: {
                type: String,
                default: ''
            },
        },
        data() {
            return {
                content: '',
                editor: null,
                editorT: null,
                cTinyMce: null,
                checkerTimeout: null,
                isTyping: false,

                spinShow: true,
                transfer: false,

                uploadIng: 0,
                uploadFormat: ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'esp', 'pdf', 'rar', 'zip', 'gz', 'ai', 'avi', 'bmp', 'cdr', 'eps', 'mov', 'mp3', 'mp4', 'pr', 'psd', 'svg', 'tif'],
                actionUrl: $A.apiUrl('system/fileupload'),
                maxSize: 10240
            };
        },
        mounted() {
            this.content = this.value;
            this.init();
        },
        activated() {
            this.content = this.value;
            this.init();
        },
        deactivated() {
            if (this.editor !== null) {
                this.editor.destroy();
            }
            this.spinShow = true;
            $A(this.$refs.myTextarea).show();
        },
        computed: {
            ...mapState(['userToken', 'themeIsDark']),

            headers() {
                return {
                    fd: $A.getStorageString("userWsFd"),
                    token: this.userToken,
                }
            },
        },
        watch: {
            value(newValue) {
                if (newValue == null) {
                    newValue = "";
                }
                if (!this.isTyping) {
                    this.setContent(newValue);
                }
            },
            readOnly(value) {
                if (this.editor !== null) {
                    if (value) {
                        this.editor.setMode('readonly');
                    } else {
                        this.editor.setMode('design');
                    }
                }
            }
        },
        methods: {
            init() {
                this.$nextTick(() => {
                    tinymce.init(this.concatAssciativeArrays(this.option(false), this.options));
                });
            },

            initTransfer() {
                this.$nextTick(() => {
                    tinymce.init(this.concatAssciativeArrays(this.option(true), this.optionFull));
                });
            },

            plugin(isFull) {
                if (isFull) {
                    return this.plugins.filter((val) => val != 'autoresize');
                } else {
                    return this.plugins;
                }
            },

            option(isFull) {
                let optionInfo = {
                    inline: isFull ? false : this.inline,
                    selector: (isFull ? '#T_' : '#') + this.id,
                    base_url: $A.originUrl('js/tinymce'),
                    language: "zh_CN",
                    toolbar: this.toolbar,
                    plugins: this.plugin(isFull),
                    placeholder: isFull && this.placeholderFull ? this.placeholderFull : this.placeholder,
                    save_onsavecallback: (e) => {
                        this.$emit('editorSave', e);
                    },
                    paste_data_images: true,
                    menu: {
                        view: {
                            title: 'View',
                            items: 'code | visualaid visualchars visualblocks | spellchecker | preview fullscreen screenload | showcomments'
                        },
                        insert: {
                            title: "Insert",
                            items: "image link media addcomment pageembed template codesample inserttable | charmap emoticons hr | pagebreak nonbreaking anchor toc | insertdatetime | uploadImages | uploadFiles"
                        }
                    },
                    codesample_languages: [
                        {text:"HTML/VUE/XML",value:"markup"},
                        {text:"JavaScript",value:"javascript"},
                        {text:"CSS",value:"css"},
                        {text:"PHP",value:"php"},
                        {text:"Ruby",value:"ruby"},
                        {text:"Python",value:"python"},
                        {text:"Java",value:"java"},
                        {text:"C",value:"c"},
                        {text:"C#",value:"csharp"},
                        {text:"C++",value:"cpp"}
                    ],
                    height: isFull ? '100%' : ($A.rightExists(this.height, '%') ? this.height : ($A.runNum(this.height) || 360)),
                    resize: !isFull,
                    convert_urls:false,
                    toolbar_mode: 'sliding',
                    content_css: this.themeIsDark ? 'dark' : 'default',
                    setup: (editor) => {
                        editor.ui.registry.addMenuButton('uploadImages', {
                            text: this.$L('图片'),
                            tooltip: this.$L('上传/浏览 图片'),
                            fetch: (callback) => {
                                let items = [{
                                    type: 'menuitem',
                                    text: this.$L('上传本地图片'),
                                    onAction: () => {
                                        this.$refs.myUpload.handleClick();
                                    }
                                }, {
                                    type: 'menuitem',
                                    text: this.$L('浏览已上传图片'),
                                    onAction: () => {
                                        this.$refs.myUpload.browsePicture();
                                    }
                                }];
                                callback(items);
                            }
                        });
                        editor.ui.registry.addNestedMenuItem('uploadImages', {
                            icon: 'image',
                            text: this.$L('上传图片'),
                            getSubmenuItems: () => {
                                return [{
                                    type: 'menuitem',
                                    text: this.$L('上传本地图片'),
                                    onAction: () => {
                                        this.$refs.myUpload.handleClick();
                                    }
                                }, {
                                    type: 'menuitem',
                                    text: this.$L('浏览已上传图片'),
                                    onAction: () => {
                                        this.$refs.myUpload.browsePicture();
                                    }
                                }];
                            }
                        });
                        editor.ui.registry.addMenuItem('imagePreview', {
                            text: this.$L('预览图片'),
                            onAction: () => {
                                const array = this.getValueImages();
                                if (array.length === 0) {
                                    $A.messageWarning("没有可预览的图片")
                                    return;
                                }
                                this.$store.state.previewImageIndex = 0;
                                this.$store.state.previewImageList = array;
                            }
                        });
                        editor.ui.registry.addButton('uploadFiles', {
                            text: this.$L('文件'),
                            tooltip: this.$L('上传文件'),
                            onAction: () => {
                                if (this.handleBeforeUpload()) {
                                    this.$refs.fileUpload.handleClick();
                                }
                            }
                        });
                        editor.ui.registry.addMenuItem('uploadFiles', {
                            text: this.$L('上传文件'),
                            onAction: () => {
                                if (this.handleBeforeUpload()) {
                                    this.$refs.fileUpload.handleClick();
                                }
                            }
                        });
                        if (isFull) {
                            editor.ui.registry.addButton('screenload', {
                                icon: 'fullscreen',
                                tooltip: this.$L('退出全屏'),
                                onAction: () => {
                                    this.closeFull();
                                }
                            });
                            editor.ui.registry.addMenuItem('screenload', {
                                text: this.$L('退出全屏'),
                                onAction: () => {
                                    this.closeFull();
                                }
                            });
                            editor.on('Init', (e) => {
                                this.editorT = editor;
                                this.editorT.setContent(this.content);
                                if (this.readOnly) {
                                    this.editorT.setMode('readonly');
                                } else {
                                    this.editorT.setMode('design');
                                }
                            });
                        }else{
                            editor.ui.registry.addButton('screenload', {
                                icon: 'fullscreen',
                                tooltip: this.$L('全屏'),
                                onAction: () => {
                                    this.content = editor.getContent();
                                    this.transfer = true;
                                    this.initTransfer();
                                }
                            });
                            editor.ui.registry.addMenuItem('screenload', {
                                text: this.$L('全屏'),
                                onAction: () => {
                                    this.content = editor.getContent();
                                    this.transfer = true;
                                    this.initTransfer();
                                }
                            });
                            editor.on('Init', (e) => {
                                this.spinShow = false;
                                this.editor = editor;
                                this.editor.setContent(this.content);
                                if (this.readOnly) {
                                    this.editor.setMode('readonly');
                                } else {
                                    this.editor.setMode('design');
                                }
                                this.$emit('editorInit', this.editor);
                            });
                            editor.on('KeyUp', (e) => {
                                if (this.editor !== null) {
                                    this.submitNewContent();
                                }
                            });
                            editor.on('Change', (e) => {
                                if (this.editor !== null) {
                                    if (this.getContent() !== this.value) {
                                        this.submitNewContent();
                                    }
                                    this.$emit('editorChange', e);
                                }
                            });
                            editor.on('focus', () => {
                                this.$emit('on-focus');
                            });
                            editor.on('blur', () => {
                                this.$emit('on-blur');
                            });
                        }
                    },
                };
                if (this.autoSize) {
                    optionInfo.plugins.push('autoresize')
                }
                if (this.minHeight > 0) {
                    optionInfo.min_height = this.minHeight
                }
                return optionInfo;
            },

            closeFull() {
                this.content = this.getContent();
                this.$emit('input', this.content);
                this.$emit('on-blur');
                this.transfer = false;
                if (this.editorT != null) {
                    this.editorT.destroy();
                    this.editorT = null;
                }
            },

            transferChange(visible) {
                if (!visible && this.editorT != null) {
                    this.content = this.editorT.getContent();
                    this.$emit('input', this.content);
                    this.editorT.destroy();
                    this.editorT = null;
                }
            },

            getEditor() {
                return this.transfer ? this.editorT : this.editor;
            },

            concatAssciativeArrays(array1, array2) {
                if (array2.length === 0) return array1;
                if (array1.length === 0) return array2;
                let dest = [];
                for (let key in array1) {
                    if (array1.hasOwnProperty(key)) {
                        dest[key] = array1[key];
                    }
                }
                for (let key in array2) {
                    if (array2.hasOwnProperty(key)) {
                        dest[key] = array2[key];
                    }
                }
                return dest;
            },

            submitNewContent() {
                this.isTyping = true;
                if (this.checkerTimeout !== null) {
                    clearTimeout(this.checkerTimeout);
                }
                this.checkerTimeout = setTimeout(() => {
                    this.isTyping = false;
                }, 300);
                this.$emit('input', this.getContent());
            },

            insertContent(content) {
                if (this.getEditor() !== null) {
                    this.getEditor().insertContent(content);
                }else{
                    this.content+= content;
                }
            },

            getContent() {
                if (this.getEditor() === null) {
                    return "";
                }
                return this.getEditor().getContent();
            },

            setContent(content) {
                if (this.getEditor() === null) {
                    this.content = content;
                } else if (content != this.getEditor().getContent()){
                    this.getEditor().setContent(content);
                }
            },

            focus() {
                if (this.getEditor() === null) {
                    return "";
                }
                return this.getEditor().focus();
            },

            insertImage(src) {
                this.insertContent('<img src="' + src + '">');
            },

            editorImage(lists) {
                for (let i = 0; i < lists.length; i++) {
                    let item = lists[i];
                    if (typeof item === 'object' && typeof item.url === "string") {
                        this.insertImage(item.url);
                    }
                }
            },

            getValueImages() {
                let imgs = [];
                let imgReg = /<img.*?(?:>|\/>)/gi;
                let srcReg = /src=['"]?([^'"]*)['"]?/i;
                let array = (this.getContent() + "").match(imgReg);
                if (array) {
                    for (let i = 0; i < array.length; i++) {
                        let src = array[i].match(srcReg);
                        if(src[1]){
                            imgs.push(src[1]);
                        }
                    }
                }
                return imgs;
            },

            /********************文件上传部分************************/

            handleProgress(event, file) {
                //开始上传
                if (file._uploadIng === undefined) {
                    file._uploadIng = true;
                    this.uploadIng++;
                }
            },

            handleSuccess(res, file) {
                //上传完成
                this.uploadIng--;
                if (res.ret === 1) {
                    this.insertContent(`<a href="${res.data.url}" target="_blank">${res.data.name} (${$A.bytesToSize(res.data.size * 1024)})</a>`);
                } else {
                    $A.noticeWarning({
                        title: this.$L('上传失败'),
                        desc: this.$L('文件 ' + file.name + ' 上传失败，' + res.msg)
                    });
                }
            },

            handleError() {
                //上传错误
                this.uploadIng--;
            },

            handleFormatError(file) {
                //上传类型错误
                $A.noticeWarning({
                    title: this.$L('文件格式不正确'),
                    desc: this.$L('文件 ' + file.name + ' 格式不正确，仅支持上传：' + this.uploadFormat.join(','))
                });
            },

            handleMaxSize(file) {
                //上传大小错误
                $A.noticeWarning({
                    title: this.$L('超出文件大小限制'),
                    desc: this.$L('文件 ' + file.name + ' 太大，不能超过：' + $A.bytesToSize(this.maxSize * 1024))
                });
            },

            handleBeforeUpload() {
                //上传前判断
                return true;
            },
        }
    }
</script>
