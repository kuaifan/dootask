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
    name: 'TaskUpload',
    props: {
        maxSize: {
            type: Number,
            default: 204800
        }
    },

    data() {
        return {
            uploadFormat: ['jpg', 'jpeg', 'png', 'gif', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'esp', 'pdf', 'rar', 'zip', 'gz', 'ai', 'avi', 'bmp', 'cdr', 'eps', 'mov', 'mp3', 'mp4', 'pr', 'psd', 'svg', 'tif'],
            actionUrl: this.$store.state.method.apiUrl('project/task/upload'),
        }
    },

    computed: {
        ...mapState(['userToken', 'projectOpenTask']),

        headers() {
            return {
                fd: this.$store.state.method.getStorageString("userWsFd"),
                token: this.userToken,
            }
        },

        params() {
            return {
                task_id: this.projectOpenTask.id,
            }
        },
    },

    methods: {
        handleProgress(event, file) {
            //上传时
            if (typeof file.tempId === "undefined") {
                file.tempId = $A.randomString(8);
                this.projectOpenTask.files.push(file);
            }
        },

        handleSuccess({ret, data, msg}, file) {
            //上传完成
            let index = this.projectOpenTask.files.findIndex(({tempId}) => tempId == file.tempId);
            if (index > -1) {
                this.projectOpenTask.files.splice(index, 1);
            }
            if (ret === 1) {
                this.$store.commit("taskUploadSuccess", data)
            } else {
                this.$refs.upload.fileList.pop();
                $A.modalWarning({
                    title: '发送失败',
                    content: '文件 ' + file.name + ' 发送失败，' + msg
                });
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
