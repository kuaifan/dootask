<template>
    <micro-app
        v-if="details.mode=='page'"
        v-show="details.show"
        :name="details.name"
        :url="details.url"
        :data="appData"
        @created="created"
        @beforemount="beforemount"
        @mounted="mounted"
        @unmount="unmount"
        @error="error"/>
    <DrawerOverlay
        v-else-if="details.mode=='drawer'"
        v-model="details.show"
        ref="drawer"
        placement="right"
        modal-class="micro-apps-modal"
        drawer-class="micro-apps-drawer"
        :size="1200">
        <div v-if="details.show" class="page-microapp">
            <micro-app
                :name="details.name"
                :url="details.url"
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
import {unmountAllApps} from '@micro-zoe/micro-app'
import DialogWrapper from '../pages/manage/components/DialogWrapper.vue'
import UserSelect from "./UserSelect.vue";
import {languageList, languageName} from "../language";
import {DatePicker} from 'view-design-hi';
import DrawerOverlay from "./DrawerOverlay/index.vue";
import emitter from "../store/events";

const mountApps = new Map();

export default {
    name: "MicroApps",
    components: {DrawerOverlay},

    data() {
        return {
            loadIng: 0,
            details: {},
        }
    },

    mounted() {
        emitter.on('openMicroApp', this.openMicroApp);
    },

    beforeDestroy() {
        emitter.off('openMicroApp', this.openMicroApp);
    },

    watch: {
        userToken(val) {
            if (!val) {
                unmountAllApps({destroy: true})
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
                    ...this.details.params,

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
            if (!mountApps.has(e.detail.name)) {
                this.loadIng++
            }
        },

        // 即将渲染
        beforemount(e) {
            if (!mountApps.has(e.detail.name)) {
                mountApps.set(e.detail.name, e.detail.name);
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
         * @param data
         */
        openMicroApp(data) {
            this.details = Object.assign({
                mode: 'drawer',     // page, drawer
                name: 'drawer-app', // 微应用名称
                show: true,         // 是否显示
                url: null,          // 微应用地址
                params: {},         // 传递给微应用的数据
            }, data)
        }
    }
}
</script>
