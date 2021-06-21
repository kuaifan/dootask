<template>
    <div class="page-calendar">
        <PageTitle>{{$L('日历')}}</PageTitle>
        <div class="calendar-head">
            <div class="calendar-titbox">
                <div class="calendar-title">
                    <h1>{{rangeText}}</h1>
                </div>
                <ButtonGroup class="calendar-arrow" size="small">
                    <Button @click="preMonth"><Icon type="ios-arrow-back"></Icon></Button>
                    <Button @click="afterMonth"><Icon type="ios-arrow-forward"></Icon></Button>
                </ButtonGroup>
                <ButtonGroup class="calendar-arrow" size="small">
                    <Button @click="curMonth">{{$L('今天')}}</Button>
                </ButtonGroup>
                <ButtonGroup class="calendar-view">
                    <Button @click="setView('day')" :type="calendarView == 'day' ? 'primary' : 'default'">{{$L('日')}}</Button>
                    <Button @click="setView('week')" :type="calendarView == 'week' ? 'primary' : 'default'">{{$L('周')}}</Button>
                    <Button @click="setView('month')" :type="calendarView == 'month' ? 'primary' : 'default'">{{$L('月')}}</Button>
                </ButtonGroup>
            </div>
        </div>
        <div class="calendar-box">
            <Calendar
                ref="cal"
                :view="calendarView"
                :week="calendarWeek"
                :month="calendarMonth"
                :theme="calendarTheme"
                :template="calendarTemplate"
                :calendars="calendarList"
                :schedules="list"
                @beforeCreateSchedule="onBeforeCreateSchedule"
                @beforeClickSchedule="onBeforeClickSchedule"
                @beforeUpdateSchedule="onBeforeUpdateSchedule"
                disable-click/>
        </div>
    </div>
</template>

<script>
import 'tui-date-picker/dist/tui-date-picker.css';
import 'tui-time-picker/dist/tui-time-picker.css';
import 'tui-calendar-hi/dist/tui-calendar-hi.css'

import {mapState} from "vuex";
import Calendar from "./components/Calendar";
import moment from "moment";

