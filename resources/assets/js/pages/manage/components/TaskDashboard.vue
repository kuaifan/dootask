<template>
    <ul class="dashboard-list overlay-y">
        <li
            v-for="(item, index) in list"
            :key="index"
            :class="{complete: item.complete_at}"
            :style="item.color ? {backgroundColor: item.color} : {}"
            @click="openTask(item)">
            <em
                v-if="item.p_name"
                class="priority-color"
                :style="{backgroundColor:item.p_color}"></em>
            <TaskMenu :ref="`taskMenu_${item.id}`" :task="item">
                <div slot="icon" class="drop-icon" @click.stop="">
                    <i class="taskfont" v-html="item.complete_at ? '&#xe627;' : '&#xe625;'"></i>
                </div>
            </TaskMenu>
            <div class="item-title">
                <!--工作流状态-->
                <span v-if="item.flow_item_name" :class="item.flow_item_status"
                      @click.stop="openMenu(item)">{{ item.flow_item_name }}</span>
                <!--是否子任务-->
                <span v-if="item.sub_top === true">{{ $L('子任务') }}</span>
                <!--有多少个子任务-->
                <span v-if="item.sub_my && item.sub_my.length > 0">+{{ item.sub_my.length }}</span>
                <!--任务描述-->
                {{ item.name }}
            </div>
            <div v-if="item.desc" class="item-icon">
                <i class="taskfont">&#xe71a;</i>
            </div>
            <div v-if="item.sub_num > 0" class="item-icon">
                <i class="taskfont">&#xe71f;</i>
                <em>{{ item.sub_complete }}/{{ item.sub_num }}</em>
            </div>
            <ETooltip v-if="item.end_at" :content="item.end_at" placement="right">
                <div :class="['item-icon', item.today ? 'today' : '', item.overdue ? 'overdue' : '']">
                    <i class="taskfont">&#xe71d;</i>
                    <em>{{ expiresFormat(item.end_at) }}</em>
                </div>
            </ETooltip>
        </li>
    </ul>
</template>

<script>
import TaskMenu from "../components/TaskMenu";
import {mapGetters, mapState} from "vuex";

export default {
    components: {TaskMenu},
    name: "TaskDashboard",
    props: {
        list: {
            default: {}
        },
        title: {
            default: null
        },
    },
    data() {
        return {
            nowTime: $A.Time(),
            nowInterval: null,
        }
    },
    mounted() {
        this.nowInterval = setInterval(() => {
            this.nowTime = $A.Time();
        }, 1000)
    },
    destroyed() {
        clearInterval(this.nowInterval)
    },

    activated() {
        this.$store.dispatch("getTaskForDashboard");
    },

    deactivated() {
        this.$store.dispatch("forgetTaskCompleteTemp", true);
    },
    methods: {
        openTask(task) {
            this.$store.dispatch("openTask", task)
        },

        openMenu(task) {
            const el = this.$refs[`taskMenu_${task.id}`];
            if (el) {
                el[0].handleClick()
            }
        },

        expiresFormat(date) {
            return $A.countDownFormat(date, this.nowTime)
        },
    }
}
</script>

<style scoped>

</style>
