<template>
    <div class="page-project">
        <PageTitle>{{ $L('项目面板') }}</PageTitle>
        <ProjectList/>
        <ProjectDialog v-if="tablePanel('chat')"/>
    </div>
</template>

<script>
import {mapGetters} from "vuex";
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

    computed: {
        ...mapGetters(['tablePanel']),
    },

    watch: {
        '$route' (route) {
            this.project_id = route.params.id;
        },
        project_id(id) {
            if (id > 0) {
                setTimeout(() => {
                    this.$store.state.projectId = $A.runNum(id);
                    this.$store.dispatch("getProjectOne", id).then(() => {
                        this.$store.dispatch("getColumns", id);
                        this.$store.dispatch("getTasks", {project_id: id});
                    }).catch(({msg}) => {
                        $A.modalWarning({
                            content: msg,
                            onOk: () => {
                                const project = this.$store.state.projects.find(({id}) => id);
                                if (project) {
                                    $A.goForward({path: '/manage/project/' + project.id});
                                } else {
                                    $A.goForward({path: '/manage/dashboard'});
                                }
                            }
                        });
                    });
                });
            }
        }
    },
}
</script>
