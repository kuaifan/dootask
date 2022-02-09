<template>
    <div class="setting-item submit">
        <Tabs v-model="tabAction">
            <TabPane :label="$L('任务优先级')" name="priority">
                <PreferenceTaskPriority/>
            </TabPane>
            <TabPane :label="$L('新建项目模板')" name="columnTemplate">
                <PreferenceColumnTemplate/>
            </TabPane>
        </Tabs>
    </div>
</template>

<script>
import PreferenceTaskPriority from "./taskPriority";
import PreferenceColumnTemplate from "./columnTemplate";
export default {
    components: {PreferenceColumnTemplate, PreferenceTaskPriority},
    data() {
        return {
            tabAction: 'priority',
        }
    },
}
</script>
