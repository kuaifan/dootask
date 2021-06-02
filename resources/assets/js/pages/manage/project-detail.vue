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
            width: 0;
            height: 100%;
            background-color: #fafafa;
        }
        .project-message {
            position: relative;
            height: 100%;
            width: 40%;
            max-width: 410px;
            flex-shrink: 0;
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
            project_id: 0,
        }
    },
    mounted() {
        this.project_id = this.$route.params.id;
    },
    watch: {
        '$route' (route) {
            this.project_id = route.params.id;
        },
        project_id(id) {
            this.$store.commit('getProjectDetail', id);
        }
    },
}
</script>
