<template>
    <div class="content-loading">
        <Icon v-if="error === true" type="ios-alert-outline" />
        <Loading v-else/>
    </div>
</template>

<script lang="ts">
export default {
    props: {
        error: Boolean,
    },
}
</script>
