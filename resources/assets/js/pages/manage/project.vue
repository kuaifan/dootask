<template>
    <div class="page-project">
        <template v-if="projectId > 0">
            <ProjectPanel/>
            <ProjectDialog/>
        </template>
        <ProjectList v-if="windowPortrait" v-show="projectId === 0"/>
    </div>
</template>

<script>
import {mapState} from "vuex";
import ProjectPanel from "./components/ProjectPanel";
import ProjectDialog from "./components/ProjectDialog";
import ProjectList from "./components/ProjectList";
export default {
    components: {ProjectList, ProjectDialog, ProjectPanel},

    deactivated() {
        this.$store.dispatch("forgetTaskCompleteTemp", true);
    },

    computed: {
        ...mapState(['cacheProjects', 'wsOpenNum']),

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
            const projectId = this.projectId;
            this.$nextTick(() => {
                this.$store.state.projectId = projectId;
                this.$store.dispatch("getProjectOne", projectId).then(() => {
                    this.$store.dispatch("getColumns", projectId).catch(() => {});
                    this.$store.dispatch("getTaskForProject", projectId).catch(() => {})
                }).catch(({msg}) => {
                    if (projectId !== this.projectId) {
                        return;
                    }
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
