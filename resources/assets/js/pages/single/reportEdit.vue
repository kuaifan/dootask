<template>
    <div class="electron-report">
        <PageTitle :title="title"/>
        <ReportEdit :id="id" @saveSuccess="saveSuccess"/>
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
        id() {
            return $A.runNum(this.detail.id || this.$route.params.id)
        },
        title() {
            return this.$L(this.id > 0 ? '修改报告' : '新增报告');
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
            }
        }
    }
}
</script>
