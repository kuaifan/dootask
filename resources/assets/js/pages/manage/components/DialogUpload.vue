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
import {mapGetters} from "vuex";
import {chunkedUpload, CHUNK_THRESHOLD} from "../../../store/chunkedUpload";

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
            chunkedTasks: {},   // uid -> {controller, uploadId} 仅分片路径在跑的任务，供 cancel() 命中
        }
    },

    computed: {
        ...mapGetters(['getDialogQuote']),

        headers() {
            return {
                fd: $A.getSessionStorageString("userWsFd"),
                token: this.userToken,
            }
        },

        params() {
            return {
                dialog_id: this.dialogId,
                reply_id: this.quoteData?.id || 0,
            }
        },

        quoteData() {
            return this.getDialogQuote(this.dialogId)?.content || null
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
                data.thumb = $A.mainUrl(`images/ext/${ext}.png`)
            } else {
                data.thumb = $A.mainUrl(`images/ext/file.png`)
            }
            this.fileMsgCaches[cacheName] = data
        },

        handleBeforeUpload(file) {
            //上传前
            // ≥ 10MB 走分片上传（拦截 iview 原生 action POST）
            if (file.size >= CHUNK_THRESHOLD) {
                this.handleChunkedUpload(file);
                return false;
            }
            return new Promise((resolve) => {
                this.fileMsgData(file)
                if (/\.(jpe?g|webp|png|gif)$/i.test(file.name)) {
                    this.$store.dispatch("showSpinner", 600)
                    this.imageFileToObject(file).then(data => {
                        this.fileMsgData(file, data)
                        resolve();
                    }).finally(() => {
                        this.$store.dispatch("hiddenSpinner")
                    });
                    return
                }
                resolve();
            });
        },

        async handleChunkedUpload(rawFile) {
            // 大文件分片上传：构造与 iview file 同 shape 的伪 file 对象，
            // 沿用原 on-progress/on-success/on-error 协议给父级 DialogWrapper
            this.fileMsgData(rawFile);
            if (/\.(jpe?g|webp|png|gif)$/i.test(rawFile.name)) {
                try {
                    const imgData = await this.imageFileToObject(rawFile);
                    this.fileMsgData(rawFile, imgData);
                } catch (_e) { /* 图片预处理失败不阻断上传 */ }
            }
            const pseudo = {
                uid: 'chunked-' + Date.now() + '-' + Math.random().toString(36).slice(2),
                name: rawFile.name,
                size: rawFile.size,
                status: 'uploading',
                percentage: 0,
                showProgress: true, // DialogWrapper.chatFile 根据此字段决定是否渲染进度
            };
            if (this.$parent.$options.name === 'DialogWrapper') {
                pseudo.tempId = this.$parent.getTempId();
            } else {
                pseudo.tempId = $A.randNum(1000000000, 9999999999);
            }
            pseudo.msg = {};
            const msgName = this.fileMsgName(rawFile);
            if (this.fileMsgCaches[msgName]) {
                pseudo.msg = this.fileMsgCaches[msgName];
                delete this.fileMsgCaches[msgName];
            }
            this.$emit('on-progress', pseudo);

            const controller = new AbortController();
            const task = {controller, uploadId: ''};
            this.chunkedTasks[pseudo.uid] = task;

            chunkedUpload({
                file: rawFile,
                scene: 'dialog_file',
                sceneParams: {
                    dialog_ids: [this.dialogId],
                    reply_id: this.quoteData?.id || 0,
                },
                onProgress: percent => {
                    pseudo.percentage = percent;
                    this.$emit('on-progress', pseudo);
                },
                onStart: uploadId => { task.uploadId = uploadId; },
                signal: controller.signal,
            }).then(data => {
                pseudo.status = 'finished';
                pseudo.percentage = 100;
                pseudo.data = data;
                this.$emit('on-success', pseudo);
                if (data && data.task_id) {
                    this.$store.dispatch("getTaskFiles", data.task_id);
                }
            }).catch(err => {
                // 用户主动取消：abort 在 axios 抛 CanceledError(message='canceled')，calcMd5 抛 Error('aborted')；signal.aborted 兜底
                if (controller.signal.aborted) {
                    return;
                }
                const msg = (err && err.message) || $L('发送失败');
                $A.modalWarning({
                    title: '发送失败',
                    content: '文件 ' + rawFile.name + ' 发送失败，' + msg,
                });
                this.$emit('on-error', pseudo);
            }).finally(() => {
                delete this.chunkedTasks[pseudo.uid];
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
            const task = this.chunkedTasks[uid];
            if (task) {
                // 分片路径：abort 在飞请求 + 通知后端清掉 Redis/分片目录。
                // 返回 true 后由 DialogWrapper.onCancelSend 调 forgetTempMsg 移除临时气泡。
                task.controller.abort();
                if (task.uploadId) {
                    this.$store.dispatch('call', {
                        url: 'upload/cancel',
                        data: {upload_id: task.uploadId},
                        method: 'post',
                    }).catch(() => { /* 后端有 24h TTL 兜底，失败可忽略 */ });
                }
                delete this.chunkedTasks[uid];
                return true;
            }
            return this.$refs.upload.cancel(uid);
        },

        /**
         * 获取图片文件详情（缩略图、宽、高）
         * @param file
         * @returns {Promise<unknown>}
         */
        imageFileToObject(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = ({target}) => {
                    const image = new Image();
                    image.onload = () => {
                        const canvas = document.createElement('canvas');
                        const context = canvas.getContext('2d');
                        const originWidth = image.width, originHeight = image.height;
                        const maxWidth = 500, maxHeight = 500;
                        let targetWidth = originWidth, targetHeight = originHeight;
                        if (originWidth > maxWidth || originHeight > maxHeight) {
                            if (originWidth / originHeight > maxWidth / maxHeight) {
                                targetWidth = maxWidth;
                                targetHeight = Math.round(maxWidth * (originHeight / originWidth));
                            } else {
                                targetHeight = maxHeight;
                                targetWidth = Math.round(maxHeight * (originWidth / originHeight));
                            }
                        }
                        canvas.width = targetWidth;
                        canvas.height = targetHeight;
                        context.clearRect(0, 0, targetWidth, targetHeight);
                        context.drawImage(image, 0, 0, targetWidth, targetHeight);
                        resolve({
                            type: 'img',
                            thumb: canvas.toDataURL('image/webp', 0.92),
                            width: canvas.width,
                            height: canvas.height,
                        });
                    };
                    image.onerror = () => {
                        reject();
                    }
                    image.src = target.result;
                }
                reader.onerror = () => {
                    reject();
                }
                reader.readAsDataURL(file);
            })
        },
    }
}
</script>
