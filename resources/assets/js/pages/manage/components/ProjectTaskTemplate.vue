
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
                        <div v-if="item.title" class="task-title">{{ item.title }}</div>
                        <div v-if="item.content" class="task-content">
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
                <FormItem v-if="!editingTemplate.id">
                    <div class="project-task-template-system">
                        <div v-if="!systemTemplateShow" @click="systemTemplateShow=true" class="tip-title">{{$L('使用示例模板')}}</div>
                        <ul v-else>
                            <li v-for="(item, index) in systemTemplates" :key="index" @click="useSystemTemplate(item)">{{item.name}}</li>
                        </ul>
                    </div>
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
                ]
            },

            systemTemplateShow: false,
            systemTemplates: [
                {
                    "name": "通用任务",
                    "title": "xxxx 任务",
                    "content": "描述：xxxx\n清单：xxxx"
                },
                {
                    "name": "产品需求",
                    "title": "xxxx 功能需求/产品任务",
                    "content": "背景：xxxx\n目标：xxxx\n清单：xxxx"
                },
                {
                    "name": "技术任务",
                    "title": "xxxx 开发任务/技术优化任务",
                    "content": "背景：xxxx\n技术目标：xxxx\n任务清单：xxxx"
                },
                {
                    "name": "运营任务",
                    "title": "xxxx 活动策划/运营任务",
                    "content": "背景：xxxx\n活动方案：xxxx\n数据指标：xxxx\n任务清单：xxxx"
                },
                {
                    "name": "市场推广",
                    "title": "xxxx 推广任务/品牌活动",
                    "content": "背景：xxxx\n推广方案：xxxx\n数据指标：xxxx\n任务清单：xxxx"
                },
                {
                    "name": "设计任务",
                    "title": "xxxx 设计任务",
                    "content": "背景：xxxx\n设计要求：xxxx\n任务清单：xxxx\n相关资源：xxxx"
                },
                {
                    "name": "人力资源",
                    "title": "xxxx 招聘任务/培训任务",
                    "content": "目标：xxxx\n内容：xxxx\n任务清单：xxxx"
                },
                {
                    "name": "财务任务",
                    "title": "xxxx 预算审批/报销任务",
                    "content": "背景：xxxx\n审批流程：xxxx\n报销清单：xxxx"
                },
                {
                    "name": "销售任务",
                    "title": "xxxx 销售跟进任务",
                    "content": "客户信息：xxxx\n销售目标：xxxx\n任务清单：xxxx"
                },
                {
                    "name": "客户支持",
                    "title": "xxxx 客户问题处理任务",
                    "content": "客户问题：xxxx\n优先级：xxxx\n解决方案：xxxx\n任务清单：xxxx"
                },
                {
                    "name": "内容创作",
                    "title": "xxxx 内容创作任务",
                    "content": "主题：xxxx\n目标：xxxx\n任务清单：xxxx"
                },
                {
                    "name": "法律事务",
                    "title": "xxxx 合同审核/法律任务",
                    "content": "合同背景：xxxx\n审核重点：xxxx\n任务清单：xxxx"
                },
                {
                    "name": "学习计划",
                    "title": "xxxx 学习计划任务",
                    "content": "学习目标：xxxx\n学习资源：xxxx\n任务清单：xxxx"
                },
                {
                    "name": "项目管理",
                    "title": "xxxx 项目管理任务",
                    "content": "项目背景：xxxx\n任务清单：xxxx\n状态：xxxx"
                },
                {
                    "name": "测试任务",
                    "title": "xxxx 测试任务",
                    "content": "测试目标：xxxx\n测试范围：xxxx\n测试用例：xxxx\n问题记录：xxxx"
                },
                {
                    "name": "数据分析",
                    "title": "xxxx 数据分析任务",
                    "content": "分析目标：xxxx\n数据来源：xxxx\n分析方法：xxxx\n结论与建议：xxxx"
                },
                {
                    "name": "供应链管理",
                    "title": "xxxx 供应链任务",
                    "content": "任务目标：xxxx\n供应商信息：xxxx\n任务清单：xxxx"
                },
                {
                    "name": "安全检查",
                    "title": "xxxx 安全检查任务",
                    "content": "检查范围：xxxx\n检查标准：xxxx\n问题记录：xxxx\n整改计划：xxxx"
                },
                {
                    "name": "行政事务",
                    "title": "xxxx 行政任务",
                    "content": "任务描述：xxxx\n负责人：xxxx\n任务清单：xxxx"
                }
            ]
        }
    },
    computed: {
        ...mapState(['formOptions'])
    },
    created() {
        this.loadTemplates()
    },
    watch: {
        showEditModal(val) {
            if (!val) {
                this.$refs.editForm.resetFields()
                this.systemTemplateShow = false
            }
        }
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
                $A.messageError(msg || '加载模板失败')
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
                $A.messageWarning('请输入模板名称')
                return
            }

            try {
                await this.$store.dispatch("call", {
                    url: 'project/task/template_save',
                    data: this.editingTemplate,
                    method: 'post',
                    spinner: 300
                })
                $A.messageSuccess('保存成功')
                this.showEditModal = false
                this.loadTemplates()
            } catch ({msg}) {
                $A.messageError(msg || '保存失败')
            }
        },

        // 删除模板
        async handleDelete(template) {
            $A.modalConfirm({
                title: '确认删除',
                content: '确定要删除该模板吗？',
                onOk: async () => {
                    try {
                        await this.$store.dispatch("call", {
                            url: 'project/task/template_delete',
                            data: {
                                id: template.id
                            },
                            spinner: 300
                        })
                        $A.messageSuccess('删除成功')
                        this.loadTemplates()
                    } catch ({msg}) {
                        $A.messageError(msg || '删除失败')
                    }
                }
            })
        },

        // 设置默认模板
        async handleSetDefault(template) {
            try {
                await this.$store.dispatch("call", {
                    url: 'project/task/template_default',
                    data: {
                        id: template.id,
                        project_id: this.projectId
                    },
                    spinner: 300
                })
                $A.messageSuccess('设置成功')
                this.loadTemplates()
            } catch ({msg}) {
                $A.messageError(msg || '设置失败')
            }
        },

        // 使用系统模板
        useSystemTemplate(item) {
            this.editingTemplate.name = item.name
            this.editingTemplate.title = item.title
            this.editingTemplate.content = item.content
        }
    }
}
</script>
