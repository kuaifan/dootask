<template>
    <div class="page-calendar">
        <PageTitle>{{$L('日历')}}</PageTitle>
        <div class="calendar-head">
            <div class="calendar-titbox">
                <div class="calendar-title">
                    <h1>{{$L('日历')}}</h1>
                </div>
            </div>
        </div>
        <div class="calendar-box">
            <ul>
                <li>MON</li>
                <li>TUE</li>
                <li>WED</li>
                <li>THU</li>
                <li>FRI</li>
                <li>SAT</li>
                <li>SUN</li>
            </ul>
            {{days}}
            <ul>
                <li v-for="n in 5"></li>
            </ul>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    data() {
        return {
            days: []

        }
    },
    mounted() {
        this.days = this.generateMonthCalendar('2020-09-19');
    },
    computed: {
        ...mapState(['userInfo']),
    },
    watch: {

    },
    methods: {
        generateMonthCalendar(dates, line = 6) {
            var date = new Date(dates);                          // 初始时间格式
            var y = date.getFullYear();
            var m = date.getMonth();
            var days = new Date(y, m + 1, 0).getDate();          // 获取这个月共有多少天
            var firstDayWeek = new Date(y, m, 1).getDay();       // 月份第一天星期几
            var arr = [];     // 存储日历格式的数组
            var n = [];       // 日历格式中的一行
            var d = 1;        // 日历格式中的天数
            // 先根据这个月第一天排星期几
            // 把上个月剩下几天留在这个月的'奸细'放在最前头
            for(let i = 0; i < firstDayWeek; i++) {
                // new Date(2020, 8, 0)   --> 9月没有0号 === 8月3127     n.unshift(getDate(y, m, 0 - i).getDate());
            }
            // 开启循环
            // 一星期占一行，一行一个外循环
            // 这里我默认想要6行
            for (let j = 0; j < line; j++) {
                // 一天占一个格子，最多一星期7个格子
                // 这里我想要7个格子
                for (let i = 0; i < 7; i++) {
                    if(d > days) {
                        // 这个月都放完了，该放什么？
                        // new Date(2020, 8, 31)  --> 9月没有31 === 10月1　　　　　　 n.push(new Date(y, m, d++).getDate());
                    } else {
                        // 放置这个月的天数　　　　　　 n.push(d++);
                    }51
                    if (n.length == 7) break;    // 放了7个格子该结束了
                }
                arr.push(n);
                n = [];           // 这一行放完了，清空ba
            }
            return arr;
        }
    }
}
</script>
