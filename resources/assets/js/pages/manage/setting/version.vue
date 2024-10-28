<template>
    <div class="setting-item submit">
        <div class="version-box">
            <div v-if="loadIng" class="version-load">{{$L('加载中...')}}</div>
            <VMPreview v-else :value="updateLog"/>
        </div>
    </div>
</template>

<style lang="scss">
.version-box {
    overflow: auto;
    padding: 24px 34px;
    .version-load {
        font-size: 16px;
    }
    .vuepress-markdown-body {
        padding: 0 !important;
        color: inherit;
    }
}
</style>
<script>
import VMPreview from "../../../components/VMEditor/preview.vue";

export default {
    components: {VMPreview},
    data() {
        return {
            loadIng: 0,
            updateLog: '',
        }
    },
    mounted() {
        this.getLog();
    },
    methods: {
        getLog() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'system/get/updatelog',
                data: {
                    take: 50
                }
            }).then(({data}) => {
                this.updateLog = data.updateLog;
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },
    }
}
</script>
