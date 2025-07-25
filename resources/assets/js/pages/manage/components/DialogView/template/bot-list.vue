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
                "### {{我的机器人}}",
                "",
                "| ID | {{名称}} | {{清理时间}} | Webhook |",
                "| ------ | ------ | ------ | ------ |",
                ...this.msg.data.map(item => {
                    return "| " + item.userid + " | " + item.nickname + " | " + item.clear_day + " | " + (item.webhook_url ? '√' : '') + " |";
                }),
            ].map(item => item.replace(/\{\{([^}]+)\}\}/g, (_, v1) => this.$L(v1))).join("\n");
        },
    },
    methods: {},
}
</script>
