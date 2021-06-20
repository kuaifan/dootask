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
    watch: {
        '$route': {
            handler(route) {
                this.project_id = route.params.id;
            },
            immediate: true
        },
        project_id(id) {
            this.$store.state.projectId = id;
            this.$store.dispatch("getProjectOne", id);
        }
    },
}
</script>
