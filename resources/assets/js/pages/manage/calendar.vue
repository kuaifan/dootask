<template>
    <div class="page-calendar">
        <PageTitle>{{$L('日历')}}</PageTitle>
        <div class="calendar-head">
            <div class="calendar-titbox">
                <div class="calendar-title">
                    <h1>{{viewDay}}</h1>
                </div>
                <ButtonGroup class="calendar-arrow" size="small">
                    <Button @click="preMonth"><Icon type="ios-arrow-back"></Icon></Button>
                    <Button @click="curMonth">{{$L('今天')}}</Button>
                    <Button @click="afterMonth"><Icon type="ios-arrow-forward"></Icon></Button>
                </ButtonGroup>
            </div>
        </div>
        <div class="calendar-box">
            <ul class="head">
                <li>SUN</li>
                <li>MON</li>
                <li>TUE</li>
                <li>WED</li>
                <li>THU</li>
                <li>FRI</li>
                <li>SAT</li>
            </ul>
            <ul class="days">
                <li v-for="row in days">
                    <ul>
                        <li v-for="(item, key) in row" :key="key" :class="item.place">
                            <div class="time"><em :class="{'cur-day': item.ymd == curDay}">{{item.day}}</em></div>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    data() {
        return {
            viewDay: $A.formatDate("Y-m"),

            curDay: $A.formatDate("Y-m-d"),
            curInterval: null,

        }
    },
    mounted() {
        this.curInterval = setInterval(() => {
            this.curDay = $A.formatDate("Y-m-d");
        }, 60000)
    },

    destroyed() {
        clearInterval(this.curInterval)
    },
    computed: {
        ...mapState(['userInfo']),

        days() {
            const days = this.getDateJson(new Date(this.viewDay));
            let row = days[days.length - 1].day >= 7 ? 5 : 6
            let array = [], tmp = [];
            for (let i = 0; i < days.length; i++) {
                let obj = days[i];
                tmp.push(obj);
                if ((i + 1) % 7 == 0) {
                    array.push(tmp);
                    tmp = [];
                }
                if (array.length >= row) {
                    break;
                }
            }
            return array;
        }
    },
    watch: {

    },
    methods: {
        preMonth() {
            const date = new Date(this.viewDay);
            date.setMonth(date.getMonth() - 1);
            this.viewDay = $A.formatDate("Y-m", date)
        },

        curMonth() {
            this.viewDay = $A.formatDate("Y-m");
        },

        afterMonth() {
            const date = new Date(this.viewDay);
            date.setMonth(date.getMonth() + 1);
            this.viewDay = $A.formatDate("Y-m", date)
        },

        getDateJson(date){
            const getMonths = (yy) => {
                let months = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                let months2 = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                let mrun = yy % 100 == 0 && yy % 400 == 0 ? true : (yy % 4 == 0);
                return mrun ? months2 : months;
            }
            const zeroFill = (num) => {
                if (num < 9) return '0' + num;
                return '' + num;
            }
            //获取要绘制月份的年月，
            let yy = date.getFullYear();
            let mm = date.getMonth();
            //获取应该使用的月份数组。
            let month = getMonths(yy);
            //定义此月的1号的日期，获取其星期。
            let begin_date = new Date(yy, mm, 1);
            //获得上个月应该显示几天
            let pre_num = begin_date.getDay();
            //数组的总个数
            let const_num = 7 * 6;
            //当月的天数
            let cur_num = month[mm];
            //下个月的天数
            let after_num = const_num - cur_num - pre_num;
            //
            let preyy = yy;
            let premm = mm;
            //月份-1小于0，则前一月为上一年
            if (premm == 0) {
                preyy -= 1;
            }
            //上个月的月份以及天数
            premm = premm - 1 < 0 ? 11 : (premm - 1);
            let pre_max = month[premm];

            //下个月的月份
            let afteryy = yy;
            let aftermm = mm;
            if (aftermm == 11) {
                afteryy += 1;
            }
            aftermm = aftermm + 1 > 11 ? 0 : (aftermm + 1);
            //定义日历数组。
            let dateJson = [];
            //循环得到上个月的日期。
            for (let i = pre_num; i > 0; i--) {
                let obj = {year: preyy, month: premm + 1, day: (pre_max - i + 1), place: 'pre'};
                obj.ymd = obj.year + '-' + zeroFill(obj.month) + '-' + zeroFill(obj.day)
                dateJson.push(obj);
            }
            //循环添加当月日期
            for (let i = 1; i <= cur_num; i++) {
                let obj = {year: yy, month: mm + 1, day: i, place: 'cur'};
                obj.ymd = obj.year + '-' + zeroFill(obj.month) + '-' + zeroFill(obj.day)
                dateJson.push(obj);
            }
            //循环添加下个月的日期。
            for (let i = 1; i <= after_num; i++) {
                let obj = {year: afteryy, month: aftermm + 1, day: i, place: 'after'};
                obj.ymd = obj.year + '-' + zeroFill(obj.month) + '-' + zeroFill(obj.day)
                dateJson.push(obj);
            }
            return dateJson;
        }
    }
}
</script>
