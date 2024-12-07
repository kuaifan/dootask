
<template>
    <div class="project-task-template">
        <div class="header">
            <div class="title">{{$L('任务标签')}}</div>
            <div class="actions">
                <Button type="primary" icon="md-add" @click="handleAdd">
                    {{$L('新建标签')}}
                </Button>
            </div>
        </div>

        <div class="content">
            <div v-if="!tags.length" class="empty">
                <div class="empty-text">{{$L('当前项目暂无任务标签')}}</div>
                <Button type="primary" icon="md-add" @click="handleAdd">{{$L('新建标签')}}</Button>
            </div>
            <div v-else class="template-list">
                <div v-for="item in tags" :key="item.id" class="tag-item">
                    <div class="tag-contents">
                        <div class="tag-title">{{ item.name }}</div>
                        <div v-if="item.desc" class="tag-desc">{{ item.desc }}</div>
                    </div>
                    <div class="tag-actions">
                        <Button @click="handleEdit(item)" type="primary">
                            {{$L('编辑')}}
                        </Button>
                        <Button @click="handleDelete(item)" type="error">
                            {{$L('删除')}}
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 编辑标签弹窗 -->
        <Modal
            v-model="showEditModal"
            :title="editingTag.id ? $L('编辑标签') : $L('新建标签')"
            :mask-closable="false">
            <Form
                ref="editForm"
                :model="editingTag"
                :rules="formRules"
                v-bind="formOptions"
                @submit.native.prevent>
                <FormItem prop="name" :label="$L('标签名称')">
                    <Input ref="tagName" v-model="editingTag.name" :disabled="systemTagIsMultiple" :placeholder="$L('请输入标签名称')"/>
                </FormItem>
                <FormItem prop="desc" :label="$L('标签描述')">
                    <Input v-model="editingTag.desc" :disabled="systemTagIsMultiple" :placeholder="$L('请输入标签描述')"/>
                </FormItem>
                <FormItem v-if="!editingTag.id">
                    <div class="project-task-template-system">
                        <div v-if="!systemTagShow" @click="onSystemTag" class="tip-title">{{$L('使用示例标签')}}</div>
                        <ul v-else>
                            <li
                                :class="{selected:systemTagIsMultiple}"
                                @click="systemTagIsMultiple=!systemTagIsMultiple">
                                <i class="taskfont" v-html="systemTagIsMultiple ? '&#xe627;' : '&#xe625;'"></i>
                                {{$L('多选')}}
                            </li>
                            <li
                                v-for="(item, index) in systemTagData"
                                :key="index"
                                :class="{selected:systemTagIsMultiple && systemTagMultipleData.indexOf(item)!==-1}"
                                @click="useSystemTag(item)">{{item.name}}</li>
                        </ul>
                    </div>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="showEditModal=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loading" @click="handleSave">
                    {{ $L('保存') }}
                    {{ systemTagIsMultiple && systemTagMultipleData.length > 0 ? ` (${systemTagMultipleData.length})` : '' }}
                </Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import {mapState} from 'vuex'
import AllTaskTags from "./tags";
import {getLanguage} from "../../../../language";

export default {
    name: 'ProjectTaskTag',
    props: {
        projectId: {
            type: [Number, String],
            required: true
        }
    },
    data() {
        return {
            loading: false,
            tags: [],
            showEditModal: false,
            editingTag: this.getEmptyTag(),
            formRules: {
                name: [
                    { required: true, message: this.$L('请输入标签名称'), trigger: 'blur' }
                ]
            },

            systemTagShow: false,
            systemTagData: [],
            systemTagIsMultiple: false,
            systemTagMultipleData: [],
        }
    },
    computed: {
        ...mapState(['formOptions'])
    },
    created() {
        this.loadTags()
    },
    watch: {
        showEditModal(val) {
            if (!val) {
                this.$refs.editForm.resetFields()
                this.systemTagShow = false
                this.systemTagIsMultiple = false
            }
        }
    },
    methods: {
        // 获取空标签对象
        getEmptyTag() {
            return {
                id: null,
                project_id: this.projectId,
                name: '',
                desc: '',
                color: ''
            }
        },

        // 加载标签列表
        async loadTags() {
            this.loading = true
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
            }
            this.loading = false
        },

        // 新建标签
        handleAdd() {
            this.editingTag = this.getEmptyTag()
            this.showEditModal = true
        },

        // 编辑标签
        handleEdit(tag) {
            this.editingTag = { ...tag }
            this.showEditModal = true
        },

        // 保存标签
        async handleSave() {
            if (!this.editingTag.name) {
                $A.messageWarning('请输入标签名称')
                return
            }
            let savePromises = []
            if (this.systemTagIsMultiple) {
                if (this.systemTagMultipleData.length === 0) {
                    $A.messageWarning('请选择示例标签')
                    return
                }
                savePromises = this.systemTagMultipleData.map(item => {
                    const tag = { ...this.editingTag, id: null, name: item.name, desc: item.desc, color: item.color }
                    return this.handleSaveCall(tag)
                })
            } else {
                savePromises.push(this.handleSaveCall(this.editingTag))
            }

            try {
                const results = await Promise.all(savePromises)
                $A.messageSuccess(results.length === 1 ? results[0].msg : '全部保存成功')
                this.showEditModal = false
                this.loadTags()
            } catch (error) {
                $A.messageError(error.msg || '保存失败')
            }
        },

        // 保存标签请求
        async handleSaveCall(data) {
            return this.$store.dispatch("call", {
                url: 'project/tag/save',
                data,
                method: 'post',
                spinner: 300
            })
        },

        // 删除标签
        async handleDelete(tag) {
            $A.modalConfirm({
                title: '确认删除',
                content: '确定要删除该标签吗？',
                onOk: async () => {
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
                    }
                }
            })
        },

        onSystemTag() {
            const lang = getLanguage()
            this.systemTagData = typeof AllTaskTags[lang] === "undefined" ? AllTaskTags['en'] : AllTaskTags[lang]
            this.systemTagShow = true
        },

        // 使用系统标签
        useSystemTag(item) {
            this.editingTag.name = item.name
            this.editingTag.desc = item.desc
            this.editingTag.color = item.color
            //
            if (this.systemTagIsMultiple) {
                const index = this.systemTagMultipleData.indexOf(item)
                if (index === -1) {
                    this.systemTagMultipleData.push(item)
                } else {
                    this.systemTagMultipleData.splice(index, 1)
                }
            }
        }
    }
}
</script>
