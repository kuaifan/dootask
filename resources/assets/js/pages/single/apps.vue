<template>
    <MicroApps ref="app"/>
</template>

<script>
import MicroApps from "../../components/MicroApps";

export default {
    components: { MicroApps },

    async mounted() {
        const name = this.$route.params.appName;
        if (!name) {
            $A.modalError("应用不存在");
            return
        }

        const app = (await $A.IDBArray("cacheMicroApps")).reverse().find(item => item.name === name);
        if (!app) {
            $A.modalError("应用不存在");
            return
        }

        this.$refs.app.openMicroApp(app)
    }
}
</script>
