<template>
    <div class="page-project">
        <ProjectMenu v-if="!windowPortrait" :projectId="projId"/>
        <template v-if="projId > 0">
            <ProjectPanel/>
            <ProjectDialog/>
        </template>
        <div v-else-if="!windowPortrait" class="page-project-empty">
            <div><i class="taskfont">&#xe6f9;</i></div>
            <span>{{ $L('选择一个项目查看更多任务') }}</span>
        </div>
        <ProjectList v-if="windowPortrait" v-show="projId === 0"/>
    </div>
</template>

<script>
import {mapState} from "vuex";
import ProjectPanel from "./components/ProjectPanel";
import ProjectDialog from "./components/ProjectDialog";
import ProjectList from "./components/ProjectList";
import ProjectMenu from "./components/ProjectMenu";
export default {
    components: {ProjectList, ProjectMenu, ProjectDialog, ProjectPanel},

    deactivated() {
        this.$store.dispatch("forgetTaskCompleteTemp", true);
    },

    computed: {
        ...mapState(['cacheProjects', 'wsOpenNum', 'projectId']),

        projId() {
            const {projectId} = this.$route.params;
            if (!this.windowPortrait){
                return parseInt(/^\d+$/.test(projectId) ? projectId : 0) || this.projectId || 0;
            }
            return parseInt(/^\d+$/.test(projectId) ? projectId : 0) || 0;
        }
    },

    watch: {
        projId: {
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
            if (this.projId <= 0) return;
            const projId = this.projId;
            this.$nextTick(() => {
                this.$store.state.projectId = projId;
                this.$store.dispatch("getProjectOne", projId).then(() => {
                    this.$store.dispatch("getColumns", projId).catch(() => {});
                    this.$store.dispatch("getTaskForProject", projId).catch(() => {})
                }).catch(({msg}) => {
                    if (projId !== this.projId) {
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
