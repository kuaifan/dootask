<template>
    <div>
        <MicroModal
            v-for="(app, key) in apps"
            :key="key"
            v-model="app.isOpen"
            :ref="`ref-${app.id}`"
            :size="1200"
            :transparent="app.transparent"
            :autoDarkTheme="app.autoDarkTheme"
            :beforeClose="async () => { await onBeforeClose(app.id) }">
            <micro-app
                v-if="app.isOpen && app.url"
                :name="app.id"
                :url="app.url"
                :keep-alive="app.keepAlive"
                :disable-scopecss="app.disableScopecss"
                :data="appData(app.id)"
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
            const app = this.apps.find(({id}) => id == e.detail.name);
            if (app) {
                app.isLoading = false
            }
        },

        /**
         * 应用数据
         * @param id
         * @returns {*}
         */
        appData(id) {
            const app = this.apps.find(item => item.id == id);
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
                        this.closeMicroApp(id, destroy)
                    },
                    back: () => {
                        this.closeById(id)
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
                                id: `url-${await $A.getSHA256Hash(config.url)}`,
                                url: config.url,
                            }
                            delete config.url
                        } else {
                            const app = this.apps.find(item => item.id == id);
                            if (!app) {
                                $A.modalError("应用不存在");
                                return
                            }
                            appConfig = Object.assign({}, app)
                        }
                        appConfig.transparent = true
                        appConfig.keepAlive = false

                        const apps = (await $A.IDBArray("cacheMicroApps")).filter(item => item.id != appConfig.id);
                        apps.length > 50 && apps.splice(0, 10)
                        apps.push(appConfig)
                        await $A.IDBSet("cacheMicroApps", apps);

                        await this.$store.dispatch('openChildWindow', {
                            name: `single-apps-${$A.randomString(6)}`,
                            path: `/single/apps/${appConfig.id}`,
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
         */
        async observeMicroApp(config) {
            const app = this.apps.find(({id}) => id == config.id);
            if (app) {
                // 更新微应用
                if (app.url != config.url) {
                    await microApp.unmountApp(app.id, {destroy: true})
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
         * 通过ID关闭微应用
         * @param id
         */
        closeById(id) {
            try {
                this.$refs[`ref-${id}`][0].onClose()
            } catch (e) {
                this.closeMicroApp(id)
            }
        },

        /**
         * 关闭微应用
         * @param id
         * @param destroy
         */
        closeMicroApp(id, destroy) {
            const app = this.apps.find(item => item.id == id);
            if (!app) {
                return;
            }

            app.isOpen = false
            if (destroy) {
                microApp.unmountApp(app.id, {destroy: true})
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
         * @param id
         * @returns {Promise<unknown>}
         */
        onBeforeClose(id) {
            return new Promise(resolve => {
                microApp.forceSetData(id, {type: 'beforeClose'}, array => {
                    if (!array?.find(item => item === true)) {
                        if (id === 'appstore') {
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
                    this.closeById(app.id)
                } else {
                    resolve()
                }
            })
        },
    }
}
</script>
