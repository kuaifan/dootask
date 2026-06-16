<template>
    <div class="open-approve-details" :data-id="msg.data.id">
        <b>{{ $L(`${msg.data.nickname} 提交的「${msg.data.proc_def_name}」待你审批`) }}</b>
        <div class="cause">
            <p>{{$L("状态")}}：<b>{{ $L(statusText) }}</b></p>
            <p>{{$L("申请人")}}：<span class="mark-color">@{{ msg.data.nickname }}</span> {{ msg.data.department }}</p>
            <b>{{$L("详情")}}</b>
            <p v-if="msg.data.type">{{$L("类型")}}：{{ $L(msg.data.type) }}</p>
            <p v-if="msg.data.start_time">{{$L("开始时间")}}：{{ msg.data.start_time }}<template v-if="msg.data.start_day_of_week"> ({{ $L(msg.data.start_day_of_week) }})</template></p>
            <p v-if="msg.data.end_time">{{$L("结束时间")}}：{{ msg.data.end_time }}<template v-if="msg.data.end_day_of_week"> ({{ $L(msg.data.end_day_of_week) }})</template></p>
            <p v-if="msg.data.description">{{$L("事由")}}：{{ msg.data.description }}</p>
            <p v-if="msg.data.thumb" v-html="imageHtml(msg.data.thumb)"></p>
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
        statusText({msg}) {
            switch (msg.action) {
                case 'pass': return '已同意';
                case 'refuse': return '已拒绝';
                case 'withdraw': return '已撤销';
                default: return '待审批';
            }
        },
    },
    methods: {
        imageHtml(info) {
            const data = $A.imageRatioHandle({
                src: info.url,
                width: info.width,
                height: info.height,
                crops: {ratio: 5, percentage: '320x0'},
                scaleSize: 220,
            })
            return `<img src="${data.src}" width="${data.width}" height="${data.height}" />`
        }
    },
}
</script>
