<template>
    <div class="open-approve-details" :data-id="msg.data.id">
        <b>{{ $L(`${msg.data.nickname} 提交的「${msg.data.proc_def_name}」待你审批`) }}</b>
        <div class="cause">
            <p>{{$L("申请人")}}：<span class="mark-color">@{{ msg.data.nickname }}</span> {{ msg.data.department }}</p>
            <b>{{$L("详情")}}</b>
            <p v-if="msg.data.type">{{$L("假期类型")}}：{{ $L(msg.data.type) }}</p>
            <p>{{$L("开始时间")}}：{{ msg.data.start_time }} ({{ $L(msg.data.start_day_of_week) }})</p>
            <p>{{$L("结束时间")}}：{{ msg.data.end_time }} ({{ $L(msg.data.end_day_of_week) }})</p>
            <p>{{$L("事由")}}：{{ msg.data.description }}</p>
            <p v-if="msg.data.thumb" v-html="imageHtml(msg.data.thumb)"></p>
        </div>
        <div class="btn-raw no-dark-content">
            <button v-if="msg.action === 'pass'" class="ivu-btn ivu-btn-grey">{{$L("已同意")}}</button>
            <button v-else-if="msg.action === 'refuse'" class="ivu-btn ivu-btn-grey">{{$L("已拒绝")}}</button>
            <button v-else-if="msg.action === 'withdraw'" class="ivu-btn ivu-btn-grey">{{$L("已撤销")}}</button>
            <template v-else>
                <button class="ivu-btn ivu-btn-primary">{{$L("同意")}}</button>
                <button class="ivu-btn ivu-btn-error">{{$L("拒绝")}}</button>
            </template>
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
    methods: {
        imageHtml(info) {
            const data = $A.imageRatioHandle({
                src: info.url,
                width: info.width,
                height: info.height,
                crops: {ratio: 3, percentage: '320x0'},
                scaleSize: 220,
            })
            return `<img src="${data.src}" width="${data.width}" height="${data.height}" />`
        }
    },
}
</script>
