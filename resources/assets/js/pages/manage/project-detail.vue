<template>
    <div class="project-detail">
        <PageTitle>{{ $L('项目面板') }}</PageTitle>
        <ProjectList/>
        <ProjectMessage/>
    </div>
</template>

<style lang="scss" scoped>
:global {
    .project-detail {
        display: flex;
        align-items: flex-start;
        .project-list {
            flex: 1;
            height: 100%;
        }
        .project-message {
            position: relative;
            height: 100%;
            width: 40%;
            max-width: 410px;
            &:before {
                content: "";
                position: absolute;
                top: 0;
                left: 0;
                height: 100%;
                width: 1px;
                background-color: #f2f2f2;
            }
        }
    }
}
</style>

<script>
import ProjectList from "./components/project-list";
import ProjectMessage from "./components/project-message";
export default {
    components: {ProjectMessage, ProjectList},
    data() {
        return {

        }
    },
    mounted() {

    },
}
</script>
