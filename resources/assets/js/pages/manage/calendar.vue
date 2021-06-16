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
                    <Button @click="curMonth">{{$L('今天')}}</Button>
                    <Button @click="afterMonth"><Icon type="ios-arrow-forward"></Icon></Button>
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
                :theme="calendarTheme"
                :template="calendarTemplate"
                :calendars="calendarList"
                :schedules="scheduleLists"
                @beforeClickSchedule="onBeforeClickSchedule"
                @beforeUpdateSchedule="onBeforeUpdateSchedule"/>
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

const today = new Date();

const getDate = (type, start, value, operator) => {
    start = new Date(start);
    type = type.charAt(0).toUpperCase() + type.slice(1);

    if (operator === '+') {
        start[`set${type}`](start[`get${type}`]() + value);
    } else {
        start[`set${type}`](start[`get${type}`]() - value);
    }

    return start;
};

export default {
    components: {Calendar},
    data() {
        return {
            lists: [],

            rangeText: 'Calendar',
            rangeTime: [],

            calendarView: 'month',
            calendarTheme: {},
            calendarTemplate: {},
            calendarList: [],

            scheduleLoad: 0,
            scheduleList: [],
        }
    },

    mounted() {
        this.setRenderRange();
    },

    activated() {
        this.$refs.cal.resetRender();
    },

    computed: {
        ...mapState(['projectList']),

        scheduleLists() {
            return this.scheduleList.filter(({complete_at}) => !complete_at)
        }
    },

    watch: {
        rangeTime(time) {
            this.getTask(time);
        },

        projectList(data) {
            const list = data.map((project) => {
                return {
                    id: project.id,
                    name: project.name,
                }
            });
            if (JSON.stringify(list) != JSON.stringify(this.calendarList)) {
                this.calendarList = list;
            }
        }
    },

    methods: {
        initLanguage() {
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
                popupEdit: () => {
                    return this.$L("详情");
                },
                popupDelete: () => {
                    return this.$L("删除");
                }
            }
        },

        getTask(time) {
            if (this.scheduleLoad > 0) {
                setTimeout(() => {
                    this.getTask(time)
                }, 100)
                return;
            }
            this.scheduleLoad++;
            this.$store.dispatch("getTaskList", {
                time
            }).then(({data}) => {
                this.scheduleLoad--;
                data.data.some((task) => {
                    let schedule = {
                        id: task.id,
                        calendarId: task.project_id,
                        title: task.name,
                        category: 'allday',
                        start: new Date(task.start_at).toISOString(),
                        end: new Date(task.end_at).toISOString(),
                        color: "#515a6e",
                        bgColor: task.color || '#E3EAFD',
                        borderColor: task.p_color,
                        complete_at: task.complete_at,
                        preventClick: true,
                        isChecked: false,
                    };
                    if (task.p_name) {
                        schedule.priority = '<span class="priority" style="background-color:' + task.p_color + '">' + task.p_name + '</span>';
                    }
                    if (task.overdue) {
                        schedule.color = "#f56c6c"
                        schedule.bgColor = "#fef0f0"
                        schedule.priority+= '<span class="overdue">' + this.$L('超期未完成') + '</span>';
                    }
                    let index = this.scheduleList.findIndex(({id}) => id === task.id);
                    if (index > -1) {
                        this.scheduleList.splice(index, 1, schedule)
                    } else {
                        this.scheduleList.push(schedule)
                    }
                });
            }).catch(() => {
                this.scheduleLoad--;
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

        onBeforeClickSchedule({type, schedule}) {
            let data = this.scheduleList.find(({id}) => id === schedule.id);
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
                    this.$store.dispatch("openTask", schedule.id)
                    break;

                case "delete":
                    $A.modalConfirm({
                        title: '删除任务',
                        content: '你确定要删除任务【' + data.title + '】吗？',
                        loading: true,
                        onOk: () => {
                            this.scheduleList = this.scheduleList.filter(({id}) => id !== data.id);
                            this.$store.dispatch("taskArchivedOrRemove", {
                                task_id: data.id,
                                type: 'delete',
                            }).then(({msg}) => {
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
            console.group('onBeforeUpdateSchedule');
            console.log('BeforeUpdate : ', res);
            console.groupEnd();
        }
    }
}
</script>
