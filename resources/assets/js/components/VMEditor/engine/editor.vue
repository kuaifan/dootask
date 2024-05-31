<template>
    <div class="vmeditor-wrapper">
        <v-md-editor
            ref="editor"
            class="vmeditor-editor"
            v-show="showEditor"
            v-model="content"
            :toolbar="toolbar"
            :left-toolbar="leftToolbar"
            :right-toolbar="rightToolbar"
            :toc-nav-position-right="tocNavPositionRight"
            :include-level="includeLevel"
            :disabled-menus="[]"
            @upload-image="handleUpload"/>
        <Spin fix v-if="uploadIng > 0">
            <Icon type="ios-loading" class="vmeditor-icon-loading"></Icon>
            <div>{{$L('正在上传文件...')}}</div>
        </Spin>
        <ImgUpload
            ref="myUpload"
            class="vmeditor-upload-control"
            type="callback"
            :uploadIng.sync="uploadIng"
            @on-callback="handleInsertImages"
            num="50"/>
        <Upload
            name="files"
            ref="fileUpload"
            class="vmeditor-upload-control"
            :action="actionUrl"
            :headers="headers"
            multiple
            paste
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
</template>

<style lang="scss" scoped>
.vmeditor-wrapper {
    width: 100%;
    height: 100%;

    .vmeditor-editor {
        width: 100%;
        height: 100%;
    }
}
.vmeditor-upload-control {
    display: none;
    width: 0;
    height: 0;
    overflow: hidden;
}
.vmeditor-icon-loading {
    font-size: 24px;
    margin-bottom: 6px;
}
</style>
<script>
import VMdEditor from '@kangc/v-md-editor/lib/codemirror-editor';
import '@kangc/v-md-editor/lib/style/codemirror-editor.css';
import vuepressTheme from '@kangc/v-md-editor/lib/theme/vuepress.js';
import '@kangc/v-md-editor/lib/theme/style/vuepress.css';

// Prism
import Prism from 'prismjs';

// Language
import {languageName} from "../../../language";
import zhCN from '@kangc/v-md-editor/lib/lang/zh-CN';
import enUS from '@kangc/v-md-editor/lib/lang/en-US';

if (languageName === "zh" || languageName === "zh-CHT") {
    VMdEditor.lang.use('zh-CN', zhCN);
} else {
    VMdEditor.lang.use('en-US', enUS);
}

// Katex
import createKatexPlugin from '@kangc/v-md-editor/lib/plugins/katex/cdn';

VMdEditor.use(createKatexPlugin());

// Mermaid
import createMermaidPlugin from '@kangc/v-md-editor/lib/plugins/mermaid/cdn';
import '@kangc/v-md-editor/lib/plugins/mermaid/mermaid.css';

VMdEditor.use(createMermaidPlugin());

// TodoList
import createTodoListPlugin from '@kangc/v-md-editor/lib/plugins/todo-list/index';
import '@kangc/v-md-editor/lib/plugins/todo-list/todo-list.css';

VMdEditor.use(createTodoListPlugin());

// CopyCode
import createCopyCodePlugin from '@kangc/v-md-editor/lib/plugins/copy-code/index';
import '@kangc/v-md-editor/lib/plugins/copy-code/copy-code.css';

VMdEditor.use(createCopyCodePlugin());

// Codemirror 编辑器的相关资源
import Codemirror from 'codemirror';
import 'codemirror/mode/markdown/markdown';
import 'codemirror/mode/javascript/javascript';
import 'codemirror/mode/css/css';
import 'codemirror/addon/edit/closebrackets';
import 'codemirror/addon/edit/closetag';
import 'codemirror/addon/edit/matchbrackets';
import 'codemirror/addon/display/placeholder';
import 'codemirror/addon/selection/active-line';
import 'codemirror/addon/scroll/simplescrollbars';
import 'codemirror/addon/scroll/simplescrollbars.css';
import 'codemirror/lib/codemirror.css';

VMdEditor.Codemirror = Codemirror;

import { editorMixin } from '../mixin';
import ImgUpload from "../../ImgUpload.vue";

