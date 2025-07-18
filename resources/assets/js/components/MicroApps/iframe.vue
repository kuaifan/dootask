<template>
    <div class="micro-app-iframe">
        <iframe
            ref="iframe"
            class="micro-app-iframe-container"
            :class="{'iframe-immersive': immersive}"
            :src="src"
            sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-popups-to-escape-sandbox">
        </iframe>
        <div v-if="isLoading" class="micro-app-iframe-cover"></div>
    </div>
</template>

<style lang="scss" scoped>
.micro-app-iframe {
    position: relative;
    width: 100%;
    height: 100%;

    .micro-app-iframe-container {
        border: none;
        width: 100%;
        height: 100%;
        padding-top: var(--status-bar-height);
        padding-bottom: var(--navigation-bar-height);

        &.iframe-immersive {
            padding-top: 0;
            padding-bottom: 0;
        }
    }

    .micro-app-iframe-cover {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 100;
    }
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
        },
        immersive: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            src: this.url,
            isLoading: true,
            onBeforeClose: {},
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
        handleLoad() {
            this.injectMicroApp()

            this.$emit('mounted', {
                detail: {
                    name: this.name,
                }
            })
        },

        // 处理 iframe 加载错误
        handleError(e) {
            this.$emit('error', {
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
                case 'MICRO_APP_READY':
                    this.handleLoad()
                    this.isLoading = false
                    if (message && message.supportBeforeClose) {
                        this.$store.commit('microApps/update', {
                            name: this.name,
                            data: {
                                onBeforeClose: () => {
                                    return new Promise(resolve => {
                                        const message = {
                                            id: $A.randomString(16),
                                            name: this.name
                                        }
                                        this.$refs.iframe.contentWindow.postMessage({
                                            type: 'MICRO_APP_BEFORE_CLOSE',
                                            message
                                        }, '*')
                                        this.onBeforeClose[message.id] = resolve
                                    })
                                }
                            }
                        })
                    }
                    break

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

                case 'MICRO_APP_BEFORE_CLOSE':
                    if (this.onBeforeClose[message.id]) {
                        this.onBeforeClose[message.id]()
                        delete this.onBeforeClose[message.id]
                    }
                    break

                default:
                    break
            }
        },

        // 验证消息是否来自当前 iframe
        isFromCurrentIframe(event) {
            try {
                const {source} = event
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
