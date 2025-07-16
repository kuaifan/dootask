<template>
    <MicroApps ref="app" window-type="popout"/>
</template>

<script>
import MicroApps from "../../components/MicroApps";

export default {
    components: { MicroApps },

    async mounted() {
        const {name} = this.$route.params;
        if (!name) {
            $A.modalError("应用不存在");
            return
        }

        const app = (await $A.IDBArray("cacheMicroApps")).reverse().find(item => item.name === name);
        if (!app) {
            $A.modalError("应用不存在");
            return
        }

        await this.$refs.app.onOpen(app)
    }
}
</script>
