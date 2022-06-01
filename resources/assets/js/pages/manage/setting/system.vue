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
            <TabPane :label="$L('邮件设置')" name="emailSetting">
                <SystemEmailSetting/>
            </TabPane>
            <TabPane v-if="appPush" :label="$L('APP推送')" name="appPush">
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

export default {
    components: {
        SystemMeeting,
        SystemAppPush, SystemColumnTemplate, SystemTaskPriority, SystemSetting, SystemEmailSetting},
    data() {
        return {
            tabAction: 'setting',
            appPush: false,
        }
    },

    mounted() {
        if ([
            '127.0.0.1:2222',
            't.hitosea.com',
            'dootask.com',
            'www.dootask.com'
        ].includes(this.getDomain($A.apiUrl('../')))) {
            this.appPush = true;
        }
    },

    methods: {
        getDomain(weburl) {
            let urlReg = /http(s)?:\/\/([^\/]+)/i;
            let domain = (weburl + "").match(urlReg);
            return ((domain != null && domain.length > 0) ? domain[2] : "");
        }
    }
}
</script>
