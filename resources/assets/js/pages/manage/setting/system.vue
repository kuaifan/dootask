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
        </Tabs>
    </div>
</template>

<script>
import SystemSetting from "./components/SystemSetting";
import SystemTaskPriority from "./components/SystemTaskPriority";
import SystemColumnTemplate from "./components/SystemColumnTemplate";

export default {
    components: {SystemColumnTemplate, SystemTaskPriority, SystemSetting},
    data() {
        return {
            tabAction: 'setting',
        }
    },
}
</script>
