<template>
    <div class="page-microapp">
        <transition name="microapp-load">
            <div class="microapp-load">
                <Loading/>
            </div>
        </transition>
        <micro-app name='micro-app' v-if="appUrl && !loading"
            :url='appUrl' 
            inline
            destroy
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
import store from '../../store/index'
import {mapState} from "vuex";
import { EventCenterForMicroApp } from '@micro-zoe/micro-app'
import DialogWrapper from './components/DialogWrapper'
import UserSelect from "../../components/UserSelect.vue";
import {languageList, languageType} from "../../language";

export default {
    data() {
        return {
            loading: false,
            appUrl: '',
            appData: {}
        }
    },

    deactivated() {
        this.loading = true;
    },

    watch: {
        '$route': {
            handler(to) {
                if( to.name == 'manage-apps' ){
                    this.loading = true;
                    this.$nextTick(()=>{
                        this.loading = false;
                        let url = $A.apiUrl(this.$route.query.url)
                        if( url.indexOf('http') == -1 ){
                            url = window.location.origin + url
                        }
                        this.appUrl = url
                        window.eventCenterForAppNameVite = new EventCenterForMicroApp("micro-app")
                    })
                }
            },
            immediate: true,
        },
    },

    computed: {
        ...mapState([
            'userInfo',
            'themeMode',
        ])
    },

    methods: {
        handleCreate(e) {
            // console.log("子应用创建了",e)
        },
        handleBeforeMount(e) {
            this.appData = { 
                type: 'init',
                vues:{
                    Vue,
                    store,
                    components:{
                        DialogWrapper,
                        UserSelect
                    }
                },
                theme: this.themeMode,
                languages: {
                    languageList,
                    languageType,
                },
                userInfo: this.userInfo,
            }
        },
        handleMount(e) {
           
        },
        handleUnmount(e) {
            this.loading = true;
            window.dispatchEvent(new Event('apps-unmount'));
            // console.log("子应用卸载了",e)
        },
        handleError(e) {
            // console.log("子应用加载出错了",e.detail.error)
        },
        handleDataChange(e) {
            // console.log('来自子应用 child-vite 的数据:', e.detail.data)
        }
    }
}
</script>