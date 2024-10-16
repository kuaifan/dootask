<template>
    <div>
        <p><b>机器人 <span class="mark-color">{{ msg.data.nickname }} (ID:{{ msg.data.userid }})</span> 已加入的会话：</b></p>
        <p>&nbsp;</p>

        <p><b>会话ID | 会话名称</b></p>
        <p v-for="item in msg.data.list">
            {{ item.id }} | {{ item.name }}{{ item.type == 'user' ? ' (个人)' : '' }}
        </p>
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
