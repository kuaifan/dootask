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
                "### {{机器人}} " + this.msg.data.nickname + " (ID:" + this.msg.data.userid + ") {{已加入的会话}}：",
                "",
                "| {{会话ID}} | {{会话名称}} |",
                "| ------ | ------ |",
                ...this.msg.data.list.map(item => {
                    return "| " + item.id + " | " + item.name + (item.type === 'user' ? "{{ (个人)}}" : "") + " |";
                }),
            ].map(item => item.replace(/\{\{([^}]+)\}\}/g, (_, v1) => this.$L(v1))).join("\n");
        },
    },
    methods: {},
}
</script>
