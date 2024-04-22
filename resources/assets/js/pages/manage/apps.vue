<template>
    <MicroApps :url="appUrl" :path="path" v-if="!loading && $route.name == 'manage-apps'" />
</template>

<script>
import MicroApps from "../../components/MicroApps.vue";

export default {
    components: { MicroApps },
    data() {
        return {
            loading: false,
            appUrl: '',
            path: '',
        }
    },

    deactivated() {
        this.loading = true;
    },

    watch: {
        '$route': {
            handler(to) {
                this.loading = true;
                if (to.name == 'manage-apps') {
                    this.$nextTick(() => {
                        this.loading = false;
                        this.appUrl = import.meta.env.VITE_OKR_WEB_URL || $A.mainUrl("apps/okr")
                        this.path = this.$route.query.path || '';
                    })
                }else{
                    this.appUrl = '';
                }
            },
            immediate: true
        }
    }
}
</script>
