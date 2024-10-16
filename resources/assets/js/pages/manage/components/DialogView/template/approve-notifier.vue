<template>
    <div class="open-approve-details" :data-id="msg.data.id">
        <b>抄送{{ msg.data.nickname }}提交的「{{ msg.data.proc_def_name }}」记录</b>
        <div class="cause">
            <p>申请人：<span class="mark-color">@{{ msg.data.nickname }}</span> {{ msg.data.department }}</p>
            <b>详情</b>
            <p v-if="msg.data.type">假期类型：{{ msg.data.type }}</p>
            <p>开始时间：{{ msg.data.start_time }} ({{ msg.data.start_day_of_week }})</p>
            <p>结束时间：{{ msg.data.end_time }} ({{ msg.data.end_day_of_week }})</p>
            <p>事由：{{ msg.data.description }}</p>
        </div>
        <div class="btn-raw no-dark-content">
            <button v-if="msg.is_finished" class="ivu-btn ivu-btn-grey">已同意</button>
            <button v-else class="ivu-btn ivu-btn-grey">查看详情</button>
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
    computed: {},
    methods: {},
}
</script>
