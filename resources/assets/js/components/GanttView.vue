<template>
    <div class="common-gantt">
        <div class="gantt-left" :style="{width:menuWidth+'px'}">
            <div class="gantt-title">
                <div class="gantt-title-text">{{$L('任务名称')}}</div>
            </div>
            <ul ref="ganttItem"
                class="gantt-item"
                @scroll="itemScrollListener"
                @mouseenter="mouseType='item'">
                <li v-for="(item, key) in lists" :key="key">
                    <div v-if="item.overdue" class="item-overdue" @click="clickItem(item)">{{$L('已超期')}}</div>
                    <div class="item-title" :class="{complete:item.complete, overdue:item.overdue}" @click="clickItem(item)">{{item.label}}</div>
                    <Icon class="item-icon" type="ios-locate-outline" @click="scrollPosition(key)"/>
                </li>
            </ul>
        </div>
        <div ref="ganttRight" class="gantt-right">
            <div class="gantt-chart">
                <ul class="gantt-month">
                    <li v-for="(item, key) in monthNum" :key="key" :style="monthStyle(key)">
                        <div class="month-format">{{monthFormat(key)}}</div>
                    </li>
                </ul>
                <ul class="gantt-date" @mousedown="dateMouseDown">
                    <li v-for="(item, key) in dateNum" :key="key" :style="dateStyle(key)">
                        <div class="date-format">
                            <div class="format-day">{{dateFormat(key, 'day')}}</div>
                            <div v-if="dateWidth > 46" class="format-week">{{dateFormat(key, 'week')}}</div>
                        </div>
                    </li>
                </ul>
                <ul ref="ganttTimeline"
                    class="gantt-timeline"
                    @scroll="timelineScrollListener"
                    @mouseenter="mouseType='timeline'">
                    <li v-for="(item, key) in lists" :key="key">
                        <div
                            class="timeline-item"
                            :style="itemStyle(item)"
                            @mousedown="itemMouseDown($event, item)">
                            <div class="timeline-title" :title="item.label">{{item.label}}</div>
                            <div class="timeline-resizer"></div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'GanttView',
    props: {
        lists: {
            type: Array
        },
        menuWidth: {
            type: Number,
            default: 300
        },
        itemWidth: {
            type: Number,
            default: 100
        }
    },
    data() {
        return {
            mouseType: '',
            mouseWidth: 0,
            mouseScaleWidth: 0,

            dateWidth: 100,
            ganttWidth: 0,

            mouseItem: null,
            mouseBak: {},

            dateMove: null
        }
    },
    mounted() {
        this.dateWidth = this.itemWidth;
        this.$refs.ganttRight.addEventListener('mousewheel', this.handleScroll, false);
        document.addEventListener('mousemove', this.itemMouseMove);
        document.addEventListener('mouseup', this.itemMouseUp);
        window.addEventListener("resize", this.handleResize, false);
        this.handleResize();
    },
    beforeDestroy() {
        this.$refs.ganttRight.removeEventListener('mousewheel', this.handleScroll, false);
        document.removeEventListener('mousemove', this.itemMouseMove);
        document.removeEventListener('mouseup', this.itemMouseUp);
        window.removeEventListener("resize", this.handleResize, false);
    },
    watch: {
        itemWidth(val) {
            this.dateWidth = val;
        }
    },
    computed: {
        monthNum() {
            const {ganttWidth, dateWidth} = this;
            return Math.floor(ganttWidth / dateWidth / 30) + 2
        },
        monthStyle() {
            const {mouseWidth, dateWidth} = this;
            return function(index) {
                let mouseDay = mouseWidth == 0 ? 0 : mouseWidth / dateWidth;
                let date = new Date();
                //今天00:00:00
                let nowDay = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0);
                //当前时间
                let curDay = new Date(nowDay.getTime() + mouseDay * 86400000);
                //当月最后一天
                let lastDay = new Date(curDay.getFullYear(), curDay.getMonth() + 1, 0, 23, 59, 59);
                //相差天数
                let diffDay = (lastDay - curDay)  / 1000 / 60 / 60 / 24;
                //
                let width = dateWidth * diffDay;
                if (index > 0) {
                    lastDay = new Date(curDay.getFullYear(), curDay.getMonth() + 1 + index, 0);
                    width = lastDay.getDate() * dateWidth;
                }
                return {
                    width: width + 'px',
                }
            }
        },
        monthFormat() {
            const {mouseWidth, dateWidth} = this;
            return function(index) {
                let mouseDay = mouseWidth == 0 ? 0 : mouseWidth / dateWidth;
                let date = new Date();
                //开始位置时间（今天00:00:00）
                let nowDay = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0);
                //当前时间
                let curDay = new Date(nowDay.getTime() + mouseDay * 86400000);
                //
                if (index > 0) {
                    curDay = new Date(curDay.getFullYear(), curDay.getMonth() + 1 + index, 0);
                }
                return $A.formatDate("Y-m", curDay)
            }
        },
        dateNum() {
            const {ganttWidth, dateWidth} = this;
            return Math.floor(ganttWidth / dateWidth) + 2
        },
        dateStyle() {
            const {mouseWidth, dateWidth} = this;
            return function(index) {
                const style = {};
                //
                let mouseDay = mouseWidth == 0 ? 0 : mouseWidth / dateWidth;
                let mouseData = Math.floor(mouseDay) + index;
                if (mouseDay == Math.floor(mouseDay)) {
                    mouseData--;
                }
                let j = mouseWidth == 0 ? index - 1 : mouseData;
                let date = new Date(new Date().getTime() + j * 86400000);
                if ([0, 6].indexOf(date.getDay()) !== -1) {
                    style.backgroundColor = '#f9fafb';
                }
                //
                let width = dateWidth;
                if (index == 0) {
                    width = Math.abs((mouseWidth % width - width) % width);
                }
                style.width = width + 'px';
                return style
            }
        },
        dateFormat() {
            const {mouseWidth, dateWidth} = this;
            return function(index, type) {
                let mouseDay = mouseWidth == 0 ? 0 : mouseWidth / dateWidth;
                let mouseData = Math.floor(mouseDay) + index;
                if (mouseDay == Math.floor(mouseDay)) {
                    mouseData--;
                }
                let j = mouseWidth == 0 ? index - 1 : mouseData;
                let date = new Date(new Date().getTime() + j * 86400000)
                if (type == 'day') {
                    return date.getDate();
                } else if (type == 'week') {
                    return this.$L(`星期${'日一二三四五六'.charAt(date.getDay())}`);
                } else {
                    return date;
                }
            }
        },
        itemStyle() {
            const {mouseWidth, dateWidth, ganttWidth} = this;
            return function(item) {
                const {start, end} = item.time;
                const {style, moveX, moveW} = item;
                let date = new Date();
                //开始位置时间戳（今天00:00:00时间戳）
                let nowTime = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0).getTime();
                //距离开始位置多少天
                let diffStartDay = (start - nowTime) / 1000 / 60 / 60 / 24;
                let diffEndDay = (end - nowTime) / 1000 / 60 / 60 / 24;
                //
                let left = dateWidth * diffStartDay + (mouseWidth * -1);
                let width = dateWidth * (diffEndDay - diffStartDay);
                if (typeof moveX === "number") {
                    left+= moveX;
                }
                if (typeof moveW === "number") {
                    width+= moveW;
                }
                //
                const customStyle = {
                    left: Math.min(Math.max(left, width * -1.2), ganttWidth * 1.2).toFixed(2) + 'px',
                    width: width.toFixed(2) + 'px',
                };
                if (left < 0 && Math.abs(left) < width) {
                    customStyle.paddingLeft = Math.abs(left).toFixed(2) + 'px'
                }
                if (left + width > ganttWidth && left < ganttWidth) {
                    customStyle.paddingRight = Math.abs(left + width - ganttWidth).toFixed(2) + 'px'
                }
                if (typeof style === "object") {
                    return Object.assign(customStyle, style);
                }
                return customStyle
            }
        }
    },
    methods: {
        itemScrollListener(e) {
            if (this.mouseType == 'timeline') {
                return;
            }
            this.$refs.ganttTimeline.scrollTop = e.target.scrollTop;
        },
        timelineScrollListener(e) {
            if (this.mouseType == 'item') {
                return;
            }
            this.$refs.ganttItem.scrollTop = e.target.scrollTop;
        },
        handleScroll(e) {
            e.preventDefault();
            if (e.ctrlKey) {
                //缩放
                this.dateWidth = Math.min(600, Math.max(24, this.dateWidth - Math.floor(e.deltaY)));
                this.mouseWidth = this.ganttWidth / 2 * ((this.dateWidth - 100) / 100) + this.dateWidth / 100 * this.mouseScaleWidth;
                return;
            }
            if (e.deltaY != 0) {
                const ganttTimeline = this.$refs.ganttTimeline;
                let newTop = ganttTimeline.scrollTop + e.deltaY;
                if (newTop < 0) {
                    newTop = 0;
                } else if (newTop > ganttTimeline.scrollHeight - ganttTimeline.clientHeight) {
                    newTop = ganttTimeline.scrollHeight - ganttTimeline.clientHeight;
                }
                if (ganttTimeline.scrollTop != newTop) {
                    this.mouseType = 'timeline';
                    ganttTimeline.scrollTop = newTop;
                }
            }
            if (e.deltaX != 0) {
                this.mouseWidth+= e.deltaX;
                this.mouseScaleWidth+= e.deltaX * (100 / this.dateWidth);
            }
        },
        handleResize() {
            this.ganttWidth = this.$refs.ganttTimeline.clientWidth;
        },
        dateMouseDown(e) {
            e.preventDefault();
            this.mouseItem = null;
            this.dateMove = {
                clientX: e.clientX
            };
        },
        itemMouseDown(e, item) {
            e.preventDefault();
            let type = 'moveX';
            if (e.target.className == 'timeline-resizer') {
                type = 'moveW';
            }
            if (typeof item[type] !== "number") {
                this.$set(item, type, 0);
            }
            this.mouseBak = {
                type: type,
                clientX: e.clientX,
                value: item[type],
            };
            this.mouseItem = item;
            this.dateMove = null;
        },
        itemMouseMove(e) {
            if (this.mouseItem != null) {
                e.preventDefault();
                const diff = this.mouseBak.value + (e.clientX - this.mouseBak.clientX);
                if (this.mouseBak.type === 'moveW') {
                    const oneWidthTime = 86400000 / this.dateWidth;
                    const {start, end} = this.mouseItem.time;
                    let moveTime = diff * oneWidthTime;
                    if (end + moveTime - start <= 0) {
                        return
                    }
                }
                this.$set(this.mouseItem, this.mouseBak.type, diff);
            } else if (this.dateMove != null) {
                e.preventDefault();
                let moveX = (this.dateMove.clientX - e.clientX) * 5;
                this.dateMove.clientX = e.clientX;
                this.mouseWidth+= moveX;
                this.mouseScaleWidth+= moveX * (100 / this.dateWidth);
            }
        },
        itemMouseUp(e) {
            if (this.mouseItem != null) {
                const {start, end} = this.mouseItem.time;
                let isM = false;
                //一个宽度的时间
                let oneWidthTime = 86400000 / this.dateWidth;
                //修改起止时间
                if (typeof this.mouseItem.moveX === "number" && this.mouseItem.moveX != 0) {
                    let moveTime = this.mouseItem.moveX * oneWidthTime;
                    this.$set(this.mouseItem.time, 'start', start + moveTime);
                    this.$set(this.mouseItem.time, 'end', end + moveTime);
                    this.$set(this.mouseItem, 'moveX', 0);
                    isM = true;
                }
                //修改结束时间
                if (typeof this.mouseItem.moveW === "number" && this.mouseItem.moveW != 0) {
                    let moveTime = this.mouseItem.moveW * oneWidthTime;
                    this.$set(this.mouseItem.time, 'end', end + moveTime);
                    this.$set(this.mouseItem, 'moveW', 0);
                    isM = true;
                }
                //
                if (isM) {
                    this.$emit("on-change", this.mouseItem)
                } else if (e.target.className == 'timeline-title') {
                    this.clickItem(this.mouseItem);
                }
                this.mouseItem = null;
            } else if (this.dateMove != null) {
                this.dateMove = null;
            }
        },
        scrollPosition(pos) {
            let date = new Date();
            //今天00:00:00
            let nowDay = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0);
            //一个宽度的时间
            let oneWidthTime = 86400000 / this.dateWidth;
            //
            let moveWidth = (this.lists[pos].time.start - nowDay) / oneWidthTime - this.dateWidth - this.mouseWidth;
            this.mouseWidth+= moveWidth;
            this.mouseScaleWidth+= moveWidth * (100 / this.dateWidth);
        },
        clickItem(item) {
            this.$emit("on-click", item)
        }
    }
}
</script>

