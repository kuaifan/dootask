<template>
    <div class="project">
        <PageTitle>{{ $L('项目面板') }}</PageTitle>
        <ProjectList/>
        <ProjectDialog v-if="$store.state.projectChatShow"/>
    </div>
</template>

<style lang="scss" scoped>
:global {
    .project {
        display: flex;
        align-items: flex-start;
        .project-list {
            flex: 1;
            width: 0;
            height: 100%;
            background-color: #fafafa;
        }
        .project-dialog {
            position: relative;
            height: 100%;
            width: 35%;
            min-width: 320px;
            max-width: 520px;
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
import ProjectList from "./components/ProjectList";
import ProjectDialog from "./components/ProjectDialog";
export default {
    components: {ProjectDialog, ProjectList},
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
