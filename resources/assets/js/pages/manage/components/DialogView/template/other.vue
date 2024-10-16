<template>
    <div v-if="isBot">
        你好，我是你的机器人助理，你可以发送 <span class="mark-color">/help</span> 查看帮助菜单。
    </div>
    <div v-else>
        未知消息类型
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
        isBot() {
            return this.msg.type.startsWith('/');
        },
    },
    methods: {},
}
</script>
