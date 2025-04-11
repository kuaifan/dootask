<template>
    <div class="electron-dialog">
        <PageTitle :title="dialogData.name"/>
        <DialogWrapper v-if="dialogId > 0" :dialogId="dialogId"/>
    </div>
</template>

<style lang="scss" scoped>
.electron-dialog {
    height: 100%;
    display: flex;
    flex-direction: column;
}
</style>
<script>
import DialogWrapper from "../manage/components/DialogWrapper.vue";
import {mapState} from "vuex";

export default {
    components: {DialogWrapper},
    computed: {
        ...mapState(['cacheDialogs']),

        dialogId() {
            const {dialogId} = this.$route.params;
            return parseInt(/^\d+$/.test(dialogId) ? dialogId : 0);
        },

        dialogData() {
            return this.cacheDialogs.find(({id}) => id === this.dialogId) || {}
        }
    },
}
</script>
