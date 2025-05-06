<template>
    <DrawerOverlay
        ref="drawer"
        v-model="appConfig.isOpen"
        placement="right"
        modal-class="micro-app-modal"
        drawer-class="micro-app-drawer"
        :transitions="transitions"
        :force-fullscreen="appConfig.forceFullscreen"
        :size="1200">
        <div v-if="appConfig.isOpen" class="micro-app-wrapper">
            <micro-app
                :name="appConfig.appName"
                :url="appConfig.appUrl"
                :keep-alive="appConfig.keepAlive"
                :data="appData"
                @created="created"
                @beforemount="beforemount"
                @mounted="mounted"
                @unmount="unmount"
                @error="error"/>
            <div v-if="loadIng > 0" class="micro-app-load">
                <Loading/>
            </div>
        </div>
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

.micro-app-wrapper {
    .micro-app-load {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        align-items: center;
        display: flex;
        justify-content: center;
    }
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

const appMaps = new Map();

export default {
    name: "MicroApps",
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
            'router-mode': 'state',     // 路由设置为state模式
        })

        emitter.on('openMicroApp', this.openMicroApp);
    },

    beforeDestroy() {
        emitter.off('openMicroApp', this.openMicroApp);
    },

    watch: {
        userToken(val) {
            if (!val) {
                microApp.unmountAllApps({destroy: true})
            }
        },
    },

    computed: {
        ...mapState([
            'userInfo',
            'themeName',
        ]),

        transitions() {
            return this.appConfig.forceFullscreen ? ['', ''] : []
        },

        appData() {
            const {initialData, appName} = this.appConfig;

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
                    ...initialData,

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
                    this.$refs.drawer.onClose();
                    if (destroy) {
                        setTimeout(_ => {
                            microApp.unmountApp(appName, {destroy: true})
                        }, 301)
                    }
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
            const item = appMaps.get(e.detail.name)
            if (item?.isLoading) {
                this.loadIng++
            }
        },

        // 即将渲染
        beforemount(e) {
            const item = appMaps.get(e.detail.name)
            if (item?.isLoading) {
                this.loadIng--
            }
        },

        // 已经渲染完成
        mounted() {
        },

        // 已经卸载
        unmount() {
        },

        // 加载出错
        error() {
        },

        /**
         * 打开微应用
         * @param config
         */
        openMicroApp(config) {
            // 处理数据
            config = Object.assign({
                appName: 'micro-app',       // 微应用唯一标识名称
                appUrl: null,               // 微应用的入口URL地址
                initialData: {},            // 初始化时传递给微应用的数据对象
                forceFullscreen: false,     // 是否强制全屏(true/false)，默认自动适应
                keepAlive: true,            // 是否开启微应用保活(true/false)，默认开启

                isLoading: true,            // 私有参数，是否显示加载状态(true/false)
                isOpen: false,              // 私有参数，是否打开微应用(true/false)
            }, config);

            // 判断卸载上次
            const lastApp = appMaps.get(config.appName)
            if (lastApp) {
                if (lastApp.displayMode != config.displayMode || lastApp.appUrl != config.appUrl) {
                    microApp.unmountApp(config.appName, {destroy: true})
                } else {
                    config.isLoading = false;
                }
            }

            // 更新数据
            appMaps.set(config.appName, this.appConfig = config);

            // 打开微应用
            this.$nextTick(_ => {
                this.appConfig.isOpen = true
            })
        }
    }
}
</script>
