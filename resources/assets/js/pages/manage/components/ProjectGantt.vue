<template>
    <div class="project-gstc-gantt">
        <GanttView :lists="lists" :menuWidth="windowWidth ? 180 : 260" :itemWidth="80" @on-change="updateTime" @on-click="clickItem"/>
        <Dropdown class="project-gstc-dropdown-filtr" :style="windowWidth?{left:'142px'}:{}" @on-click="tapProject">
            <Icon class="project-gstc-dropdown-icon" :class="{filtr:filtrProjectId>0}" type="md-funnel" />
            <DropdownMenu slot="list">
                <DropdownItem :name="0" :class="{'dropdown-active':filtrProjectId==0}">{{$L('全部')}}</DropdownItem>
                <DropdownItem v-for="(item, index) in projectLabel"
                              :key="index"
                              :name="item.id"
                              :class="{'dropdown-active':filtrProjectId==item.id}">
                    {{item.name}}
                    <span v-if="item.tasks">({{item.tasks.length}})</span>
                </DropdownItem>
            </DropdownMenu>
        </Dropdown>
        <div class="project-gstc-close" @click="$emit('on-close')"><Icon type="md-close" /></div>
        <div class="project-gstc-edit" :class="{info:editShowInfo, visible:editData&&editData.length > 0}">
            <div class="project-gstc-edit-info">
                <Table size="small" max-height="600" :columns="editColumns" :data="editData"></Table>
                <div class="project-gstc-edit-btns">
                    <Button :loading="editLoad > 0" size="small" type="text" @click="editSubmit(false)">{{$L('取消')}}</Button>
                    <Button :loading="editLoad > 0" size="small" type="primary" @click="editSubmit(true)">{{$L('保存')}}</Button>
                    <Icon type="md-arrow-dropright" class="zoom" @click="editShowInfo=false"/>
                </div>
            </div>
            <div class="project-gstc-edit-small">
                <div class="project-gstc-edit-text" @click="editShowInfo=true">{{$L('未保存计划时间')}}: <span v-if="editData">{{editData.length}}</span></div>
                <Button :loading="editLoad > 0" size="small" type="text" @click="editSubmit(false)">{{$L('取消')}}</Button>
                <Button :loading="editLoad > 0" size="small" type="primary" @click="editSubmit(true)">{{$L('保存')}}</Button>
            </div>
        </div>
    </div>
</template>

<script>
import GanttView from "./GanttView";
import {mapState} from "vuex";

/**
 * 甘特图
 */
