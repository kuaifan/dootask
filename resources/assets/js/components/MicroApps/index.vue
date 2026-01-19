<template>
    <div>
        <MicroModal
            v-for="(app, key) in microApps"
            :key="key"
            :open="app.isOpen"
            :ref="`ref-${app.name}`"
            :size="1200"
            :options="app"
            :windowType="windowType"
            :beforeClose="onBeforeClose"
            @on-capsule-more="onCapsuleMore"
            @on-popout-window="onPopoutWindow"
            @on-confirm-close="closeMicroApp">
            <MicroIFrame
                v-if="shouldRenderIFrame(app)"
                :name="app.name"
                :url="app.url"
                :data="appData(app.name)"
                :immersive="app.immersive"
                @mounted="mounted"
                @error="error"/>
            <micro-app
                v-else-if="shouldRenderMicro(app)"
                :name="app.name"
                :url="app.url"
                :keep-alive="app.keep_alive"
                :disable-scopecss="app.disable_scope_css"
                :data="appData(app.name)"
                @mounted="mounted"
                @error="error"/>

            <!--加载中-->
            <transition name="fade">
                <div v-if="loadings.includes(app.name)" class="micro-app-loader">
                    <Loading/>
                </div>
            </transition>
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
    z-index: 9999;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.transparent-mode {
    &:not(.popout-window) {
        .micro-app-loader {
            background-color: rgba(255, 255, 255, 0.6);
        }
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
            isRestarting: false,
            userSelectOptions: {value: [], config: {}},

            backupConfigs: {},
            loadings: [],
            closings: [],
        }
    },

    created() {
        // 卸载所有微应用（防止刷新导致的缓存）
        microApp.unmountAllApps({destroy: true})

        // 初始化微应用
        microApp.start({
            'router-mode': 'state',
            'iframe': true,
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
            this.unmountAllMicroApp()
        },
        themeName() {
            this.unmountAllMicroApp()
        },
        assistShow(show) {
            if (!show && $A.isSubElectron && !this.isRestarting) {
                // 如果是子 Electron 窗口，关闭窗口助理时销毁窗口（但是重启过程中不销毁）
                $A.Electron.sendMessage('windowDestroy');
            }
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
            this.loadings = this.loadings.filter(item => item !== name);
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
                    type: app.type,
                    urlType: app.type, // 兼容旧版本

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
                        await this.onPopoutWindow(name, windowConfig)
                    },
                    openWindow: (params) => {
                        if (!$A.isJson(params)) {
                            params = {path: params}
                        }
                        if (params.url) {
                            params.path = params.url
                            delete params.url
                        }
                        // 兼容旧格式
                        if ($A.isJson(params.config)) {
                            Object.assign(params, params.config)
                            delete params.config
                        }
                        this.$store.dispatch('openWindow', params);
                    },
                    openTabWindow: (url) => {
                        this.$store.dispatch('openWindow', {path: url});
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
                        return await new Promise((resolve, reject) => {
                            this.$refs.userSelect.onSelection((res) => {
                                resolve(res)
                            }, reject)
                        })
                    },
                    setCapsuleConfig: (config) => {
                        if (!$A.isJson(config)) {
                            return
                        }
                        this.$store.commit('microApps/update', {
                            name,
                            data: {
                                capsule: config,
                            }
                        })
                        this.setCapsuleCache(name, config)
                    },
                    nextZIndex: () => {
                        if (typeof window.modalTransferIndex === 'number') {
                            return window.modalTransferIndex++;
                        }
                        return 1000;
                    },
                    isFullScreen: () => {
                        return window.innerWidth < 768 || this.windowType === 'popout'
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
                    extraCallStore: async (...args) => {
                        if (args.length > 0 && typeof args[0] === 'string') {
                            const actionName = args[0];
                            const payload = args.slice(1);
                            await this.$store.dispatch(actionName, ...payload)
                        }
                        return null;
                    },
                    extraCallEmitter: async (...args) => {
                        if (args.length > 0 && typeof args[0] === 'string') {
                            const actionName = args[0];
                            const payload = args.slice(1);
                            emitter.emit(actionName, ...payload);
                        }
                        return null;
                    },
                },
            }
        },

        /**
         * 设置胶囊缓存
         * @param name
         * @param config
         */
        async setCapsuleCache(name, config) {
            const cache = await $A.IDBJson("microAppsCapsuleCache");
            if ($A.isTrue(config.no_cache)) {
                if (typeof cache[name] === "undefined") {
                    return
                }
                delete cache[name];
            } else {
                cache[name] = config;
            }
            await $A.IDBSet("microAppsCapsuleCache", cache);
        },

        /**
         * 移除胶囊缓存
         * @param name
         */
        async removeCapsuleCache(name) {
            const cache = await $A.IDBJson("microAppsCapsuleCache");
            if (typeof cache[name] === "undefined") {
                return
            }
            delete cache[name];
            await $A.IDBSet("microAppsCapsuleCache", cache);
        },

        /**
         * 观察打开微应用
         * @param config
         */
        async onOpen(config) {
            // 备份配置
            this.backupConfigs[config.name] = $A.cloneJSON(config);

            // 从缓存读取胶囊配置
            const capsuleCache = await $A.IDBJson("microAppsCapsuleCache");
            if ($A.isJson(capsuleCache[config.name])) {
                if ($A.isHave(config.capsule, true)) {
                    Object.assign(config.capsule, capsuleCache[config.name]);
                } else {
                    config.capsule = capsuleCache[config.name];
                }
            }

            // 解析 type 字段
            config.type = this.resolveType(config.type)

            // 如果是 blank 链接，则在新窗口打开
            if (/_blank$/i.test(config.type)) {
                await this.inlineBlank(config)
                return
            }

            // 如果是外部链接，则在新窗口打开
            if (config.type === 'external') {
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
                if (app.url != config.url || !app.keep_alive) {
                    this.unmountMicroApp(app)
                    this.loadings.push(app.name)
                }
                Object.assign(app, config)
                requestAnimationFrame(_ => {
                    app.isOpen = true
                    app.lastOpenAt = Date.now()
                    this.$store.commit('microApps/keepAlive', 3)
                })
            } else {
                // 新建微应用
                config.isOpen = false
                config.postMessage = () => {}
                config.onBeforeClose = () => true
                this.$store.commit('microApps/push', config)
                this.loadings.push(config.name)
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

                // 新窗口强制参数
                type: config.type.replace(/_blank$/, ''),
                transparent: true,
                keep_alive: false,
            };
            if (windowConfig?.url) {
                appConfig.url = windowConfig.url;
                delete windowConfig.url;
            }

            const path = `/single/apps/${appConfig.name}`
            const apps = (await $A.IDBArray("cacheMicroApps")).filter(item => item.name != appConfig.name);
            apps.length > 50 && apps.splice(0, 10)
            apps.push(appConfig)
            await $A.IDBSet("cacheMicroApps", $A.cloneJSON(apps));

            if (this.$Electron) {
                const mergedConfig = Object.assign({
                    title: appConfig.title || ' ',
                }, $A.isJson(windowConfig) ? windowConfig : {});
                await this.$store.dispatch('openWindow', {
                    name: `single-apps-${$A.randomString(6)}`,
                    path: path,
                    title: mergedConfig.title,
                    width: mergedConfig.width,
                    height: mergedConfig.height,
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
                await this.$store.dispatch('openWindow', {
                    name: `external-apps-${$A.randomString(6)}`,
                    path: config.url,
                    title: config.title || ' ',
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
         * 关闭微应用状态
         * @param {Object} app 微应用对象
         * @param app
         */
        closeAppState(app) {
            this.loadings = this.loadings.filter(item => item !== app.name);
            this.closings.push(app.name);
            app.isOpen = false;
            setTimeout(() => {
                this.closings = this.closings.filter(item => item !== app.name);
            }, 300);
        },

        /**
         * 关闭微应用（关闭前执行beforeClose）
         * @param name
         */
        closeByName(name) {
            try {
                this.$refs[`ref-${name}`][0].attemptClose()
            } catch (e) {
                this.closeMicroApp(name)
            }
        },

        /**
         * 关闭微应用（直接关闭）
         * @param name
         * @param destroy
         */
        closeMicroApp(name, destroy = false) {
            const app = this.microApps.find(item => item.name == name);
            if (!app) {
                return;
            }

            this.closeAppState(app)
            if (destroy === true) {
                this.unmountMicroApp(app)
            }
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
         * 卸载所有微应用
         */
        unmountAllMicroApp() {
            this.microApps.forEach(app => {
                this.closeAppState(app)
                this.unmountMicroApp(app)
            });
        },

        /**
         * 关闭之前判断
         * @param name
         * @returns {Promise<unknown>}
         */
        onBeforeClose(name) {
            return new Promise(resolve => {
                const handleClose = () => {
                    if ($A.isSubElectron) {
                        $A.Electron.sendMessage('windowDestroy');
                    } else {
                        resolve()
                    }
                }

                const app = this.microApps.find(item => item.name == name);
                if (!app) {
                    // 如果应用不存在，则直接关闭
                    handleClose()
                    return
                }

                if (this.isIframe(app.type)) {
                    const before = app.onBeforeClose();
                    if (before && before.then) {
                        before.then(() => {
                            handleClose()
                        });
                    } else {
                        handleClose()
                    }
                    return
                }

                microApp.forceSetData(name, {type: 'beforeClose'}, array => {
                    if (!array?.find(item => item === true)) {
                        if ($A.leftExists(name, 'appstore')) {
                            this.$store.dispatch("updateMicroAppsStatus");
                        }
                        handleClose()
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
         * 点击更多操作
         * @param name
         * @param action
         */
        onCapsuleMore(name, action) {
            switch (action) {
                case "popout":
                    this.onPopoutWindow(name)
                    break;

                case "restart":
                    this.removeCapsuleCache(name)
                    this.onRestartApp(name)
                    break;

                case "destroy":
                    this.removeCapsuleCache(name)
                    this.closeMicroApp(name, true)
                    break;

                default:
                    const app = this.microApps.find(item => item.name == name);
                    if (!app) {
                        return
                    }
                    if (this.isIframe(app.type)) {
                        app.postMessage({
                            type: 'MICRO_APP_MENU_CLICK',
                            message: action
                        });
                        return
                    }
                    microApp.forceSetData(name, {type: 'menuClick', message: action})
                    break;
            }
        },

        /**
         * 重启应用
         * @param name
         */
        async onRestartApp(name) {
            this.isRestarting = true
            try {
                this.closeMicroApp(name, true)
                await new Promise(resolve => setTimeout(resolve, 300));

                const app = this.backupConfigs[name];
                if (!app) {
                    $A.modalError("应用不存在");
                    return
                }
                await this.onOpen(app)
            } finally {
                this.isRestarting = false
            }
        },

        /**
         * 弹出窗口（全屏）
         * @param name
         * @param windowConfig
         */
        async onPopoutWindow(name, windowConfig = null) {
            const app = this.microApps.find(item => item.name == name);
            if (!app) {
                $A.modalError("应用不存在");
                return
            }
            await this.inlineBlank(app, windowConfig)
            this.closeMicroApp(name, true)
        },

        /**
         * 是否 iframe 类型
         * @param type
         * @returns {boolean}
         */
        isIframe(type) {
            return /^iframe/i.test(type)
        },

        /**
         * 是否渲染 iframe
         * @param app
         * @returns {boolean}
         */
        shouldRenderIFrame(app) {
            return app.url && this.isIframe(app.type) && (app.isOpen || app.keep_alive);
        },

        /**
         * 是否渲染 micro
         * @param app
         * @returns {boolean}
         */
        shouldRenderMicro(app) {
            return app.url && !this.isIframe(app.type) && (app.isOpen || this.closings.includes(app.name));
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
        },

        /**
         * 解析类型
         * @param type
         */
        resolveType(type) {
            if (typeof type === 'string') {
                return type
            }
            if ($A.isJson(type)) {
                const defaultType = typeof type.default === 'string' ? type.default : 'iframe'
                const mobileType = typeof type.mobile === 'string'
                    ? type.mobile
                    : (typeof type.app === 'string' ? type.app : defaultType)
                const desktopType = typeof type.desktop === 'string' ? type.desktop : defaultType
                return $A.platformType() === 'desktop' ? desktopType : mobileType
            }
            return 'inline'
        }
    }
}
</script>
