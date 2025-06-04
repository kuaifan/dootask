<template>
    <iframe
        ref="iframe"
        class="micro-app-iframe"
        :src="src"
        sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-popups-to-escape-sandbox"/>
</template>

<style lang="scss" scoped>
.micro-app-iframe {
    border: none;
    width: 100%;
    height: 100%;
}
</style>
<script>
export default {
    name: "MicroIFrame",
    props: {
        name: {
            type: String,
            default: ''
        },
        url: {
            type: String,
            default: ''
        },
    },

    data() {
        return {
            src: this.url,
        }
    },

    mounted() {
        this.$refs.iframe.addEventListener('load', this.handleLoad.bind(this))
        this.$refs.iframe.addEventListener('error', this.handleError.bind(this))
    },

    beforeDestroy() {
        this.$refs.iframe.removeEventListener('load', this.handleLoad.bind(this))
        this.$refs.iframe.removeEventListener('error', this.handleError.bind(this))
    },

    methods: {
        handleLoad(e) {
            this.$emit('mounted', {
                ...e,
                detail: {
                    name: this.name,
                }
            })
        },
        
        handleError(e) {
            this.$emit('error', {
                ...e,
                detail: {
                    name: this.name,
                    error: e,
                }
            })
        }
    }
}
</script>