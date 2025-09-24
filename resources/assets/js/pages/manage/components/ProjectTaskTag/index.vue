
<template>
    <div class="project-task-template">
        <div class="header">
            <div class="title">
                {{$L('任务标签')}}
                <Loading v-if="loadIng > 0"/>
                <template v-else-if="tags.length > 0">({{tags.length}})</template>
            </div>
            <div class="actions">
                <Button
                    v-if="canSortTags && tags.length"
                    :type="sortMode ? 'primary' : 'default'"
                    :loading="sortLoading"
                    icon="md-move"
                    @click="toggleSortMode">
                    {{$L(sortMode ? '完成排序' : '调整排序')}}
                </Button>
                <Button type="primary" icon="md-add" @click="handleAdd(null)">
                    {{$L('新建标签')}}
                </Button>
            </div>
        </div>

        <div class="content">
            <div v-if="!tags.length" class="empty">
                <div class="empty-text">{{$L('当前项目暂无任务标签')}}</div>
                <Button type="primary" icon="md-add" @click="handleAdd(null)">{{$L('新建标签')}}</Button>
            </div>
            <Draggable
                v-else
                class="template-list"
                tag="div"
                :list="tags"
                :animation="150"
                :disabled="!canSortTags || !sortMode || sortLoading"
                item-key="id"
                handle=".tag-drag-handle"
                @end="handleSortEnd">
                <div
                    v-for="item in tags"
                    :key="item.id"
                    class="tag-item"
                    :class="{'is-sorting': sortMode && canSortTags}">
                    <div
                        v-if="sortMode && canSortTags"
                        class="tag-drag-handle"
                        :title="$L('拖拽调整排序')">
                        <Icon type="md-menu" />
                    </div>
                    <div class="tag-contents">
                        <div class="tag-title">
                            <Tags :tags="item"/>
                        </div>
                        <div v-if="item.desc" class="tag-desc">{{ item.desc }}</div>
                    </div>
                    <div class="tag-actions">
                        <div
                            v-if="item.userid === userId || isProjectOwner"
                            class="tag-actions-btns">
                            <Button :disabled="sortMode" @click="handleAdd(item)" type="primary">
                                {{$L('编辑')}}
                            </Button>
                            <Button :disabled="sortMode" @click="handleDelete(item)" type="error">
                                {{$L('删除')}}
                            </Button>
                        </div>
                        <div class="tag-actions-owner">
                            <UserAvatar v-if="item.userid !== userId" :title="$L('创建人')" :userid="item.userid" show-name :show-icon="false" :size="16"/>
                            <span :title="$L('创建时间')">{{item.created_at}}</span>
                        </div>
                    </div>
                </div>
            </Draggable>
        </div>

        <!-- 标签添加/编辑 -->
        <TaskTagAdd ref="addTag" :project-id="projectId" @on-save="getTagData" @on-save-error="getTagData"/>
    </div>
</template>

<script>
import {mapState, mapGetters} from 'vuex';
import Draggable from 'vuedraggable';
import Tags from "./tags.vue";
import TaskTagAdd from "./add.vue";

export default {
    name: 'ProjectTaskTag',
    components: {
        TaskTagAdd,
        Tags,
        Draggable
    },
    props: {
        projectId: {
            type: [Number, String],
            required: true
        }
    },
    data() {
        return {
            loadIng: 0,
            tags: [],
            sortMode: false,
            sortLoading: false,
        }
    },
    computed: {
        ...mapGetters(['projectData']),
        isProjectOwner() {
            return this.projectData && this.projectData.owner_userid === this.userId
        },
        canSortTags() {
            return this.isProjectOwner
        }
    },
    created() {
        this.getTagData()
    },
    methods: {
        // 加载标签列表
        async getTagData() {
            this.loadIng++
            try {
                const {data} = await this.$store.dispatch('call', {
                    url: 'project/tag/list',
                    data: {
                        project_id: this.projectId
                    },
                    spinner: 3000
                })
                this.tags = data || []
            } catch ({msg}) {
                $A.messageError(msg || '加载标签失败')
            } finally {
                this.loadIng--
            }
        },

        toggleSortMode() {
            if (!this.canSortTags || this.sortLoading) return
            this.sortMode = !this.sortMode
        },

        async handleSortEnd(event) {
            if (!this.sortMode || !this.canSortTags) {
                return
            }
            if (event && event.oldIndex === event.newIndex) {
                return
            }
            const list = this.tags.map(tag => tag.id)
            if (!list.length) {
                return
            }
            this.sortLoading = true
            try {
                const {msg} = await this.$store.dispatch('call', {
                    url: 'project/tag/sort',
                    method: 'post',
                    data: {
                        project_id: this.projectId,
                        list
                    },
                    spinner: 2000
                })
                $A.messageSuccess(msg || '排序已保存')
                await this.getTagData()
            } catch ({msg}) {
                $A.messageError(msg || '排序保存失败')
                await this.getTagData()
            } finally {
                this.sortLoading = false
            }
        },

        // 新建、编辑标签
        handleAdd(tag) {
            this.$refs.addTag.onOpen(tag)
        },

        // 删除标签
        async handleDelete(tag) {
            $A.modalConfirm({
                title: '确认删除',
                content: '确定要删除该标签吗？',
                onOk: async () => {
                    this.loadIng++
                    try {
                        const {msg} = await this.$store.dispatch('call', {
                            url: 'project/tag/delete',
                            data: {
                                id: tag.id
                            },
                            spinner: 3000
                        })
                        $A.messageSuccess(msg || '删除成功')
                        await this.getTagData()
                    } catch ({msg}) {
                        $A.messageError(msg || '删除失败')
                    } finally {
                        this.loadIng--
                    }
                }
            })
        },
    }
}
</script>
