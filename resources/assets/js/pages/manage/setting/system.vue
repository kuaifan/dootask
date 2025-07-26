<template>
    <div class="setting-item submit">
        <Tabs v-model="tabAction">
            <TabPane :label="$L('系统设置')" name="setting">
                <SystemSetting @on-switch-tab="tabAction = $event"/>
            </TabPane>
            <TabPane :label="$L('任务优先级')" name="taskPriority">
                <SystemTaskPriority/>
            </TabPane>
            <TabPane :label="$L('项目模板')" name="columnTemplate">
                <SystemColumnTemplate/>
            </TabPane>
            <TabPane :label="$L('文件设置')" name="fileSetting">
                <SystemFileSetting/>
            </TabPane>
            <TabPane :label="$L('AI 助手')" name="aiAssistant">
                <SystemAiAssistant/>
            </TabPane>
        </Tabs>
    </div>
</template>

<script>
import SystemSetting from "./components/SystemSetting";
import SystemTaskPriority from "./components/SystemTaskPriority";
import SystemColumnTemplate from "./components/SystemColumnTemplate";
import SystemFileSetting from "./components/SystemFileSetting";
import SystemAiAssistant from "./components/SystemAiAssistant";

export default {
    components: {SystemColumnTemplate, SystemTaskPriority, SystemSetting, SystemFileSetting, SystemAiAssistant},
    data() {
        return {
            tabAction: 'setting',
        }
    },
}
</script>
