<template>
    <div class="report">
        <Tabs v-model="reportTabs">
            <TabPane :label="$L('填写汇报')" name="edit">
                <ReportEdit :id="reportId" @saveSuccess="saveSuccess"></ReportEdit>
            </TabPane>
            <TabPane :label="$L('我的汇报')" name="my">
                <ReportMy v-if="reportTabs === 'my'" @detail="showDetail" @edit="editReport"></ReportMy>
            </TabPane>
            <TabPane :label="tabRebder(reportUnreadNumber)" name="receive">
                <ReportReceive v-if="reportTabs === 'receive'" @detail="showDetail"></ReportReceive>
            </TabPane>
        </Tabs>
        <DrawerOverlay
            v-model="showDetailDrawer"
            placement="right"
            :size="950"
            transfer>
            <ReportDetail :data="detailData"/>
        </DrawerOverlay>
    </div>
</template>

<script>
import ReportEdit from "./ReportEdit"
import ReportMy from "./ReportMy"
import ReportReceive from "./ReportReceive"
import ReportDetail from "./ReportDetail"
import DrawerOverlay from "../../../components/DrawerOverlay";

export default {
    name: "Report",
    components: {
        DrawerOverlay,
        ReportEdit, ReportMy, ReportReceive, ReportDetail
    },
    props: {
        reportUnreadNumber: {
            type: Number,
            default: 0
        },
        reportType: {
            default: "my"
        }
    },
    data() {
        return {
            reportTabs: "my",
            showDetailDrawer: false,
            detailData: {},
            reportId: 0
        }
    },
    mounted() {
        this.reportTabs = this.reportType;
    },
    methods: {
        tabRebder(data) {
            return h => {
                if (data > 0) {
                    return h('div', [
                        h('span', {class: 'navbar-item-content'}, this.$L('收到的汇报')),
                        h('Badge', {class: 'manage-box-report'}, data),
                    ])
                } else {
                    return h('div', [
                        h('span', {class: 'navbar-item-content'},  this.$L('收到的汇报')),
                    ])
                }
            }
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
