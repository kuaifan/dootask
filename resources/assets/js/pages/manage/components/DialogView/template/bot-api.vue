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
            const sessionDesc = !/^(ai-|user-session-)/.test(this.msg.email) ? " <span style='color:#999;padding-left:4px;'>({{该机器人不支持}})</span>" : "";
            return [
                "## {{API 使用说明}}",
                "",
                "### 1. {{发送文本消息}}",
                "",
                "{{开发者可以通过此接口调用机器人向指定对话发送文本消息。}}",
                "",
                "#### {{接口信息}}",
                "",
                "| {{属性}} | {{结果}} |",
                "|------|------|",
                "| **{{请求方式}}** | POST |",
                "| **{{接口地址}}** | `" + $A.apiUrl('dialog/msg/sendtext') + "` |",
                "| **{{说明}}** | {{通过机器人向指定对话发送文本消息}} |",
                "",
                "#### {{请求头}}",
                "",
                "| {{参数名}} | {{值}} | {{必填}} |",
                "|--------|-----|------|",
                "| `version` | `" + this.msg.version + "` | √ |",
                "| `token` | {{机器人Token}} | √ |",
                "",
                "#### {{请求参数}}",
                "",
                "| {{参数名}} | {{说明}} | {{类型}} | {{必填}} | {{示例值}} |",
                "|--------|------|------|------|--------|",
                "| `dialog_id` | {{对话ID}} | string | √ | - |",
                "| `text` | {{消息内容}} | string | √ | - |",
                "| `text_type` | {{文本类型}} | string | - | {{html 或 md}} |",
                "| `key` | {{搜索词}} | string | - | {{留空自动生成}} |",
                "| `silence` | {{静默模式}} | string | - | {{yes 或 no}} |",
                "| `reply_id` | {{回复指定消息ID}} | string | - | - |",
                "",
                "### 2. {{Webhook 消息推送}}",
                "",
                "{{机器人收到消息后会自动POST推送到配置的Webhook地址，请求超时为10秒。}}",
                "",
                "#### {{推送参数}}",
                "",
                "| {{参数名}} | {{说明}} | {{类型}} |",
                "|--------|------|------|",
                "| `text` | {{消息文本内容}} | string |",
                "| `reply_text` | {{回复/引用的消息文本}} | string |",
                "| `token` | {{机器人Token}} | string |",
                "| `session_id` | {{会话ID}}" + sessionDesc + " | string |",
                "| `dialog_id` | {{对话ID}} | string |",
                "| `dialog_type` | {{对话类型}} | string |",
                "| `msg_id` | {{消息ID}} | string |",
                "| `msg_uid` | {{消息发送人ID}} | string |",
                "| `msg_user` | {{消息发送人信息}} | JSON |",
                "| `mention` | {{是否被@到}} | boolean |",
                "| `bot_uid` | {{机器人ID}} | string |",
                "| `version` | {{系统版本}} | string |",
            ].map(item => item.replace(/\{\{([^}]+)\}\}/g, (_, v1) => this.$L(v1))).join("\n");
        },
    },
    methods: {},
}
</script>