export default {
    name: 'ProjectGantt',
    components: {GanttView },
    props: {
        projectLabel: {
            default: []
        },
        lineTaskData: {
            default: []
        },
        levelList: {},
    },

    data () {
        return {
            loadFinish: false,

            lists: [],

            editColumns: [],
            editData: [],
            editShowInfo: false,
            editLoad: 0,

            filtrProjectId: 0,
        }
    },

    mounted() {
        this.editColumns = [
            {
                title: this.$L('任务名称'),
                key: 'label',
                minWidth: 150,
                ellipsis: true,
            }, {
                title: this.$L('原计划时间'),
                minWidth: 135,
                align: 'center',
                render: (h, params) => {
                    if (params.row.notime === true) {
                        return h('span', '-');
                    }
                    return h('div', {
                        style: {},
                    }, [
                        h('div', $A.formatDate('Y-m-d H:i', Math.round(params.row.backTime.start / 1000))),
                        h('div', $A.formatDate('Y-m-d H:i', Math.round(params.row.backTime.end / 1000)))
                    ]);
                }
            }, {
                title: this.$L('新计划时间'),
                minWidth: 135,
                align: 'center',
                render: (h, params) => {
                    return h('div', {
                        style: {},
                    }, [
                        h('div', $A.formatDate('Y-m-d H:i', Math.round(params.row.newTime.start / 1000))),
                        h('div', $A.formatDate('Y-m-d H:i', Math.round(params.row.newTime.end / 1000)))
                    ]);
                }
            }
        ];
        //
        this.initData();
        this.loadFinish = true;
    },
    computed: {
        ...mapState(['userId', 'windowWidth', 'taskPriority']),
    },
    watch:{
        projectLabel: {
            handler() {
                this.initData();
            },
            deep: true,
        }
    },

    methods: {
        verifyLists(item){
            if (this.filtrProjectId > 0) {
                if (item.id != this.filtrProjectId) {
                    return;
                }
            }
            item.tasks && item.tasks.forEach((taskData) => {
                let notime = !taskData.start_at || !taskData.end_at;
                let times = this.getTimeObj(taskData);
                let start = times.start;
                let end = times.end;
                //
                let color = '#058ce4';
                if (taskData.complete_at) {
                    color = '#c1c1c1';
                } else {
                    // 等级颜色
                    this.levelList.some(level => {
                        if (level.level === taskData.level) {
                            color = level.color;
                            return true;
                        }
                    });
                }
                //
                let tempTime = { start, end };
                let findData = this.editData.find((t) => { return t.id == taskData.id });
                if (findData) {
                    findData.backTime = $A.cloneData(tempTime)
                    tempTime = $A.cloneData(findData.newTime);
                }
                //
                this.lists.push({
                    id: taskData.id,
                    label: taskData.name,
                    complete: taskData.complete_at,
                    overdue: taskData.overdue,
                    time: tempTime,
                    notime: notime,
                    style: { background: color },
                });
            });
        },
        initData() {
            this.lists = [];
            this.projectLabel && this.projectLabel.forEach((item) => {
                this.verifyLists(item);
            });
            if (this.lists&&this.lists.length == 0) {
                this.lineTaskData && this.lineTaskData.forEach((item) => {
                    this.verifyLists(item);
                });
            }
            //
            if (this.lists&&this.lists.length == 0 && this.filtrProjectId == 0) {
                $A.modalWarning({
                    content: '任务列表为空，请先添加任务。',
                    onOk: () => {
                        this.$emit('on-close');
                    },
                });
            }
        },

        updateTime(item) {
            let original = this.getRawTime(item.id);
            if (Math.abs(original.end - item.time.end) > 1000 || Math.abs(original.start - item.time.start) > 1000) {
                //修改时间（变化超过1秒钟)
                let backTime = $A.cloneData(original);
                let newTime = $A.cloneData(item.time);
                let findData = this.editData.find(({id}) => id == item.id);
                if (findData) {
                    findData.newTime = newTime;
                } else {
                    this.editData.push({
                        id: item.id,
                        label: item.label,
                        notime: item.notime,
                        backTime,
                        newTime,
                    })
                }
            }
        },

        clickItem(item) {
            this.$store.dispatch("openTask", item);
        },

        editSubmit(save) {
            this.editData&&this.editData.forEach((item) => {
                if (save) {
                    this.editLoad++;
                    let timeStart = $A.formatDate('Y-m-d H:i', Math.round(item.newTime.start / 1000));
                    let timeEnd = $A.formatDate('Y-m-d H:i', Math.round(item.newTime.end / 1000));
                    let dataJson = {
                        task_id: item.id,
                        times:[timeStart,timeEnd],
                    };
                    this.$store.dispatch("taskUpdate", dataJson).then(({msg}) => {
                        $A.messageSuccess(msg);
                        this.editLoad--;
                        if (typeof successCallback === "function") successCallback();
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    })
                } else {
                    this.lists.some((task) => {
                        if (task.id == item.id) {
                            this.$set(task, 'time', item.backTime);
                            return true;
                        }
                    })
                }
            });
            this.editData = [];
        },

        getRawTime(taskId) {
            let times = null;
            this.lists.some((taskData) => {
                if (taskData.id == taskId) {
                    times = this.getTimeObj(taskData);
                }
            });
            return times;
        },

        getTimeObj(taskData) {
            let start = $A.Time(taskData.start_at) || $A.Time(taskData.start_at);
            let end = $A.Time(taskData.start_at) || ($A.Time(taskData.created_at) + 86400);
            if (end == start) {
                end = Math.round(new Date($A.formatDate('Y-m-d 23:59:59', end)).getTime()/1000);
            }
            end = Math.max(end, start + 60);
            start*= 1000;
            end*= 1000;
            return {start, end};
        },

        tapProject(e) {
            this.filtrProjectId = $A.runNum(e);
            this.initData();
        },
    }
}
</script>
