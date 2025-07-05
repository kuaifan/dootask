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
        window.addEventListener('message', this.handleMessage.bind(this))
        this.$refs.iframe.addEventListener('load', this.handleLoad.bind(this))
        this.$refs.iframe.addEventListener('error', this.handleError.bind(this))
    },

    beforeDestroy() {
        this.cleanupMicroApp()
        window.removeEventListener('message', this.handleMessage.bind(this))
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

        // 处理 iframe 消息
        handleMessage(e) {
            if (!this.isFromCurrentIframe(e)) {
                return
            }
            const {type, message} = e.data;
            switch (type) {
                case 'MICRO_APP_METHOD':
                    if (!this.data || !this.data.methods) {
                        return
                    }
                    const {id, method, args} = message;
                    if (this.data.methods[method]) {
                        const postMessage = (result) => {
                            this.$refs.iframe.contentWindow.postMessage({
                                type: 'MICRO_APP_METHOD_RESULT',
                                message: {id, result: $A.cloneJSON(result)}
                            }, '*')
                        }
                        const before = this.data.methods[method](...args)
                        if (before && before.then) {
                            before.then(postMessage).catch(postMessage)
                        } else {
                            postMessage(before)
                        }
                    }
                    break
                default:
                    break
            }
        },

        // 验证消息是否来自当前 iframe
        isFromCurrentIframe(event) {
            try {
                const { source } = event
                return this.$refs.iframe && source === this.$refs.iframe.contentWindow
            } catch (error) {
                // console.error('Failed to validate message from current iframe:', error)
                return false
            }
        },

        // 注入 microApp 对象到 iframe
        injectMicroApp() {
            try {
                const iframeWindow = this.$refs.iframe.contentWindow
                if (iframeWindow && this.data) {
                    try {
                        // 直接注入 microApp 对象
                        iframeWindow.microApp = {
                            getData: () => this.data
                        }
                    } catch (crossOriginError) {
                        // 跨域情况，使用 postMessage 发送 microApp 对象
                        iframeWindow.postMessage({
                            type: 'MICRO_APP_INJECT',
                            message: {
                                type: this.data.type,
                                props: this.data.props
                            }
                        }, '*')
                    }
                }
            } catch (error) {
                // console.error('Failed to inject microApp object:', error)
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
                // console.error('Failed to clean up microApp object:', error)
            }
        },
    }
}
</script>
