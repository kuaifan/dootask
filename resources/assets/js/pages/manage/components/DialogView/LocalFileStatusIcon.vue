<template>
    <span class="local-file-status-icon" aria-hidden="true">
        <Loading v-if="status === 'downloading'"/>
        <svg
            v-else-if="status === 'available'"
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
                d="M3.5 16V6A2 2 0 0 1 5.5 4H8.2A2 2 0 0 1 9.6 4.6L11 6H18.5A2 2 0 0 1 20.5 8V9"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"/>
            <path
                d="M5.2 19.5H17.7A2 2 0 0 0 19.6 18.1L21.2 12.8A1.5 1.5 0 0 0 19.8 10.8H8.3A2 2 0 0 0 6.4 12.2L3.6 17A1.7 1.7 0 0 0 5.2 19.5Z"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"/>
        </svg>
        <svg
            v-else
            width="18"
            height="18"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
                d="M12 3V15M7.5 10.5L12 15L16.5 10.5M5 20H19"
                stroke="currentColor"
                stroke-width="1.8"
                stroke-linecap="round"
                stroke-linejoin="round"/>
        </svg>
    </span>
</template>

<script>
export default {
    name: "LocalFileStatusIcon",
    props: {
        status: {
            type: String,
            default: 'missing',
        },
    },
}
</script>

<style lang="scss" scoped>
.local-file-status-icon {
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: none;

    .common-loading {
        width: 16px;
        height: 16px;
    }
}
</style>
