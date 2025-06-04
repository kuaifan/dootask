<template>
    <div>
        <MicroModal
            v-for="(app, key) in apps"
            :key="key"
            v-model="app.isOpen"
            :ref="`ref-${app.name}`"
            :size="1200"
            :transparent="app.transparent"
            :autoDarkTheme="app.auto_dark_theme"
            :beforeClose="async () => { await onBeforeClose(app.name) }">
            <MicroIFrame
                v-if="app.url_type === 'iframe' && app.isOpen && app.url"
                :name="app.name"
                :url="app.url"
                @mounted="mounted"
                @error="error"/>
            <micro-app
                v-else-if="app.isOpen && app.url"
                :name="app.name"
                :url="app.url"
                :keep-alive="app.keep_alive"
                :disable-scopecss="app.disable_scope_css"
                :data="appData(app.name)"
                @mounted="mounted"
                @error="error"/>
            <div v-if="app.isLoading" class="micro-app-loader">
                <Loading/>
            </div>
        </MicroModal>

        <!--选择用户-->
        <UserSelect
            ref="userSelect"
            v-model="userSelectOptions.value"
            v-bind="userSelectOptions.config"
            module/>

        <!--窗口助理-->
        <Modal
            v-model="assistShow"
            :closable="true"
            :mask="false"
            :mask-closable="false"
            :footer-hide="true"
            :transition-names="['', '']"
            :beforeClose="onAssistClose"
            class-name="micro-app-assist"/>
    </div>
</template>

<style lang="scss">
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

.transparent-mode {
    .micro-app-loader {
        background-color: rgba(255, 255, 255, 0.6);
    }
}

