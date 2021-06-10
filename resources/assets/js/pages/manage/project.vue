<template>
    <div class="page-project">
        <PageTitle>{{ $L('项目面板') }}</PageTitle>
        <ProjectList/>
        <ProjectDialog v-if="$store.state.projectChatShow"/>
    </div>
</template>

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
