<template>
    <div class="electron-file">
        <PageTitle :title="fileInfo.name"/>
        <Loading v-if="loadIng > 0"/>
        <template v-else>
            <FilePreview v-if="fileCode" :code="fileCode" :file="fileInfo"/>
            <FileContent v-else v-model="fileShow" :file="fileInfo"/>
        </template>
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
import FilePreview from "../manage/components/FilePreview";

export default {
    components: {FilePreview, FileContent},
    data() {
        return {
            loadIng: 0,

            fileShow: true,
            fileInfo: {},
            fileCode: null,
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
            let id = this.$route.params.id;
            let data = {};
            if (id > 0) {
                data.id = id;
                this.fileCode = null;
            } else if (id != '') {
                data.code = id;
                this.fileCode = id;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'file/one',
                data,
            }).then(({data}) => {
                this.loadIng--;
                this.fileInfo = data;
            }).catch(({msg}) => {
                this.loadIng--;
                $A.modalError({
                    content: msg,
                    onOk: () => {
                        window.close();
                    }
                });
            });
        }
    }
}
</script>
