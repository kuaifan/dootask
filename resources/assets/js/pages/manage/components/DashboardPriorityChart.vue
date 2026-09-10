<template>
    <div class="dashboard-priority-chart">
        <div ref="chart" class="priority-chart-canvas" :aria-label="$L('优先级分布')"/>
        <div class="priority-chart-total">
            <strong :class="{'total-long': String(total).length > 5}">{{total}}</strong>
            <span>{{$L('未完成任务')}}</span>
        </div>
    </div>
</template>

<script>
import {init, use} from 'echarts/core';
import {PieChart} from 'echarts/charts';
import {CanvasRenderer} from 'echarts/renderers';
import {TooltipComponent} from 'echarts/components';

use([PieChart, CanvasRenderer, TooltipComponent]);

export default {
    props: {
        items: {type: Array, default: () => []},
        total: {type: Number, default: 0},
    },
    watch: {
        items: {deep: true, handler() { this.renderChart() }},
    },
    mounted() {
        this.chart = init(this.$refs.chart)
        this.chart.on('click', params => {
            const item = this.items[params.dataIndex]
            if (item) this.$emit('select', item)
        })
        this.renderChart()
    },
    beforeDestroy() {
        if (this.chart) this.chart.dispose()
    },
    methods: {
        renderChart() {
            if (!this.chart) return
            this.chart.setOption({
                tooltip: {trigger: 'item', confine: true},
                series: [{
                    type: 'pie',
                    radius: [70, 90],
                    center: ['50%', '50%'],
                    startAngle: 180,
                    minAngle: 3,
                    padAngle: 2.72,
                    label: {show: false},
                    labelLine: {show: false},
                    stillShowZeroSum: false,
                    emptyCircleStyle: {color: '#e9ebef'},
                    itemStyle: {borderWidth: 0, borderRadius: 4, shadowBlur: 0},
                    emphasis: {scaleSize: 10, itemStyle: {borderWidth: 0, shadowBlur: 0}},
                    data: this.items.map(item => ({
                        name: item.name,
                        value: item.num,
                        itemStyle: {color: item.color || '#bbc2db', opacity: 0.55},
                    })),
                }],
            })
        },
    },
}
</script>

<style scoped>
.dashboard-priority-chart {
    position: relative;
    width: 180px;
    height: 180px;
    flex: 0 0 180px;
}
.priority-chart-canvas {
    /* Reserve canvas space for the 10px emphasis expansion. */
    position: absolute;
    top: -12px;
    left: -12px;
    width: 204px;
    height: 204px;
}
.priority-chart-total {
    position: absolute;
    inset: 36px 24px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    pointer-events: none;
    color: #666;
    font-size: 14px;
    text-align: center;
}
.priority-chart-total strong {
    max-width: 100%;
    font-size: 40px;
    line-height: 1.2;
    color: #2a2b2d;
    overflow-wrap: anywhere;
}
.priority-chart-total .total-long {
    font-size: 24px;
}
</style>
