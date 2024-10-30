<template>
    <div>
        <p><b>{{$L("我的机器人")}}</b></p>
        <p>&nbsp;</p>

        <p><b>{{$L("ID | 名称 | 清理时间 | Webhook")}}</b></p>
        <p v-for="item in msg.data">{{ item.userid }} | {{ item.nickname }} | {{ item.clear_day }} | {{ item.webhook_url ? '✅' : '-' }}</p>
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
