<template>
    <div class="page-microapp">
        <transition name="microapp-load">
            <div class="microapp-load">
                <Loading />
            </div>
        </transition>
        <micro-app v-if="url && !loading" 
            :name='name' 
            :url='url' 
            inline 
            destroy 
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
import { EventCenterForMicroApp } from '@micro-zoe/micro-app'
import DialogWrapper from '../pages/manage/components/DialogWrapper.vue'
import UserSelect from "./UserSelect.vue";
import { languageList, languageType } from "../language";

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
        data:{
            type: Object,
            default: () => {}
        }
    },
    data() {
        return {
            loading: false,
            appUrl: '',
            appData: {}
        }
    },
    deactivated() {
    },
    mounted() {
    },
    watch: {
        url(val) {
            this.loading = true;
            this.$nextTick(() => {
                this.loading = false;
                this.appUrl = val
            })
        },
        path(val) {
            this.appData = { path: val }
        },
        data: {
            handler(info) {
                this.appData = info
            },
            deep: true,
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
            window.eventCenterForAppNameVite = new EventCenterForMicroApp(this.name)
        },
        handleBeforeMount(e) {
            this.appData = {
                type: 'init',
                vues: {
                    Vue,
                    store,
                    components: {
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
                path: this.path
            }
        },
        handleMount(e) {
            this.appData = this.data;
            if(this.path){
                this.appData.path = this.path
            }
        },
        handleUnmount(e) {
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