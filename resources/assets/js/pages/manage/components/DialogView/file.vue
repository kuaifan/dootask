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
                    <div class="play-icon">
                        <i class="taskfont">&#xe745;</i>
                    </div>
                </div>
            </div>
            <div v-else class="file-box" @click="downFile">
                <img class="file-thumb" :src="msg.thumb"/>
                <div class="file-info">
                    <div class="file-name">{{ msg.name }}</div>
                    <div class="file-size">{{ $A.bytesToSize(msg.size) }}</div>
                </div>
            </div>
            <div v-if="msg.percentage" class="file-percentage">
                <span :style="fileStyle(msg.percentage)"></span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        msg: Object,
    },
    methods: {
        viewFile() {
            this.$emit('viewFile');
        },
        downFile() {
            this.$emit('downFile');
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
