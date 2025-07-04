<template>
    <iframe
        ref="iframe"
        class="micro-app-iframe"
        :src="src"
        sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-popups-to-escape-sandbox">
    </iframe>
</template>

<style lang="scss" scoped>
.micro-app-iframe {
    border: none;
    width: 100%;
    height: 100%;
    padding-top: var(--status-bar-height);
    padding-bottom: var(--navigation-bar-height);
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
        data: {
            type: Object,
            default: null
        }
    },

    data() {
        return {
            src: this.url,
        }
    },

    mounted() {
        this.injectMicroApp()
        this.$refs.iframe.addEventListener('load', this.handleLoad.bind(this))
        this.$refs.iframe.addEventListener('error', this.handleError.bind(this))
    },

    beforeDestroy() {
        this.cleanupMicroApp()
        this.$refs.iframe.removeEventListener('load', this.handleLoad.bind(this))
        this.$refs.iframe.removeEventListener('error', this.handleError.bind(this))
    },

    methods: {
        // 处理 iframe 加载完成
        handleLoad(e) {
            this.injectMicroApp()

            this.$emit('mounted', {
                ...e,
                detail: {
                    name: this.name,
                }
            })
        },

        // 处理 iframe 加载错误
        handleError(e) {
            this.$emit('error', {
                ...e,
                detail: {
                    name: this.name,
                    error: e,
                }
            })
        },

        // 注入 microApp 对象到 iframe
        injectMicroApp() {
            try {
                const iframeWindow = this.$refs.iframe.contentWindow
                if (iframeWindow && this.data) {
                    iframeWindow.microApp = {
                        getData: () => this.data
                    }
                }
            } catch (error) {
                console.error('Failed to inject microApp object:', error)
            }
        },

        // 清理注入的 microApp 对象
        cleanupMicroApp() {
            try {
                const iframeWindow = this.$refs.iframe.contentWindow
                if (iframeWindow && iframeWindow.microApp) {
                    delete iframeWindow.microApp
                }
            } catch (error) {
                console.error('Failed to cleanup microApp object:', error)
            }
        },
    }
}
</script>
