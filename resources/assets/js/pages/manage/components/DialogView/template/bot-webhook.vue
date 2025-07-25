<template>
    <DialogMarkdown :text="content"/>
</template>

<script>
import DialogMarkdown from "../../DialogMarkdown.vue";

export default {
    components: {DialogMarkdown},
    props: {
        msg: Object,
    },
    data() {
        return {};
    },
    computed: {
        content() {
            return [
                "### {{设置Webhook地址}}",
                "",
                "| {{属性}} | {{结果}} |",
                "| ------ | ------ |",
                "| {{机器人ID}} | " + this.msg.data.userid + " |",
                "| {{机器人名称}} | " + this.msg.data.nickname + " |",
                "| {{Webhook地址}} | " + this.msg.data.webhook_url + " |",
            ].map(item => item.replace(/\{\{([^}]+)\}\}/g, (_, v1) => this.$L(v1))).join("\n");
        },
    },
    methods: {},
}
</script>
