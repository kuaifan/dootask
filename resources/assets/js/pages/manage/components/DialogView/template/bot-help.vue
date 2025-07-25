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
        IDLabel() {
            return this.msg.manager ? this.$L('机器人ID') : '';
        },
        content() {
            return [
                "### {{您可以通过发送以下命令来控制我}}",
                "",
                "| {{命令}} | {{说明}} |",
                "| ------ | ------ |",
                this.msg.manager ? "| ~/list~ | {{机器人列表}} |" : null,
                this.msg.manager ? "| ~/newbot ({{机器人名称}})~ | {{创建机器人}} |": null,
                !this.msg.manager ? "| ~/info~ | {{查看机器人详情}} |" : null,
                "",
                "### {{修改机器人}}",
                "",
                "| {{命令}} | {{说明}} |",
                "| ------ | ------ |",
                "| ~/setname:IDLabel: ({{机器人名称}})~ | {{修改机器人名称}} |",
                "| ~/deletebot:IDLabel:~ | {{删除机器人}} |",
                "| ~/clearday:IDLabel: ({{天数}})~ | {{设置保留消息时间（默认30天）}} |",
                "| ~/webhook:IDLabel: [url]~ | {{设置消息Webhook（详情请看 API接口文档）}} |",
                "",
                "### {{机器人设置}}",
                "",
                "| {{命令}} | {{说明}} |",
                "| ------ | ------ |",
                "| ~/token:IDLabel:~ | {{生成Token令牌}} |",
                "| ~/revoke:IDLabel:~ | {{撤销机器人Token令牌}} |",
                "",
                "### {{会话管理}}",
                "",
                "| {{命令}} | {{说明}} |",
                "| ------ | ------ |",
                "| ~/dialog:IDLabel: [{{搜索关键词}}]~ | {{查看会话ID}} |",
                "",
                "### {{API接口文档}}",
                "",
                "| {{命令}} | {{说明}} |",
                "| ------ | ------ |",
                "| ~/api~ | {{查看接口列表}} |",
            ].filter(Boolean).map(item => {
                return item
                .replace(/~([^~]+)~/g, (_, v1) => '<span class="mark-color mark-set">' + v1 + '</span>')
                .replace(/\{\{([^}]+)\}\}/g, (_, v1) => this.$L(v1))
                .replace(/:IDLabel:/g, ' {' + this.IDLabel + '}');
            }).join("\n");
        },
    },
}
</script>
