<template>
    <ETooltip :disabled="windowSmall || $isEEUiApp || content == ''" :content="content">
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
        },
    }
</script>
