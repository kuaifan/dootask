<template>
    <div
        v-if="appConfig.transparent"
        v-transfer-dom
        :data-transfer="true">
        <micro-app
            v-if="appConfig.isOpen"
            :name="appConfig.appName"
            :url="appConfig.appUrl"
            :keep-alive="appConfig.keepAlive"
            :data="appData"
            @created="created"
            @beforemount="beforemount"
            @mounted="mounted"
            @unmount="unmount"
            @error="error"/>
    </div>
    <DrawerOverlay
        v-else
        v-model="appConfig.isOpen"
        modal-class="micro-app-modal"
        drawer-class="micro-app-drawer"
        placement="right"
        :beforeClose="onBeforeClose"
        :size="1200">
        <template>
            <micro-app
                v-if="appConfig.isOpen"
                :name="appConfig.appName"
                :url="appConfig.appUrl"
                :keep-alive="appConfig.keepAlive"
                :data="appData"
                @created="created"
                @beforemount="beforemount"
                @mounted="mounted"
                @unmount="unmount"
                @error="error"/>
            <div v-if="loadIng > 0" class="micro-app-loading">
                <Loading/>
            </div>
        </template>
    </DrawerOverlay>
</template>

<style lang="scss">
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

.micro-app-loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    align-items: center;
    display: flex;
    justify-content: center;
}
</style>

<script>
import Vue from 'vue'
import store from '../store/index'
import {mapState} from "vuex";
import microApp from '@micro-zoe/micro-app'
import DialogWrapper from '../pages/manage/components/DialogWrapper.vue'
import UserSelect from "./UserSelect.vue";
import {languageList, languageName} from "../language";
import {DatePicker} from 'view-design-hi';
import DrawerOverlay from "./DrawerOverlay/index.vue";
import emitter from "../store/events";
import TransferDom from "../directives/transfer-dom";

const appMaps = new Map();

export default {
    name: "MicroApps",
    directives: {TransferDom},
    components: {DrawerOverlay},

    data() {
        return {
            loadIng: 0,
            appConfig: {},
        }
    },

    mounted() {
        microApp.start({
            'iframe': true,
            'router-mode': 'state',
        })

        emitter.on('openMicroApp', this.openMicroApp);
    },

    beforeDestroy() {
        emitter.off('openMicroApp', this.openMicroApp);
    },

    watch: {
        userToken(token) {
            !token && microApp.unmountAllApps({destroy: true})
        },
    },

    computed: {
        ...mapState([
            'userInfo',
            'themeName',
        ]),

        appData() {
            return {
                instance: {
                    Vue,
                    store,
                    components: {
                        DialogWrapper,
                        UserSelect,
                        DatePicker,
                    },
                    options: {
                        shortcuts: $A.timeOptionShortcuts(),
                    }
                },

                initialData: {
                    ...this.appConfig.initialData,

                    systemInfo: window.systemInfo,
                    baseUrl: $A.mainUrl(),

                    isEEUIApp: $A.isEEUIApp,
                    isElectron: $A.isElectron,
                    isMainElectron: $A.isMainElectron,
                    isSubElectron: $A.isSubElectron,

                    languages: {
                        languageList,
                        languageName,
                    },
                    themeName: this.themeName,

                    userInfo: this.userInfo,
                    userToken: this.userToken,
                },

                handleClose: (destroy = false) => {
                    this.closeMicroApp(destroy)
                },

                nextModalIndex: () => {
                    if (typeof window.modalTransferIndex === 'number') {
                        return window.modalTransferIndex++;
                    }
                    return 1000;
                },

                openAppChildPage: (objects) => {
                    this.$store.dispatch('openAppChildPage', objects);
                },

                openChildWindow: (params) => {
                    this.$store.dispatch('openChildWindow', params);
                },

                openWebTabWindow: (url) => {
                    this.$store.dispatch('openWebTabWindow', url);
                },
            }
        }
    },
    methods: {
        // 元素被创建
        created(e) {
            this.stateChange(e)
        },

        // 即将渲染
        beforemount() {
        },

        // 已经渲染完成
        mounted(e) {
            this.stateChange(e, true)
        },

        // 已经卸载
        unmount() {
        },

        // 加载出错
        error(e) {
            this.stateChange(e, true)
            $A.modalError({
                language: false,
                title: this.$L('应用加载失败'),
                content: e.detail.error,
                onOk: () => {
                    this.closeMicroApp(true)
                },
            });
        },

        // 加载状态变化
        stateChange(e, end = false) {
            const appConfig = appMaps.get(e.detail.name)
            if (appConfig?.isLoading) {
                if (end) {
                    if (appConfig.transparent) {
                        this.$store.dispatch('hiddenSpinner');
                    } else {
                        this.loadIng--;
                    }
                } else {
                    if (appConfig.transparent) {
                        this.$store.dispatch('showSpinner');
                    } else {
                        this.loadIng++;
                    }
                }
            }
        },

        /**
         * 打开微应用
         * @param appConfig
         */
        openMicroApp(appConfig) {
            // 处理数据
            appConfig = Object.assign({
                appName: 'micro-app',       // 微应用唯一标识名称
                appUrl: null,               // 微应用的入口URL地址
                initialData: {},            // 初始化时传递给微应用的数据对象

                transparent: false,         // 是否透明模式(true/false)，默认不透明
                keepAlive: true,            // 是否开启微应用保活(true/false)，默认开启

                isLoading: true,            // 私有参数，是否显示加载状态(true/false)
                isOpen: false,              // 私有参数，是否打开微应用(true/false)
            }, appConfig);

            // 判断处理
            const lastConfig = appMaps.get(appConfig.appName)
            if (lastConfig) {
                if (lastConfig.displayMode != appConfig.displayMode || lastConfig.appUrl != appConfig.appUrl) {
                    microApp.unmountApp(appConfig.appName, {destroy: true})
                } else {
                    appConfig.isLoading = false;
                }
            }

            // 更新数据
            appMaps.set(appConfig.appName, this.appConfig = appConfig);

            // 打开微应用
            this.$nextTick(_ => {
                this.appConfig.isOpen = true
            })
        },

        /**
         * 关闭微应用
         * @param destroy
         */
        closeMicroApp(destroy) {
            this.appConfig.isOpen = false
            if (destroy) {
                microApp.unmountApp(this.appConfig.appName, {destroy: true})
            }
        },

        /**
         * 关闭之前判断
         * @returns {Promise<unknown>}
         */
        onBeforeClose() {
            return new Promise(resolve => {
                resolve()
            })
        },
    }
}
</script>
