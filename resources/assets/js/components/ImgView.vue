<template>
    <img :src="srcValue" :alt="alt" @error.once="onError">
</template>

<script>
export default {
    name: 'ImgView',
    props: {
        src: {
            default: ""
        },
        errorSrc: {
            default: ""
        },
        alt: {
            default: ""
        },
    },
    computed: {
        srcValue({src}) {
            return this.toSrc(src)
        }
    },
    methods: {
        toSrc(src) {
            if (src.substring(0, 10) === "data:image" ||
                src.substring(0, 2) === "//" ||
                src.substring(0, 7) === "http://" ||
                src.substring(0, 8) === "https://" ||
                src.substring(0, 6) === "ftp://" ||
                src.substring(0, 1) === "/") {
                return src;
            }
            return $A.mainUrl(src)
        },

        onError(e) {
            if (!this.errorSrc) {
                return;
            }
            e.target.src = this.toSrc(this.errorSrc);
        }
    }
}
</script>
