<template>
    <div class="micro-app-wrapper">
        <template v-for="app in apps">
            <Modal
                v-if="app.transparent"
                v-model="app.isOpen"
                :ref="`ref-${app.name}`"
                :mask="false"
                :footer-hide="true"
                :transition-names="[]"
                :beforeClose="async () => { await onBeforeClose(app.name) }"
                class-name="micro-app-trans"
                fullscreen>
                <MicroContent
                    :is-open="app.isOpen"
                    :app-name="app.name"
                    :app-url="app.url"
                    :keep-alive="app.keepAlive"
                    :is-loading="app.isLoading"
                    :app-data="appData(app.name)"
                    @created="created"
                    @beforemount="beforemount"
                    @mounted="mounted"
                    @unmount="unmount"
                    @error="error"/>
            </Modal>
            <DrawerOverlay
                v-else
                v-model="app.isOpen"
                :ref="`ref-${app.name}`"
                modal-class="micro-app-modal"
                drawer-class="micro-app-drawer"
                placement="right"
                :beforeClose="async () => { await onBeforeClose(app.name) }"
                :size="1200">
                <MicroContent
                    :is-open="app.isOpen"
                    :app-name="app.name"
                    :app-url="app.url"
                    :keep-alive="app.keepAlive"
                    :is-loading="app.isLoading"
                    :app-data="appData(app.name)"
                    @created="created"
                    @beforemount="beforemount"
                    @mounted="mounted"
                    @unmount="unmount"
                    @error="error"/>
            </DrawerOverlay>
        </template>
    </div>
</template>

<style lang="scss">
.micro-app-trans {
    .ivu-modal-close {
        display: none;
    }
    .ivu-modal-content {
        background: transparent;
    }
    .micro-app-loader {
        background-color: rgba(255, 255, 255, 0.6);
    }
}

.micro-app-modal {
    .ivu-modal-close {
        display: none;
    }
}

.micro-app-drawer {
    .overlay-content {
        overflow: hidden;
    }
}