export default {
    mixins: [editorMixin],

    components: {
        [VMdEditor.name]: VMdEditor,
        ImgUpload,
    },

    data() {
        return {
            showEditor: false,
            content: '',
            toolbar: {
                customImages: {
                    icon: 'v-md-icon-img',
                    title: (editor) => editor.langConfig.image.toolbar,
                    menus: [
                        {
                            name: 'image-link',
                            text: (editor) => editor.langConfig.imageLink.toolbar,
                            action: _ => {
                                this.handleInsertImages([
                                    {
                                        name: 'Description',
                                        url: 'http://',
                                    },
                                ]);
                            },
                        },
                        {
                            name: 'browse-image',
                            text: this.$L('浏览图片空间'),
                            action: _ => {
                                this.$refs.myUpload.browsePicture();
                            },
                        },
                        {
                            name: 'upload-image',
                            text: (editor) => editor.langConfig.uploadImage.toolbar,
                            action: _ => {
                                this.$refs.myUpload.handleClick();
                            },
                        },
                        {
                            name: 'upload-local',
                            text: this.$L('上传本地文件'),
                            action: _ => {
                                this.$refs.fileUpload.handleClick();
                            },
                        },
                    ],
                },
            },

            uploadIng: 0,
            uploadFormat: ['jpg', 'jpeg', 'webp', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'esp', 'pdf', 'rar', 'zip', 'gz', 'ai', 'avi', 'bmp', 'cdr', 'eps', 'mov', 'mp3', 'mp4', 'pr', 'psd', 'svg', 'tif'],
            actionUrl: $A.apiUrl('system/fileupload'),
            maxSize: 1024000,
        };
    },

    created() {
        VMdEditor.use(vuepressTheme, {
            Prism,
            extend(md) {
                // md为 markdown-it 实例，可以在此处进行修改配置,并使用 plugin 进行语法扩展
                // md.set(option).use(plugin);
            },
        });
    },

    mounted() {
        if (this.windowWidth > 1200) {
            this.$refs.editor.toggleToc(true);
        }
        this.showEditor = true;
    },

    computed: {
        headers() {
            return {
                fd: $A.getSessionStorageString("userWsFd"),
                token: this.userToken,
            }
        },
    },

    watch: {
        value: {
            handler(val) {
                if (val == null) {
                    val = "";
                }
                this.content = val;
            },
            immediate: true
        },

        content(val) {
            this.$emit('input', val);
        },
    },

    methods: {
        handleUpload(e) {
            if (e.type === 'drop') {
                this.$refs.fileUpload.onDrop(e)
            } else {
                this.$refs.fileUpload.handlePaste(e)
            }
        },

        handleInsertText(text, selctionText = '') {
            this.$refs.editor.focus();
            this.$refs.editor.replaceSelectionText(text);
            this.$refs.editor.changeSelctionTo(text, selctionText);
        },

        handleInsertImages(lists) {
            this.$refs.editor.focus();
            lists.forEach((item) => {
                const name = item.name || 'image';
                const text = `![${name}](${item.url})\n`;
                this.$refs.editor.replaceSelectionText(text);
                this.$refs.editor.changeSelctionTo(text, lists.length === 1 ? name : '');
            });
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
                const {data} = res;
                this.handleInsertText(`[${data.name} (${$A.bytesToSize(data.size * 1024)})](${data.url})`);
            } else {
                $A.modalWarning({
                    title: '上传失败',
                    content: '文件 ' + file.name + ' 上传失败，' + res.msg
                });
            }
        },

        handleError() {
            //上传错误
            this.uploadIng--;
        },

        handleFormatError(file) {
            //上传类型错误
            $A.modalWarning({
                title: '文件格式不正确',
                content: '文件 ' + file.name + ' 格式不正确，仅支持上传：' + this.uploadFormat.join(',')
            });
        },

        handleMaxSize(file) {
            //上传大小错误
            $A.modalWarning({
                title: '超出文件大小限制',
                content: '文件 ' + file.name + ' 太大，不能超过：' + $A.bytesToSize(this.maxSize * 1024) + '。'
            });
        },

        handleBeforeUpload() {
            //上传前判断
            return true;
        },
    }
}
</script>
