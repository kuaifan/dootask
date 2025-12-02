<template>
    <div class="micro-app-iframe">
        <iframe
            ref="iframe"
            class="micro-app-iframe-container"
            :class="{'iframe-immersive': immersive}"
            :src="src"
            sandbox="allow-scripts allow-forms allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-modals allow-pointer-lock allow-storage-access-by-user-activation allow-downloads"
            allow="microphone; camera; speaker-selection; geolocation; accelerometer; gyroscope; magnetometer; fullscreen; picture-in-picture; clipboard-read; clipboard-write">
        </iframe>
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
            isReady: false,
            hasMounted: false,
            hearTbeatLastTime: 0,
        }
    },

    mounted() {
        this.pendingBeforeCloses = new Map();
        this.pendingFunctionCalls = new Map();

        this.boundHandleMessage = this.handleMessage.bind(this);
        this.boundHandleLoad = this.handleLoad.bind(this);
        this.boundHandleError = this.handleError.bind(this);

        window.addEventListener('message', this.boundHandleMessage);
        this.$refs.iframe.addEventListener('load', this.boundHandleLoad);
        this.$refs.iframe.addEventListener('error', this.boundHandleError);

        this.injectMicroApp();
    },

    beforeDestroy() {
        // 正确移除事件监听器
        if (this.boundHandleMessage) {
            window.removeEventListener('message', this.boundHandleMessage);
        }
        if (this.boundHandleLoad) {
            this.$refs.iframe.removeEventListener('load', this.boundHandleLoad);
        }
        if (this.boundHandleError) {
            this.$refs.iframe.removeEventListener('error', this.boundHandleError);
        }

        // 清理待处理的函数调用
        if (this.pendingFunctionCalls) {
            this.pendingFunctionCalls.forEach(pending => {
                clearTimeout(pending.timeout);
                pending.reject(new Error('Component destroyed'));
            });
            this.pendingFunctionCalls.clear();
        }

        // 清理待处理的关闭回调
        if (this.pendingBeforeCloses) {
            this.pendingBeforeCloses.forEach(resolve => {
                resolve(false);
            });
            this.pendingBeforeCloses.clear();
        }
    },

    methods: {
        // 处理 iframe 加载完成
        handleLoad() {
            // 总是执行注入
            this.injectMicroApp()

            // 只触发一次 mounted 事件
            if (!this.hasMounted) {
                this.hasMounted = true
                this.$emit('mounted', {
                    detail: {
                        name: this.name,
                    }
                })
            }
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
            const type = e.data.type;
            const message = this.handleMessageEnsureJson(e.data.message);
            switch (type) {
                case 'MICRO_APP_READY':
                    this.handleMessageOfReady(message);
                    break

                case 'MICRO_APP_HEARTBEAT':
                    this.handleMessageOfHeartbeat(message);
                    break

                case 'MICRO_APP_METHOD':
                    this.handleMessageOfMethod(message)
                    break

                case 'MICRO_APP_FUNCTION_RESULT':
                    this.handleMessageOfFunctionResult(message)
                    break

                case 'MICRO_APP_BEFORE_CLOSE':
                    this.handleMessageOfBeforeClose(message)
                    break

                case 'MICRO_APP_BEFORE_UNLOAD':
                    this.handleMessageOfBeforeUnload(message);
                    break

                default:
                    break
            }
        },

        // 确保消息是 JSON 格式
        handleMessageEnsureJson(message) {
            return $A.isJson(message) ? message : {}
        },

        // 处理消息的准备状态 (MICRO_APP_READY)
        handleMessageOfReady({supportBeforeClose}) {
            this.handleLoad()
            this.isReady = true

            if (supportBeforeClose) {
                this.$store.commit('microApps/update', {
                    name: this.name,
                    data: {
                        postMessage: (message) => {
                            if (!this.$refs.iframe || !this.$refs.iframe.contentWindow) {
                                return
                            }
                            this.$refs.iframe.contentWindow.postMessage(message, '*')
                        },
                        onBeforeClose: () => {
                            if (this.hearTbeatLastTime && Date.now() - this.hearTbeatLastTime > 5000) {
                                return true // 超时，允许关闭
                            }
                            if (!this.$refs.iframe || !this.$refs.iframe.contentWindow) {
                                return true // iframe 不存在，允许关闭
                            }
                            return new Promise(resolve => {
                                const message = {
                                    id: $A.randomString(16),
                                    name: this.name
                                }
                                this.$refs.iframe.contentWindow.postMessage({
                                    type: 'MICRO_APP_BEFORE_CLOSE',
                                    message
                                }, '*')
                                this.pendingBeforeCloses.set(message.id, resolve)
                            })
                        }
                    }
                })
            }
        },

        // 处理心跳消息 (MICRO_APP_HEARTBEAT)
        handleMessageOfHeartbeat() {
            this.hearTbeatLastTime = Date.now()
        },

        // 处理方法消息 (MICRO_APP_METHOD)
        handleMessageOfMethod({id, method, args}) {
            if (!this.data || !this.data.methods || !this.data.methods[method]) {
                return
            }

            const postMessage = (result) => {
                this.$refs.iframe.contentWindow.postMessage({
                    type: 'MICRO_APP_METHOD_RESULT',
                    message: {id, result: $A.cloneJSON(result)}
                }, '*')
            }

            const postError = (error) => {
                this.$refs.iframe.contentWindow.postMessage({
                    type: 'MICRO_APP_METHOD_RESULT',
                    message: {id, result: null, error: error?.message || error}
                }, '*')
            }

            try {
                const processedArgs = this.deserializeFunctions(args);
                const before = this.data.methods[method](...processedArgs);

                if (before && before.then) {
                    before.then(postMessage).catch(postError);
                } else {
                    postMessage(before);
                }
            } catch (error) {
                postError(error);
            }
        },

        // 处理函数结果消息 (MICRO_APP_FUNCTION_RESULT)
        handleMessageOfFunctionResult({callId, result, error}) {
            const pending = this.pendingFunctionCalls.get(callId);
            if (!pending) {
                return
            }

            this.pendingFunctionCalls.delete(callId);
            clearTimeout(pending.timeout);

            if (error) {
                pending.reject(new Error(error));
            } else {
                pending.resolve(result);
            }
        },

        // 处理方法消息 (MICRO_APP_BEFORE_CLOSE)
        handleMessageOfBeforeClose({id}) {
            if (!this.pendingBeforeCloses.has(id)) {
                return
            }
            this.pendingBeforeCloses.get(id)()
            this.pendingBeforeCloses.delete(id)
        },

        // 处理卸载前消息 (MICRO_APP_BEFORE_UNLOAD)
        handleMessageOfBeforeUnload() {
            this.isReady = false

            this.$store.commit('microApps/update', {
                name: this.name,
                data: {
                    onBeforeClose: () => true
                }
            })
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

        // 反序列化函数
        deserializeFunctions(value) {
            if (value && typeof value === 'object' && value.__func) {
                return (...args) => {
                    return new Promise((resolve, reject) => {
                        const callId = `call_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;

                        // 设置超时
                        const timeout = setTimeout(() => {
                            this.pendingFunctionCalls.delete(callId);
                            reject(new Error('Function call timeout'));
                        }, 5000);

                        // 存储待处理的调用
                        this.pendingFunctionCalls.set(callId, {
                            resolve,
                            reject,
                            timeout
                        });

                        // 发送函数调用请求
                        if (!this.$refs.iframe || !this.$refs.iframe.contentWindow) {
                            reject(new Error('Iframe not ready'));
                            return;
                        }
                        this.$refs.iframe.contentWindow.postMessage({
                            type: 'MICRO_APP_FUNCTION_CALL',
                            message: {funcId: value.__func, callId, args}
                        }, '*');
                    });
                };
            }

            if (Array.isArray(value)) {
                return value.map(item => this.deserializeFunctions(item));
            }

            if (value && typeof value === 'object' && value.constructor === Object) {
                const result = {};
                for (const key in value) {
                    result[key] = this.deserializeFunctions(value[key]);
                }
                return result;
            }

            return value;
        },

        // 注入 microApp 对象到 iframe
        injectMicroApp() {
            try {
                const iframeWindow = this.$refs.iframe.contentWindow
                if (iframeWindow && this.data) {
                    try {
                        // 同源情况，直接注入 microApp 对象
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
    }
}
</script>
