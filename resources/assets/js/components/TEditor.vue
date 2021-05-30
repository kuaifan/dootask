<template>
    <div>
        <div class="teditor-box" :class="[spinShow?'teditor-loadstyle':'teditor-loadedstyle']">
            <textarea ref="myTextarea" :id="id">{{ content }}</textarea>
            <Spin fix v-if="spinShow">
                <Icon type="ios-loading" size=18 class="upload-control-spin-icon-load"></Icon>
                <div>{{$L('加载组件中...')}}</div>
            </Spin>
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
                :data="params"
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
            <Icon type="ios-loading" class="upload-control-spin-icon-load"></Icon>
            <div>{{$L('正在上传文件...')}}</div>
        </Spin>
        <Modal v-model="transfer" class="teditor-transfer" @on-visible-change="transferChange" class-name="simple-modal" footer-hide fullscreen transfer>
            <div slot="close">
                <Button type="primary" size="small">{{$L('完成')}}</Button>
            </div>
            <div class="teditor-transfer-body">
                <textarea :id="'T_' + id">{{ content }}</textarea>
            </div>
            <Spin fix v-if="uploadIng > 0">
                <Icon type="ios-loading" class="upload-control-spin-icon-load"></Icon>
                <div>{{$L('正在上传文件...')}}</div>
            </Spin>
        </Modal>
    </div>
</template>

<style lang="scss">
:global {
    .teditor-box {
        textarea {
            opacity: 0;
        }

        .tox-tinymce {
            box-shadow: none;
            box-sizing: border-box;
            border-color: #dddee1;
            border-radius: 4px;
            overflow: hidden;

            .tox-statusbar {
                span.tox-statusbar__branding {
                    a {
                        display: none;
                    }
                }
            }
        }
    }

    .teditor-transfer {
        background-color: #ffffff;

        .tox-toolbar {
            > div:last-child {
                > button:last-child {
                    margin-right: 64px;
                }
            }
        }

        .ivu-modal-header {
            display: none;
        }

        .ivu-modal-close {
            top: 7px;
            z-index: 2;
        }

        .teditor-transfer-body {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 0;
            margin: 0;

            textarea {
                opacity: 0;
            }

            .tox-tinymce {
                border: 0;

                .tox-statusbar {
                    span.tox-statusbar__branding {
                        a {
                            display: none;
                        }
                    }
                }
            }
        }
    }

    .tox {
        &.tox-silver-sink {
            z-index: 13000;
        }
    }
}
</style>
<style lang="scss" scoped>
:global {
    .teditor-loadstyle {
        width: 100%;
        height: 180px;
        overflow: hidden;
        position: relative;
    }

    .teditor-loadedstyle {
        width: 100%;
        max-height: inherit;
        overflow: inherit;
        position: relative;
    }

    .upload-control {
        display: none;
        width: 0;
        height: 0;
        overflow: hidden;
    }
}
</style>

<script>
    import tinymce from 'tinymce/tinymce';
    import ImgUpload from "./ImgUpload";

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
            htmlClass: {
                default: '',
                type: String
            },
            plugins: {
                type: Array,
                default: () => {
                    return [
                        'advlist autolink lists link image charmap print preview hr anchor pagebreak imagetools',
                        'searchreplace visualblocks visualchars code',
                        'insertdatetime media nonbreaking save table contextmenu directionality',
                        'emoticons paste textcolor colorpicker imagetools codesample'
                    ];
                }
            },
            toolbar: {
                type: String,
                default: ' undo redo | styleselect | uploadImages | uploadFiles | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist outdent indent | link image emoticons media codesample | preview screenload',
            },
            other_options: {
                type: Object,
                default: () => {
                    return {};
                }
            },
            readonly: {
                type: Boolean,
                default: false
            }
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
                uploadFormat: ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'esp', 'pdf', 'rar', 'zip', 'gz'],
                actionUrl: $A.apiUrl('system/fileupload'),
                params: { token: $A.getToken() },
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
        watch: {
            value(newValue) {
                if (newValue == null) {
                    newValue = "";
                }
                if (!this.isTyping) {
                    if (this.getEditor() !== null) {
                        this.getEditor().setContent(newValue);
                    } else{
                        this.content = newValue;
                    }
                }
            },
            readonly(value) {
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
                    tinymce.init(this.concatAssciativeArrays(this.options(false), this.other_options));
                });
            },

            initTransfer() {
                this.$nextTick(() => {
                    tinymce.init(this.concatAssciativeArrays(this.options(true), this.other_options));
                });
            },

            options(isFull) {
                return {
                    selector: (isFull ? '#T_' : '#') + this.id,
                    base_url: $A.serverUrl('js/build'),
                    language: "zh_CN",
                    toolbar: this.toolbar,
                    plugins: this.plugins,
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
                            items: "image link media addcomment pageembed template codesample inserttable | charmap emoticons hr | pagebreak nonbreaking anchor toc | insertdatetime | uploadImages browseImages | uploadFiles"
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
                    toolbar_drawer: 'floating',
                    setup: (editor) => {
                        editor.ui.registry.addMenuButton('uploadImages', {
                            text: this.$L('图片'),
                            tooltip: this.$L('上传/浏览 图片'),
                            fetch: (callback) => {
                                let items = [{
                                    type: 'menuitem',
                                    text: this.$L('上传图片'),
                                    onAction: () => {
                                        this.$refs.myUpload.handleClick();
                                    }
                                }, {
                                    type: 'menuitem',
                                    text: this.$L('浏览图片'),
                                    onAction: () => {
                                        this.$refs.myUpload.browsePicture();
                                    }
                                }];
                                callback(items);
                            }
                        });
                        editor.ui.registry.addMenuItem('uploadImages', {
                            text: this.$L('上传图片'),
                            onAction: () => {
                                this.$refs.myUpload.handleClick();
                            }
                        });
                        editor.ui.registry.addMenuItem('browseImages', {
                            text: this.$L('浏览图片'),
                            onAction: () => {
                                this.$refs.myUpload.browsePicture();
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
                                if (this.readonly) {
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
                                if (this.readonly) {
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
                        }
                    },
                };
            },

            closeFull() {
                this.content = this.getContent();
                this.$emit('input', this.content);
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

            /********************文件上传部分************************/

            handleProgress() {
                //开始上传
                this.uploadIng++;
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
                this.params = {
                    token: $A.getToken(),
                };
                return true;
            },
        }
    }
</script>
