<template>
    <div class="page-project">
        <transition
            :enter-active-class="panelTransition.enter"
            :leave-active-class="panelLeaveClass"
            @enter="onPanelCover"
            @leave="onPanelCover"
            @after-enter="onPanelEntered"
            @enter-cancelled="onPanelEntered"
            @after-leave="onPanelLeft"
            @leave-cancelled="onPanelLeft">
            <ProjectPanel v-if="projectId > 0" ref="panel"/>
        </transition>
        <ProjectList v-if="windowPortrait" v-show="projectId === 0 || listStay"/>
    </div>
</template>

<script>
import {mapState} from "vuex";
import ProjectPanel from "./components/ProjectPanel";
import ProjectList from "./components/ProjectList";
export default {
    components: {ProjectList, ProjectPanel},

    data() {
        return {
            panelTransition: {enter: '', leave: ''},
            listStay: false,    // 详情划入期间保持列表显示
        }
    },

    deactivated() {
        this.$store.dispatch("forgetTaskCompleteTemp", true);
    },

    computed: {
        ...mapState(['cacheProjects', 'wsOpenNum']),

        /**
         * 划出类名须在详情渲染时即确定（Vue 离开动画读取的是元素上一次渲染时的类名）
         * 非返回列表的离开（切到其它页面）此时页面已被 keep-alive 移出，动画不可见
         * @returns {string}
         */
        panelLeaveClass() {
            return this.$isEEUIApp && this.windowPortrait ? 'mobile-dialog-leave-active' : ''
        },

        projectId() {
            const {projectId} = this.$route.params;
            return parseInt(/^\d+$/.test(projectId) ? projectId : 0);
        }
    },

    watch: {
        // 须在 projectId 之前定义：先确定动画再处理项目数据
        '$route'(to, from) {
            this.updatePanelTransition(to, from)
        },

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
                this.routeName == 'manage-project' && this.getProjectData();
            }, 5000)
        }
    },

    methods: {
        /**
         * 移动端项目列表与详情切换动画（同一页面组件，路由动画不会触发）
         * @param to
         * @param from
         */
        updatePanelTransition(to, from) {
            const isList = route => route.name === 'manage-project' && !/^\d+$/.test(route.params.projectId)
            const isPanel = route => route.name === 'manage-project' && /^\d+$/.test(route.params.projectId)
            this.listStay = false
            this.panelTransition = {enter: '', leave: ''}
            if (this.$isEEUIApp && this.windowPortrait) {
                if (isList(from) && isPanel(to)) {
                    this.listStay = true
                    this.panelTransition.enter = 'mobile-dialog-enter-active'
                } else if (isPanel(from) && isList(to)) {
                    this.panelTransition.leave = 'mobile-dialog-leave-active'
                }
            }
        },

        onPanelCover(el) {
            if (this.panelTransition.enter || this.panelTransition.leave) {
                // 动画期间详情固定覆盖于列表及底部选项卡之上
                // fixed 的参照可能不是视口（祖先 will-change: transform），按实际位置换算 top
                el.setAttribute('data-panel-cover', '')
                el.style.top = `${this.$el.getBoundingClientRect().top - el.getBoundingClientRect().top}px`
            }
        },

        releasePanelCover(el) {
            el.style.top = ''
            el.removeAttribute('data-panel-cover')
        },

        onPanelEntered(el) {
            this.releasePanelCover(el)
            this.listStay = false
            this.$store.state.mobileTabbarHold = false
        },

        onPanelLeft(el) {
            this.releasePanelCover(el)
            if (this.projectId <= 0) {
                this.$store.state.projectId = 0;
            }
        },

        getProjectData() {
            if (this.projectId <= 0) {
                // 详情划出期间保留项目数据，动画结束后再清除
                if (!this.panelTransition.leave) {
                    this.$store.state.projectId = 0;
                }
                return;
            }
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
