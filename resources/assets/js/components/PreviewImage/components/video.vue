<template>
    <div ref="view" class="common-preview-video">
        <video v-if="item.src" :width="videoStyle('width')" :height="videoStyle('height')" controls autoplay>
            <source :src="item.src" type="video/mp4">
        </video>
    </div>
</template>

<style lang="scss" scoped>
.common-preview-video {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    top: 0;
    backdrop-filter: blur(4px);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    > video {
        max-width: 100%;
        max-height: 100%;
    }
}
</style>

<script>

export default {
    props: {
        item: {
            type: Object,
            default: () => ({
                src: '',
                width: 0,
                height: 0,
            }),
        },
    },

    data() {
        return {

        }
    },

    mounted() {

    },

    methods: {
        videoStyle(type) {
            let {width, height} = this.item;
            const maxWidth = this.windowWidth;
            const maxHeight = this.windowHeight;
            if (width > maxWidth) {
                height = height * maxWidth / width;
                width = maxWidth;
            }
            if (height > maxHeight) {
                width = width * maxHeight / height;
                height = maxHeight;
            }
            if (type === 'width') return width;
            if (type === 'height') return height;
            return {
                width: `${width}px`,
                height: `${height}px`,
            }
        },
    },
};
</script>
