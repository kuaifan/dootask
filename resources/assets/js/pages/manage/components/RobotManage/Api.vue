<template>
    <Modal
        v-model="apiShow"
        :title="$L('API文档')"
        :mask-closable="false"
        :footer-hide="true">
        <div class="version-box">
            <div v-if="loading > 0" class="version-load">{{$L('加载中...')}}</div>
            <VMPreview v-else :value="apiDoc"/>
        </div>
    </Modal>
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
import VMPreview from "../../../../components/VMEditor/preview.vue";

export default {
    components: {VMPreview},
    name: 'RobotManageApi',
    data() {
        return {
            apiShow: false,
            loading: 0,
            apiDoc: '',
        }
    },

    watch: {
        apiShow(val) {
            if (!val) {
                this.$emit('update:apiShow', false);
            } else {
                this.onGetApiDoc();
            }
        }
    },

    methods: {
        // 获取API文档
        onGetApiDoc() {
            this.loading++;
            this.$store.dispatch("call", {
                url: 'system/bot/apidoc',
            }).then(({data}) => {
                this.apiDoc = data.content;
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.loading--;
            });
        },
    }
}
</script>