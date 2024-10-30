<template>
    <div>
        <p><b>{{$L("机器人详情")}}</b></p>
        <p>&nbsp;</p>

        <p>{{$L("机器人ID")}}：<span class="mark-color">{{ msg.data.userid }}</span></p>
        <p>{{$L("机器人名称")}}：<span class="mark-color">{{ msg.data.nickname }}</span></p>
        <p>{{$L("保留消息时间")}}：<span class="mark-color">{{ msg.data.clear_day }}</span></p>
        <p>{{$L("最后一次清理时间")}}：<span class="mark-color">{{ msg.data.clear_at || '-' }}</span></p>
        <p>{{$L("Webhook地址")}}：<span class="mark-color">{{ msg.data.webhook_url || '-' }}</span></p>
        <p>{{$L("Webhook请求次数")}}：<span class="mark-color">{{ msg.data.webhook_num }}</span></p>
    </div>
</template>

<script>
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
