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
        <table class="sign_tab" border="0px" cellpadding="0px" cellspacing="0px">
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
            <tr v-for="item in dateArr" v-if="contains(item)">
                <template v-for="data in item">
                    <td v-if="isCheck(data.date)"
                        :class="{'disa':monthClass(data.month), 'cur_day': doCheck(data.date),'check_day': isCheck(data.date) }">
                        <Tooltip max-width="auto" transfer>
                            <div slot="content" v-html="getTimes(data.date)"></div>
                            <template v-if="doCheck(data.date)">{{$L('今天')}}</template>
                            <template v-else>{{data.date | getCD}}</template>
                            <span :class="{'ui-state-down': true }">{{$L('已签到(*)次', getGold(data.date))}}</span>
                        </Tooltip>
                    </td>
                    <template v-if="(!isCheck(data.date) && (doCheck(data.date) && !hasCheckin))">
                        <td v-if="!monthClass(data.month)"
                            @click="checkNow"
                            :class="{'disa':monthClass(data.month), 'over':data.date == '', 'cur_day': doCheck(data.date) }">
                            {{$L('今天')}}
                            <span :class="{'ui-state-default': true }">{{$L('尚未签到')}}</span>
                        </td>
                        <td v-else
                            :class="{'disa':monthClass(data.month), 'over':data.date == '', 'cur_day': doCheck(data.date) }">
                            {{data.date | getCD}}
                        </td>
                    </template>
                    <td v-if="!isCheck(data.date) && (!doCheck(data.date)) "
                        :class="{'disa':monthClass(data.month), 'over':data.date == '', 'cur_day': doCheck(data.date) }">
                        {{data.date | getCD}}
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
            today: new Date(),
            year: '',
            month: '',
            day: '',
            date: '',
            startTime: '',
            endTime: '',
            dateArr: [],
            hasCheckin: false,
        };
    },
    created() {
        this.year = this.today.getFullYear();
        this.month = this.today.getMonth() + 1;
        this.day = this.today.getDay();
        this.date = this.today.getDate();

        this.getCalendar();
    },
    filters: {
        getCD(val) {
            return val.split('/')[2]
        }
    },
    watch: {
        dateArr: {
            deep: true,
            handler: function (val, oldVal) {
                this.startTime = val[0][0].date;
                this.endTime = val[5][6].date;
                this.setMonth(this.year + '/' + this.month, [this.startTime, this.endTime]);
            }
        }
    },
    computed: {
        hasNextMonth() {
            const {year, month} = this;
            const {y, m} = {y: $A.formatDate("Y"), m: $A.formatDate("m")};
            return parseInt(year) != y || parseInt(month) < parseInt(m);
        }
    },
    methods: {
        checkNow() {
            this.$emit('checkIn')
        },
        setMonth(date,) {
            this.$emit('setMonth', date, [this.startTime, this.endTime])
        },
        monthClass(type) {
            return type != 'cur';
        },
        getGold(thisDay) {
            for (let i in this.checkin) {
                if (this.checkin.hasOwnProperty(i)) {
                    var d = new Date(this.checkin[i].time.replace(/-/g, '/'));
                    var _ymd = d.getFullYear() + '/' + (d.getMonth() + 1) + '/' + d.getDate();

                    if (new Date(thisDay).getTime() == new Date(_ymd).getTime()) {
                        return this.checkin[i].all.length;
                    }
                }
            }
        },
        getTimes(thisDay) {
            for (let i in this.checkin) {
                if (this.checkin.hasOwnProperty(i)) {
                    var d = new Date(this.checkin[i].time.replace(/-/g, '/'));
                    var _ymd = d.getFullYear() + '/' + (d.getMonth() + 1) + '/' + d.getDate();

                    if (new Date(thisDay).getTime() == new Date(_ymd).getTime()) {
                        return this.checkin[i].all.join('<br/>');
                    }
                }
            }
        },
        isLeap() {
            const year = this.year;
            if (year % 4 == 0 && year % 100 > 0) {
                return true;
            } else return year % 400 == 0 && year % 3200 > 0;
        },
        getLen(m) {
            const month = m || this.month;
            if (month == 2) {
                if (this.isLeap) {
                    return 29;
                } else {
                    return 28;
                }
            } else {
                if (month < 8) {
                    if (month % 2 > 0) {
                        return 31;
                    } else {
                        return 30;
                    }
                } else {
                    if (month % 2 > 0) {
                        return 30;
                    } else {
                        return 31;
                    }
                }
            }
        },
        getCalendarTime() {
            return this.year + '-' + this.month + '-' + this.date;
        },
        getCalendar() {
            let len = this.getLen();
            let d = new Date(this.year, this.month - 1, 1);
            let dfw = d.getDay();
            let arr = [];
            let tem = 0;
            let nextTem = 1;
            let pre = dfw - 1

            let _lastLen = this.getLen(this.month - 1)

            for (let i = 0; i < 6; i++) {
                arr[i] = [];
                for (let j = 0; j < 7; j++) {
                    tem++;
                    if (tem - dfw > 0 && tem - dfw <= len) {
                        arr[i][j] = {date: this.year + '/' + (this.month) + '/' + (tem - dfw), month: 'cur'};
                    } else {
                        if (tem <= dfw) {
                            arr[i][j] = {
                                date: this.year + '/' + (this.month - 1) + '/' + (_lastLen - pre),
                                month: 'pre'
                            };
                            pre--;

                        } else {
                            arr[i][j] = {date: this.year + '/' + (this.month + 1) + '/' + (nextTem), month: 'next'};
                            nextTem++

                        }
                    }
                }
            }
            this.dateArr = arr;
        },
        nextMonth() {
            if (this.month == 12) {
                this.year++;
                this.month = 1;
            } else {
                this.month++;
            }
            this.getCalendar();
            this.$emit('changeMonth', this.ym())
        },
        prevMonth() {
            if (this.month == 1) {
                this.year--;
                this.month = 12;
            } else {
                this.month--;
            }
            this.getCalendar();
            this.$emit('changeMonth', this.ym())
        },
        nowMonth() {
            this.year = parseInt($A.formatDate("Y"));
            this.month = parseInt($A.formatDate("m"));
            this.getCalendar();
            this.$emit('changeMonth', this.ym())
        },
        contains(arr) {
            return !((arr[0] == '') && (arr[1] == '') && (arr[2] == '') && (arr[3] == '') && (arr[4] == '') && (arr[5] == '') && (arr[6] == ''));
        },
        isCheck(index) {
            for (let i in this.checkin) {
                let todayDate = new Date();
                let today = todayDate.getFullYear() + '/' + (todayDate.getMonth() + 1) + '/' + todayDate.getDate();
                let d = new Date(this.checkin[i].time.replace(/-/g, '/'));
                let _ymd = d.getFullYear() + '/' + (d.getMonth() + 1) + '/' + d.getDate();
                if (new Date(today).getTime() == new Date(_ymd).getTime()) {
                    //今日已经签到
                    this.hasCheckin = true;
                }

                if (new Date(index).getTime() == new Date(_ymd).getTime()) {
                    //console.log('已经签到')
                    return true;
                }
            }
            return false;
        },
        doCheck(d) {
            let dString = new Date().getFullYear() + '/' + (new Date().getMonth() + 1) + '/' + new Date().getDate();
            return new Date(d).getTime() == new Date(dString).getTime();
        },
        ym() {
            return this.year + '-' + (this.month < 10 ? ('0' + this.month) : this.month);
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
    .sign_tab {
        width: 100%;
        table-layout: fixed;
        th {
            text-align: center;
            height: 48px;
            font-weight: 700;
        }
        td {
            position: relative;
            padding: 15px 0;
            text-align: center;
            font-size: 14px;
            border-right: 1px solid #eee;
            border-top: 1px solid #eee;
            &:last-child {
                border-right: 0;
            }
            &.over {
                background-color: #fff;
                border-left: 0;
                border-right: 0;
            }
            &.disa {
                color: #ccc !important;
                background: none !important;

                * {
                    color: #ccc !important;

                }
            }
            &.check_day {
                background-color: #f8f8f8;
                color: #58ce7a;
                position: relative;
                font-size: 14px;
                padding-top: 2px;
                line-height: 26px;
                .ivu-tooltip {
                    position: absolute;
                    left: 0;
                    right: 0;
                    top: 0;
                    bottom: 0;
                    .ivu-tooltip-rel {
                        width: 100%;
                        height: 100%;
                        line-height: 26px;
                        padding-top: 4px;
                    }
                }
            }
        }
    }

    .ui-state-down,
    .ui-state-default {
        font-size: 12px;
        width: 100%;
        text-align: center;
        position: absolute;
        bottom: 3px;
        left: 0;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .sign_tab {
        td {
            &.cur_day {
                background-color: #F29D38;
                color: #FFF;
                padding-top: 2px;
                line-height: 26px;
            }
        }
    }
}
</style>
