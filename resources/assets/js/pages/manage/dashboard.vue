<template>
    <div class="page-dashboard">
        <PageTitle>{{$L('仪表板')}}</PageTitle>
        <div class="dashboard-wrapper">
            <div class="dashboard-hello">{{$L('欢迎您，' + userInfo.nickname)}}</div>
            <div class="dashboard-desc">{{$L('以下是你当前的任务统计数据')}}</div>
            <ul class="dashboard-block">
                <li>
                    <div class="block-title">{{$L('今日待完成')}}</div>
                    <div class="block-data">
                        <div class="block-num">21</div>
                        <i class="iconfont">&#xe6f4;</i>
                    </div>
                </li>
                <li>
                    <div class="block-title">{{$L('超期未完成')}}</div>
                    <div class="block-data">
                        <div class="block-num">2</div>
                        <i class="iconfont">&#xe603;</i>
                    </div>
                </li>
                <li>
                    <div class="block-title">{{$L('参与的项目')}}</div>
                    <div class="block-data">
                        <div class="block-num">{{projectList.length}}</div>
                        <i class="iconfont">&#xe6f9;</i>
                    </div>
                </li>
            </ul>
            <div class="dashboard-title">{{$L('今日任务')}}</div>
            <ul class="dashboard-list overlay-y">
                <li>
                    <i class="iconfont">&#xe625;</i>
                    <div class="item-title">超全!B端通用界面设计法则全方位科普</div>
                    <div class="item-time"></div>
                </li>
                <li>
                    <i class="iconfont">&#xe625;</i>
                    <div class="item-title">做B端后台产品很复杂?送你一份完整的设计流程和规范!</div>
                    <div class="item-time"></div>
                </li>
                <li>
                    <i class="iconfont">&#xe625;</i>
                    <div class="item-title">如何设计「时间显示」更专业?来看大厂总结的方法</div>
                    <div class="item-time"></div>
                </li>
                <li>
                    <i class="iconfont">&#xe625;</i>
                    <div class="item-title">用实战分析和案例,教你网页配色的终极套路</div>
                    <div class="item-time"></div>
                </li>
                <li>
                    <i class="iconfont">&#xe625;</i>
                    <div class="item-title">用一篇超全面的文章,帮你了解维也纳艺术团体「分离派」</div>
                    <div class="item-time"></div>
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    data() {
        return {}
    },
    mounted() {

    },
    computed: {
        ...mapState(['userInfo', 'projectList']),
    }
}
</script>