.micro-app-assist {
    width: 0;
    height: 0;
    opacity: 0;
    display: none;
    visibility: hidden;
    pointer-events: none;
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
import emitter from "../../store/events";
import TransferDom from "../../directives/transfer-dom";
import store from "../../store";
import MicroModal from "./modal.vue";
import MicroIFrame from "./iframe.vue";

export default {
    name: "MicroApps",
    directives: {TransferDom},
    components: {MicroModal, UserSelect, MicroIFrame},

    props: {
        windowType: {
            type: String,
            default: 'embed',
        },
    },

    data() {
        return {
            apps: [],
            assistShow: false,
            userSelectOptions: {value: [], config: {}},
        }
    },

    created() {
        microApp.start({
            'iframe': true,
            'router-mode': 'state',
            'iframeSrc': window.location.origin + '/assets/empty.html',
        })
    },

    mounted() {
        emitter.on('observeMicroApp:open', this.observeMicroApp);
    },

    beforeDestroy() {
        emitter.off('observeMicroApp:open', this.observeMicroApp);
    },

    watch: {
        userToken(token) {
            if (token) {
                return
            }
            this.closeAllMicroApp()
        },
        themeName() {
            this.closeAllMicroApp()
        },
        apps: {
            handler(apps) {
                this.assistShow = !!apps.find(item => item.isOpen)
            },
            deep: true,
        }
    },

    computed: {
        ...mapState([
            'userInfo',
            'themeName',
        ]),
    },

    methods: {
        // 已经渲染完成
        mounted(e) {
            this.finish(e.detail.name)
        },

        // 加载出错
        error(e) {
            this.finish(e.detail.name)
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
        finish(name) {
            const app = this.apps.find(app => app.name == name);
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
                    windowType: this.windowType,

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
                        this.closeByName(name)
                    },
                    nextZIndex: () => {
                        if (typeof window.modalTransferIndex === 'number') {
                            return window.modalTransferIndex++;
                        }
                        return 1000;
                    },
                    selectUsers: async (params) => {
                        if (!$A.isJson(params)) {
                            params = {value: params}
                        }
                        if ($A.isArray(params.value)) {
                            params.value = params.value ? [params.value] : []
                        }
                        this.userSelectOptions.value = params.value
                        delete params.value
                        this.userSelectOptions.config = params
                        return await new Promise(resolve => {
                            this.$refs.userSelect.onSelection((res) => {
                                return resolve(res)
                            })
                        })
                    },
                    popoutWindow: async (windowConfig = null) => {
                        const app = this.apps.find(item => item.name == name);
                        if (!app) {
                            $A.modalError("应用不存在");
                            return
                        }
                        await this.inlineBlank(app, windowConfig)
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
         * 观察打开微应用
         * @param config
         */
        async observeMicroApp(config) {
            if (config.url_type === 'inline_blank') {
                await this.inlineBlank(config)
                return
            }
            if (config.url_type === 'external') {
                await this.externalWindow(config)
                return
            }

            const app = this.apps.find(({name}) => name == config.name);
            if (app) {
                // 更新微应用
                if (app.url != config.url) {
                    await microApp.unmountApp(app.name, {destroy: true})
                    app.isLoading = true
                }
                Object.assign(app, config)
                requestAnimationFrame(_ => app.isOpen = true)
            } else {
                // 新建微应用
                config.isLoading = true
                config.isOpen = false
                this.apps.push(config)
                requestAnimationFrame(_ => config.isOpen = true)
            }
        },

        /**
         * 内联链接，在新窗口打开
         * @param config
         * @param windowConfig
         * @returns {Promise<void>}
         */
        async inlineBlank(config, windowConfig = null) {
            const appConfig = {
                ...config,
                url_type: 'inline',
                transparent: true,
                keep_alive: false,
            };
            if (windowConfig?.url) {
                appConfig.url = windowConfig.url;
                delete windowConfig.url;
            }
            //
            const path = `/single/apps/${appConfig.name}`
            const apps = (await $A.IDBArray("cacheMicroApps")).filter(item => item.name != appConfig.name);
            apps.length > 50 && apps.splice(0, 10)
            apps.push(appConfig)
            await $A.IDBSet("cacheMicroApps", apps);

            if (this.$Electron) {
                await this.$store.dispatch('openChildWindow', {
                    name: `single-apps-${$A.randomString(6)}`,
                    path: path,
                    force: false,
                    config: Object.assign({
                        title: ' ',
                        parent: null,
                        width: Math.min(window.screen.availWidth, 1440),
                        height: Math.min(window.screen.availHeight, 900),
                    }, $A.isJson(windowConfig) ? windowConfig : {}),
                });
            } else if (this.$isEEUIApp) {
                await this.$store.dispatch('openAppChildPage', {
                    pageType: 'app',
                    pageTitle: ' ',
                    url: 'web.js',
                    params: {
                        url: $A.urlReplaceHash(path)
                    },
                })
            } else {
                window.open($A.mainUrl(path.substring(1)))
            }
        },

        /**
         * 外部链接，在新窗口打开
         * @param config
         * @returns {Promise<void>}
         */
        async externalWindow(config) {
            if (this.$Electron) {
                await this.$store.dispatch('openChildWindow', {
                    name: `external-apps-${$A.randomString(6)}`,
                    path: config.url,
                    force: false,
                    config: {
                        title: ' ',
                        parent: null,
                        width: Math.min(window.screen.availWidth, 1440),
                        height: Math.min(window.screen.availHeight, 900),
                    },
                });
            } else if (this.$isEEUIApp) {
                await this.$store.dispatch('openAppChildPage', {
                    pageType: 'app',
                    pageTitle: ' ',
                    url: 'web.js',
                    params: {
                        url: config.url
                    },
                });
            } else {
                window.open(config.url)
            }
        },

        /**
         * 通过名称关闭微应用
         * @param name
         */
        closeByName(name) {
            try {
                this.$refs[`ref-${name}`][0].onClose()
            } catch (e) {
                this.closeMicroApp(name)
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
         * 关闭所有微应用
         * @param destroy
         */
        closeAllMicroApp(destroy = true) {
            this.apps.forEach(app => {
                app.isOpen = false
                if (destroy) {
                    microApp.unmountApp(app.name, {destroy: true})
                }
            });
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
                        if ($A.leftExists(name, 'appstore')) {
                            this.$store.dispatch("updateMicroAppsStatus");
                        }
                        if ($A.isSubElectron) {
                            $A.Electron.sendMessage('windowDestroy');
                        } else {
                            resolve()
                        }
                    }
                })
            })
        },

        /**
         * 关闭之前判断（助理）
         * @returns {Promise<unknown>}
         */
        onAssistClose() {
            return new Promise(resolve => {
                const app = this.apps.findLast(item => item.isOpen)
                if (app) {
                    this.closeByName(app.name)
                } else {
                    resolve()
                }
            })
        },
    }
}
</script>
