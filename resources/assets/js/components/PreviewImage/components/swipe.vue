<template>

</template>
<style lang="scss">
body {
    .preview-image-swipe {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        > img {
            max-width: 100%;
            max-height: 100%;
        }
    }
    div.pswp__img--placeholder {
        background: transparent;
    }
}
</style>
<script>
import PhotoSwipeLightbox from 'photoswipe/lightbox';
import 'photoswipe/style.css';

export default {
    props: {
        urlList: {
            type: Array,
            default: () => []
        },
        initialIndex: {
            type: Number,
            default: 0
        },
    },
    data() {
        return {
            lightbox: null,
        };
    },
    beforeDestroy() {
        this.lightbox?.destroy();
    },
    watch: {
        urlList: {
            handler(array) {
                this.lightbox?.destroy();
                const dataSource = array.map(item => {
                    if ($A.isJson(item)) {
                        if (item.src) {
                            item.src = $A.rightDelete(item.src, "_thumb.jpg");
                        }
                        return item
                    }
                    return {
                        html: `<div class="preview-image-swipe"><img src="${$A.rightDelete(item, "_thumb.jpg")}"/></div>`,
                    }
                })
                this.lightbox = new PhotoSwipeLightbox({
                    dataSource,
                    escKey: false,
                    showHideAnimationType: 'none',
                    pswpModule: () => import('photoswipe'),
                });
                this.lightbox.on('close', () => {
                    this.$emit("on-close")
                });
                this.lightbox.on('destroy', () => {
                    this.$emit("on-destroy")
                });
                this.lightbox.init();
                this.lightbox.loadAndOpen(this.initialIndex);
            },
            immediate: true
        },
        initialIndex(index) {
            this.lightbox?.loadAndOpen(index);
        }
    },
};
</script>
