<template>
    <span class="prompt-image-wrapper" @click="showPreview">
        <img v-if="imageUrl" :src="imageUrl" class="prompt-image-thumb" alt="uploaded image" />
        <span v-else class="prompt-image-placeholder">
            <i class="taskfont">&#xe6ef;</i>
        </span>
    </span>
</template>

<script>
export default {
    name: 'PromptImage',
    props: {
        imageId: {
            type: String,
            required: true,
        },
        getImage: {
            type: Function,
            required: true,
        },
    },
    data() {
        return {
            imageUrl: null,
            loading: true,
        };
    },
    mounted() {
        this.loadImage();
    },
    methods: {
        async loadImage() {
            try {
                const url = await this.getImage(this.imageId);
                this.imageUrl = url;
            } catch (e) {
                console.warn('[PromptImage] 加载图片失败:', e);
            } finally {
                this.loading = false;
            }
        },
        showPreview() {
            if (this.imageUrl) {
                this.$store.dispatch("previewImage", this.imageUrl);
            }
        },
    },
};
</script>

<style lang="scss" scoped>
.prompt-image-wrapper {
    display: inline-block;
    width: 60px;
    height: 60px;
    border-radius: 6px;
    overflow: hidden;
    cursor: pointer;
    border: 1px solid rgba(0, 0, 0, 0.1);
    transition: transform 0.2s, box-shadow 0.2s;

    &:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
}

.prompt-image-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.prompt-image-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: #f5f5f5;
    color: #999;
    font-size: 18px;
}
</style>
