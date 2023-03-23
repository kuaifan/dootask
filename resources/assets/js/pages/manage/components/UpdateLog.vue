<template>
    <Modal
        v-model="uplogShow"
        :fullscreen="uplogFull"
        class-name="update-log">
        <div slot="header">
            <div class="uplog-head">
                <div class="uplog-title">{{$L('更新日志')}}</div>
                <Tag v-if="updateVer" color="volcano">{{updateVer}}</Tag>
            </div>
        </div>
        <MarkdownPreview class="uplog-body scrollbar-overlay" :initialValue="updateLog"/>
        <div slot="footer" class="adaption">
            <Button type="default" @click="uplogFull=!uplogFull">{{$L(uplogFull ? '缩小查看' : '全屏查看')}}</Button>
        </div>
    </Modal>
</template>

<script>
import MarkdownPreview from "../../../components/MDEditor/components/preview";
export default {
    name: 'UpdateLog',
    components: {MarkdownPreview},
    props: {
        value:{
            type: Boolean,
            default: false
        },
        updateVer: {},
        updateLog: {},
    },

    data() {
        return {
            uplogShow: false,
            uplogFull: false
        }
    },

    watch: {
        value: {
            handler(val) {
                this.uplogShow = val
            },
            immediate: true
        },
        uplogShow(val) {
            this.$emit("input", val)
        }
    },
}
</script>
