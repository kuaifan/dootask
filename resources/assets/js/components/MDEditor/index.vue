<template>
    <div class="mdeditor-wrapper">
        <div class="mdeditor-box">
            <MarkdownPro
                ref="md1"
                v-model="content"
                :height="height"
                :toolbars="toolbars"
                :copyBtnText="$L('复制代码')"
                :is-custom-fullscreen="transfer"
                @on-custom="customClick"
                @on-upload-image="handleUploadImageUpload"/>
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
        <Modal v-model="transfer" class="mdeditor-transfer" footer-hide fullscreen transfer :closable="false">
            <div class="mdeditor-transfer-body">
                <MarkdownPro
                    ref="md2"
                    v-if="transfer"
                    v-model="content"
                    :toolbars="toolbars"
                    :copyBtnText="$L('复制代码')"
                    :is-custom-fullscreen="transfer"
                    height="100%"
                    @on-custom="customClick"
                    @on-upload-image="handleUploadImageUpload"/>
            </div>
            <Spin fix v-if="uploadIng > 0">
                <Icon type="ios-loading" class="icon-loading"></Icon>
                <div>{{$L('正在上传文件...')}}</div>
            </Spin>
        </Modal>
        <Modal v-model="html2md" title="html转markdown" okText="转换成markdown" width="680" class-name="simple-modal" @on-ok="htmlOk" transfer>
            <Input type="textarea" v-model="htmlValue" :rows="14" placeholder="请输入html代码..." />
        </Modal>
    </div>
</template>

<style lang="scss">
    .mdeditor-transfer {
        background-color: #ffffff;
        .ivu-modal-header {
            display: none;
        }
        .ivu-modal-close {
            top: 7px;
        }
        .mdeditor-transfer-body {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            padding: 0;
            margin: 0;
        }
    }
</style>
<style lang="scss" scoped>
    .mdeditor-box {
        position: relative;
    }
    .upload-control {
        display: none;
        width: 0;
        height: 0;
        overflow: hidden;
    }
</style>
<script>
    import MarkdownPro from './pro';
    import ImgUpload from "../ImgUpload";
    import {mapState} from "vuex";

    export default {
        name: 'MDEditor',
        components: {ImgUpload, MarkdownPro},
        props: {
            value: {
                default: ''
            },
            height: {
                default: 380,
            },
            toolbars: {
                type: Object,
                default: () => {
                    return {
                        strong: true,
                        italic: true,
                        overline: true,
                        h1: true,
                        h2: true,
                        h3: true,
                        h4: false,
                        h5: false,
                        h6: false,
                        hr: true,
                        quote: true,
                        ul: true,
                        ol: true,
                        code: true,
                        link: true,
                        image: false,
                        uploadImage: false,
                        table: true,
                        checked: true,
                        notChecked: true,
                        split: true,
                        preview: true,
                        fullscreen: false,
                        theme: false,
                        exportmd: false,
                        importmd: false,
                        save: false,
                        clear: false,

                        custom_image: true,
                        custom_uploadImage: true,
                        custom_uploadFile: true,
                        custom_fullscreen: true,
                    };
                }
            }
        },
        data() {
            return {
                content: '',
                transfer: false,
                html2md: false,
                htmlValue: '',

                uploadIng: 0,
                uploadFormat: ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'esp', 'pdf', 'rar', 'zip', 'gz', 'ai', 'avi', 'bmp', 'cdr', 'eps', 'mov', 'mp3', 'mp4', 'pr', 'psd', 'svg', 'tif'],
                actionUrl: $A.apiUrl('system/fileupload'),
                maxSize: 1024000
            };
        },
        mounted() {
            this.content = this.value;
        },
        activated() {
            this.content = this.value;
        },
        computed: {
            ...mapState(['userToken']),

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
                this.content = newValue;
            },
            content(val) {
                this.$emit('input', val);
            },
        },
        methods: {
            editorImage(lists) {
                for (let i = 0; i < lists.length; i++) {
                    let item = lists[i];
                    if (typeof item === 'object' && typeof item.url === "string") {
                        if (this.transfer) {
                            this.$refs.md2.insertContent('\n![image](' + item.url + ')');
                        } else {
                            this.$refs.md1.insertContent('\n![image](' + item.url + ')');
                        }
                    }
                }
            },
            customClick(act) {
                switch (act) {
                    case "image-browse": {
                        this.$refs.myUpload.browsePicture();
                        break;
                    }
                    case "image-upload": {
                        this.$refs.myUpload.handleClick();
                        break;
                    }
                    case "file-upload": {
                        this.$refs.fileUpload.handleClick();
                        break;
                    }
                    case "fullscreen": {
                        this.transfer = !this.transfer;
                        break;
                    }
                    case "html2md": {
                        this.html2md = true;
                        break;
                    }
                }
            },
            htmlOk() {
                $A.loadScript('js/html2md.js', (e) => {
                    if (e !== null || typeof toMarkdown !== 'function') {
                        $A.modalAlert("组件加载失败！");
                        return;
                    }
                    if (this.transfer) {
                        this.$refs.md2.insertContent('\n' + toMarkdown(this.htmlValue, { gfm: true }));
                    } else {
                        this.$refs.md1.insertContent('\n' + toMarkdown(this.htmlValue, { gfm: true }));
                    }
                    this.htmlValue = "";
                });
            },

            handleUploadImageUpload(file) {
                //手动传图片
                this.$refs.myUpload.handleManual(file);
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
                    let con = `[${res.data.name} (${$A.bytesToSize(res.data.size * 1024)})](${res.data.url})`;
                    if (this.transfer) {
                        this.$refs.md2.insertContent(con);
                    } else {
                        this.$refs.md1.insertContent(con);
                    }
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
