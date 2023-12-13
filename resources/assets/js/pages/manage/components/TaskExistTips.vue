<template>
    <Modal
        v-model="show"
        :title="$L('计划时间冲突提示')"
        :styles="{
            width: '90%',
            maxWidth: '550px'
        }"
        class="task-exist-tips">
        <List :split="false" size="small">
            <ListItem v-for="(items, userid) in tipsTask" :key="userid">
                <div class="list-content">
                    <UserAvatar :userid="userid" :size="28" :show-icon="true" :show-name="true"/>
                    <div class="list-task" v-for="(item, key) in items" :key="key">
                        <div class="list-task-info">
                            <span>[{{ item.project_name }}] </span>
                            <span :title="item.name">{{ item.name }}</span>
                        </div>
                        <div class="list-task-date">{{ getCutTime(item) }}</div>
                    </div>
                </div>
            </ListItem>
        </List>
        <div slot="footer">
            <Button type="default" @click="show = false">{{ $L('取消') }}</Button>
            <Button type="primary" @click="onAdd">{{ $L('忽略并继续') }}</Button>
        </div>
    </Modal>
</template>

<script>
export default {
    name: "TaskExistTips",
    props: {
        value: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            isExist: false,
            show: false,
            tipsTask: [],
            loadIng: false,
        }
    },

    methods: {

        onAdd() {
            this.$emit('onAdd', {})
            this.show = false;
        },

        getCutTime(item) {
            let start_at = $A.Date(item.start_at, true);
            let end_at = $A.Date(item.end_at, true);
            let string = "";
            if ($A.formatDate('Y/m/d', start_at) == $A.formatDate('Y/m/d', end_at)) {
                string = $A.formatDate('Y/m/d H:i', start_at) + " ~ " + $A.formatDate('H:i', end_at)
            } else if ($A.formatDate('Y', start_at) == $A.formatDate('Y', end_at)) {
                string = $A.formatDate('Y/m/d H:i', start_at) + " ~ " + $A.formatDate('m/d H:i', end_at)
                string = string.replace(/( 00:00| 23:59)/g, "")
            } else {
                string = $A.formatDate('Y/m/d H:i', start_at) + " ~ " + $A.formatDate('Y/m/d H:i', end_at)
                string = string.replace(/( 00:00| 23:59)/g, "")
            }
            return string
        },

        isExistTask({ userids, timerange, taskid }) {
            this.isExist = false;
            return new Promise(async resolve => {
                this.$store.dispatch("call", {
                    url: 'project/task/easylists',
                    data: {
                        userid: userids,
                        timerange: timerange,
                        taskid: taskid
                    },
                    method: 'get',
                }).then(({ data }) => {
                    if (data.data.length > 0) {
                        this.show = true;
                        let taskObj = {}
                        userids.map(userid => {
                            data.data.map(h => {
                                if ((h.task_user || []).map(k => k.owner ? k.userid : 0).indexOf(userid) !== -1) {
                                    if (!taskObj[userid]) {
                                        taskObj[userid] = [];
                                    }
                                    taskObj[userid].push(h);
                                }
                            });
                        });
                        this.tipsTask = taskObj
                        this.isExist = true;
                    }
                    resolve(this.isExist)
                });
            });
        }
    }
}
</script>
