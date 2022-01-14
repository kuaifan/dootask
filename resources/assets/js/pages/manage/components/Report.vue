<template>
    <div class="report">
        <Tabs v-model="reportTabs">
            <TabPane label="填写" name="edit" icon="md-create">
                <ReportEdit :id="reportId" @saveSuccess="saveSuccess">填写</ReportEdit>
            </TabPane>
            <TabPane label="我的汇报" name="my" icon="ios-paper-plane-outline">
                <ReportMy v-if="reportTabs === 'my'" @detail="showDetail" @edit="editReport">我的汇报</ReportMy>
            </TabPane>
            <TabPane label="收到的汇报" name="receive" icon="ios-paper-outline">
                <ReportReceive v-if="reportTabs === 'receive'" @detail="showDetail">收到的汇报</ReportReceive>
            </TabPane>
        </Tabs>
        <Drawer v-model="showDetailDrawer"  width="900px"  :closable="false">
            <ReportDetail :data="detailData" @closeDrawer="closeDrawer"/>
        </Drawer>
    </div>
</template>

<script>
import ReportEdit from "./ReportEdit"
import ReportMy from "./ReportMy"
import ReportReceive from "./ReportReceive"
import ReportDetail from "./ReportDetail"

export default {
    name: "Report",
    components: {
        ReportEdit, ReportMy, ReportReceive,ReportDetail
    },
    data() {
        return {
            reportTabs: "my",
            showDetailDrawer: false,
            detailData: {},
            reportId: 0,
        }
    },
    methods: {
        closeDrawer(){
            this.showDetailDrawer = false
        },
        showDetail(row) {
            this.showDetailDrawer = true;
            this.detailData = row;
            //1.5秒后执行
            setTimeout(() => {
                this.$emit("read");
            }, 1500);
        },
        editReport(id) {
            this.reportId = id;
            this.reportTabs = "edit";
        },
        saveSuccess() {
            this.reportId = 0;
            this.reportTabs = "my";
        }
    }
}
</script>

<style scoped>

</style>
