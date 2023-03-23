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
            default: 1024000
        }
    },

    data() {
        return {
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
        handleProgress(event, file) {
            //上传时
            if (file.tempId === undefined) {
                if (this.$parent.$options.name === 'DialogWrapper') {
                    file.tempId = this.$parent.getTempId()
                } else {
                    file.tempId = $A.randNum(1000000000, 9999999999)
                }
                this.$emit('on-progress', file)
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
