<template>
    <Modal
        v-model="visibleProxy"
        class-name="user-works-modal"
        :title="$L('项目与任务')"
        :footer-hide="true"
        width="560">

        <RadioGroup v-if="activeTab !== 'projects'" v-model="taskOwner" type="button" size="small" class="task-owner-filter" @on-change="onOwnerChange">
            <Radio label="all">{{$L('全部')}}</Radio>
            <Radio label="1">{{$L('负责')}}</Radio>
            <Radio label="0">{{$L('协作')}}</Radio>
        </RadioGroup>

        <Tabs v-model="activeTab" class="user-works-tabs">
            <!-- 项目 -->
            <TabPane :label="projectsTabLabel" name="projects">
                <div class="user-works-content">
                    <div v-if="projects.loading > 0 && projects.list.length === 0" class="loading-wrapper">
                        <Loading/>
                    </div>
                    <div v-else-if="projects.list.length === 0" class="empty-wrapper">
                        <div class="empty-content">
                            <Icon type="ios-folder-outline" size="48"/>
                            <p>{{$L('暂无项目')}}</p>
                        </div>
                    </div>
                    <div v-else class="works-list">
                        <div
                            v-for="project in projects.list"
                            :key="project.id"
                            class="works-item"
                            @click="onOpenProject(project)">
                            <div class="works-icon project">
                                <i class="taskfont">&#xe6f9;</i>
                            </div>
                            <div class="works-info">
                                <div class="works-name" v-html="transformEmojiToHtml(project.name)"></div>
                                <div class="works-meta">
                                    <span class="role-badge" :class="ownerClass(project.owner)">{{ownerText(project.owner)}}</span>
                                    <span class="works-stat">{{$L('任务')}} {{project.task_complete || 0}}/{{project.task_num || 0}}</span>
                                </div>
                            </div>
                            <Icon class="enter-icon" type="ios-arrow-forward"/>
                        </div>
                        <div v-if="projects.hasMore" class="load-more-wrapper">
                            <Button type="primary" @click="loadProjects(true)" :loading="projects.loading > 0">{{$L('加载更多')}}</Button>
                        </div>
                    </div>
                </div>
            </TabPane>
            <!-- 待办任务 / 已完成任务 -->
            <TabPane v-for="tab in taskTabs" :key="tab.key" :label="taskTabLabel(tab.key)" :name="tab.key">
                <div class="user-works-content tasks">
                    <div v-if="tabState(tab.key).loading > 0 && tabState(tab.key).list.length === 0" class="loading-wrapper">
                        <Loading/>
                    </div>
                    <div v-else-if="tabState(tab.key).list.length === 0" class="empty-wrapper">
                        <div class="empty-content">
                            <Icon type="ios-list-box-outline" size="48"/>
                            <p>{{$L('暂无任务')}}</p>
                        </div>
                    </div>
                    <div v-else class="works-list">
                        <div
                            v-for="task in tabState(tab.key).list"
                            :key="task.id"
                            class="works-item"
                            @click="onOpenTask(task)">
                            <div class="works-icon task" :class="{completed: !!task.complete_at}">
                                <i class="taskfont">&#xe6f4;</i>
                            </div>
                            <div class="works-info">
                                <div class="works-name" :class="{completed: !!task.complete_at}" v-html="transformEmojiToHtml(task.name)"></div>
                                <div class="works-meta">
                                    <span
                                        v-if="task.flow_item_name"
                                        class="flow-name"
                                        :class="task.flow_item_status"
                                        :style="$A.generateColorVarStyle(task.flow_item_color, [10], 'flow-item-custom-color')">{{task.flow_item_name}}</span>
                                    <span v-else-if="task.complete_at" class="flow-name end">{{$L('已完成')}}</span>
                                    <span class="role-badge" :class="task.owner ? 'owner' : 'assist'">{{task.owner ? $L('负责') : $L('协作')}}</span>
                                    <span v-if="task.project_name" class="works-project">{{task.project_name}}</span>
                                    <span v-if="task.end_at" class="works-time" :class="{overdue: task.overdue}">{{$A.timeFormat(task.end_at)}}</span>
                                </div>
                            </div>
                            <Icon class="enter-icon" type="ios-arrow-forward"/>
                        </div>
                        <div v-if="tabState(tab.key).hasMore" class="load-more-wrapper">
                            <Button type="primary" @click="loadTasks(tab.key, true)" :loading="tabState(tab.key).loading > 0">{{$L('加载更多')}}</Button>
                        </div>
                    </div>
                </div>
            </TabPane>
        </Tabs>
    </Modal>
</template>

<script>
import transformEmojiToHtml from "../../../utils/emoji";

const emptyState = () => ({list: [], page: 1, hasMore: false, loading: 0, total: null});

