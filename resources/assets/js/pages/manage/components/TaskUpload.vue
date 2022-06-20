<template>
    <Upload
        name="files"
        ref="upload"
        action=""
        multiple
        :format="uploadFormat"
        :show-upload-list="false"
        :max-size="maxSize"
        :on-format-error="handleFormatError"
        :on-exceeded-size="handleMaxSize"
        :before-upload="handleBeforeUpload">
    </Upload>
</template>

<script>
export default {
    name: 'TaskUpload',
    props: {
        maxSize: {
            type: Number,
            default: 1024000
        }
    },

    data() {
        return {
            uploadFormat: ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'esp', 'pdf', 'rar', 'zip', 'gz', 'ai', 'avi', 'bmp', 'cdr', 'eps', 'mov', 'mp3', 'mp4', 'pr', 'psd', 'svg', 'tif'],
        }
    },

    methods: {
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

        handleBeforeUpload(file) {
            // 拦截上传
            this.$emit("on-select-file", file)
            return false;
        },

        handleClick() {
            //手动上传
            this.$refs.upload.handleClick()
        },
    }
}
</script>
