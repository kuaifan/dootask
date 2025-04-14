<template>
    <div class="electron-report">
        <PageTitle :title="$L('报告详情')"/>
        <ReportDetail :data="detailData" :type="type"/>
    </div>
</template>
<style lang="scss" scoped>
.electron-report {
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: auto;
}
</style>
<script>
import ReportDetail from "../manage/components/ReportDetail";

export default {
    components: {ReportDetail},
    data() {
        return {
            type: 'view',
            detailData: {},
        };
    },
    computed: {
        reportId() {
            const {reportDetailId} = this.$route.params;
            return reportDetailId;
        },
    },
    watch: {
        reportId: {
            handler() {
                this.getDetail();
            },
            immediate: true
        },
    },
    methods: {
        getDetail() {
            if (!this.reportId) {
                return;
            }
            const data = {}
            if (/^\d+$/.test(this.reportId)) {
                data.id = this.reportId;
                this.type = 'view';
            } else {
                data.code = this.reportId;
                this.type = 'share';
            }
            this.$store.dispatch("call", {
                url: 'report/detail',
                data,
                spinner: 600,
            }).then(({data}) => {
                this.detailData = data;
            }).catch(({msg}) => {
                $A.messageError(msg);
            });
        },
    }
}
</script>
