<template>
    <div class="project-detail">
        <PageTitle>{{ $L('项目面板') }}</PageTitle>
        <Loading/>
        project-detail
    </div>
</template>

<style lang="scss" scoped>
:global {
    .project-detail {
        background-image: url("../../../statics/images/loading.png");
    }
}
</style>

<script>
export default {
    data() {
        return {}
    },
    mounted() {

    },
}
</script>
