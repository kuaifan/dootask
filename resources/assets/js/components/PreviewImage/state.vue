<template>
    <PreviewImage v-model="show" :index="previewImageIndex" :list="previewImageList"/>
</template>

<script>
import {mapState} from "vuex";
import PreviewImage from "./index";

export default {
    name: 'PreviewImageState',
    components: {PreviewImage},
    computed: {
        ...mapState([
            'previewImageIndex',
            'previewImageList',
        ]),
    },
    data() {
        return {
            show: false,
        }
    },
    watch: {
        show(v) {
            if (v) {
                this.$store.state.previewImageIndex = Math.min(Math.max(this.$store.state.previewImageIndex, 0), this.$store.state.previewImageList.length - 1)
            } else {
                this.$store.state.previewImageIndex = 0;
                this.$store.state.previewImageList = [];
            }
        },
        previewImageList(l) {
            if (l.length > 0) {
                if ($A.isEEUiApp) {
                    let position = Math.min(Math.max(this.$store.state.previewImageIndex, 0), this.$store.state.previewImageList.length - 1)
                    let paths = l.map(item => {
                        if ($A.isJson(item)) {
                            return item.src;
                        }
                        return item
                    })
                    let max = 50;
                    if (paths.length > max) {
                        const newPaths = [];
                        let i = 0;
                        while (newPaths.length < max && i < max) {
                            let front = position - i;
                            let behind = position + i + 1;
                            if (front >= 0) {
                                newPaths.unshift(paths[front]);
                            }
                            if (behind < paths.length) {
                                newPaths.push(paths[behind]);
                            }
                            i++;
                        }
                        position = newPaths.findIndex(item => item === paths[position]);
                        paths = newPaths;
                    }
                    const videoPath = paths.find(src => {
                        return /\.mp4$/i.test(src)
                    });
                    if (videoPath) {
                        $A.eeuiAppSendMessage({
                            action: 'videoPreview',
                            path: videoPath
                        });
                        return
                    }
                    $A.eeuiAppSendMessage({
                        action: 'picturePreview',
                        position,
                        paths
                    });
                } else {
                    this.show = true;
                }
            }
        }
    }
};
</script>
