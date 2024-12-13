<template>
    <div class="content-text no-dark-content">
        <DialogMarkdown v-if="msg.type === 'md'" @click="viewText" :text="msg.text"/>
        <pre v-else @click="viewText" v-html="$A.formatTextMsg(msg.text, userId)"></pre>

        <div class="content-longtext-footer">
            <span @click="downFile">{{$L('查看详情')}}</span>
        </div>
    </div>
</template>

<script>
import DialogMarkdown from "../DialogMarkdown.vue";

export default {
    components: {DialogMarkdown},
    props: {
        msgId: Number,
        msg: Object,
    },
    methods: {
        viewText(e) {
            this.$emit('viewText', e);
        },
        downFile() {
            this.$emit('downFile');
        },
    },
}
</script>
