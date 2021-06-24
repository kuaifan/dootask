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
            default: 204800
        }
    },

    data() {
        return {
            uploadFormat: ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'esp', 'pdf', 'rar', 'zip', 'gz', 'ai', 'avi', 'bmp', 'cdr', 'eps', 'mov', 'mp3', 'mp4', 'pr', 'psd', 'svg', 'tif'],
            actionUrl: this.$store.state.method.apiUrl('dialog/msg/sendfile'),
        }
    },

    computed: {
        ...mapState(['userToken']),

        headers() {
            return {
                fd: this.$store.state.method.getStorageString("userWsFd"),
                token: this.userToken,
            }
        },

        params() {
            return {
                dialog_id: this.dialogId,
            }
        }
    },

    methods: {
        handleProgress(event, file) {
            //上传时
            if (typeof file.tempId === "undefined") {
                file.tempId = $A.randomString(8);
                this.$emit('on-progress', file);
            }
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
    }
}
</script>