export default {
    components: {Calendar},
    data() {
        return {
            lists: [],

            rangeText: 'Calendar',
            rangeTime: [],

            calendarView: 'month',
            calendarWeek: {},
            calendarMonth: {},
            calendarTheme: {},
            calendarTemplate: {},
            calendarList: [],

            loadIng: 0,
        }
    },

    mounted() {
        this.setRenderRange();
    },

    activated() {
        this.$refs.cal.resetRender();
    },

    computed: {
        ...mapState(['userId', 'projects', 'tasks']),

        list() {
            let datas = $A.cloneJSON(this.tasks);
            datas = datas.filter((data) => {
                if (data.parent_id > 0) {
                    return false;
                }
                if (data.complete_at) {
                    return false;
                }
                if (!data.end_at) {
                    return false;
                }
                return data.owner;
            })
            return datas.map(data => {
                let task = {
                    id: data.id,
                    calendarId: String(data.project_id),
                    title: data.name,
                    body: data.desc,
                    category: 'allday',
                    start: new Date(data.start_at).toISOString(),
                    end: new Date(data.end_at).toISOString(),
                    color: "#515a6e",
                    bgColor: data.color || '#E3EAFD',
                    borderColor: data.p_color,
                    complete_at: data.complete_at,
                    priority: '',
                    preventClick: true,
                    isChecked: false,
                };
                if (data.p_name) {
                    task.priority = '<span class="priority" style="background-color:' + data.p_color + '">' + data.p_name + '</span>';
                }
                if (data.overdue) {
                    task.title = '[' + $A.L('超期') + '] ' + task.title
                    task.color = "#f56c6c"
                    task.bgColor = "#fef0f0"
                    task.priority+= '<span class="overdue">' + $A.L('超期未完成') + '</span>';
                }
                if (!task.borderColor) {
                    task.borderColor = task.bgColor;
                }
                return task;
            });
        }
    },

    watch: {
        rangeTime(time) {
            this.getTask(time);
        },

        projects: {
            handler(data) {
                const list = data.map((project) => {
                    return {
                        id: String(project.id),
                        name: project.name,
                    }
                });
                if (JSON.stringify(list) != JSON.stringify(this.calendarList)) {
                    this.calendarList = list;
                }
            },
            immediate: true,
        },
    },

    methods: {
        initLanguage() {
            let daynames = [
                this.$L('周日'),
                this.$L('周一'),
                this.$L('周二'),
                this.$L('周三'),
                this.$L('周四'),
                this.$L('周五'),
                this.$L('周六')
            ];
            this.calendarWeek = {daynames};
            this.calendarMonth = {daynames};
            this.calendarTheme = {
                'common.border': '1px solid #f4f5f5',
                'month.dayname.fontSize': '14px',
                'month.dayname.borderLeft': '1px solid #f4f5f5',
                'month.dayname.height': '50px',
            }
            this.calendarTemplate = {
                titlePlaceholder: () => {
                    return this.$L("任务描述")
                },
                popupSave: () => {
                    return this.$L("保存");
                },
                popupEdit: () => {
                    return this.$L("详情");
                },
                popupDelete: () => {
                    return this.$L("删除");
                }
            }
        },

        getTask(time) {
            if (this.loadIng > 0) {
                setTimeout(() => {
                    this.getTask(time)
                }, 100)
                return;
            }
            this.loadIng++;
            this.$store.dispatch("getTasks", {
                time: time,
                complete: "no"
            }).then(() => {
                this.loadIng--;
            }).catch(() => {
                this.loadIng--;
            })
        },

        preMonth() {
            this.$refs.cal.getInstance().prev();
            this.setRenderRange()
        },

        curMonth() {
            this.$refs.cal.getInstance().today();
            this.setRenderRange()
        },

        afterMonth() {
            this.$refs.cal.getInstance().next();
            this.setRenderRange()
        },

        setView(view) {
            this.calendarView = view;
            this.setRenderRange()
        },

        setRenderRange() {
            this.$nextTick(() => {
                const cal = this.$refs.cal.getInstance();
                let options = cal.getOptions();
                let viewName = cal.getViewName();
                let html = [];
                if (viewName === 'day') {
                    html.push(this.currentCalendarDate('YYYY.MM.DD'));
                } else if (viewName === 'month' &&
                    (!options.month.visibleWeeksCount || options.month.visibleWeeksCount > 4)) {
                    html.push(this.currentCalendarDate('YYYY.MM'));
                } else {
                    html.push(moment(cal.getDateRangeStart().getTime()).format('YYYY.MM.DD'));
                    html.push(' ~ ');
                    html.push(moment(cal.getDateRangeEnd().getTime()).format(' MM.DD'));
                }
                this.rangeText = html.join('');
                this.rangeTime = [moment(cal.getDateRangeStart().getTime()).format('YYYY-MM-DD'), moment(cal.getDateRangeEnd().getTime()).format('YYYY-MM-DD')];
            })
        },

        currentCalendarDate(format) {
            const cal = this.$refs.cal.getInstance();
            let currentDate = moment([cal.getDate().getFullYear(), cal.getDate().getMonth(), cal.getDate().getDate()]);
            return currentDate.format(format);
        },

        onBeforeCreateSchedule(res) {
            this.$store.dispatch("taskAdd", {
                project_id: res.calendarId,
                times: [res.start.toDate(), res.end.toDate()],
                name: res.title
            }).then(({msg}) => {
                $A.messageSuccess(msg);
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        },

        onBeforeClickSchedule({type, schedule}) {
            let data = this.tasks.find(({id}) => id === schedule.id);
            if (!data) {
                return;
            }
            switch (type) {
                case "check":
                    this.$set(data, 'complete_at', $A.formatDate("Y-m-d H:i:s"))
                    this.$store.dispatch("taskUpdate", {
                        task_id: data.id,
                        complete_at: $A.formatDate("Y-m-d H:i:s"),
                    }).then(({msg}) => {
                        $A.messageSuccess(msg);
                    }).catch(({msg}) => {
                        this.$set(data, 'complete_at', null)
                        $A.modalError(msg);
                    });
                    break;

                case "edit":
                    this.$store.dispatch("openTask", data.id)
                    break;

                case "delete":
                    $A.modalConfirm({
                        title: '删除任务',
                        content: '你确定要删除任务【' + data.name + '】吗？',
                        loading: true,
                        onOk: () => {
                            this.$store.dispatch("removeTask", data.id).then(({msg}) => {
                                $A.messageSuccess(msg);
                                this.$Modal.remove();
                            }).catch(({msg}) => {
                                $A.modalError(msg, 301);
                                this.$Modal.remove();
                                this.setRenderRange();
                            });
                        }
                    });
                    break;
            }
        },

        onBeforeUpdateSchedule(res) {
            const {changes, schedule} = res;
            let data = this.tasks.find(({id}) => id === schedule.id);
            if (!data) {
                return;
            }
            if (changes.start || changes.end) {
                const cal = this.$refs.cal.getInstance();
                cal.updateSchedule(schedule.id, schedule.calendarId, changes);
                //
                this.$store.dispatch("taskUpdate", {
                    task_id: data.id,
                    times: [
                        (changes.start || schedule.start).toDate(),
                        (changes.end || schedule.end).toDate(),
                    ],
                }).then(({msg}) => {
                    $A.messageSuccess(msg);
                }).catch(({msg}) => {
                    $A.modalError(msg);
                });
            }
        }
    }
}
</script>
