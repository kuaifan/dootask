<template>
    <div class="electron-report">
        <PageTitle :title="title"/>
        <ReportEdit :id="reportEditId" @saveSuccess="saveSuccess"/>
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
import ReportEdit from "../manage/components/ReportEdit"

export default {
    components: {ReportEdit},
    data() {
        return {
            detail: {}
        }
    },
    computed: {
        reportEditId() {
            if (/^\d+$/.test(this.detail.id)) {
                return parseInt(this.detail.id)
            }
            const {reportEditId} = this.$route.params;
            return parseInt(/^\d+$/.test(reportEditId) ? reportEditId : 0);
        },
        title() {
            return this.$L(this.reportEditId > 0 ? '修改报告' : '新增报告');
        }
    },
    methods: {
        saveSuccess(data) {
            this.detail = data;
            if (this.$isSubElectron) {
                $A.Electron.sendMessage('sendForwardMain', {
                    channel: 'reportSaveSuccess',
                    data,
                });
                window.close();
            }
        }
    }
}
</script>
