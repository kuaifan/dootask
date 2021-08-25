<template>
    <ETooltip :disabled="content == ''" :content="content">
        <svg viewBox="25 25 50 50" class="common-loading">
            <circle cx="50" cy="50" r="20" fill="none" stroke-width="5" stroke-miterlimit="10" class="common-path"></circle>
        </svg>
    </ETooltip>
</template>

<script>
    export default {
        name: 'Loading',
        props: {
            content: {
                type: [String, Number],
                default: ''
            },
        },
    }
</script>
