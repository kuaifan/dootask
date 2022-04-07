<template>
    <div class="report">
        <Tabs v-model="reportTabs">
            <TabPane :label="$L('我的汇报')" name="my">
                <ReportMy ref="report" v-if="reportTabs === 'my'" @on-view="onView" @on-edit="onEditReport"/>
            </TabPane>
            <TabPane :label="tabRebder(reportUnreadNumber)" name="receive">
                <ReportReceive v-if="reportTabs === 'receive'" @on-view="onView"/>
            </TabPane>
        </Tabs>
        <DrawerOverlay
            v-model="showDetailDrawer"
            placement="right"
            :size="950"
            transfer>
            <ReportDetail v-if="showDetailDrawer" :data="detailData"/>
        </DrawerOverlay>
        <DrawerOverlay
            v-model="showEditDrawer"
            placement="right"
            :size="1000"
            transfer>
            <ReportEdit v-if="showEditDrawer" :id="reportId" @saveSuccess="saveSuccess"/>
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
            showEditDrawer: false,
            detailData: {},
            reportId: 0
        }
    },

    mounted() {
        this.reportTabs = this.reportType;
        //
        if (this.$isMainElectron) {
            this.$Electron.registerMsgListener('reportSaveSuccess', this.saveSuccess)
        }
    },

    methods: {
        tabRebder(num) {
            return h => {
                if (num > 0) {
                    return h('div', [
                        h('span', {class: 'navbar-item-content'}, this.$L('收到的汇报')),
                        h('Badge', {
                            class: 'manage-box-report',
                            props: {
                                count: num
                            }
                        }),
                    ])
                } else {
                    return h('div', [
                        h('span', {class: 'navbar-item-content'}, this.$L('收到的汇报')),
                    ])
                }
            }
        },

        onView(row) {
            this.detailData = row;
            this.$emit("on-read");
            if (this.$Electron) {
                let config = {
                    title: row.title,
                    titleFixed: true,
                    parent: null,
                    width: Math.min(window.screen.availWidth, 1440),
                    height: Math.min(window.screen.availHeight, 900),
                }
                this.$Electron.sendMessage('windowRouter', {
                    name: 'report-' + row.id,
                    path: "/single/report/detail/" + row.id,
                    force: false,
                    config
                });
            }else{
                this.showDetailDrawer = true;
            }
        },

        onEditReport(id) {
            if (this.$Electron) {
                let config = {
                    title: this.$L(id > 0 ? '修改报告' : '新增报告'),
                    parent: null,
                    width: Math.min(window.screen.availWidth, 1440),
                    height: Math.min(window.screen.availHeight, 900),
                }
                this.$Electron.sendMessage('windowRouter', {
                    name: 'report-' + id,
                    path: "/single/report/edit/" + id,
                    force: false,
                    config
                });
            } else {
                this.reportId = id;
                this.showEditDrawer = true;
            }
        },

        saveSuccess() {
            this.reportId = 0;
            this.reportTabs = "my";
            this.showEditDrawer = false;
            this.$refs.report && this.$refs.report.getLists();
        }
    }
}
</script>
