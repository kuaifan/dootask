<template>
    <div class="electron-report">
        <PageTitle :title="$L('报告详情')"/>
        <ReportDetail :data="detailData"/>
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
            detailData: {},
        };
    },
    computed: {
        reportDetailId() {
            const {reportDetailId} = this.$route.params;
            return parseInt(/^\d+$/.test(reportDetailId) ? reportDetailId : 0);
        },
    },
    watch: {
        reportDetailId: {
            handler() {
                this.getDetail();
            },
            immediate: true
        },
    },
    methods: {
        getDetail() {
            if (this.reportDetailId <= 0) {
                return;
            }
            this.$store.dispatch("call", {
                url: 'report/detail',
                data: {
                    id: this.reportDetailId,
                },
            }).then(({data}) => {
                this.detailData = data;
            }).catch(({msg}) => {
                $A.messageError(msg);
            });
        },
    }
}
</script>
