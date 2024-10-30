<template>
    <div>
        <p><b>{{$L("设置保留消息时间")}}</b></p>
        <p>&nbsp;</p>

        <p>{{$L("机器人ID")}}：<span class="mark-color">{{ msg.data.userid }}</span></p>
        <p>{{$L("机器人名称")}}：<span class="mark-color">{{ msg.data.nickname }}</span></p>
        <p>{{$L("清理周期")}}：<span class="mark-color">{{ msg.data.clear_day }}{{$L("天")}}</span></p>
        <p>{{$L("下次清理")}}：<span class="mark-color">{{ msg.data.clear_at }}</span></p>
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
