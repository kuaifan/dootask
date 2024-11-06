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
        className: {
            type: String,
            default: () => {
                return "preview-image-swipe-" + Math.round(Math.random() * 10000);
            }
        },
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
                let dragIng = false
                let htmlZoom = false;
                this.lightbox?.destroy();
                const dataSource = array.map(item => {
                    if ($A.isJson(item)) {
                        if (parseInt(item.width) > 0 && parseInt(item.height) > 0) {
                            return item
                        }
                        item = item.src;
                    }
                    htmlZoom = true;
                    return {
                        html: `<div class="preview-image-swipe"><img src="${item}"/></div>`,
                    }
                })
                this.lightbox = new PhotoSwipeLightbox({
                    dataSource,
                    escKey: false,
                    mainClass: this.className + ' no-dark-content',
                    showHideAnimationType: 'none',
                    pswpModule: () => import('photoswipe'),
                });
                this.lightbox.on('change', _ => {
                    if (!htmlZoom) {
                        return;
                    }
                    $A.loadScript('js/pinch-zoom.umd.min.js').then(_ => {
                        const swiperItems = document.querySelector(`.${this.className}`).querySelectorAll(".preview-image-swipe")
                        swiperItems.forEach(swipeItem => {
                            if (swipeItem.getAttribute("data-init-pinch-zoom") !== "init") {
                                swipeItem.setAttribute("data-init-pinch-zoom", "init")
                                swipeItem.querySelector("img").addEventListener("pointermove", e => {
                                    if (dragIng) {
                                        e.stopPropagation();
                                    }
                                })
                                new PinchZoom.default(swipeItem, {
                                    draggableUnzoomed: false,
                                    onDragStart: () => {
                                        dragIng = true;
                                    },
                                    onDragEnd: () => {
                                        dragIng = false;
                                    }
                                })
                            }
                        })
                    })
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
