<template>
    <micro-app
        v-if="appConfig.displayMode=='page'"
        v-show="appConfig.isVisible"
        :name="appConfig.appName"
        :url="appConfig.appUrl"
        :data="appData"
        @created="created"
        @beforemount="beforemount"
        @mounted="mounted"
        @unmount="unmount"
        @error="error"/>
    <DrawerOverlay
        v-else-if="appConfig.displayMode=='drawer'"
        v-model="appConfig.isVisible"
        ref="drawer"
        placement="right"
        modal-class="micro-apps-modal"
        drawer-class="micro-apps-drawer"
        :size="1200">
        <div v-if="appConfig.isVisible" class="page-microapp">
            <micro-app
                :name="appConfig.appName"
                :url="appConfig.appUrl"
                :data="appData"
                @created="created"
                @beforemount="beforemount"
                @mounted="mounted"
                @unmount="unmount"
                @error="error"/>
            <div v-if="loadIng > 0" class="microapp-load">
                <Loading/>
            </div>
        </div>
    </DrawerOverlay>
</template>

<style lang="scss">
.micro-apps-modal {
    .ivu-modal-close {
        display: none;
    }
}

.micro-apps-drawer {
    .overlay-content {
        overflow: hidden;
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
            'keep-alive': true,         // 全局开启保活模式
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

        appData() {
            return {
                vues: {
                    Vue,
                    store,
                    components: {
                        DialogWrapper,
                        UserSelect,
                        DatePicker
                    }
                },

                datas: {
                    ...this.appConfig.initialData,

                    // theme: this.themeName,
                    themeName: this.themeName,
                    languages: {
                        languageList,
                        languageName,
                        // languageType: languageName,
                    },

                    userInfo: this.userInfo,
                    userToken: this.userToken,

                    electron: this.$Electron,
                },

                onClose: () => {
                    this.$refs.drawer?.onClose();
                },

                nextZIndex: () => {
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
                displayMode: 'drawer',      // 显示模式: 'page'-全屏模式, 'drawer'-抽屉模式
                isVisible: true,            // 是否显示微应用(true/false)
                appUrl: null,               // 微应用的入口URL地址
                initialData: {},            // 初始化时传递给微应用的数据对象
                isLoading: true,            // 私有参数，是否显示加载状态(true/false)
            }, config);

            // 判断卸载上一次
            const item = appMaps.get(config.appName)
            if (item) {
                if (item.displayMode != config.displayMode || item.appUrl != config.appUrl) {
                    microApp.unmountApp(config.appName, {destroy: true})
                } else {
                    config.isLoading = false;
                }
            }

            // 更新数据
            appMaps.set(config.appName, this.appConfig = config);
        }
    }
}
</script>
