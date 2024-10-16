<template>
    <div class="open-approve-details" :data-id="msg.data.id">
        <b>{{ msg.data.nickname }}提交的「{{ msg.data.proc_def_name }}」待你审批</b>
        <div class="cause">
            <p>申请人：<span class="mark-color">@{{ msg.data.nickname }}</span> {{ msg.data.department }}</p>
            <b>详情</b>
            <p v-if="msg.data.type">假期类型：{{ msg.data.type }}</p>
            <p>开始时间：{{ msg.data.start_time }} ({{ msg.data.start_day_of_week }})</p>
            <p>结束时间：{{ msg.data.end_time }} ({{ msg.data.end_day_of_week }})</p>
            <p>事由：{{ msg.data.description }}</p>
        </div>
        <div class="btn-raw no-dark-content">
            <button v-if="msg.action === 'pass'" class="ivu-btn ivu-btn-grey">已同意</button>
            <button v-else-if="msg.action === 'refuse'" class="ivu-btn ivu-btn-grey">已拒绝</button>
            <button v-else-if="msg.action === 'withdraw'" class="ivu-btn ivu-btn-grey">已撤销</button>
            <template v-else>
                <button class="ivu-btn ivu-btn-primary">同意</button>
                <button class="ivu-btn ivu-btn-error">拒绝</button>
            </template>
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
