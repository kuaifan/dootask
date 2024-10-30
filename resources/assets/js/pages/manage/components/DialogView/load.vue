<template>
    <div class="content-loading">
        <Icon v-if="error === true" type="ios-alert-outline" />
        <Loading v-else/>
    </div>
</template>

<script>
export default {
    props: {
        error: Boolean,
    },
}
</script>
