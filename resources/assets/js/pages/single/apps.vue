<template>
    <MicroApps ref="app"/>
</template>

<script>
import MicroApps from "../../components/MicroApps";

export default {
    components: { MicroApps },

    async mounted() {
        const id = this.$route.params.appId;
        if (!id) {
            $A.modalError("应用不存在");
            return
        }

        const app = (await $A.IDBArray("cacheMicroApps")).reverse().find(item => item.id === id);
        if (!app) {
            $A.modalError("应用不存在");
            return
        }

        await this.$refs.app.observeMicroApp(app)
    }
}
</script>
