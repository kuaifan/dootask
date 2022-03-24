<template>
    <div class="page-project">
        <ProjectList/>
        <ProjectDialog v-if="projectParameter('chat')"/>
    </div>
</template>

<script>
import {mapState, mapGetters} from "vuex";
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
        this.project_id = $A.runNum(this.$route.params.id);
    },

    deactivated() {
        this.$store.dispatch("forgetTaskCompleteTemp", true);
    },

    computed: {
        ...mapState(['cacheProjects', 'wsOpenNum']),
        ...mapGetters(['projectParameter']),
    },

    watch: {
        '$route' ({params}) {
            this.project_id = $A.runNum(params.id);
        },

        project_id() {
            this.getProjectData();
        },

        wsOpenNum(num) {
            if (num <= 1) return
            this.wsOpenTimeout && clearTimeout(this.wsOpenTimeout)
            this.wsOpenTimeout = setTimeout(() => {
                if (this.$route.name == 'manage-project') {
                    this.getProjectData();
                }
            }, 5000)
        }
    },

    methods: {
        getProjectData() {
            let id = this.project_id;
            if (id <= 0) return;
            setTimeout(() => {
                this.$store.state.projectId = $A.runNum(id);
                this.$store.dispatch("getProjectOne", id).then(() => {
                    this.$store.dispatch("getColumns", id).catch(() => {});
                    this.$store.dispatch("getTaskForProject", id).catch(() => {})
                }).catch(({msg}) => {
                    $A.modalWarning({
                        content: msg,
                        onOk: () => {
                            const project = this.cacheProjects.find(({id}) => id);
                            if (project) {
                                $A.goForward({path: '/manage/project/' + project.id});
                            } else {
                                $A.goForward({name: 'manage-dashboard'});
                            }
                        }
                    });
                });
                this.$store.dispatch("forgetTaskCompleteTemp", true);
            });
        }
    }
}
</script>
