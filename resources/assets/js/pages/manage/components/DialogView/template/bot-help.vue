<template>
    <div>
        <p>您可以通过发送以下命令来控制我：</p>
        <p>&nbsp;</p>

        <template v-if="msg.manager">
            <p><span class="mark-color">/list</span> - 机器人列表</p>
            <p><span class="mark-color">/newbot {机器人名称}</span> - 创建机器人</p>
        </template>
        <template v-else>
            <p><span class="mark-color">/info</span> - 查看机器人详情</p>
        </template>
        <p>&nbsp;</p>

        <p><b>修改机器人</b></p>
        <p><span class="mark-color">/setname{{IDLabel}} {机器人名称}</span> - 修改机器人名称</p>
        <p><span class="mark-color">/deletebot{{IDLabel}}</span> - 删除机器人</p>
        <p><span class="mark-color">/clearday{{IDLabel}} {天数}</span> - 设置保留消息时间（默认30天）</p>
        <p><span class="mark-color">/webhook{{IDLabel}} [url]</span> - 设置消息Webhook（详情请看 <u>Api接口文档</u>）</p>
        <p>&nbsp;</p>

        <p><b>机器人设置</b></p>
        <p><span class="mark-color">/token{{IDLabel}}</span> - 生成Token令牌</p>
        <p><span class="mark-color">/revoke{{IDLabel}}</span> - 撤销机器人Token令牌</p>
        <p>&nbsp;</p>

        <p><b>会话管理</b></p>
        <p><span class="mark-color">/dialog{{IDLabel}} [搜索关键词]</span> - 查看会话ID</p>
        <p>&nbsp;</p>

        <p><b>Api接口文档</b></p>
        <p><span class="mark-color">/api</span> - 查看接口列表</p>
    </div>
</template>

<script lang="ts">
export default {
    props: {
        msg: Object,
    },
    data() {
        return {};
    },
    computed: {
        IDLabel() {
            return this.msg.manager ? ' {机器人ID}' : '';
        },
    },
}
</script>
