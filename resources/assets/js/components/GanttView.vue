<template>
    <div class="common-gantt">
        <div class="gantt-left" :style="leftStyle">
            <div class="gantt-title">
                <div class="gantt-title-text">{{$L('任务名称')}}</div>
                <div class="gantt-title-right"><slot name="titleTool"/></div>
            </div>
            <ul ref="ganttItem"
                class="gantt-item"
                @scroll="itemScrollListener"
                @mouseenter="mouseType='item'">
                <li v-for="(item, key) in lists" :key="key" @click="clickItem(item, key)">
                    <div v-if="item.overdue" class="item-overdue">{{$L('已超期')}}</div>
                    <div class="item-title" :class="{complete:item.complete, overdue:item.overdue}">{{item.label}}</div>
                    <Icon class="item-icon" type="ios-locate-outline" @click.stop="scrollPosition(key)"/>
                </li>
            </ul>
        </div>
        <div ref="ganttRight" class="gantt-right">
            <div class="gantt-size" @click="maximize=!maximize">
                <i v-if="maximize" class="taskfont">&#xe7d4;</i>
                <i v-else class="taskfont">&#xe7d3;</i>
            </div>
            <div
                ref="ganttChart"
                class="gantt-chart"
                @touchstart="dateTouchstart"
                @touchmove="dateTouchmove"
                @touchend="dateTouchend">
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
                    <li v-for="(item, key) in lists" :key="key" :data-id="item.id">
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

            dateMove: null,

            maximize: false,
        }
    },
    mounted() {
        this.maximize = this.windowPortrait;
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
        },
        maximize() {
            this.$nextTick(() => {
                this.handleResize();
            })
        }
    },
    computed: {
        leftStyle({menuWidth, maximize}) {
            const style = {width: menuWidth + 'px'}
            if (maximize) {
                style.display = 'none';
            }
            return style
        },
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
        },
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
        dateTouchstart(e) {
            if (this.windowPortrait) {
                this.maximize = true
            }
            let parent = e.target.parentNode
            let item = null
            while (parent) {
                if (!parent || parent === this.$refs.ganttChart) {
                    break
                }
                if (parent.tagName === 'LI') {
                    const itemId = parent.getAttribute('data-id')
                    if (itemId) {
                        item = this.lists.find(({id}) =>  itemId == id)
                    }
                }
                parent = parent.parentNode
            }
            if (!item) {
                this.onDateMove(e.touches[0].clientX);
                return
            }
            this.onItemMove(item, e.target, e.touches[0].clientX);
        },
        dateTouchmove(e) {
            this.onMoving(e.touches[0].clientX)
        },
        dateTouchend() {
            this.onMoveOver(null);
        },
        dateMouseDown(e) {
            e.preventDefault();
            this.onDateMove(e.clientX);
        },
        itemMouseDown(e, item) {
            e.preventDefault();
            this.onItemMove(item, e.target, e.clientX);
        },
        itemMouseMove(e) {
            if (this.mouseItem != null || this.dateMove != null) {
                e.preventDefault();
                this.onMoving(e.clientX);
            }
        },
        itemMouseUp(e) {
            this.onMoveOver(e.target);
        },
        onDateMove(clientX) {
            this.mouseItem = null;
            this.dateMove = {
                clientX
            };
        },
        onItemMove(item, target, clientX) {
            let type = 'moveX';
            if (target.classList.contains('timeline-resizer')) {
                type = 'moveW';
            }
            if (typeof item[type] !== "number") {
                this.$set(item, type, 0);
            }
            this.mouseBak = {
                type: type,
                clientX: clientX,
                value: item[type],
            };
            this.mouseItem = item;
            this.dateMove = null;
        },
        onMoving(clientX) {
            if (this.mouseItem != null) {
                const diff = this.mouseBak.value + (clientX - this.mouseBak.clientX);
                if (this.mouseBak.type === 'moveW') {
                    const oneWidthTime = 86400000 / this.dateWidth;
                    const {start, end} = this.mouseItem.time;
                    let moveTime = diff * oneWidthTime;
                    if (end + moveTime - start <= 0) {
                        return
                    }
                }
                this.$set(this.mouseItem, this.mouseBak.type, diff);
                return;
            }
            if (this.dateMove != null) {
                let moveX = (this.dateMove.clientX - clientX) * 5;
                this.dateMove.clientX = clientX;
                this.mouseWidth+= moveX;
                this.mouseScaleWidth+= moveX * (100 / this.dateWidth);
            }
        },
        onMoveOver(target) {
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
                } else if (target && target.className == 'timeline-title') {
                    this.clickItem(this.mouseItem);
                }
                this.mouseItem = null;
                return
            }
            if (this.dateMove != null) {
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
        clickItem(item, key = undefined) {
            if (key !== undefined && this.windowPortrait) {
                this.scrollPosition(key)
                return
            }
            this.$emit("on-click", item)
        }
    }
}
</script>
