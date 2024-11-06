<template>
    <div class="open-approve-details" :data-id="msg.data.id">
        <b>{{ $L(`抄送 ${msg.data.nickname} 提交的「${msg.data.proc_def_name}」记录`) }}</b>
        <div class="cause">
            <p>{{$L("申请人")}}：<span class="mark-color">@{{ msg.data.nickname }}</span> {{ msg.data.department }}</p>
            <b>{{$L("详情")}}</b>
            <p v-if="msg.data.type">{{$L("假期类型")}}：{{ $L(msg.data.type) }}</p>
            <p>{{$L("开始时间")}}：{{ msg.data.start_time }} ({{ $L(msg.data.start_day_of_week) }})</p>
            <p>{{$L("结束时间")}}：{{ msg.data.end_time }} ({{ $L(msg.data.end_day_of_week) }})</p>
            <p>{{$L("事由")}}：{{ msg.data.description }}</p>
        </div>
        <div class="btn-raw no-dark-content">
            <button v-if="msg.is_finished" class="ivu-btn ivu-btn-grey">{{$L("已同意")}}</button>
            <button v-else class="ivu-btn ivu-btn-grey">{{$L("查看详情")}}</button>
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
