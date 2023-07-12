<template>
    <div class="page-microapp">
        <transition name="microapp-load">
            <div class="microapp-load">
                <Loading/>
            </div>
        </transition>
        <micro-app name='micro-app' v-if="microAppUrl && !loading"
            :url='microAppUrl' 
            inline 
            destroy
            disableSandbox 
            :data='microAppData'
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
import {languageList, languageType} from "../../language";

export default {
    data() {
        return {
            loading: false,
            microAppUrl: '',
            microAppData: {}
        }
    },

    deactivated() {
        this.loading = true;
    },

    watch: {
        '$route': {
            handler(to) {
                if( to.name == 'manage-microapp' ){
                    this.loading = true;
                    this.$nextTick(()=>{
                        this.loading = false;
                        this.microAppUrl = this.$route.query.url
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
            console.log("子应用创建了",e)
        },
        handleBeforeMount(e) {
            console.log("子应用即将被渲染",e)
        },
        handleMount(e) {
            this.microAppData = { 
                type: 'init',
                vues:{
                    Vue,
                    store,
                    components:{
                        DialogWrapper
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
        handleUnmount(e) {
            this.loading = true;
            console.log("子应用卸载了",e)
        },
        handleError(e) {
            console.log("子应用加载出错了",e.detail.error)
        },
        handleDataChange(e) {
            console.log('来自子应用 child-vite 的数据:', e.detail.data)
        }
    }
}
</script>