<style lang="scss" scoped>
.common-gantt {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-direction: row;
    align-items: self-start;
    color: #747a81;
    * {
        box-sizing: border-box;
    }
    .gantt-left {
        flex-grow:0;
        flex-shrink:0;
        height: 100%;
        background-color: #ffffff;
        position: relative;
        display: flex;
        flex-direction: column;
        &:after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 1px;
            background-color: rgba(237, 241, 242, 0.75);
        }
        .gantt-title {
            height: 76px;
            flex-grow: 0;
            flex-shrink: 0;
            background-color: #F9FAFB;
            padding-left: 12px;
            overflow: hidden;
            .gantt-title-text {
                line-height: 100px;
                max-width: 200px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                font-weight: 600;
            }
        }
        .gantt-item {
            transform: translateZ(0);
            max-height: 100%;
            overflow: auto;
            -ms-overflow-style: none;
            &::-webkit-scrollbar {
                display: none;
            }
            > li {
                height: 40px;
                border-bottom: 1px solid rgba(237, 241, 242, 0.75);
                position: relative;
                display: flex;
                align-items: center;
                padding-left: 12px;
                &:hover {
                    .item-icon {
                        display: flex;
                    }
                }
                .item-overdue {
                    flex-grow:0;
                    flex-shrink:0;
                    color: #ffffff;
                    margin-right: 4px;
                    background-color: #ff0000;
                    padding: 1px 3px;
                    border-radius: 3px;
                    font-size: 12px;
                    line-height: 18px;
                }
                .item-title {
                    flex: 1;
                    padding-right: 12px;
                    cursor: default;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                    &.complete {
                        text-decoration: line-through;
                    }
                    &.overdue {
                        font-weight: 600;
                    }
                }
                .item-icon {
                    display: none;
                    align-items: center;
                    justify-content: center;
                    width: 32px;
                    margin-right: 2px;
                    font-size: 16px;
                    color: #888888;
                }
            }
        }
    }
    .gantt-right {
        flex: 1;
        height: 100%;
        background-color: #ffffff;
        position: relative;
        overflow: hidden;
        .gantt-chart {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            transform: translateZ(0);
            .gantt-month {
                display: flex;
                align-items: center;
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                z-index: 1;
                height: 26px;
                line-height: 20px;
                font-size: 14px;
                background-color: #F9FAFB;
                > li {
                    flex-grow: 0;
                    flex-shrink: 0;
                    height: 100%;
                    position: relative;
                    overflow: hidden;
                    &:after {
                        content: "";
                        position: absolute;
                        top: 0;
                        right: 0;
                        width: 1px;
                        height: 100%;
                        background-color: rgba(237, 241, 242, 0.75);
                    }
                    .month-format {
                        overflow: hidden;
                        white-space: nowrap;
                        padding: 6px 6px 0;
                    }
                }
            }
            .gantt-date {
                display: flex;
                align-items: center;
                position: absolute;
                top: 26px;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 2;
                cursor: move;
                &:before {
                    content: "";
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    height: 50px;
                    background-color: #F9FAFB;
                }
                > li {
                    flex-grow: 0;
                    flex-shrink: 0;
                    height: 100%;
                    position: relative;
                    overflow: hidden;
                    &:after {
                        content: "";
                        position: absolute;
                        top: 0;
                        right: 0;
                        width: 1px;
                        height: 100%;
                        background-color: rgba(237, 241, 242, 0.75);
                    }
                    .date-format {
                        overflow: hidden;
                        white-space: nowrap;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        height: 44px;
                        .format-day {
                            line-height: 28px;
                            font-size: 18px;
                        }
                        .format-week {
                            line-height: 16px;
                            font-weight: 300;
                            font-size: 13px;
                        }
                    }
                }
            }
            .gantt-timeline {
                position: absolute;
                top: 76px;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 3;
                overflow-x: hidden;
                overflow-y: auto;
                > li {
                    cursor: default;
                    height: 40px;
                    border-bottom: 1px solid rgba(237, 241, 242, 0.75);
                    position: relative;
                    .timeline-item {
                        position: absolute;
                        top: 0;
                        touch-action: none;
                        pointer-events: auto;
                        padding: 4px;
                        margin-top: 4px;
                        background: #e74c3c;
                        border-radius: 18px;
                        color: #fff;
                        display: flex;
                        align-items: center;
                        will-change: contents;
                        height: 32px;
                        .timeline-title {
                            touch-action: none;
                            flex-grow: 1;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                            margin-left: 4px;
                            margin-right: 10px;
                        }
                        .timeline-resizer {
                            height: 22px;
                            touch-action: none;
                            width: 8px;
                            background: rgba(255,255,255,0.1);
                            cursor: ew-resize;
                            flex-shrink: 0;
                            will-change: visibility;
                            position: absolute;
                            top: 5px;
                            right: 5px;
                        }
                    }
                }
            }
        }
    }
}
</style>
