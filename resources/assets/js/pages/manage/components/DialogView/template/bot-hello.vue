<template>
    <div>
        您好，我是机器人：{{msg.data.nickname}}，我的机器人ID是：{{msg.data.userid}}，你可以发送 <span class="mark-color">/help</span> 查看我支持什么命令。
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
