<template>
    <div>
        <p><b>创建成功。</b></p>
        <p>&nbsp;</p>

        <p>机器人ID：<span class="mark-color">{{ msg.data.userid }}</span></p>
        <p>机器人名称：<span class="mark-color">{{ msg.data.nickname }}</span></p>
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
