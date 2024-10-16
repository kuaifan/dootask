<template>
    <div>
        <p><b>设置保留消息时间。</b></p>
        <p>&nbsp;</p>

        <p>机器人ID：<span class="mark-color">{{ msg.data.userid }}</span></p>
        <p>机器人名称：<span class="mark-color">{{ msg.data.nickname }}</span></p>
        <p>清理周期：<span class="mark-color">{{ msg.data.clear_day }}天</span></p>
        <p>下次清理：<span class="mark-color">{{ msg.data.clear_at }}</span></p>
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
