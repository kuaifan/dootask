<template>
    <div class="open-approve-details" :data-id="msg.data.id">
        <b>{{ $L(`${msg.data.comment_nickname} 评论了 ${msg.data.nickname} 的「${msg.data.proc_def_name}」审批`) }}</b>
        <div class="cause">
            <p>{{$L('申请人')}}：<span class="mark-color">@{{ msg.data.nickname }}</span> {{ msg.data.department }}</p>
            <b>{{$L('评论内容')}}</b>
            <p>{{ msg.data.comment_content }}</p>
            <p v-if="msg.data.thumb" v-html="imageHtml(msg.data.thumb)"></p>
        </div>
        <div class="btn-raw no-dark-content">
            <button class="ivu-btn ivu-btn-grey">{{$L('查看详情')}}</button>
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

