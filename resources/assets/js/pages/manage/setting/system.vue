<template>
    <div class="setting-item submit">
        <Tabs v-model="tabAction">
            <TabPane :label="$L('系统设置')" name="setting">
                <SystemSetting/>
            </TabPane>
            <TabPane :label="$L('任务优先级')" name="taskPriority">
                <SystemTaskPriority/>
            </TabPane>
            <TabPane :label="$L('项目模板')" name="columnTemplate">
                <SystemColumnTemplate/>
            </TabPane>
            <TabPane :label="$L('会议功能')" name="meeting">
                <SystemMeeting/>
            </TabPane>
            <TabPane :label="$L('签到功能')" name="checkin">
                <SystemCheckin/>
            </TabPane>
            <TabPane :label="$L('邮件设置')" name="emailSetting">
                <SystemEmailSetting/>
            </TabPane>
            <TabPane :label="$L('认证设置')" name="thirdAccess">
                <SystemThirdAccess/>
            </TabPane>
            <TabPane v-if="$A.isDooServer()" :label="$L('APP推送')" name="appPush">
                <SystemAppPush/>
            </TabPane>
        </Tabs>
    </div>
</template>

<script>
import SystemSetting from "./components/SystemSetting";
import SystemTaskPriority from "./components/SystemTaskPriority";
import SystemColumnTemplate from "./components/SystemColumnTemplate";
import SystemEmailSetting from "./components/SystemEmailSetting";
import SystemAppPush from "./components/SystemAppPush";
import SystemMeeting from "./components/SystemMeeting";
import SystemCheckin from "./components/SystemCheckin";
import SystemThirdAccess from "./components/SystemThirdAccess";

export default {
    components: {
        SystemThirdAccess,
        SystemCheckin,
        SystemMeeting,
        SystemAppPush, SystemColumnTemplate, SystemTaskPriority, SystemSetting, SystemEmailSetting},
    data() {
        return {
            tabAction: 'setting',
        }
    },
}
</script>
