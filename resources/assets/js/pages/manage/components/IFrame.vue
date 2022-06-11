<template>
    <iframe v-if="src" ref="iframe" :src="src"></iframe>
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
        window.addEventListener('message', this.handleMessage)
    },

    beforeDestroy() {
        window.removeEventListener('message', this.handleMessage)
    },

    methods: {
        handleMessage({data, source}) {
            if (source !== this.$refs.iframe?.contentWindow) {
                return;
            }
            data = $A.jsonParse(data);
            if (data.source === 'fileView' && data.action === 'picture') {
                this.$store.state.previewImageIndex = data.params.index;
                this.$store.state.previewImageList = data.params.array;
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
