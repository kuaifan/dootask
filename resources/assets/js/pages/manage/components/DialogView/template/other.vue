<template>
    <div v-if="isBot">
        不支持的指令 <span class="warning-color">{{msg.type}}</span>，你可以发送 <span class="mark-color">/help</span> 查看帮助菜单。
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
            return /^\//.test(this.msg.type);
        },
    },
    methods: {},
}
</script>
