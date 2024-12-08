
<template>
    <div class="project-task-template">
        <div class="header">
            <div class="title">
                {{$L('任务标签')}}
                <Loading v-if="loadIng > 0"/>
            </div>
            <div class="actions">
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
            <div v-else class="template-list">
                <div v-for="item in tags" :key="item.id" class="tag-item">
                    <div class="tag-contents">
                        <div class="tag-title">
                            <Tags :tags="item"/>
                        </div>
                        <div v-if="item.desc" class="tag-desc">{{ item.desc }}</div>
                    </div>
                    <div class="tag-actions">
                        <Button @click="handleAdd(item)" type="primary">
                            {{$L('编辑')}}
                        </Button>
                        <Button @click="handleDelete(item)" type="error">
                            {{$L('删除')}}
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 标签添加/编辑 -->
        <TaskTagAdd ref="addTag" :project-id="projectId" @on-save="loadTags"/>
    </div>
</template>

<script>
import {mapState} from 'vuex'
import Tags from "./tags.vue";
import TaskTagAdd from "./add.vue";

export default {
    name: 'ProjectTaskTag',
    components: {
        TaskTagAdd,
        Tags
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
        }
    },
    computed: {
        ...mapState(['formOptions'])
    },
    created() {
        this.loadTags()
    },
    methods: {
        // 加载标签列表
        async loadTags() {
            this.loadIng++
            try {
                const {data} = await this.$store.dispatch("call", {
                    url: 'project/tag/list',
                    data: {
                        project_id: this.projectId
                    },
                    spinner: 300
                })
                this.tags = data || []
            } catch ({msg}) {
                $A.messageError(msg || '加载标签失败')
            } finally {
                this.loadIng--
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
                        const {msg} = await this.$store.dispatch("call", {
                            url: 'project/tag/delete',
                            data: {
                                id: tag.id
                            },
                            spinner: 300
                        })
                        $A.messageSuccess(msg || '删除成功')
                        this.loadTags()
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
