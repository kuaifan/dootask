<template>
    <div class="page-calendar">
        <PageTitle :title="$L('日历')"/>
        <div class="calendar-head">
            <div class="calendar-titbox">
                <div class="calendar-title">
                    <div class="common-nav-back portrait" @click="goForward({name: 'manage-application'}, true)"><i class="taskfont">&#xe676;</i></div>
                    <h1>{{ rangeText }}</h1>
                </div>
                <div class="calendar-nav">
                    <ButtonGroup class="calendar-arrow" size="small">
                        <Button @click="onMove(-1)">
                            <Icon type="ios-arrow-back"></Icon>
                        </Button>
                        <Button @click="onMove(1)">
                            <Icon type="ios-arrow-forward"></Icon>
                        </Button>
                    </ButtonGroup>
                    <ButtonGroup class="calendar-arrow" size="small">
                        <Button @click="onToDay">{{ $L('今天') }}</Button>
                    </ButtonGroup>
                </div>
                <ButtonGroup class="calendar-view">
                    <Button @click="setView('day')" :type="options.view == 'day' ? 'primary' : 'default'">{{ $L('日') }}</Button>
                    <Button @click="setView('week')" :type="options.view == 'week' ? 'primary' : 'default'">{{ $L('周') }}</Button>
                    <Button @click="setView('month')" :type="options.view == 'month' ? 'primary' : 'default'">{{ $L('月') }}</Button>
                </ButtonGroup>
            </div>
        </div>
        <div class="calendar-box">
            <Calendar
                ref="calendar"
                :view="options.view"
                :week="options.week"
                :month="options.month"
                :theme="options.theme"
                :template="options.template"
                :events="events"
                :is-read-only="windowTouch"
                @selectDateTime="onSelectDateTime"
                @beforeUpdateEvent="onBeforeUpdateEvent"
                @clickDayName="onClickDayName"
                @clickEvent="onClickEvent"/>
        </div>
    </div>
</template>

<script>
import 'tui-calendar-hi/toastui-calendar.css';
import Calendar from "./components/Calendar";
import {theme} from './components/Calendar/theme';
import emitter from "../../store/events";
import {addLanguage} from "../../language";
import {mapGetters, mapState} from "vuex";

