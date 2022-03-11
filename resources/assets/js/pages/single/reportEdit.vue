<template>
    <div class="electron-report">
        <PageTitle :title="$L('添加/编辑报告')"/>
        <ReportEdit :id="$A.runNum(this.$route.params.id)"/>
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
    name: "reportEdit"
}
</script>

<style scoped>

</style>
