<template>
    <Upload
        name="files"
        ref="upload"
        :action="actionUrl"
        :headers="headers"
        :data="params"
        multiple
        :format="uploadFormat"
        :show-upload-list="false"
        :max-size="maxSize"
        :before-upload="handleBeforeUpload"
        :on-progress="handleProgress"
        :on-success="handleSuccess"
        :on-format-error="handleFormatError"
        :on-exceeded-size="handleMaxSize">
    </Upload>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: 'DialogUpload',
    props: {
        dialogId: {
            type: Number,
            default: 0
        },
        maxSize: {
            type: Number,
            default: 1024000
        }
    },

    data() {
        return {
            fileMsgCaches: {},  // 文件信息缓存
            uploadFormat: [],   // 不限制上传文件类型
            actionUrl: $A.apiUrl('dialog/msg/sendfile'),
        }
    },

    computed: {
        ...mapState(['cacheDialogs']),

        headers() {
            return {
                fd: $A.getSessionStorageString("userWsFd"),
                token: this.userToken,
            }
        },

        params() {
            return {
                dialog_id: this.dialogId,
                reply_id: this.dialogData.extra_quote_id || 0,
            }
        },

        dialogData() {
            return this.cacheDialogs.find(({id}) => id == this.dialogId) || {};
        },
    },

    methods: {
        fileMsgName(file) {
            return `${file.name}::${file.size}`
        },

        fileMsgData(file, data = undefined) {
            const cacheName = this.fileMsgName(file)
            if ($A.isJson(data)) {
                this.fileMsgCaches[cacheName] = Object.assign(this.fileMsgCaches[cacheName] || {}, data)
                return
            }
            data = {
                type: 'file',
                thumb: null,
                width: -1,
                height: -1,
                name: file.name,
                size: file.size,
                ext: file.name.split('.').pop(),
            }
            let {ext} = data
            if (ext === 'docx') {
                ext = 'doc'
            } else if (ext === 'xlsx') {
                ext = 'xls'
            } else if (ext === 'pptx') {
                ext = 'ppt'
            }
            if (["ai", "avi", "bmp", "cdr", "doc", "eps", "gif", "mov", "mp3", "mp4", "pdf", "ppt", "pr", "psd", "rar", "svg", "tif", "txt", "xls", "zip"].includes(ext)) {
                data.thumb = $A.apiUrl(`../images/ext/${ext}.png`)
            } else {
                data.thumb = $A.apiUrl(`../images/ext/file.png`)
            }
            this.fileMsgCaches[cacheName] = data
        },

        handleBeforeUpload(file) {
            //上传前
            return new Promise((resolve) => {
                this.fileMsgData(file)
                if (/\.(jpe?g|webp|png|gif)$/i.test(file.name)) {
                    const reader = new FileReader();
                    reader.readAsDataURL(file);
                    reader.onload = ({target}) => {
                        const image = new Image();
                        image.onload = () => {
                            this.fileMsgData(file, {
                                type: 'img',
                                thumb: target.result,
                                width: image.width,
                                height: image.height,
                            })
                            resolve();
                        };
                        image.src = target.result;
                    }
                    return
                }
                resolve();
            });
        },

        handleProgress(event, file) {
            //上传时
            if (file.tempId === undefined) {
                if (this.$parent.$options.name === 'DialogWrapper') {
                    file.tempId = this.$parent.getTempId()
                } else {
                    file.tempId = $A.randNum(1000000000, 9999999999)
                }
                //
                file.msg = {}
                const msgName = this.fileMsgName(file)
                if (this.fileMsgCaches[msgName]) {
                    file.msg = this.fileMsgCaches[msgName]
                    delete this.fileMsgCaches[msgName]
                }
            }
            this.$emit('on-progress', file)
        },

        handleSuccess(res, file) {
            //上传完成
            if (res.ret === 1) {
                file.data = res.data;
                this.$emit('on-success', file);
                if (res.data.task_id) {
                    this.$store.dispatch("getTaskFiles", res.data.task_id)
                }
            } else {
                $A.modalWarning({
                    title: '发送失败',
                    content: '文件 ' + file.name + ' 发送失败，' + res.msg
                });
                this.$emit('on-error', file);
                this.$refs.upload.fileList.pop();
            }
        },

        handleFormatError(file) {
            //上传类型错误
            $A.modalWarning({
                title: '文件格式不正确',
                content: '文件 ' + file.name + ' 格式不正确，仅支持发送：' + this.uploadFormat.join(',')
            });
        },

        handleMaxSize(file) {
            //上传大小错误
            $A.modalWarning({
                title: '超出文件大小限制',
                content: '文件 ' + file.name + ' 太大，不能发送超过' + $A.bytesToSize(this.maxSize * 1024) + '。'
            });
        },

        handleClick() {
            //手动上传
            this.$refs.upload.handleClick()
        },

        upload(file) {
            //手动传file
            this.$refs.upload.upload(file);
        },

        cancel(uid) {
            //取消上传
            return this.$refs.upload.cancel(uid);
        }
    }
}
</script>
