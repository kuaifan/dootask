<template>
    <ETooltip v-if="visible" :disabled="$isEEUiApp || windowTouch || content == ''" :content="content">
        <svg v-if="type === 'svg'" viewBox="25 25 50 50" class="common-loading">
            <circle cx="50" cy="50" r="20" fill="none" stroke-width="5" stroke-miterlimit="10" class="common-path"></circle>
        </svg>
        <div v-else class="common-pureing"></div>
    </ETooltip>
</template>

<script>
    export default {
        name: 'Loading',
        props: {
            type: {
                type: String,
                default: 'svg'
            },
            content: {
                type: [String, Number],
                default: ''
            },
            delay: {
                type: Number,
                default: 0
            }
        },
        data() {
            return {
                visible: this.delay === 0,
                timer: null,
            }
        },
        mounted() {
            if (this.delay > 0) {
                this.timer = setTimeout(_ => {
                    this.visible = true
                }, this.delay)
            }
        },
        beforeDestroy() {
            this.timer && clearTimeout(this.timer)
        }
    }
</script>