.micro-app-loader {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
import Vue from 'vue'
import {mapState} from "vuex";
import {DatePicker} from 'view-design-hi';
import microApp from '@micro-zoe/micro-app'
import DialogWrapper from '../../pages/manage/components/DialogWrapper.vue'
import UserSelect from "../UserSelect.vue";
import {languageList, languageName} from "../../language";
import DrawerOverlay from "../DrawerOverlay/index.vue";
import emitter from "../../store/events";
import TransferDom from "../../directives/transfer-dom";
import MicroContent from "./content.vue";
import store from "../../store";

export default {
    name: "MicroApps",
    directives: {TransferDom},
    components: {MicroContent, DrawerOverlay},

    data() {
        return {
            apps: [],
        }
    },

    created() {
        microApp.start({
            'iframe': true,
            'router-mode': 'state',
        })
    },

    mounted() {
        emitter.on('openMicroApp', this.openMicroApp);
    },

    beforeDestroy() {
        emitter.off('openMicroApp', this.openMicroApp);
    },

    watch: {
        userToken(token) {
            if (token) {
                return
            }
            this.apps = [];
            microApp.unmountAllApps({destroy: true})
        },
    },

    computed: {
        ...mapState([
            'userInfo',
            'themeName',
        ]),
    },

    methods: {
        // 元素被创建
        created() {
        },

        // 即将渲染
        beforemount() {
        },

        // 已经渲染完成
        mounted(e) {
            this.finish(e)
        },

        // 已经卸载
        unmount() {
        },

        // 加载出错
        error(e) {
            this.finish(e)
            $A.modalError({
                language: false,
                title: this.$L('应用加载失败'),
                content: e.detail.error,
                onOk: () => {
                    this.closeMicroApp(e.detail.name, true)
                },
            });
        },

        // 加载结束
        finish(e) {
            const app = this.apps.find(({name}) => name == e.detail.name);
            if (app) {
                app.isLoading = false
            }
        },

        /**
         * 应用数据
         * @param name
         * @returns {*}
         */
        appData(name) {
            const app = this.apps.find(item => item.name == name);
            if (!app) {
                return {};
            }

            return {
                type: 'init',

                instance: {
                    Vue,
                    store,
                    components: {
                        DialogWrapper,
                        UserSelect,
                        DatePicker,
                    },
                },

                props: {
                    ...app.props,

                    userId: this.userId,
                    userToken: this.userToken,
                    userInfo: this.userInfo,

                    baseUrl: $A.mainUrl(),
                    systemInfo: window.systemInfo,

                    isEEUIApp: $A.isEEUIApp,
                    isElectron: $A.isElectron,
                    isMainElectron: $A.isMainElectron,
                    isSubElectron: $A.isSubElectron,

                    languageList,
                    languageName,
                    themeName: this.themeName,
                },

                methods: {
                    close: (destroy = false) => {
                        this.closeMicroApp(name, destroy)
                    },
                    back: () => {
                        try {
                            this.$refs[`ref-${name}`][0].close()
                        } catch (e) {
                            this.closeMicroApp(name)
                        }
                    },
                    nextZIndex: () => {
                        if (typeof window.modalTransferIndex === 'number') {
                            return window.modalTransferIndex++;
                        }
                        return 1000;
                    },
                    openWindow: (params) => {
                        if (!$A.isJson(params)) {
                            params = {path: params}
                        }
                        if (params.url) {
                            params.path = params.url
                            delete params.url
                        }
                        this.$store.dispatch('openChildWindow', params);
                    },
                    openTabWindow: (url) => {
                        this.$store.dispatch('openWebTabWindow', url);
                    },
                    openAppPage: (params) => {
                        if (!$A.isJson(params)) {
                            params = {url: params}
                        }
                        this.$store.dispatch('openAppChildPage', {
                            pageType: 'app',
                            pageTitle: params.title || " ",
                            url: 'web.js',
                            params: {
                                url: params.url,
                                titleFixed: typeof params.titleFixed === 'boolean' ? params.titleFixed : false,
                            },
                        });
                    },
                    extraCallA: (...args) => {
                        if (args.length > 0 && typeof args[0] === 'string') {
                            const methodName = args[0];
                            const methodParams = args.slice(1);
                            if (typeof $A[methodName] === 'function') {
                                return $A[methodName](...methodParams);
                            }
                        }
                        return null;
                    },
                },
            }
        },

        /**
         * 打开微应用
         * @param config
         *  - name          应用名称
         *  - url           应用地址
         *  - props         传递参数
         *  - transparent   是否透明模式 (true/false)，默认 false
         *  - keepAlive     是否开启微应用保活 (true/false)，默认 true
         */
        openMicroApp(config) {
            // 处理数据
            config.name = config.name || 'micro-app'
            config.url = config.url || null
            config.props = $A.isJson(config.props) ? config.props : {}
            config.transparent = typeof config.transparent == 'boolean' ? config.transparent : false
            config.keepAlive = typeof config.keepAlive == 'boolean' ? config.keepAlive : true

            // 判断处理
            const app = this.apps.find(({name}) => name == config.name);
            if (app) {
                // 更新微应用
                if (app.url != config.url) {
                    microApp.unmountApp(app.name, {destroy: true})
                    app.isLoading = true
                }
                for (let key in config) {
                    app[key] = config[key]
                }
                this.$nextTick(_ => {
                    app.isOpen = true
                })
            } else {
                // 新建微应用
                config.isLoading = true
                config.isOpen = false
                this.apps.push(config)
                this.$nextTick(_ => {
                    config.isOpen = true
                })
            }
        },

        /**
         * 关闭微应用
         * @param name
         * @param destroy
         */
        closeMicroApp(name, destroy) {
            const app = this.apps.find(item => item.name == name);
            if (!app) {
                return;
            }

            app.isOpen = false
            if (destroy) {
                microApp.unmountApp(app.name, {destroy: true})
            }
        },

        /**
         * 关闭之前判断
         * @param name
         * @returns {Promise<unknown>}
         */
        onBeforeClose(name) {
            return new Promise(resolve => {
                microApp.forceSetData(name, {type: 'beforeClose'}, array => {
                    if (!array?.find(item => item === true)) {
                        resolve()
                    }
                })
            })
        },
    }
}
</script>
