<template>
    <div class="single-file">
        <PageTitle :title="fileInfo.name"/>
        <Loading v-if="loadIng > 0"/>
        <template v-else>
            <FilePreview v-if="code || fileInfo.permission === 0" :code="code" :file="fileInfo"/>
            <FileContent v-else v-model="fileShow" :file="fileInfo"/>
        </template>
    </div>
</template>

<style lang="scss" scoped>
.single-file {
    display: flex;
    align-items: center;
    .file-content,
    .file-preview {
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

            code: null,

            fileShow: true,
            fileInfo: {},
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
            let data = {id};
            if (/^\d+$/.test(id)) {
                this.code = null;
            } else if (id) {
                this.code = id;
            } else {
                return;
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
