<template>
    <micro-app
        v-if="appMode=='page'"
        v-show="appShow"
        :name="appName"
        :url="appUrl"
        :data="appData"
        @created="created"
        @beforemount="beforemount"
        @mounted="mounted"
        @unmount="unmount"
        @error="error"/>
    <DrawerOverlay
        v-else-if="appMode=='drawer'"
        v-model="appShow"
        ref="drawer"
        placement="right"
        modal-class="micro-apps-modal"
        drawer-class="micro-apps-drawer"
        :size="1200">
        <div v-if="appShow" class="page-microapp">
            <micro-app
                :name="appName"
                :url="appUrl"
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

export default {
    name: "MicroApps",
    components: {DrawerOverlay},

    data() {
        return {
            loadIng: 0,

            appMode: '',
            appShow: false,

            appName: "micro-app",
            appUrl: "",
            appParams: {},
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
                theme: this.themeName,
                languages: {
                    languageList,
                    languageName,
                    languageType: languageName,
                },
                userInfo: this.userInfo,
                userToken: this.userToken,
                electron: this.$Electron,
                params: this.appParams,

                nextZIndex: () => {
                    if (typeof window.modalTransferIndex === 'number') {
                        return window.modalTransferIndex++;
                    }
                    return 1000;
                },

                onClose: () => {
                    this.$refs.drawer?.onClose();
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
        created() {
            console.log('元素被创建')
            this.loadIng++
        },
        beforemount() {
            console.log('即将渲染')
            this.loadIng--
        },
        mounted() {
            console.log('已经渲染完成')
        },
        unmount() {
            console.log('已经卸载')
        },
        error() {
            console.log('加载出错')
        },

        openMicroApp(data) {
            this.appName    = data.name ?? 'micro-app';
            this.appUrl     = data.url ?? null;
            this.appParams  = data.params ?? {};

            this.appShow    = data.show ?? true;
            this.appMode    = data.mode ?? 'drawer';
        }
    }
}
</script>
