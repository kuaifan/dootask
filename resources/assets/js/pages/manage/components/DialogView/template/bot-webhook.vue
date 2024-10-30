<template>
    <div>
        <p><b>{{$L("设置Webhook地址")}}</b></p>
        <p>&nbsp;</p>

        <p>{{$L("机器人ID")}}：<span class="mark-color">{{ msg.data.userid }}</span></p>
        <p>{{$L("机器人名称")}}：<span class="mark-color">{{ msg.data.nickname }}</span></p>
        <p>{{$L("Webhook地址")}}：<span class="mark-color">{{ msg.data.webhook_url }}</span></p>
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
