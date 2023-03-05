<template>
    <div class="checkin-calendar">
        <div class="calendar-header">
            <div class="calendar-header-menu">
                <Icon type="ios-arrow-back" class="month-less" @click="prevMonth"/>
                <h4>{{$L('(*)年(*)月', year, month)}}</h4>
                <Icon v-if="hasNextMonth" type="ios-arrow-forward" class="month-add" @click="nextMonth"/>
            </div>
            <Button v-if="hasNextMonth" class="calendar-header-back" size="small" @click="nowMonth">{{$L('返回本月')}}</Button>
        </div>
        <table class="check-table">
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
                    <td v-if="data.month" :class="{today: data.today, checkin:isCheck(data.date)}">
                        <ETooltip max-width="auto" :disabled="!isCheck(data.date)">
                            <div slot="content" v-html="getTimes(data.date)"></div>
                            <div class="item-day">
                                <div v-if="data.today">{{$L('今天')}}</div>
                                <div v-else>{{data.day}}</div>

                                <div v-if="isCheck(data.date)" class="ui-state-down">{{$L('已签到')}}</div>
                                <div v-else-if="data.today" class="ui-state-default">{{$L('尚未签到')}}</div>
                            </div>
                        </ETooltip>
                    </td>
                    <td v-else class="disabled">
                        <div class="item-day">
                            <div>{{data.day}}</div>
                            <div v-if="isCheck(data.date)" class="ui-state-down">{{$L('已签到')}}</div>
                        </div>
                    </td>
                </template>
            </tr>
            </tbody>
        </table>
        <div v-if="loadIng" class="calendar-loading">
            <Loading/>
        </div>
    </div>
</template>
<script>
export default {
    name: 'CheckinCalendar',
    props: {
        checkin: {
            type: Array
        },
        loadIng: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            year: '',
            month: '',

            startTime: '',
            endTime: '',

            dateArray: [],
            historys: [],
        };
    },
    created() {
        const today = new Date()
        this.year = today.getFullYear();
        this.month = today.getMonth() + 1;

        this.generateCalendar();
    },
    watch: {
        checkin: {
            handler(arr) {
                arr.some(({date, section}) => {
                    date = date.replace(/-0?/g, '/')
                    let index = this.historys.findIndex(item => item.date == date)
                    if (index > -1) {
                        this.historys.splice(index, 1, {date, section})
                    } else {
                        this.historys.push({date, section})
                    }
                })
            },
            immediate: true
        }
    },
    computed: {
        hasNextMonth() {
            const {year, month} = this;
            return parseInt(year) != $A.formatDate("Y") || parseInt(month) < $A.formatDate("m");
        }
    },
    methods: {
        ym() {
            return this.year + '-' + (this.month < 10 ? ('0' + this.month) : this.month);
        },
        isCheck(date) {
            return !!this.historys.find(item => item.date == date)
        },
        setMonth(date) {
            this.$emit('setMonth', date, [this.startTime, this.endTime])
        },
        getTimes(date) {
            const data = this.historys.find(item => item.date == date)
            return data?.section.map(item => {
                return `${item[0]} - ${item[1] || 'None'}`
            }).join('<br/>')
        },
        generateCalendar() {
            let today = new Date($A.formatDate("Y/m/d"))
            let one = new Date(this.year, this.month - 1, 1)
            let calcTime = one.getTime() - one.getDay() * 86400 * 1000
            let array = []
            for (let i = 0; i < 6; i++) {
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
                    calcTime += 86400 * 1000
                }
            }
            this.dateArray = array
            this.startTime = array[0][0].date;
            this.endTime = array[5][6].date;
            this.setMonth(this.year + '/' + this.month, [this.startTime, this.endTime]);
        },
        nextMonth() {
            if (this.month == 12) {
                this.year++;
                this.month = 1;
            } else {
                this.month++;
            }
            this.generateCalendar();
            this.$emit('changeMonth', this.ym())
        },
        prevMonth() {
            if (this.month == 1) {
                this.year--;
                this.month = 12;
            } else {
                this.month--;
            }
            this.generateCalendar();
            this.$emit('changeMonth', this.ym())
        },
        nowMonth() {
            this.year = parseInt($A.formatDate("Y"));
            this.month = parseInt($A.formatDate("m"));
            this.generateCalendar();
            this.$emit('changeMonth', this.ym())
        }
    }
};
</script>
<style lang="scss">
.checkin-calendar {
    width: 100%;
    margin: -10px 0 24px 0;
    color: #555;
    position: relative;
    border: 1px solid #eee;
    border-radius: 3px;
    .calendar-header {
        display: flex;
        align-items: center;
        border-bottom: 1px solid #eee;
        .calendar-header-menu {
            position: relative;
            flex: 1;
        }
        .calendar-header-back {
            margin-right: 14px;
        }
    }
    .calendar-loading {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 1;
        background-color: rgba(55, 55, 55, .15);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    h4 {
        line-height: 40px;
        background-color: #fff;
        text-align: center;
        color: #333;
    }
    i {
        position: absolute;
        width: 30px;
        height: 30px;
        line-height: 30px;
        top: 5px;
        font-size: 18px;
        color: #777;
        &.month-less {
            left: 10px;
        }
        &.month-add {
            right: 10px;
        }
    }
    .check-table {
        width: 100%;
        border: 0;
        border-spacing: 0;
        border-collapse: collapse;
        table-layout: fixed;
        th {
            text-align: center;
            height: 48px;
            font-weight: 700;
        }
        td {
            position: relative;
            text-align: center;
            border-right: 1px solid #eee;
            border-top: 1px solid #eee;
            font-size: 14px;
            height: 52px;
            .item-day {
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                line-height: 20px;
                > div {
                    max-width: 100%;
                    padding: 0 4px;
                    overflow: hidden;
                    white-space: nowrap;
                    text-overflow: ellipsis;
                }
            }
            &:last-child {
                border-right: 0;
            }
            &.disabled {
                color: #ccc;
                background: none;
                * {
                    color: #ccc;
                }
            }
            &.today {
                background-color: #F29D38;
                color: #FFF;
                padding-top: 2px;
                line-height: 26px;
            }
            &.checkin {
                color: #58ce7a;
                background-color: #f8f8f8;
                position: relative;
            }
        }
    }

    .ui-state-down,
    .ui-state-default {
        font-size: 12px;
    }
}
</style>
