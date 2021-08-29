<template>
    <div class="page-download">
        <PageTitle :title="$L('下载')"/>
        <div v-if="loadIng > 0" class="download-load">
            <Loading/>
        </div>
        <div class="download-body">
            <canvas class="orb-canvas-1"></canvas>
            <canvas class="orb-canvas-2"></canvas>
            <div v-if="name" class="download-name">{{name}}</div>
            <div v-if="version" class="download-version">v{{version}}</div>
            <ul v-if="list.length > 0" class="download-list">
                <li v-for="(item, key) in list" :key="key">
                    <div class="app-icon">
                        <Icon :type="item.icon"/>
                    </div>
                    <div class="app-name">{{item.name}}</div>
                    <div class="app-size">{{$A.bytesToSize(item.size)}}</div>
                    <div class="app-button">
                        <a :href="item.url" target="_blank">{{$L('立即下载')}}</a>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            loadIng: 0,

            name: "Loading",
            version: "",
            list: []
        }
    },
    mounted() {
        this.getAppInfo()
    },
    methods: {
        getAppInfo() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'system/get/appinfo',
            }).then(({data}) => {
                this.loadIng--;
                this.name = data.name;
                this.version = data.version;
                this.list = data.list;
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError(msg);
            });
        }
    }
}
</script>
