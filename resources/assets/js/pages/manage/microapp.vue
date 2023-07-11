<template>
    <div class="page-microapp">
        <transition name="microapp-load">
            <div class="microapp-load">
                <Loading/>
            </div>
        </transition>
        <!-- <div class="messenger-msg" style="width: 500px;position: relative;height: 100%;">
           <DialogWrapper :dialogId="1"  is-messenger/>
        </div> -->
        <micro-app name='micro-app' v-if="microAppUrl && !loading"
            :url='microAppUrl' 
            baseRoute="/" 
            inline 
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
import { EventCenterForMicroApp } from '@micro-zoe/micro-app'
import ProjectDialog from "./components/ProjectDialog.vue";
import DialogWrapper from "./components/DialogWrapper.vue";

window.DialogWrapper = DialogWrapper;
// Vue.component('MyComponent', {
//   // 组件的配置和代码...
// })
window.Vue = Vue;

export default {
    components: { ProjectDialog, DialogWrapper },
    data() {
        return {
            loading: false,
            microAppUrl: 'http://localhost:5567/',
            microAppData: { }
        }
    },

    deactivated() {
        this.loading = true;
    },

    watch: {
        '$route': {
            handler(to) {
                if( to.name == 'manage-microapp' ){
                    this.loading = false;
                    window.eventCenterForAppNameVite = new EventCenterForMicroApp("micro-app")
                }
            },
            immediate: true,
        },
    },

    methods: {
        handleCreate(e) {
            console.log("子应用创建了",e)
        },
        handleBeforeMount(e) {
            console.log("子应用即将被渲染",e)
        },
        handleMount(e) {
            console.log("子应用已经渲染完成",e)
            this.microAppData =  { 
                msg: '来自基座的数据' 
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