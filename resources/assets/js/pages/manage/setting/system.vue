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
            <TabPane :label="$L('邮件设置')" name="emailSetting">
                <SystemEmailSetting/>
            </TabPane>
        </Tabs>
    </div>
</template>

<script>
import SystemSetting from "./components/SystemSetting";
import SystemTaskPriority from "./components/SystemTaskPriority";
import SystemColumnTemplate from "./components/SystemColumnTemplate";
import SystemEmailSetting from "./components/SystemEmailSetting";

export default {
    components: {SystemColumnTemplate, SystemTaskPriority, SystemSetting, SystemEmailSetting},
    data() {
        return {
            tabAction: 'setting',
        }
    },
}
</script>
