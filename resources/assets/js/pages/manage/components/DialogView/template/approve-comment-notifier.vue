<template>
    <div class="open-approve-details" :data-id="msg.data.id">
        <b>{{ msg.data.comment_nickname }}{{$L('评论了此审批')}}</b>
        <div class="cause">
            <p>{{$L('申请人')}}：<span class="mark-color">@{{ msg.data.nickname }}</span> {{ msg.data.department }}</p>
            <b>{{$L('评论内容')}}</b>
            <p>{{ msg.data.comment_content }}</p>
            <p v-if="isPicture">[{{$L('图片')}}]</p>
        </div>
        <div class="btn-raw no-dark-content">
            <button class="ivu-btn ivu-btn-grey">{{$L('查看详情')}}</button>
        </div>
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
    computed: {
        isPicture() {
            const {comment_pictures} = this.msg.data
            return $A.isArray(comment_pictures) && comment_pictures.length > 0
        },
    },
    methods: {},
}
</script>

