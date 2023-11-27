<template>
    <div class="home-calendar">
        <div class="calendar-header">
            <div class="calendar-header-menu">
                <h4>{{$L('(*).(*)', year, month)}}</h4>
            </div>
            <ButtonGroup size="small" >
                <Button><Icon type="ios-arrow-back" class="month-less" @click="prevMonth"/></Button>
                <Button><Icon type="ios-arrow-forward" class="month-add" @click="nextMonth"/></Button>
            </ButtonGroup>
            <Button class="calendar-header-back" size="small" @click="nowMonth">{{$L('今天')}}</Button>
        </div>
        <div class="calendar-content">
            <transition name="slide-up">
                <table class="calendar-table" >
                    <thead>
                    <tr>
                        <th>{{$L('日')}}</th>
                        <th>{{$L('一')}}</th>
                        <th>{{$L('二')}}</th>
                        <th>{{$L('三')}}</th>
                        <th>{{$L('四')}}</th>
                        <th>{{$L('五')}}</th>
                        <th>{{$L('六')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="item in dateArray">
                        <template v-for="data in item">
                            <td v-if="data.month" :class="{today: data.today}">
                                <ETooltip max-width="auto" :disabled="true">
                                    <div slot="content" v-html="getTimes(data.date)"></div>
                                    <div @click="onDayClick(data)" class="item-day">
                                        <div>{{data.day}}</div>
                                        <i v-if="data.check" class="badge"></i>
                                    </div>
                                </ETooltip>
                            </td>
                            <td v-else class="disabled">
                                <div @click="onDayClick(data)" class="item-day">
                                    <div>{{data.day}}</div>
                                </div>
                            </td>
                        </template>
                    </tr>
                    </tbody>
                </table>
            </transition>
            <div v-if="loadIng" class="calendar-loading">
                <Loading/>
            </div>
            <!--  -->
            <div class="calendar-tui">
                <Calendar style="height:calc(100% - 7px);"
                    ref="cal"
                    :view="calendarView"
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
        </div>
    </div>
</template>
<script>
import {mapState, mapGetters} from "vuex";
import Calendar from "./Calendar";
import {Store} from "le5le-store";
import TaskMenu from "./TaskMenu";
import {addLanguage} from "../../../language";

export default {
    name: 'HomeCalendar',
    components: {TaskMenu, Calendar},
    props: {
        checkin: {
            type: Array
        }
    },
    data() {
        return {
            loadIng: 0,
            //
            year: '',
            month: '',
            startTime: '',
            endTime: '',
            dateArray: [],
            historys: [],
            showTable: true,
            //
            lists: [],
            rangeText: 'Calendar',
            rangeTime: [],
            calendarView: 'day',
            calendarWeek: {},
            calendarMonth: {},
            calendarTheme: {},
            calendarTemplate: {},
            calendarTask: {},
            calendarMenuStyles: {
                top: 0,
                left: 0
            },
            loadTimeout: null,
        };
    },
    created() {
        const today = new Date()
        this.year = today.getFullYear();
        this.month = today.getMonth() + 1;
        this.generateCalendar();
        this.generateCalendarInstance();
    },
    computed: {
        ...mapState(['cacheTasks', 'taskCompleteTemps', 'wsOpenNum', 'themeIsDark']),

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
    methods: {
        isCheck(date){
            let time = new Date(date).getTime()
            return this.list.find(h=>{
                if(!h.start_at || !h.end_at){
                    return false;
                }
                let start = new Date( h.start_at.split(' ')[0].replace(/-/g,'/') ).getTime()
                let end = new Date( h.end_at.split(' ')[0].replace(/-/g,'/') ).getTime()
                return start <= time && end >= time;
            })
        },

        getTimes(date) {
            const data = this.historys.find(item => item.date == date)
            return data?.section.map(item => {
                return `${item[0]} - ${item[1] || 'None'}`
            }).join('<br/>')
        },

        generateCalendar(date) {
            let today = new Date($A.formatDate("Y/m/d",date))
            let one = new Date(this.year, this.month - 1, 1)
            let calcTime = one.getTime() - one.getDay() * 86400 * 1000
            let array = []
            for (let i = 0; i < 5; i++) {
                array[i] = []
                for (let j = 0; j < 7; j++) {
                    let curDate = new Date(calcTime)
                    let curMonth = curDate.getMonth() + 1
                    array[i][j] = {
                        day: curDate.getDate(),
                        date: `${curDate.getFullYear()}/${curMonth}/${curDate.getDate()}`,
                        today: today.getTime() == curDate.getTime(),
                        future: today.getTime() < curDate.getTime(),
                        month: curMonth == this.month
                    }
                    array[i][j].check = this.isCheck( array[i][j].date )
                    calcTime += 86400 * 1000
                }
            }
            this.dateArray = array
            this.startTime = array[0][0].date;
            this.endTime = array[4][6].date;
        },

        nextMonth() {
            if (this.month == 12) {
                this.year++;
                this.month = 1;
            } else {
                this.month++;
            }
            this.generateCalendar();
        },

        prevMonth() {
            if (this.month == 1) {
                this.year--;
                this.month = 12;
            } else {
                this.month--;
            }
            this.generateCalendar();
        },

        nowMonth() {
            this.year = parseInt($A.formatDate("Y"));
            this.month = parseInt($A.formatDate("m"));
            this.generateCalendar();
            this.$refs.cal.getInstance().setDate(new Date());
        },

        onDayClick(item){
            const date = new Date(item.date);
            this.year = date.getFullYear();
            this.month = date.getMonth() + 1;
            this.generateCalendar(item.date);
            this.$refs.cal.getInstance().setDate(date);
        },

        //
        generateCalendarInstance(){
            addLanguage([
                {"key": "{日}", "zh": "日", "general": "Sun"},
                {"key": "{一}", "zh": "一", "general": "Mon"},
                {"key": "{二}", "zh": "二", "general": "Tue"},
                {"key": "{三}", "zh": "三", "general": "Wed"},
                {"key": "{四}", "zh": "四", "general": "Thu"},
                {"key": "{五}", "zh": "五", "general": "Fri"},
                {"key": "{六}", "zh": "六", "general": "Sat"},
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
                'common.border': '1px solid rgba(0,0,0,0)',
                'month.dayname.fontSize': '14px',
                'month.dayname.borderLeft': '1px solid rgba(0,0,0,0)',
                'month.dayname.height': '50px',
            }
            if (this.windowLandscape) {
                this.calendarTheme = {
                    'common.border': '1px solid #f4f5f5',
                    'month.dayname.fontSize': '14px',
                    'month.dayname.borderLeft': '1px solid #f4f5f5',
                    'month.dayname.height': '50px',
                }
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
                },
            }
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
                beforeClose: () => guide.clearGuideElement()
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
                            return new Promise((resolve, reject) => {
                                this.$store.dispatch("removeTask", {task_id: data.id}).then(({msg}) => {
                                    resolve(msg);
                                }).catch(({msg}) => {
                                    reject(msg);
                                    this.setRenderRange();
                                });
                            })
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
            if(changes?.start?.getTime() == schedule?.start?.getTime() && changes?.end?.getTime() == schedule?.end?.getTime()){
                return;
            }
            if (changes?.start || changes?.end) {
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
};
</script>
