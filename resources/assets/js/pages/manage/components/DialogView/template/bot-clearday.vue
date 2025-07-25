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
                "### {{设置保留消息时间}}",
                "",
                "| {{属性}} | {{结果}} |",
                "| ------ | ------ |",
                "| {{机器人ID}} | " + this.msg.data.userid + " |",
                "| {{机器人名称}} | " + this.msg.data.nickname + " |",
                "| {{清理周期}} | " + this.msg.data.clear_day + " {{天}} |",
                "| {{下次清理}} | " + this.msg.data.clear_at + " |",
            ].map(item => item.replace(/\{\{([^}]+)\}\}/g, (_, v1) => this.$L(v1))).join("\n");
        },
    },
    methods: {},
}
</script>
