<template>
    <div class="open-approve-details" :data-id="msg.data.id">
        <b>{{ $L(title) }}</b>
        <div class="cause">
            <p>{{$L("申请人")}}：<span class="mark-color">@{{ msg.data.start_nickname }}</span> {{ msg.data.department }}</p>
            <b>{{$L("详情")}}</b>
            <p v-if="msg.data.type">{{$L("假期类型")}}：{{ $L(msg.data.type) }}</p>
            <p>{{$L("开始时间")}}：{{ msg.data.start_time }} ({{ $L(msg.data.start_day_of_week) }})</p>
            <p>{{$L("结束时间")}}：{{ msg.data.end_time }} ({{ $L(msg.data.end_day_of_week) }})</p>
            <p>{{$L("事由")}}：{{ msg.data.description }}</p>
        </div>
        <div class="btn-raw no-dark-content">
            <button v-if="msg.action === 'pass'" class="ivu-btn ivu-btn-grey">{{$L("已同意")}}</button>
            <button v-else-if="msg.action === 'refuse'" class="ivu-btn ivu-btn-grey">{{$L("已拒绝")}}</button>
            <button v-else-if="msg.action === 'withdraw'" class="ivu-btn ivu-btn-grey">{{$L("已撤销")}}</button>
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
    computed: {
        title({msg}) {
            return msg.action === 'pass' ? `您发起的「${msg.data.proc_def_name}」已通过` : `您发起的「${msg.data.proc_def_name}」被 ${msg.data.nickname} 拒绝`
        }
    },
    methods: {},
}
</script>
