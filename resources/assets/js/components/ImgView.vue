<template>
    <img :src="srcValue" :alt="alt">
</template>

<script>
export default {
    name: 'ImgView',
    props: {
        src: {
            default: ""
        },
        alt: {
            default: ""
        },
    },
    computed: {
        srcValue() {
            const {src} = this;
            if (src.substring(0, 10) === "data:image" ||
                src.substring(0, 2) === "//" ||
                src.substring(0, 7) === "http://" ||
                src.substring(0, 8) === "https://" ||
                src.substring(0, 6) === "ftp://" ||
                src.substring(0, 1) === "/") {
                return src;
            }
            return $A.apiUrl(`../${src}`)
        }
    }
}
</script>
