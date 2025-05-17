<template>
    <div>
        <MicroModal
            v-for="(app, key) in apps"
            :key="key"
            v-model="app.isOpen"
            :ref="`ref-${app.name}`"
            :size="1200"
            :transparent="app.transparent"
            :inheritDarkMode="app.inheritDarkMode"
            :beforeClose="async () => { await onBeforeClose(app.name) }">
            <micro-app
                v-if="app.isOpen"
                :name="app.name"
                :url="app.url"
                :keep-alive="app.keepAlive"
                :disable-scopecss="app.disableScopecss"
                :data="appData(app.name)"
                @created="created"
                @beforemount="beforemount"
                @mounted="mounted"
                @unmount="unmount"
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

export default {
    name: "MicroApps",
    directives: {TransferDom},
    components: {MicroModal, UserSelect},

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
                    popoutWindow: async (config) => {
                        let appConfig = {}
                        if (config.url) {
                            appConfig = {
                                name: `url-${await $A.getSHA256Hash(config.url)}`,
                                url: config.url,
                            }
                            delete config.url
                        } else {
                            const app = this.apps.find(item => item.name == name);
                            if (!app) {
                                $A.modalError("应用不存在");
                                return
                            }
                            appConfig = Object.assign({}, app)
                        }
                        appConfig.transparent = true
                        appConfig.keepAlive = false

                        const apps = (await $A.IDBArray("cacheMicroApps")).filter(item => item.name != appConfig.name);
                        apps.length > 50 && apps.splice(0, 10)
                        apps.push(appConfig)
                        await $A.IDBSet("cacheMicroApps", apps);

                        await this.$store.dispatch('openChildWindow', {
                            name: `single-apps-${$A.randomString(6)}`,
                            path: `/single/apps/${appConfig.name}`,
                            force: false,
                            config
                        });
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
         *  - name              应用名称
         *  - url               应用地址
         *  - props             传递参数
         *  - transparent       是否透明模式 (true/false)，默认 false
         *  - keepAlive         是否开启微应用保活 (true/false)，默认 true
         *  - disableScopecss   是否禁用样式隔离 (true/false)，默认 false
         *  - inheritDarkMode   是否继承暗黑模式 (true/false)，默认 false
         */
        observeMicroApp(config) {
            // 处理数据
            config.name = config.name || 'micro-app'
            config.url = config.url || null
            config.props = $A.isJson(config.props) ? config.props : {}
            config.transparent = typeof config.transparent == 'boolean' ? config.transparent : false
            config.keepAlive = typeof config.keepAlive == 'boolean' ? config.keepAlive : true
            config.disableScopecss = typeof config.disableScopecss == 'boolean' ? config.disableScopecss : false
            config.inheritDarkMode = typeof config.inheritDarkMode == 'boolean' ? config.inheritDarkMode : false

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
         */
        closeAllMicroApp() {
            this.apps = [];
            microApp.unmountAllApps({destroy: true})
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
                        if (name === 'appstore') {
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
