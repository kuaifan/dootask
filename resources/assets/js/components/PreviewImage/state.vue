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
                this.$store.state.previewImageIndex = Math.max(this.$store.state.previewImageIndex, 0)
                this.$store.state.previewImageIndex = Math.min(this.$store.state.previewImageIndex, this.$store.state.previewImageList.length - 1)
            } else {
                this.$store.state.previewImageIndex = 0;
                this.$store.state.previewImageList = [];
            }
        },
        previewImageList(l) {
            if (l.length > 0) {
                this.show = true;
            }
        }
    }
};
</script>
