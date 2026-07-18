/**
 * 仪表盘截止时间人性化显示（个人视角 / 团队概览共用）
 */
export default {
    data() {
        return {
            nowTime: $A.dayjs().unix(),
            nowInter: null,
        }
    },

    methods: {
        /**
         * 截止时间状态 class
         * @param endAt
         * @returns {string} end-overdue|end-today|''
         */
        deadlineClass(endAt) {
            if (!endAt) {
                return ''
            }
            const end = $A.dayjs(endAt)
            const now = $A.daytz(this.nowTime)
            if (end <= now) {
                return 'end-overdue'
            }
            if (end.format('YYYY-MM-DD') === now.format('YYYY-MM-DD')) {
                return 'end-today'
            }
            return ''
        },

        /**
         * 截止时间文案（逾期 N 天 / 今天 HH:mm / 明天 HH:mm / MM-DD）
         * @param endAt
         * @returns {string}
         */
        deadlineText(endAt) {
            if (!endAt) {
                return ''
            }
            const end = $A.dayjs(endAt)
            const now = $A.daytz(this.nowTime)
            if (end <= now) {
                const days = now.startOf('day').diff(end.startOf('day'), 'day')
                if (days > 0) {
                    return this.$L('逾期 (*) 天', days)
                }
                return this.$L('逾期 (*) 小时', Math.max(now.diff(end, 'hour'), 1))
            }
            if (end.format('YYYY-MM-DD') === now.format('YYYY-MM-DD')) {
                return `${this.$L('今天')} ${end.format('HH:mm')}`
            }
            if (end.format('YYYY-MM-DD') === now.clone().add(1, 'day').format('YYYY-MM-DD')) {
                return `${this.$L('明天')} ${end.format('HH:mm')}`
            }
            if (end.year() === now.year()) {
                return end.format('MM-DD')
            }
            return end.format('YYYY-MM-DD')
        },

        /**
         * 时间刷新定时器（驱动 deadline 文案更新）
         * @param load
         */
        loadInterval(load) {
            if (this.nowInter) {
                clearInterval(this.nowInter)
                this.nowInter = null
            }
            if (load === false) {
                return
            }
            this.nowInter = setInterval(_ => {
                this.nowTime = $A.dayjs().unix()
            }, 10 * 1000)
        },
    }
}
