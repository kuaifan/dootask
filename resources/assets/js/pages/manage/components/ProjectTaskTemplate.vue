
<template>
    <div class="project-task-template">
        <div class="header">
            <div class="title">{{$L('任务模板')}}</div>
            <div class="actions">
                <Button type="primary" icon="md-add" @click="handleAdd">
                    {{$L('新建模板')}}
                </Button>
            </div>
        </div>

        <div class="content">
            <div v-if="!templates.length" class="empty">
                <div class="empty-text">{{$L('暂无任务模板')}}</div>
            </div>
            <div v-else class="template-list">
                <div v-for="item in templates" :key="item.id" class="template-item">
                    <div class="template-title">
                        <span>{{ item.name }}</span>
                        <span v-if="item.is_default" class="default-tag">{{$L('默认')}}</span>
                    </div>
                    <div class="template-content">
                        <div class="task-title">{{ item.title }}</div>
                        <div class="task-content">
                            <VMPreviewNostyle ref="descPreview" :value="item.content"/>
                        </div>
                    </div>
                    <div class="template-actions">
                        <Button @click="handleSetDefault(item)" type="primary" :icon="item.is_default ? 'md-checkmark' : ''">
                            {{$L('设为默认')}}
                        </Button>
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

        <!-- 编辑模板弹窗 -->
        <Modal
            v-model="showEditModal"
            :title="editingTemplate.id ? $L('编辑模板') : $L('新建模板')"
            :mask-closable="false">
            <Form
                ref="editForm"
                :model="editingTemplate"
                :rules="formRules"
                v-bind="formOptions"
                @submit.native.prevent>
                <FormItem prop="name" :label="$L('模板名称')">
                    <Input ref="templateName" v-model="editingTemplate.name" :placeholder="$L('请输入模板名称')"/>
                </FormItem>
                <FormItem prop="title" :label="$L('任务标题')">
                    <Input v-model="editingTemplate.title" :placeholder="$L('请输入任务标题')"/>
                </FormItem>
                <FormItem prop="content" :label="$L('任务内容')">
                    <Input
                        type="textarea"
                        v-model="editingTemplate.content"
                        :placeholder="$L('请输入任务内容')"
                        :autosize="{ minRows: 4, maxRows: 12 }"/>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="showEditModal=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loading" @click="handleSave">{{$L('保存')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import {mapState} from 'vuex'
import VMPreviewNostyle from "../../../components/VMEditor/nostyle.vue";

export default {
    name: 'ProjectTaskTemplate',
    components: {VMPreviewNostyle},
    props: {
        projectId: {
            type: [Number, String],
            required: true
        }
    },
    data() {
        return {
            loading: false,
            templates: [],
            showEditModal: false,
            editingTemplate: this.getEmptyTemplate(),
            formRules: {
                name: [
                    { required: true, message: this.$L('请输入模板名称'), trigger: 'blur' }
                ],
                title: [
                    { required: true, message: this.$L('请输入任务标题'), trigger: 'blur' }
                ]
            }
        }
    },
    computed: {
        ...mapState(['formOptions'])
    },
    created() {
        this.loadTemplates()
    },
    methods: {
        // 获取空模板对象
        getEmptyTemplate() {
            return {
                id: null,
                project_id: this.projectId,
                name: '',
                title: '',
                content: '',
                is_default: false
            }
        },

        // 加载模板列表
        async loadTemplates() {
            this.loading = true
            try {
                const {data} = await this.$store.dispatch("call", {
                    url: 'project/task/template_list',
                    data: {
                        project_id: this.projectId
                    },
                    spinner: 300
                })
                this.templates = data || []
            } catch ({msg}) {
                this.$Message.error(msg || this.$L('加载模板失败'))
            }
            this.loading = false
        },

        // 新建模板
        handleAdd() {
            this.editingTemplate = this.getEmptyTemplate()
            this.showEditModal = true
        },

        // 编辑模板
        handleEdit(template) {
            this.editingTemplate = { ...template }
            this.showEditModal = true
        },

        // 保存模板
        async handleSave() {
            if (!this.editingTemplate.name) {
                this.$Message.warning(this.$L('请输入模板名称'))
                return
            }

            try {
                await this.$store.dispatch("call", {
                    url: 'project/task/template_save',
                    data: this.editingTemplate,
                    method: 'post',
                    spinner: 300
                })
                this.$Message.success(this.$L('保存成功'))
                this.showEditModal = false
                this.loadTemplates()
            } catch ({msg}) {
                this.$Message.error(msg || this.$L('保存失败'))
            }
        },

        // 删除模板
        async handleDelete(template) {
            this.$Modal.confirm({
                title: this.$L('确认删除'),
                content: this.$L('确定要删除该模板吗？'),
                onOk: async () => {
                    try {
                        await this.$store.dispatch("call", {
                            url: 'project/task/template_delete',
                            data: {
                                id: template.id
                            },
                            spinner: 300
                        })
                        this.$Message.success(this.$L('删除成功'))
                        this.loadTemplates()
                    } catch ({msg}) {
                        this.$Message.error(msg || this.$L('删除失败'))
                    }
                }
            })
        },

        // 设置默认模板
        async handleSetDefault(template) {
            if (template.is_default) {
                return
            }

            try {
                await this.$store.dispatch("call", {
                    url: 'project/task/template_default',
                    data: {
                        id: template.id,
                        project_id: this.projectId
                    },
                    spinner: 300
                })
                this.$Message.success(this.$L('设置成功'))
                this.loadTemplates()
            } catch ({msg}) {
                this.$Message.error(msg || this.$L('设置失败'))
            }
        }
    }
}
</script>
