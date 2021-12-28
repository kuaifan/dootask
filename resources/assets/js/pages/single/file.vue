<template>
    <div class="electron-file">
        <PageTitle :title="editInfo.name"/>
        <Loading v-if="loadIng > 0"/>
        <FileContent v-else v-model="editShow" :file="editInfo"/>
    </div>
</template>

<style lang="scss" scoped>
.electron-file {
    display: flex;
    align-items: center;
    .file-content {
        border-radius: 0;
    }
}
</style>
<script>
import FileContent from "../manage/components/FileContent";

export default {
    components: {FileContent},
    data() {
        return {
            loadIng: 0,

            editShow: true,
            editInfo: {},
        }
    },
    mounted() {
        //
    },
    watch: {
        '$route': {
            handler() {
                this.getInfo();
            },
            immediate: true
        },
    },
    methods: {
        getInfo() {
            let id = $A.runNum(this.$route.params.id);
            if (id <= 0) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'file/one',
                data: {
                    id,
                },
            }).then(({data}) => {
                this.loadIng--;
                this.editInfo = data;
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError({
                    content: msg,
                    onOk: () => {
                        if (this.$Electron) {
                            window.close();
                        }
                    }
                });
            });
        }
    }
}
</script>
