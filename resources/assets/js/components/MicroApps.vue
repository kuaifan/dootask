<template>
    <div class="page-microapp">
        <transition name="microapp-load" v-if="showSpin">
            <div class="microapp-load">
                <Loading/>
            </div>
        </transition>
        <micro-app v-if="url && !loading"
            :name='name'
            :url='url'
            inline
            keep-alive
            disableSandbox
            :data='appData'
            @created='handleCreate'
            @beforemount='handleBeforeMount'
            @mounted='handleMount'
            @unmount='handleUnmount'
            @error='handleError'
            @datachange='handleDataChange'
        ></micro-app>
    </div>
</template>

<script>
import Vue from 'vue'
import store from '../store/index'
import { mapState } from "vuex";
import { EventCenterForMicroApp, unmountAllApps } from '@micro-zoe/micro-app'
import DialogWrapper from '../pages/manage/components/DialogWrapper.vue'
import UserSelect from "./UserSelect.vue";
import { languageList, languageType } from "../language";
import { DatePicker } from 'view-design-hi';

export default {
    name: "MicroApps",
    props: {
        name: {
            type: String,
            default: "micro-app"
        },
        url: {
            type: String,
            default: ""
        },
        path: {
            type: String,
            default: ""
        },
        datas:{
            type: Object,
            default: () => {}
        }
    },
    data() {
        return {
            showSpin: false,
            loading: false,
            appUrl: '',
            appData: {},
        }
    },
    deactivated() {
    },
    mounted() {
        this.showSpin = true;
        this.appData = this.getAppData
    },
    watch: {
        loading(val){
            if(val){
                this.showSpin = true;
            }
        },
        url(val) {
            this.loading = true;
            this.$nextTick(() => {
                this.loading = false;
                let url = $A.apiUrl(val)
                if (url.indexOf('http') == -1) {
                    url = window.location.origin + url
                }
                this.appUrl =  import.meta.env.VITE_OKR_WEB_URL || url
            })
        },
        path(val) {
            this.appData = { path: val }
        },
        datas: {
            handler(info) {
                this.appData = info
            },
            deep: true,
        },
        '$route': {
            handler(to) {
                if(to.name == 'manage-apps' || to.name == 'single-apps'){
                    this.appData = {
                        path: to.hash || to.fullPath
                    }
                }
            },
            immediate: true,
        },
        userToken(val) {
            this.appData = this.getAppData;
            if(!val){
                unmountAllApps({ destroy: true })
                this.loading = true;
            }else{
                this.loading = false;
            }
        },
    },
    computed: {
        ...mapState([
            'userInfo',
            'themeMode',
        ]),
        getAppData(){
            return {
                type: 'init',
                url: this.url,
                vues: {
                    Vue,
                    store,
                    components: {
                        DialogWrapper,
                        UserSelect,
                        DatePicker
                    }
                },
                theme: this.themeMode,
                languages: {
                    languageList,
                    languageType,
                },
                userInfo: this.userInfo,
                path: this.path,
                electron: this.$Electron
            }
        }
    },
    methods: {
        handleCreate(e) {
            // 创建前
            window.eventCenterForAppNameVite = new EventCenterForMicroApp(e.detail.name)
            this.appData = this.getAppData
            this.showSpin = window["eventCenterForAppNameViteLoad-" + e.detail.name] ? false : true
        },
        handleBeforeMount(e) {
            window["eventCenterForAppNameViteLoad-" + e.detail.name] = 1;
        },
        handleMount(e) {
            // 加载完成
            if(this.datas){
                this.appData = this.datas;
            }
            if(this.path){
                this.appData.path = this.path
            }
            this.showSpin = false;
        },
        handleUnmount(e) {
            // 卸载
            window.dispatchEvent(new Event('apps-unmount'));
        },
        handleError(e) {
            console.error("子应用加载出错了",e.detail.error)
        },
        handleDataChange(e) {
            // console.log('来自子应用 child-vite 的数据:', e.detail.data)
        }
    }
}
</script>
