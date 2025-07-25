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
                "### {{生成Token令牌}}",
                "",
                "| {{属性}} | {{结果}} |",
                "| ------ | ------ |",
                "| <span style='white-space:nowrap;'>{{机器人ID}}</span> | " + this.msg.data.userid + " |",
                "| <span style='white-space:nowrap;'>{{机器人名称}}</span> | " + this.msg.data.nickname + " |",
                "| Token | " + this.msg.data.token + " |",
            ].map(item => item.replace(/\{\{([^}]+)\}\}/g, (_, v1) => this.$L(v1))).join("\n");
        },
    },
    methods: {},
}
</script>
