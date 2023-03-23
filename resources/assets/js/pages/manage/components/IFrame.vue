<template>
    <iframe v-show="src" ref="iframe" :src="src"></iframe>
</template>

<script>
export default {
    name: "IFrame",
    props: {
        src: {
            type: String,
            default: ''
        },
    },

    mounted() {
        this.$refs.iframe.addEventListener('load', this.handleLoad)
        window.addEventListener('message', this.handleMessage)
    },

    beforeDestroy() {
        this.$refs.iframe.removeEventListener('load', this.handleLoad)
        window.removeEventListener('message', this.handleMessage)
    },

    methods: {
        handleLoad() {
            this.$emit("on-load")
        },

        handleMessage({data, source}) {
            if (source !== this.$refs.iframe?.contentWindow) {
                return;
            }
            data = $A.jsonParse(data);
            if (data.source === 'fileView' && data.action === 'picture') {
                this.$store.dispatch("previewImage", {index: data.params.index, list: data.params.array})
            }
            this.$emit("on-message", data)
        },

        postMessage(message, targetOrigin = "*") {
            if (this.$refs.iframe) {
                this.$refs.iframe.contentWindow.postMessage(message, targetOrigin);
            }
        }
    }
}
</script>
