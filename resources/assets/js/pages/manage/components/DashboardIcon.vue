<template>
    <img class="dashboard-icon" :src="$A.mainUrl(`images/dashboard/${name}.svg`)" :width="size" :height="size" alt="" aria-hidden="true"/>
</template>

<script>
export default {
    props: {
        name: {type: String, required: true},
        size: {type: Number, default: 14},
    },
}
</script>

<style scoped>
.dashboard-icon {
    display: inline-block;
    flex-shrink: 0;
    object-fit: contain;
    vertical-align: middle;
}
</style>
