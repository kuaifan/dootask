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

    deactivated() {
        this.$store.dispatch("forgetTaskCompleteTemp", true);
    },

    computed: {
        ...mapState(['cacheProjects', 'wsOpenNum']),
        ...mapGetters(['projectParameter']),

        projectId() {
            const {projectId} = this.$route.params;
            return parseInt(/^\d+$/.test(projectId) ? projectId : 0);
        }
    },

    watch: {
        projectId: {
            handler() {
                this.getProjectData();
            },
            immediate: true
        },

        wsOpenNum(num) {
            if (num <= 1) return
            this.wsOpenTimeout && clearTimeout(this.wsOpenTimeout)
            this.wsOpenTimeout = setTimeout(() => {
                this.$route.name == 'manage-project' && this.getProjectData();
            }, 5000)
        }
    },

    methods: {
        getProjectData() {
            if (this.projectId <= 0) return;
            setTimeout(() => {
                this.$store.state.projectId = $A.runNum(this.projectId);
                this.$store.dispatch("getProjectOne", this.projectId).then(() => {
                    this.$store.dispatch("getColumns", this.projectId).catch(() => {});
                    this.$store.dispatch("getTaskForProject", this.projectId).catch(() => {})
                }).catch(({msg}) => {
                    $A.modalWarning({
                        content: msg,
                        onOk: () => {
                            const project = this.cacheProjects.find(({id}) => id);
                            if (project) {
                                $A.goForward({name: 'manage-project', params: {projectId: project.id}});
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