export default {
    components: {Calendar},
    data() {
        return {
            lists: [],

            rangeText: 'Calendar',
            rangeTime: [],

            loadIng: 0,
            loadTimer: null,

            options: {
                view: 'month',
                week: {
                    showTimezoneCollapseButton: true,
                    timezonesCollapsed: false,
                    eventView: true,
                    taskView: false,
                },
                month: {
                    startDayOfWeek: 0
                },
                theme: theme,
                template: {
                    allday: this.getTemplateForGeneral,
                    time: this.getTemplateForGeneral,
                },
            },
        }
    },

    created() {
        addLanguage([
            {"key": "{日}", "zh": "日", "general": "Sun"},
            {"key": "{一}", "zh": "一", "general": "Mon"},
            {"key": "{二}", "zh": "二", "general": "Tue"},
            {"key": "{三}", "zh": "三", "general": "Wed"},
            {"key": "{四}", "zh": "四", "general": "Thu"},
            {"key": "{五}", "zh": "五", "general": "Fri"},
            {"key": "{六}", "zh": "六", "general": "Sat"},
        ]);
        const dayNames = [
            this.$L('{日}'),
            this.$L('{一}'),
            this.$L('{二}'),
            this.$L('{三}'),
            this.$L('{四}'),
            this.$L('{五}'),
            this.$L('{六}')
        ];
        this.options.week.dayNames = dayNames;
        this.options.month.dayNames = dayNames;
        this.options.view = this.$store.state.cacheCalendarView || this.options.view;
        if (this.windowWidth < 600) {
            this.options.template.monthGridHeaderExceed = (hiddenEvents) => `<span>+${hiddenEvents}</span>`
        }
    },

    activated() {
        this.setDateRangeText();
    },

    deactivated() {
        this.$store.dispatch("forgetTaskCompleteTemp", true);
    },

    computed: {
        ...mapState(['cacheTasks', 'taskCompleteTemps', 'wsOpenNum', 'themeName']),
        ...mapGetters(['transforTasks']),

        calendar() {
            return this.$refs.calendar.getInstance();
        },

        events({cacheTasks, taskCompleteTemps}) {
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
                return task.owner == 1;
            }
            let array = cacheTasks.filter(task => filterTask(task));
            if (taskCompleteTemps.length > 0) {
                let tmps = cacheTasks.filter(task => taskCompleteTemps.includes(task.id) && filterTask(task, false));
                if (tmps.length > 0) {
                    array = $A.cloneJSON(array)
                    array.push(...tmps);
                }
            }
            const todayStartPlusOne = $A.dayjs().startOf('day').add(1, 'second');
            const todayEndMinusOne = $A.dayjs().endOf('day').subtract(1, 'second');
            return this.transforTasks(array).map(data => {
                const start = $A.dayjs(data.start_at);
                const end = $A.dayjs(data.end_at);
                const isAllday = start.isBefore(todayStartPlusOne) && end.isAfter(todayEndMinusOne);
                const task = {
                    id: data.id,
                    calendarId: String(data.project_id),
                    title: data.name,
                    body: data.desc,
                    isAllday: isAllday,
                    category: isAllday ? 'allday' : 'time',
                    start: start,
                    end: end,
                    color: "#515a6e",
                    backgroundColor: data.color || '#E3EAFD',
                    borderColor: data.p_color,
                    raw: data,
                }
                if (data.complete_at) {
                    task.color = "#c3c2c2"
                    task.backgroundColor = "#f3f3f3"
                    task.borderColor = "#e3e3e3"
                } else if (data.overdue) {
                    task.color = "#f56c6c"
                    task.backgroundColor = data.color || "#fef0f0"
                }
                if (!task.borderColor) {
                    task.borderColor = task.backgroundColor;
                }
                return task
            })
        }
    },

    watch: {
        rangeTime(time) {
            this.getTask(time);
        },

        wsOpenNum(num) {
            if (num <= 1) return
            this.wsTimer && clearTimeout(this.wsTimer)
            this.wsTimer = setTimeout(() => {
                this.routeName == 'manage-calendar' && this.setDateRangeText();
            }, 5000)
        }
    },

    methods: {
        /**
         * 获取任务
         * @param time
         */
        getTask(time) {
            if (this.loadIng > 0) {
                this.loadTimer && clearTimeout(this.loadTimer)
                this.loadTimer = setTimeout(() => this.getTask(time), 100)
                return;
            }
            //
            this.loadIng++;
            this.$store.dispatch("getTasks", {time}).finally(_ => {
                this.loadIng--;
            })
        },

        /**
         * 任务标题
         * @param title
         * @param data
         * @returns {string}
         */
        getTemplateForGeneral({title, raw: data}) {
            if (data.sub_my && data.sub_my.length > 0) {
                title = `[+${data.sub_my.length}] ${title}`
            }
            if (data.sub_top === true) {
                title = `[${this.$L('子任务')}] ${title}`
            }
            if (data.flow_item_name) {
                title = `[${data.flow_item_name}] ${title}`
            }
            if (data.overdue) {
                title = `[${this.$L('超期')}] ${title}`
            }
            return title;
        },

        /**
         * 选择时间
         * @param start
         * @param end
         * @returns {Promise<void>}
         */
        async onSelectDateTime({start, end}) {
            const timer = [$A.dayjs(start), $A.dayjs(end)]
            if (this.options.view == 'month') {
                timer[0] = timer[0].startOf('day')
                timer[1] = timer[1].startOf('day')
            }
            const times = await this.$store.dispatch("taskDefaultTime", $A.newDateString(timer, "YYYY-MM-DD HH:mm"))
            emitter.emit('addTask', {
                times,
                owner: [this.userId],
                beforeClose: () => this.calendar.clearGridSelections()
            });
        },

        /**
         * 更新任务
         * @param changes
         * @param event
         */
        onBeforeUpdateEvent({changes, event}) {
            if (!changes.start && !changes.end) {
                return;
            }
            // 查找任务
            const data = this.cacheTasks.find(({id}) => id === event.id);
            if (!data) {
                return;
            }
            // dayjs 处理
            const start = $A.dayjs(changes.start || data.start_at),
                end = $A.dayjs(changes.end || data.end_at),
                taskStart = $A.dayjs(data.start_at),
                taskEnd = $A.dayjs(data.end_at);
            // 判断相差1分钟内不修改
            if (start.isSame(taskStart, 'minute') && end.isSame(taskEnd, 'minute')) {
                return;
            }
            // 更新日历
            this.calendar.updateEvent(event.id, event.calendarId, { ...changes });
            // 更新任务
            this.$store.dispatch("taskUpdate", {
                task_id: data.id,
                times: $A.newDateString([start, end], "YYYY-MM-DD HH:mm"),
            }).then(({msg}) => {
                $A.messageSuccess(msg);
            }).catch(({msg}) => {
                $A.modalError({
                    content: msg,
                    onOk: _ => {
                        this.calendar.updateEvent(event.id, event.calendarId, {
                            start: taskStart,
                            end: taskEnd
                        });
                    }
                })
            });
        },

        /**
         * 点击日期
         * @param event
         */
        onClickDayName(event) {
            this.onSelectDateTime({
                start: $A.newDateString(event.date, "YYYY-MM-DD 00:00"),
                end: $A.newDateString(event.date, "YYYY-MM-DD 23:59"),
            })
        },

        /**
         * 点击事件
         * @param event
         */
        onClickEvent({event}) {
            this.$store.dispatch("openTask", event.raw)
        },

        /**
         * 上一天/周/月 下一天/周/月
         * @param offset
         */
        onMove(offset) {
            this.calendar.move(offset);
            this.setDateRangeText();
        },

        /**
         * 今天
         */
        onToDay() {
            this.calendar.today();
            this.setDateRangeText()
        },

        /**
         * 切换天/周/月
         * @param v
         */
        setView(v) {
            this.options.view = v;
            this.calendar.changeView(v);
            this.setDateRangeText();
            $A.IDBSave("cacheCalendarView", this.$store.state.cacheCalendarView = v)
        },

        /**
         * 更新日历标题
         */
        setDateRangeText() {
            const date = this.calendar.getDate();
            const start = this.calendar.getDateRangeStart();
            const end = this.calendar.getDateRangeEnd();

            switch (this.calendar.getViewName()) {
                case "month":
                    this.rangeText = $A.dayjs(date).format("YYYY.MM");
                    break;

                case "day":
                    this.rangeText = $A.dayjs(date).format("YYYY.MM.DD");
                    break;

                default:
                    const startYear = start.getFullYear();
                    const endYear = end.getFullYear();
                    if (startYear !== endYear) {
                        this.rangeText = $A.dayjs(start).format("YYYY.MM.DD") + " ~ " + $A.dayjs(end).format("YYYY.MM.DD");
                    } else {
                        this.rangeText = $A.dayjs(start).format("YYYY.MM.DD") + " ~ " + $A.dayjs(end).format("MM.DD");
                    }
                    break;
            }
            this.rangeTime = [$A.dayjs(start).format('YYYY-MM-DD'), $A.dayjs(end).format('YYYY-MM-DD')];
        },
    }
}
</script>
