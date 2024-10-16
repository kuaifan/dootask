<template>
    <div>
        <p><b>机器人详情。</b></p>
        <p>&nbsp;</p>

        <p>机器人ID：<span class="mark-color">{{ msg.data.userid }}</span></p>
        <p>机器人名称：<span class="mark-color">{{ msg.data.nickname }}</span></p>
        <p>保留消息时间：<span class="mark-color">{{ msg.data.clear_day }}</span></p>
        <p>最后一次清理时间：<span class="mark-color">{{ msg.data.clear_at || '-' }}</span></p>
        <p>Webhook地址：<span class="mark-color">{{ msg.data.webhook_url || '-' }}</span></p>
        <p>Webhook请求次数：<span class="mark-color">{{ msg.data.webhook_num }}</span></p>
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
    computed: {},
    methods: {},
}
</script>
