<template>
    <MicroApps :url="appUrl" :path="path" v-if="!loading" />
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
                        let url = $A.apiUrl("../apps/okr")
                        if (url.indexOf('http') == -1) {
                            url = window.location.origin + url
                        }
                        this.appUrl = import.meta.env.VITE_OKR_WEB_URL || url
                        this.path = this.$route.query.path || '';
                    })
                }
            },
            immediate: true
        }
    }
}
</script>