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
            uploadFormat: [
                'text', 'md', 'markdown',
                'drawio',
                'mind',
                'docx', 'wps', 'doc', 'xls', 'xlsx', 'ppt', 'pptx',
                'jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico', 'raw', 'svg',
                'rar', 'zip', 'jar', '7-zip', 'tar', 'gzip', '7z', 'gz', 'apk', 'dmg',
                'tif', 'tiff',
                'dwg', 'dxf',
                'ofd',
                'pdf',
                'txt',
                'htaccess', 'htgroups', 'htpasswd', 'conf', 'bat', 'cmd', 'cpp', 'c', 'cc', 'cxx', 'h', 'hh', 'hpp', 'ino', 'cs', 'css',
                'dockerfile', 'go', 'golang', 'html', 'htm', 'xhtml', 'vue', 'we', 'wpy', 'java', 'js', 'jsm', 'jsx', 'json', 'jsp', 'less', 'lua', 'makefile', 'gnumakefile',
                'ocamlmakefile', 'make', 'mysql', 'nginx', 'ini', 'cfg', 'prefs', 'm', 'mm', 'pl', 'pm', 'p6', 'pl6', 'pm6', 'pgsql', 'php',
                'inc', 'phtml', 'shtml', 'php3', 'php4', 'php5', 'phps', 'phpt', 'aw', 'ctp', 'module', 'ps1', 'py', 'r', 'rb', 'ru', 'gemspec', 'rake', 'guardfile', 'rakefile',
                'gemfile', 'rs', 'sass', 'scss', 'sh', 'bash', 'bashrc', 'sql', 'sqlserver', 'swift', 'ts', 'typescript', 'str', 'vbs', 'vb', 'v', 'vh', 'sv', 'svh', 'xml',
                'rdf', 'rss', 'wsdl', 'xslt', 'atom', 'mathml', 'mml', 'xul', 'xbl', 'xaml', 'yaml', 'yml',
                'asp', 'properties', 'gitignore', 'log', 'bas', 'prg', 'python', 'ftl', 'aspx',
                'mp3', 'wav', 'mp4', 'flv',
                'avi', 'mov', 'wmv', 'mkv', '3gp', 'rm',
                'xmind',
                'rp',
            ],
            actionUrl: $A.apiUrl('dialog/msg/sendfile'),
        }
    },

    computed: {
        ...mapState(['userToken']),

        headers() {
            return {
                fd: $A.getStorageString("userWsFd"),
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
            if (file.tempId === undefined) {
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
