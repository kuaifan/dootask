<template>
    <ETooltip
        :content="tipText"
        :placement="placement"
        :effect="tooltipTheme"
        :delay="delay"
        :disabled="!showTooltip || disabled"
        :max-width="tooltipMaxWidth"
        transfer>
        <span ref="content" @mouseenter="handleTooltipIn" class="common-auto-tip" @click="onClick">
            <template v-if="existSlot"><slot/></template>
            <template v-else>{{content}}</template>
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
            disabled: {
                type: Boolean,
                default: false
            },
        },

        data() {
            return {
                showTooltip: false,  // 鼠标滑过overflow文本时，再检查是否需要显示
                tooltipContent: '',
            }
        },

        computed: {
            tipText() {
                const {content, tooltipContent} = this;
                return content || tooltipContent || "";
            },
            existSlot() {
                return !(typeof this.$slots.default === 'undefined' || this.$slots.default.length < 1);
            },
        },

        methods: {
            handleTooltipIn () {
                const $content = this.$refs.content;
                let range = document.createRange();
                range.setStart($content, 0);
                range.setEnd($content, $content.childNodes.length);
                const rangeWidth = range.getBoundingClientRect().width;
                this.showTooltip = Math.floor(rangeWidth) > Math.floor($content.offsetWidth);
                if (this.showTooltip && this.existSlot) {
                    const tmpArray = this.$slots.default.map((e) => {
                        if (e.text) return e.text
                        if (e.elm.innerText) return e.elm.innerText
                        return ""
                    })
                    this.tooltipContent = tmpArray.join("");
                }
                range = null;
            },
            onClick(e) {
                this.$emit("on-click", e)
            }
        }
    }
</script>