export default {
    name: 'UserWorksModal',

    props: {
        value: { // v-model
            type: Boolean,
            default: false,
        },
        targetUserId: {
            type: [Number, String],
            required: true,
        },
    },

    data() {
        return {
            activeTab: 'projects',
            taskOwner: 'all',
            taskTabs: [
                {key: 'todo', status: 'uncompleted'},
                {key: 'done', status: 'completed'},
            ],
            counts: {project: null, todo: null, done: null},
            projects: emptyState(),
            todo: emptyState(),
            done: emptyState(),
        }
    },

    computed: {
        visibleProxy: {
            get() { return this.value; },
            set(v) { this.$emit('input', v); }
        },

        projectsTabLabel() {
            return this.withCount(this.$L('项目'), this.counts.project);
        }
    },

    watch: {
        visibleProxy(val) {
            if (val) {
                this.loadInitial();
            }
        },
        activeTab(key) {
            // 切换到尚未加载的任务 Tab 时才加载（懒加载）
            if (key !== 'projects' && this.tabState(key).list.length === 0 && this.tabState(key).loading === 0) {
                this.loadTasks(key, false);
            }
        },
        targetUserId() {
            this.resetState();
            if (this.visibleProxy) {
                this.loadInitial();
            }
        }
    },

    methods: {
        transformEmojiToHtml,

        tabState(key) {
            return this[key];
        },

        withCount(text, total) {
            return total == null ? text : `${text} (${total})`;
        },

        taskTabLabel(key) {
            const text = key === 'todo' ? this.$L('待办') : this.$L('已完成');
            return this.withCount(text, this.counts[key]);
        },

        resetState() {
            this.counts = {project: null, todo: null, done: null};
            this.projects = emptyState();
            this.todo = emptyState();
            this.done = emptyState();
        },

        // 打开时：拉取轻量计数（用于 Tab 角标）+ 仅加载当前激活 Tab 的列表，其余 Tab 首次激活再加载
        loadInitial() {
            this.loadCounts();
            this.loadActive();
        },

        loadActive() {
            if (this.activeTab === 'projects') {
                if (this.projects.list.length === 0 && this.projects.loading === 0) {
                    this.loadProjects(false);
                }
            } else {
                const state = this.tabState(this.activeTab);
                if (state.list.length === 0 && state.loading === 0) {
                    this.loadTasks(this.activeTab, false);
                }
            }
        },

        loadCounts() {
            if (!this.targetUserId) return;
            const params = {userid: this.targetUserId};
            if (this.taskOwner !== 'all') {
                params.owner = this.taskOwner;
            }
            this.$store.dispatch('call', {
                url: 'project/user/counts',
                data: params,
            }).then(({data}) => {
                this.counts = {
                    project: typeof data.project === 'number' ? data.project : 0,
                    todo: typeof data.todo === 'number' ? data.todo : 0,
                    done: typeof data.done === 'number' ? data.done : 0,
                };
            }).catch(() => {
                // 计数失败不打断列表展示，静默处理
            });
        },

        loadProjects(loadMore = false) {
            if (!this.targetUserId) return;
            this.projects.loading++;
            const page = loadMore ? this.projects.page + 1 : 1;
            this.$store.dispatch('call', {
                url: 'project/user/projects',
                data: {
                    userid: this.targetUserId,
                    page,
                }
            }).then(({data}) => {
                const list = Array.isArray(data.data) ? data.data : [];
                this.projects.list = loadMore ? [...this.projects.list, ...list] : list;
                this.projects.page = data.current_page || page;
                this.projects.hasMore = !!data.next_page_url;
                this.projects.total = typeof data.total === 'number' ? data.total : list.length;
                this.counts.project = this.projects.total;
            }).catch(({msg}) => {
                $A.modalError(msg || this.$L('加载失败'));
            }).finally(() => {
                this.projects.loading--;
            });
        },

        loadTasks(key, loadMore = false) {
            if (!this.targetUserId) return;
            const state = this.tabState(key);
            const tab = this.taskTabs.find(t => t.key === key);
            state.loading++;
            const page = loadMore ? state.page + 1 : 1;
            const params = {
                userid: this.targetUserId,
                page,
                keys: {status: tab.status},
            };
            if (this.taskOwner !== 'all') {
                params.owner = this.taskOwner;
            }
            this.$store.dispatch('call', {
                url: 'project/user/tasks',
                data: params,
            }).then(({data}) => {
                const list = (Array.isArray(data.data) ? data.data : []).map(t => this.normalizeFlow(t));
                state.list = loadMore ? [...state.list, ...list] : list;
                state.page = data.current_page || page;
                state.hasMore = !!data.next_page_url;
                state.total = typeof data.total === 'number' ? data.total : list.length;
                this.counts[key] = state.total;
            }).catch(({msg}) => {
                $A.modalError(msg || this.$L('加载失败'));
            }).finally(() => {
                state.loading--;
            });
        },

        onOwnerChange() {
            // 负责/协作筛选影响任务数量：重置两个任务 Tab 列表、刷新计数，仅重新加载当前激活的任务 Tab，另一个首次激活再加载
            this.taskTabs.forEach(tab => {
                this[tab.key] = emptyState();
            });
            this.loadCounts();
            this.loadActive();
        },

        // 解析工作流状态（flow_item_name 原始格式为 status|name|color）
        normalizeFlow(task) {
            if (task.flow_item_name && task.flow_item_name.indexOf('|') !== -1) {
                const info = $A.convertWorkflow(task.flow_item_name);
                task.flow_item_status = info.status;
                task.flow_item_name = info.name;
                task.flow_item_color = info.color;
            }
            return task;
        },

        ownerText(owner) {
            if (owner === 1) return this.$L('负责人');
            if (owner === 2) return this.$L('管理员');
            return this.$L('成员');
        },

        ownerClass(owner) {
            if (owner === 1) return 'owner';
            if (owner === 2) return 'deputy';
            return 'member';
        },

        onOpenProject(project) {
            $A.goForward({name: 'manage-project', params: {projectId: project.id}});
            this.$emit('navigate');
        },

        onOpenTask(task) {
            // 打开任务在全局任务窗口中展示，保持本弹窗不关闭
            this.$store.dispatch('openTask', task);
        },
    }
}
</script>

<style scoped>
/* 组件自身不引入额外样式，复用全局样式类名 */
</style>
