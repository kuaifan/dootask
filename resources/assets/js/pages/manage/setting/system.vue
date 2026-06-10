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
            <TabPane :label="$L('文件设置')" name="fileSetting">
                <SystemFileSetting/>
            </TabPane>
        </Tabs>
    </div>
</template>

<script>
import SystemSetting from "./components/SystemSetting";
import SystemTaskPriority from "./components/SystemTaskPriority";
import SystemColumnTemplate from "./components/SystemColumnTemplate";
import SystemFileSetting from "./components/SystemFileSetting";

const VALID_TABS = ['setting', 'taskPriority', 'columnTemplate', 'fileSetting'];

export default {
    components: {SystemColumnTemplate, SystemTaskPriority, SystemSetting, SystemFileSetting},
    data() {
        return {
            tabAction: this.tabFromRoute(),
        }
    },
    watch: {
        // 支持深链直达指定分区：dootask://link/setting_system_xxx → query.tab
        '$route.query.tab'() {
            this.tabAction = this.tabFromRoute();
        },
    },
    methods: {
        tabFromRoute() {
            const tab = this.$route?.query?.tab;
            return VALID_TABS.includes(tab) ? tab : 'setting';
        },
    },
}
</script>
