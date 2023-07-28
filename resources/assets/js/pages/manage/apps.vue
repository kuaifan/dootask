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
                if (to.name == 'manage-apps') {
                    this.loading = true;
                    this.$nextTick(() => {
                        this.loading = false;
                        let url = $A.apiUrl(this.$route.query.baseUrl)
                        if (url.indexOf('http') == -1) {
                            url = window.location.origin + url
                        }
                        this.appUrl = url
                        this.path = this.$route.query.path || '';
                    })
                }
            },
            immediate: true
        }
    }
}
</script>