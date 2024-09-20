<template>
    <div class="project-gstc-gantt">
        <GanttView
            :lists="lists"
            :menuWidth="menuWidth"
            :itemWidth="80"
            @on-change="onChange"
            @on-click="onClick">
            <template #titleTool>
                <Dropdown class="project-gstc-dropdown-filtr" trigger="click" @on-click="onSwitchColumn">
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
            </template>
        </GanttView>
        <div class="project-gstc-edit" :class="{info:editShowInfo, visible:editData && editData.length > 0}">
            <div class="project-gstc-edit-info">
                <Table max-height="600" :columns="editColumns" :data="editData"></Table>
                <div class="project-gstc-edit-btns">
                    <Button :loading="editLoad > 0" type="text" @click="editSubmit(false)">{{$L('取消')}}</Button>
                    <Button :loading="editLoad > 0" type="primary" @click="editSubmit(true)">{{$L('保存')}}</Button>
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
                    minWidth: 100,
                    ellipsis: true,
                }, {
                    title: this.$L('原计划时间'),
                    width: 140,
                    align: 'center',
                    render: (h, {row}) => {
                        if (row.notime === true) {
                            return h('span', '-');
                        }
                        return h('div', {
                            style: {},
                        }, [
                            h('div', $A.dayjs(row.baktime.start).format("YYYY-MM-DD HH:mm")),
                            h('div', $A.dayjs(row.baktime.end).format("YYYY-MM-DD HH:mm"))
                        ]);
                    }
                }, {
                    title: this.$L('新计划时间'),
                    width: 140,
                    align: 'center',
                    render: (h, {row}) => {
                        return h('div', {
                            style: {},
                        }, [
                            h('div', $A.dayjs(row.newTime.start).format("YYYY-MM-DD HH:mm")),
                            h('div', $A.dayjs(row.newTime.end).format("YYYY-MM-DD HH:mm"))
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
        ...mapState(['taskPriority']),

        ...mapGetters(['projectData']),

        menuWidth() {
            return this.windowWidth < 1440 ? 180 : 260;
        },

        completedTask() {
            return this.projectData.cacheParameter.completedTask;
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

        flowTask(task) {
            if ($A.leftExists(this.flowInfo.value, "user:") && !task.task_user.find(({userid, owner}) => userid === this.flowInfo.userid && owner)) {
                return true;
            } else if (this.flowInfo.value > 0 && task.flow_item_id !== this.flowInfo.value) {
                return true;
            }
            return false;
        },

        filtrLength(list) {
            return list.filter(taskData => {
                if (taskData.complete_at && !this.completedTask) {
                    return false;
                }
                if (this.flowTask(taskData)) {
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
                if (this.flowTask(taskData)) {
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
                    let timeStart = $A.dayjs(item.newTime.start).format("YYYY-MM-DD HH:mm");
                    let timeEnd = $A.dayjs(item.newTime.end).format("YYYY-MM-DD HH:mm");
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

        getTimeObj(taskData) {
            let start = taskData.start_at ? $A.dayjs(taskData.start_at) : $A.dayjs(taskData.created_at).startOf('day');
            let end = taskData.end_at ? $A.dayjs(taskData.end_at) : start.clone();
            if (end.unix() == start.unix()) {
                end = end.endOf('day');
            }
            return {
                start: start.valueOf(),
                end: Math.max(end.valueOf(), start.valueOf() + 60000)
            };
        },

        onSwitchColumn(e) {
            this.filtrProjectId = $A.runNum(e);
            this.initData();
        },
    }
}
</script>
