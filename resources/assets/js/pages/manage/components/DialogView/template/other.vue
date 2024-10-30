<template>
    <div v-if="isBot">
        {{$L("不支持的指令")}} <span class="warning-color">{{msg.type}}</span>，{{$L("你可以发送")}} <span class="mark-color">/help</span> {{$L("查看帮助菜单")}}。
    </div>
    <div v-else>
        {{$L("未知的消息")}}
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
    computed: {
        isBot() {
            return /^\//.test(this.msg.type);
        },
    },
    methods: {},
}
</script>
