<template>
    <div>
        {{$L("您好，我是机器人")}}：{{msg.data.nickname}}，{{$L("我的机器人ID是")}}：{{msg.data.userid}}，{{$L("你可以发送")}} <span class="mark-color">/help</span> {{$L("查看我支持什么命令")}}。
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
