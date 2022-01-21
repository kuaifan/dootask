<template>

    <div class="report-detail">
        <div class="report-title">{{ data.title }}</div>
        <div class="report-detail-context">
            <div class="report-profile">
                <Row>
                    <Col span="2">
                        <div class="report-submitter"><p>{{ $L('汇报人') }} </p></div>
                    </Col>
                    <Col span="6">
                        <div class="report-submitter">
                            <UserAvatar :userid="data.userid" :size="28"/>
                        </div>
                    </Col>
                    <Col span="2">
                        <div class="report-submitter"> <p>{{ $L('提交时间') }}</p></div>
                    </Col>
                    <Col span="6">
                        <div class="report-submitter">
                            <div>{{ data.created_at }}</div>
                        </div>
                    </Col>
                    <Col span="2">
                        <div class="report-submitter"><p>{{ $L('汇报对象') }}</p></div>
                    </Col>
                    <Col span="6">
                        <div class="report-submitter">
                            <UserAvatar v-for="item in data.receives_user" :key="item" :userid="item.userid" :size="28"/>
                        </div>
                    </Col>
                </Row>
            </div>
            <Row class="report-main">
                <Col span="2">
                    <div class="report-submitter"><p>{{ $L('汇报内容') }}</p></div>
                </Col>
                <Col span="22">
                    <div class="report-content" v-html="data.content">
                    </div>
                </Col>
            </Row>
        </div>

    </div>
</template>

<script>
export default {
    name: "ReportDetail",
    props: {
        data: {
            default: {},
        }
    },
    mounted() {
        if (this.data.id > 0) this.sendRead();
        // console.log(this.data)
    },
    watch: {
        data() {
            if (this.data.id > 0) this.sendRead();
        },
    },
    methods: {
        sendRead() {
            this.$store.dispatch("call", {
                url: 'report/read',
                data: {ids: [this.data.id]},
                method: 'get',
            }).then(({data, msg}) => {
                // data 结果数据
                // msg 结果描述
            }).catch(({msg}) => {
                // msg 错误原因
            });
        },
    }
}
</script>

<style scoped>

</style>
