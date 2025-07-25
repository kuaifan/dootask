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
                "### {{创建成功}}",
                "",
                "| {{属性}} | {{结果}} |",
                "| ------ | ------ |",
                "| {{机器人ID}} | " + this.msg.data.userid + " |",
                "| {{机器人名称}} | " + this.msg.data.nickname + " |",
            ].map(item => item.replace(/\{\{([^}]+)\}\}/g, (_, v1) => this.$L(v1))).join("\n");
        },
    },
    methods: {},
}
</script>
