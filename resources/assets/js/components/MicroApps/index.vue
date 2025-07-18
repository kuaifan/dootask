<template>
    <div>
        <MicroModal
            v-for="(app, key) in microApps"
            :key="key"
            v-model="app.isOpen"
            :ref="`ref-${app.name}`"
            :size="1200"
            :background="app.background"
            :transparent="app.transparent"
            :autoDarkTheme="app.auto_dark_theme"
            :keepAlive="app.keep_alive"
            :beforeClose="async () => { await onBeforeClose(app.name) }">
            <MicroIFrame
                v-if="shouldRenderIFrame(app)"
                :name="app.name"
                :url="app.url"
                :data="appData(app.name)"
                :immersive="app.iframe_immersive"
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
        emitter.on('observeMicroApp:open', this.onOpen);
        emitter.on('observeMicroApp:updatedOrUninstalled', this.onUpdatedOrUninstalled);
    },

    beforeDestroy() {
        emitter.off('observeMicroApp:open', this.onOpen);
        emitter.off('observeMicroApp:updatedOrUninstalled', this.onUpdatedOrUninstalled);
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
        microApps: {
            handler(items) {
                this.assistShow = !!items.find(item => item.isOpen)
            },
            deep: true,
        }
    },

    computed: {
        ...mapState([
            'userInfo',
            'themeName',
            'microApps',
            'safeAreaSize',
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
            this.$store.commit('microApps/update', {name, data: {isLoading: false}})
        },

        /**
         * 应用数据
         * @param name
         * @returns {*}
         */
        appData(name) {
            const app = this.microApps.find(item => item.name == name);
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

                    name: app.name,
                    url: app.url,
                    urlType: app.url_type,

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
                    safeArea: this.safeAreaSize,
                },

                methods: {
                    close: (destroy = false) => {
                        this.closeMicroApp(name, destroy)
                    },
                    back: () => {
                        this.closeByName(name)
                    },
                    popoutWindow: async (windowConfig = null) => {
                        const app = this.microApps.find(item => item.name == name);
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
                    requestAPI: async (params) => {
                        return await store.dispatch('call', params);
                    },
                    selectUsers: async (params) => {
                        if (!$A.isJson(params)) {
                            params = {value: params}
                        }
                        if (!$A.isArray(params.value)) {
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
                    nextZIndex: () => {
                        if (typeof window.modalTransferIndex === 'number') {
                            return window.modalTransferIndex++;
                        }
                        return 1000;
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
                    isFullScreen: () => {
                        return window.innerWidth < 768 || this.windowType === 'popout'
                    },
                },
            }
        },

        /**
         * 观察打开微应用
         * @param config
         */
        async onOpen(config) {
            if (/_blank$/i.test(config.url_type)) {
                await this.inlineBlank(config)
                return
            }
            if (config.url_type === 'external') {
                await this.externalWindow(config)
                return
            }

            const app = this.microApps.find(({name}) => name == config.name);
            if (app) {
                // 恢复 keep_alive
                if (app.keepAliveBackup !== undefined) {
                    app.keep_alive = app.keepAliveBackup
                    delete app.keepAliveBackup
                }

                // 更新微应用
                if (app.url != config.url) {
                    this.unmountMicroApp(app)
                    app.isLoading = true
                }
                Object.assign(app, config)
                requestAnimationFrame(_ => {
                    app.isOpen = true
                    app.lastOpenAt = Date.now()
                    this.$store.commit('microApps/keepAlive', 3)
                })
            } else {
                // 新建微应用
                config.isLoading = true
                config.isOpen = false
                config.onBeforeClose = () => true
                this.$store.commit('microApps/push', config)
                requestAnimationFrame(_ => {
                    config.isOpen = true
                    config.lastOpenAt = Date.now()
                    this.$store.commit('microApps/keepAlive', 3)
                })
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

                url_type: config.url_type.replace(/_blank$/, ''),
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
            await $A.IDBSet("cacheMicroApps", $A.cloneJSON(apps));

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
            const app = this.microApps.find(item => item.name == name);
            if (!app) {
                return;
            }

            app.isOpen = false
            if (destroy) {
                this.unmountMicroApp(app)
            }
        },

        /**
         * 关闭所有微应用
         * @param destroy
         */
        closeAllMicroApp(destroy = true) {
            this.microApps.forEach(app => {
                app.isOpen = false
                if (destroy) {
                    this.unmountMicroApp(app)
                }
            });
        },

        /**
         * 卸载微应用
         * @param app
         */
        unmountMicroApp(app) {
            if (app.keep_alive) {
                app.keepAliveBackup = true
                app.keep_alive = false
            }
            microApp.unmountApp(app.name, {destroy: true})
        },

        /**
         * 关闭之前判断
         * @param name
         * @returns {Promise<unknown>}
         */
        onBeforeClose(name) {
            return new Promise(resolve => {
                const onClose = () => {
                    if ($A.isSubElectron) {
                        $A.Electron.sendMessage('windowDestroy');
                    } else {
                        resolve()
                    }
                }

                const app = this.microApps.find(item => item.name == name);
                if (!app) {
                    onClose()
                    return
                }

                if (/^iframe/i.test(app.url_type)) {
                    const before = app.onBeforeClose();
                    if (before && before.then) {
                        before.then(() => {
                            onClose()
                        });
                    } else {
                        onClose()
                    }
                    return
                }

                microApp.forceSetData(name, {type: 'beforeClose'}, array => {
                    if (!array?.find(item => item === true)) {
                        if ($A.leftExists(name, 'appstore')) {
                            this.$store.dispatch("updateMicroAppsStatus");
                        }
                        onClose()
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
                const app = this.microApps.findLast(item => item.isOpen)
                if (app) {
                    this.closeByName(app.name)
                } else {
                    resolve()
                }
            })
        },

        /**
         * 是否渲染 iframe
         * @param app
         * @returns {boolean}
         */
        shouldRenderIFrame(app) {
            return app.url_type === 'iframe' && (app.isOpen || app.keep_alive) && app.url;
        },

        /**
         * 应用更新或卸载
         * @param apps
         */
        onUpdatedOrUninstalled(apps) {
            const ids = apps.map(item => item.id)
            if (ids.length === 0) {
                return
            }
            this.microApps.forEach(app => {
                if (ids.includes(app.id)) {
                    this.closeMicroApp(app.name, true)
                }
            })
        }
    }
}
</script>
