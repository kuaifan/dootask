<template>
    <div>
        <p><b>{{$L("机器人")}} <span class="mark-color">{{ msg.data.nickname }} (ID:{{ msg.data.userid }})</span> {{$L("已加入的会话")}}：</b></p>
        <p>&nbsp;</p>

        <p><b>{{$L("会话ID | 会话名称")}}</b></p>
        <p v-for="item in msg.data.list">
            {{ item.id }} | {{ item.name }}{{ item.type == 'user' ? $L(' (个人)') : '' }}
        </p>
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
