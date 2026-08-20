<template>
    <div :class="`content-file ${msg.type}`">
        <div class="dialog-file">
            <img v-if="msg.type === 'img'" class="file-img" :style="imageStyle(msg)" :src="imageSrc(msg)" @click="viewFile"/>
            <div v-else-if="isVideoFile(msg)" class="file-video" :style="imageStyle(msg)" @click="viewFile">
                <img v-if="msg.thumb" :src="msg.thumb">
                <video v-else :width="imageStyle(msg, 'width')" :height="imageStyle(msg, 'height')">
                    <source :src="msg.path" type="video/mp4">
                </video>
                <div class="file-play">
                    <div class="play-icon no-dark-content">
                        <i class="taskfont">&#xe745;</i>
                    </div>
                </div>
            </div>
            <div v-else class="file-box" @click="downFile">
                <img class="file-thumb" :src="msg.thumb"/>
                <div class="file-info">
                    <div class="file-name" :title="msg.name">{{ msg.name }}</div>
                    <div class="file-size">{{ $A.bytesToSize(msg.size) }}</div>
                </div>
                <button
                    v-if="$Electron"
                    class="file-local-action"
                    type="button"
                    :title="localActionTitle"
                    :aria-label="localActionTitle"
                    :disabled="localFileStatus === 'downloading'"
                    @click.stop="handleLocalAction">
                    <LocalFileStatusIcon :status="localFileStatus"/>
                </button>
            </div>
            <div v-if="msg.percentage" class="file-percentage">
                <span :style="fileStyle(msg.percentage)"></span>
            </div>
        </div>
    </div>
</template>

<script>
import LocalFileStatusIcon from "./LocalFileStatusIcon.vue";

export default {
    components: {LocalFileStatusIcon},
    props: {
        msgId: {
            type: [Number, String],
            default: 0,
        },
        msg: Object,
    },
    data() {
        return {
            localFileStatus: 'missing',
            removeDownloadListener: null,
        }
    },
    computed: {
        localActionTitle() {
            return this.localFileStatus === 'available' ? this.$L('在文件夹中显示') : this.$L('下载');
        },
    },
    watch: {
        msgId() {
            this.refreshLocalFileStatus();
        },
    },
    mounted() {
        if (!this.$Electron) {
            return;
        }
        this.refreshLocalFileStatus();
        this.removeDownloadListener = $A.Electron.listener('downloadItemsChanged', () => {
            this.refreshLocalFileStatus();
        });
    },
    beforeDestroy() {
        if (typeof this.removeDownloadListener === 'function') {
            this.removeDownloadListener();
        }
    },
    methods: {
        viewFile() {
            this.$emit('viewFile');
        },
        downFile() {
            this.$emit('downFile');
        },

        async refreshLocalFileStatus() {
            if (!this.$Electron || !this.msgId) {
                this.localFileStatus = 'missing';
                return;
            }
            try {
                const result = await $A.Electron.sendAsync('downloadManager', {
                    action: 'messageFileStatus',
                    msgId: this.msgId,
                });
                this.localFileStatus = result?.status || 'missing';
            } catch {
                this.localFileStatus = 'missing';
            }
        },

        async handleLocalAction() {
            if (this.localFileStatus === 'downloading') {
                return;
            }
            if (this.localFileStatus === 'available') {
                try {
                    const shown = await $A.Electron.sendAsync('downloadManager', {
                        action: 'showMessageFile',
                        msgId: this.msgId,
                    });
                    if (shown) {
                        return;
                    }
                } catch {
                    // Refresh the action when the local file disappears or cannot be revealed.
                }
                this.localFileStatus = 'missing';
                return;
            }

            this.localFileStatus = 'downloading';
            this.$store.dispatch('downUrl', $A.apiUrl(`dialog/msg/download?msg_id=${this.msgId}`));
            setTimeout(() => this.refreshLocalFileStatus(), 500);
        },

        fileStyle(percentage) {
            if (percentage) {
                return {
                    width: `${percentage}%`
                };
            }
            return {};
        },

        imageStyle({width, height, thumb}, type = 'style') {
            if (width && height) {
                const ratioExceed = $A.imageRatioExceed(width, height, 3)
                if ($A.imageRatioJudge(thumb) && ratioExceed > 0) {
                    if (width > height) {
                        width = height * ratioExceed;
                    } else {
                        height = width * ratioExceed;
                    }
                }
                let maxW = 220,
                    maxH = 220,
                    tempW = width,
                    tempH = height;
                if (width > maxW || height > maxH) {
                    if (width > height) {
                        tempW = maxW;
                        tempH = height * (maxW / width);
                    } else {
                        tempW = width * (maxH / height);
                        tempH = maxH;
                    }
                }
                if (type === 'width') {
                    return tempW
                }
                if (type === 'height') {
                    return tempH
                }
                return {
                    width: tempW + 'px',
                    height: tempH + 'px',
                };
            }
            if (type === 'width' || type === 'height') {
                return 0
            }
            return {};
        },

        imageSrc({width, height, thumb}) {
            const ratioExceed = $A.imageRatioExceed(width, height, 3)
            if ($A.imageRatioJudge(thumb) && ratioExceed > 0) {
                thumb = $A.thumbRestore(thumb) + `/crop/ratio:${ratioExceed},percentage:320x0`;
            }
            return thumb;
        },

        isVideoFile(msg) {
            return msg.type === 'file'
                && msg.ext === 'mp4'
                && msg.width > 0
                && msg.height > 0;
        },
    },
}
</script>
