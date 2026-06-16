<template>
    <div class="open-approve-details" :data-id="msg.data.id">
        <b>{{ $L(`抄送 ${msg.data.nickname} 提交的「${msg.data.proc_def_name}」记录`) }}</b>
        <div class="cause">
            <p>{{$L("状态")}}：<b>{{ msg.is_finished ? $L("已完成") : $L("审批中") }}</b></p>
            <p>{{$L("申请人")}}：<span class="mark-color">@{{ msg.data.nickname }}</span> {{ msg.data.department }}</p>
            <b>{{$L("详情")}}</b>
            <p v-if="msg.data.type">{{$L("类型")}}：{{ $L(msg.data.type) }}</p>
            <p v-if="msg.data.start_time">{{$L("开始时间")}}：{{ msg.data.start_time }}<template v-if="msg.data.start_day_of_week"> ({{ $L(msg.data.start_day_of_week) }})</template></p>
            <p v-if="msg.data.end_time">{{$L("结束时间")}}：{{ msg.data.end_time }}<template v-if="msg.data.end_day_of_week"> ({{ $L(msg.data.end_day_of_week) }})</template></p>
            <p v-if="msg.data.description">{{$L("事由")}}：{{ msg.data.description }}</p>
        </div>
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
