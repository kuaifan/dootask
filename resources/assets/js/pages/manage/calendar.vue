<template>
    <div class="page-calendar">
        <PageTitle :title="$L('日历')"/>
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
                :schedules="list"
                :taskView="false"
                :useCreationPopup="false"
                @beforeCreateSchedule="onBeforeCreateSchedule"
                @beforeClickSchedule="onBeforeClickSchedule"
                @beforeUpdateSchedule="onBeforeUpdateSchedule"
                disable-click/>
        </div>
        <div class="calendar-menu" :style="calendarMenuStyles">
            <TaskMenu ref="calendarTaskMenu" :task="calendarTask" updateBefore/>
        </div>
    </div>
</template>

<script>
import 'tui-date-picker/dist/tui-date-picker.css';
import 'tui-time-picker/dist/tui-time-picker.css';
import 'tui-calendar-hi/dist/tui-calendar-hi.css'

import {mapState, mapGetters} from "vuex";
import Calendar from "./components/Calendar";
import moment from "moment";
import {Store} from "le5le-store";
import TaskMenu from "./components/TaskMenu";

export default {
    components: {TaskMenu, Calendar},
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
            calendarTask: {},
            calendarMenuStyles: {
                top: 0,
                left: 0
            },

            loadIng: 0,
            loadTimeout: null,
        }
    },

    activated() {
        this.$refs.cal.resetRender();
        this.setRenderRange();
    },

    deactivated() {
        this.$store.dispatch("forgetTaskCompleteTemp", true);
    },

    computed: {
        ...mapState(['userId', 'cacheTasks', 'taskCompleteTemps', 'wsOpenNum', 'themeIsDark']),

        ...mapGetters(['transforTasks']),

        list() {
            const {cacheTasks, taskCompleteTemps} = this;
            const filterTask = (task, chackCompleted = true) => {
                if (task.archived_at) {
                    return false;
                }
                if (task.complete_at && chackCompleted === true) {
                    return false;
                }
                if (!task.end_at) {
                    return false;
                }
                return task.owner;
            }
            let array = cacheTasks.filter(task => filterTask(task));
            if (taskCompleteTemps.length > 0) {
                let tmps = cacheTasks.filter(task => taskCompleteTemps.includes(task.id) && filterTask(task, false));
                if (tmps.length > 0) {
                    array = $A.cloneJSON(array)
                    array.push(...tmps);
                }
            }
            return this.transforTasks(array).map(data => {
                const isAllday = $A.rightExists(data.start_at, "00:00:00") && $A.rightExists(data.end_at, "23:59:59")
                const task = {
                    id: data.id,
                    calendarId: String(data.project_id),
                    title: data.name,
                    body: data.desc,
                    isAllDay: isAllday,
                    category: isAllday ? 'allday' : 'time',
                    start: $A.Date(data.start_at).toISOString(),
                    end: $A.Date(data.end_at).toISOString(),
                    color: "#515a6e",
                    bgColor: data.color || '#E3EAFD',
                    borderColor: data.p_color,
                    priority: '',
                    preventClick: true,
                    preventCheckHide: true,
                    isChecked: !!data.complete_at,
                    //
                    complete_at: data.complete_at,
                    start_at: data.start_at,
                    end_at: data.end_at,
                    _time: data._time,
                };
                if (data.p_name) {
                    let priorityStyle = `background-color:${data.p_color}`;
                    if (this.themeIsDark) {
                        priorityStyle = `color:${data.p_color};border:1px solid ${data.p_color};padding:1px 3px;`;
                    }
                    task.priority = `<span class="priority" style="${priorityStyle}">${data.p_name}</span>`;
                }
                if (data.sub_my && data.sub_my.length > 0) {
                    task.title = `[+${data.sub_my.length}] ${task.title}`
                }
                if (data.sub_top === true) {
                    task.title = `[${this.$L('子任务')}] ${task.title}`
                }
                if (data.flow_item_name) {
                    task.title = `[${data.flow_item_name}] ${task.title}`
                }
                if (data.complete_at) {
                    task.color = "#c3c2c2"
                    task.bgColor = "#f3f3f3"
                    task.borderColor = "#e3e3e3"
                } else if (data.overdue) {
                    task.title = `[${this.$L('超期')}] ${task.title}`
                    task.color = "#f56c6c"
                    task.bgColor = data.color || "#fef0f0"
                    task.priority+= `<span class="overdue">${this.$L('超期未完成')}</span>`;
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

        wsOpenNum(num) {
            if (num <= 1) return
            this.wsOpenTimeout && clearTimeout(this.wsOpenTimeout)
            this.wsOpenTimeout = setTimeout(() => {
                if (this.$route.name == 'manage-calendar') {
                    this.setRenderRange();
                }
            }, 5000)
        }
    },

    methods: {
        initLanguage() {
            this.addLanguageData([
                {"_": "{日}","CN": "日","EN": "Sun","TC": "日","KM": "Sun","TH": "Sun","KO": "Sun","JA": "Sun"},
                {"_": "{一}","CN": "一","EN": "Mon","TC": "一","KM": "Mon","TH": "Mon","KO": "Mon","JA": "Mon"},
                {"_": "{二}","CN": "二","EN": "Tue","TC": "二","KM": "Tue","TH": "Tue","KO": "Tue","JA": "Tue"},
                {"_": "{三}","CN": "三","EN": "Wed","TC": "三","KM": "Wed","TH": "Wed","KO": "Wed","JA": "Wed"},
                {"_": "{四}","CN": "四","EN": "Thu","TC": "四","KM": "Thu","TH": "Thu","KO": "Thu","JA": "Thu"},
                {"_": "{五}","CN": "五","EN": "Fri","TC": "五","KM": "Fri","TH": "Fri","KO": "Fri","JA": "Fri"},
                {"_": "{六}","CN": "六","EN": "Sat","TC": "六","KM": "Sat","TH": "Sat","KO": "Sat","JA": "Sat"},
            ]);
            let daynames = [
                this.$L('{日}'),
                this.$L('{一}'),
                this.$L('{二}'),
                this.$L('{三}'),
                this.$L('{四}'),
                this.$L('{五}'),
                this.$L('{六}')
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
                clearTimeout(this.loadTimeout)
                this.loadTimeout = setTimeout(() => {
                    this.getTask(time)
                }, 100)
                return;
            }
            //
            const timeStart = $A.Date($A.formatDate(time[0] + " 00:00:00")),
                timeEnd = $A.Date($A.formatDate(time[1] + " 23:59:59")),
                now = $A.Time();
            const find = (item, n) => {
                if (n === true && item._time < now) {
                    return false
                }
                const start = $A.Date(item.start_at),
                    end = $A.Date(item.end_at);
                return (start <= timeStart && timeStart <= end) || (start <= timeEnd && timeEnd <= end) || (start > timeStart && timeEnd > end);
            }
            const currentIds = this.list.filter(item => find(item)).map(({id}) => id);
            const call = () => {
                const newIds = this.list.filter(item => find(item, true)).map(({id}) => id);
                this.$store.dispatch("forgetTask", currentIds.filter(v => newIds.indexOf(v) == -1))
            }
            //
            this.loadIng++;
            this.$store.dispatch("getTasks", {
                time,
                complete: "no"
            }).then(() => {
                this.loadIng--;
                call()
            }).catch(() => {
                this.loadIng--;
                call()
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

        onBeforeCreateSchedule({start, end, isAllDay, guide}) {
            if (isAllDay || this.calendarView == 'month') {
                start = $A.date2string(start.toDate(), "Y-m-d 00:00:00")
                end = $A.date2string(end.toDate(), "Y-m-d 23:59:59")
            } else {
                start = $A.date2string(start.toDate(), "Y-m-d H:i:s")
                end = $A.date2string(end.toDate(), "Y-m-d H:i:s")
            }
            Store.set('addTask', {
                times: [start, end],
                owner: [this.userId],
                beforeClose: () => {
                    guide.clearGuideElement();
                }
            });
        },

        onBeforeClickSchedule(event) {
            const {type, schedule} = event;
            let data = this.cacheTasks.find(({id}) => id === schedule.id);
            if (!data) {
                return;
            }
            switch (type) {
                case "check":
                    this.calendarMenuStyles = {
                        left: `${this.getElementLeft(event.target)}px`,
                        top: `${this.getElementTop(event.target) - 8}px`
                    }
                    this.calendarTask = data;
                    this.$nextTick(this.$refs.calendarTaskMenu.show);
                    break;

                case "edit":
                    this.$store.dispatch("openTask", data)
                    break;

                case "delete":
                    $A.modalConfirm({
                        title: '删除任务',
                        content: '你确定要删除任务【' + data.name + '】吗？',
                        loading: true,
                        onOk: () => {
                            this.$store.dispatch("removeTask", {task_id: data.id}).then(({msg}) => {
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
            let data = this.cacheTasks.find(({id}) => id === schedule.id);
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
                    this.setRenderRange();
                });
            }
        },

        getElementLeft(element) {
            let actualLeft = element.offsetLeft;
            let current = element.offsetParent;
            while (current !== null) {
                if (current == this.$el) break;
                actualLeft += (current.offsetLeft + current.clientLeft);
                current = current.offsetParent;
            }
            return actualLeft;
        },

        getElementTop(element) {
            let actualTop = element.offsetTop;
            let current = element.offsetParent;
            while (current !== null) {
                if (current == this.$el) break;
                actualTop += (current.offsetTop + current.clientTop);
                current = current.offsetParent;
            }
            return actualTop;
        }
    }
}
</script>
