<template>
    <ETooltip
        :content="text"
        :placement="placement"
        :theme="tooltipTheme"
        :delay="delay"
        :disabled="!showTooltip"
        :max-width="tooltipMaxWidth"
        transfer>
        <span ref="content" @mouseenter="handleTooltipIn" class="common-auto-tip" @click="onClick">
            <template v-if="existSlot"><slot/></template>
            <template v-else>{{text}}</template>
        </span>
    </ETooltip>
</template>

<script>
    export default {
        name: 'AutoTip',
        props: {
            content: {
                type: [String, Number],
                default: ''
            },
            placement: {
                default: 'bottom'
            },
            tooltipTheme: {
                default: 'dark'
            },
            tooltipMaxWidth: {
                type: [String, Number],
                default: 300
            },
            delay: {
                type: Number,
                default: 100
            },
        },

        data() {
            return {
                slotText: '',
                showTooltip: false  // 鼠标滑过overflow文本时，再检查是否需要显示
            }
        },

        mounted () {
            this.updateConetne()
        },

        beforeUpdate () {
            this.updateConetne()
        },

        activated() {
            this.updateConetne()
        },

        computed: {
            text() {
                const {content, slotText} = this;
                if (content) {
                    return content;
                }
                if (typeof slotText === 'undefined' || slotText.length < 1 || typeof slotText[0].text !== 'string') {
                    return '';
                }
                return slotText[0].text;
            },
            existSlot() {
                const {slotText} = this;
                return !(typeof slotText === 'undefined' || slotText.length < 1);
            },
        },

        methods: {
            updateConetne () {
                this.slotText = this.$slots.default;
            },
            handleTooltipIn () {
                const $content = this.$refs.content;
                let range = document.createRange();
                range.setStart($content, 0);
                range.setEnd($content, $content.childNodes.length);
                const rangeWidth = range.getBoundingClientRect().width;
                this.showTooltip = Math.floor(rangeWidth) > Math.floor($content.offsetWidth);
                range = null;
            },
            onClick(e) {
                this.$emit("on-click", e)
            }
        }
    }
</script>
