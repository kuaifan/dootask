<template>
    <div class="project-gstc-gantt">
        <GanttView
            :lists="lists"
            :menuWidth="menuWidth"
            :itemWidth="80"
            @on-change="onChange"
            @on-click="onClick"/>
        <Dropdown class="project-gstc-dropdown-filtr" :style="dropStyle" trigger="click" @on-click="onSwitchColumn">
            <Icon class="project-gstc-dropdown-icon" :class="{filtr:filtrProjectId > 0}" type="md-funnel" />
            <DropdownMenu slot="list">
                <DropdownItem :name="0" :class="{'dropdown-active':filtrProjectId == 0}">{{ $L('全部') }}</DropdownItem>
                <DropdownItem
                    v-for="(item, index) in projectColumn"
                    :key="index"
                    :name="item.id"
                    :class="{'dropdown-active':filtrProjectId == item.id}">
                    {{ item.name }}
                    <span v-if="item.tasks">({{ filtrLength(item.tasks) }})</span>
                </DropdownItem>
            </DropdownMenu>
        </Dropdown>
        <div class="project-gstc-edit" :class="{info:editShowInfo, visible:editData && editData.length > 0}">
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
import {mapGetters, mapState} from "vuex";
import GanttView from "../../../components/GanttView";

export default {
    name: 'ProjectGantt',
    components: {GanttView},
    props: {
        projectColumn: {
            default: []
        },
        flowInfo: {
            default: {}
        },
    },

    data() {
        return {
            lists: [],

            filtrProjectId: 0,

            editColumns: [
                {
                    title: this.$L('任务名称'),
                    key: 'label',
                    minWidth: 150,
                    ellipsis: true,
                }, {
                    title: this.$L('原计划时间'),
                    minWidth: 135,
                    align: 'center',
                    render: (h, {row}) => {
                        if (row.notime === true) {
                            return h('span', '-');
                        }
                        return h('div', {
                            style: {},
                        }, [
                            h('div', $A.formatDate('Y-m-d H:i', Math.round(row.baktime.start / 1000))),
                            h('div', $A.formatDate('Y-m-d H:i', Math.round(row.baktime.end / 1000)))
                        ]);
                    }
                }, {
                    title: this.$L('新计划时间'),
                    minWidth: 135,
                    align: 'center',
                    render: (h, {row}) => {
                        return h('div', {
                            style: {},
                        }, [
                            h('div', $A.formatDate('Y-m-d H:i', Math.round(row.newTime.start / 1000))),
                            h('div', $A.formatDate('Y-m-d H:i', Math.round(row.newTime.end / 1000)))
                        ]);
                    }
                }
            ],
            editData: [],
            editLoad: 0,
            editShowInfo: false,
        }
    },

    mounted() {
        this.initData();
    },

    computed: {
        ...mapState(['userId', 'windowWidth', 'taskPriority']),

        ...mapGetters(['projectParameter']),

        menuWidth() {
            return this.windowWidth < 1440 ? 180 : 260;
        },

        dropStyle() {
            return this.windowWidth < 1440 ? {left: '142px'} : {};
        },

        completedTask() {
            return this.projectParameter('completedTask');
        }
    },

    watch: {
        projectColumn: {
            handler() {
                this.initData();
            },
            deep: true,
        },
        flowInfo: {
            handler() {
                this.initData();
            },
            deep: true,
        },
        completedTask() {
            this.initData();
        }
    },

    methods: {
        initData() {
            this.lists = [];
            this.projectColumn && this.projectColumn.some(this.checkAdd);
        },

        filtrLength(list) {
            return list.filter(taskData => {
                if (taskData.complete_at && !this.completedTask) {
                    return false;
                }
                if (this.flowInfo.value > 0 && taskData.flow_item_id !== this.flowInfo.value) {
                    return false;
                }
                return true
            }).length
        },

        checkAdd(item) {
            if (this.filtrProjectId > 0) {
                if (item.id != this.filtrProjectId) {
                    return;
                }
            }
            item.tasks && item.tasks.some(taskData => {
                let notime = !taskData.start_at || !taskData.end_at;
                let times = this.getTimeObj(taskData);
                let start = times.start;
                let end = times.end;
                //
                if (taskData.complete_at && !this.completedTask) {
                    return false;
                }
                if (this.flowInfo.value > 0 && taskData.flow_item_id !== this.flowInfo.value) {
                    return false;
                }
                // 等级颜色
                let color = '#058ce4';
                this.taskPriority.some(level => {
                    if (level.priority === taskData.p_level) {
                        color = level.color;
                        return true;
                    }
                });
                //
                let tempTime = {start, end};
                let bakTime = $A.cloneJSON(tempTime)
                let findData = this.editData.find(({id}) => id == taskData.id);
                if (findData) {
                    tempTime = $A.cloneJSON(findData.newTime);
                }
                //
                this.lists.push({
                    id: taskData.id,
                    label: taskData.name,
                    complete: taskData.complete_at,
                    overdue: taskData.overdue,
                    time: tempTime,
                    notime: notime,
                    baktime: bakTime,
                    style: {background: color},
                });
            });
        },

        onChange(item) {
            const {time, baktime} = item;
            if (Math.abs(baktime.end - time.end) > 1000 || Math.abs(baktime.start - time.start) > 1000) {
                //修改时间（变化超过1秒钟)
                let findData = this.editData.find(({id}) => id == item.id);
                if (findData) {
                    findData.newTime = time;
                } else {
                    this.editData.push({
                        id: item.id,
                        label: item.label,
                        notime: item.notime,
                        baktime: item.baktime,
                        newTime: time,
                    })
                }
            }
        },

        onClick(item) {
            this.$store.dispatch("openTask", item);
        },

        editSubmit(save) {
            this.editData && this.editData.forEach(item => {
                let task = this.lists.find(({id}) => id == item.id)
                if (save) {
                    this.editLoad++;
                    let timeStart = $A.formatDate('Y-m-d H:i', Math.round(item.newTime.start / 1000));
                    let timeEnd = $A.formatDate('Y-m-d H:i', Math.round(item.newTime.end / 1000));
                    let dataJson = {
                        task_id: item.id,
                        times: [timeStart, timeEnd],
                    };
                    this.$store.dispatch("taskUpdate", dataJson).then(({msg}) => {
                        this.editLoad--;
                        this.editLoad === 0 && $A.messageSuccess(msg);
                        task && this.$set(task, 'baktime', $A.cloneJSON(task.time));
                    }).catch(({msg}) => {
                        this.editLoad--;
                        this.editLoad === 0 && $A.modalError(msg);
                        task && this.$set(task, 'time', $A.cloneJSON(task.baktime));
                    })
                } else {
                    task && this.$set(task, 'time', $A.cloneJSON(task.baktime));
                }
            });
            this.editData = [];
        },

        getRawTime(taskId) {
            let task = this.lists.find(({id}) => id == taskId)
            return task ? this.getTimeObj(task) : null;
        },

        getTimeObj(taskData) {
            let start = $A.Time(taskData.start_at) || $A.Time(taskData.created_at);
            let end = $A.Time(taskData.end_at) || ($A.Time(taskData.created_at) + 86400);
            if (end == start) {
                end = Math.round(new Date($A.formatDate('Y-m-d 23:59:59', end)).getTime() / 1000);
            }
            end = Math.max(end, start + 60);
            start *= 1000;
            end *= 1000;
            return {start, end};
        },

        onSwitchColumn(e) {
            this.filtrProjectId = $A.runNum(e);
            this.initData();
        },
    }
}
</script>
