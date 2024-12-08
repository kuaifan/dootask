
<template>
    <div class="project-task-template">
        <div class="header">
            <div class="title">
                {{$L('任务模板')}}
                <Loading v-if="loadIng > 0"/>
            </div>
            <div class="actions">
                <Button type="primary" icon="md-add" @click="handleAdd">
                    {{$L('新建模板')}}
                </Button>
            </div>
        </div>

        <div class="content">
            <div v-if="!templates.length" class="empty">
                <div class="empty-text">{{$L('当前项目暂无任务模板')}}</div>
                <Button type="primary" icon="md-add" @click="handleAdd">{{$L('新建模板')}}</Button>
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
                            {{$L(item.is_default ? '取消默认' : '设为默认')}}
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
                    <Input ref="templateName" v-model="editingTemplate.name" :disabled="systemTemplateIsMultiple" :placeholder="$L('请输入模板名称')"/>
                </FormItem>
                <FormItem prop="title" :label="$L('任务标题')">
                    <Input v-model="editingTemplate.title" :disabled="systemTemplateIsMultiple" :placeholder="$L('请输入任务标题')"/>
                </FormItem>
                <FormItem prop="content" :label="$L('任务内容')">
                    <Input
                        type="textarea"
                        v-model="editingTemplate.content"
                        :disabled="systemTemplateIsMultiple"
                        :placeholder="$L('请输入任务内容')"
                        :autosize="{ minRows: 4, maxRows: 12 }"/>
                </FormItem>
                <FormItem v-if="!editingTemplate.id">
                    <div class="project-task-template-system">
                        <div v-if="!systemTemplateShow" @click="onSystemTemplate" class="tip-title">{{$L('使用示例模板')}}</div>
                        <ul v-else>
                            <li
                                :class="{selected:systemTemplateIsMultiple}"
                                @click="systemTemplateIsMultiple=!systemTemplateIsMultiple">
                                <i class="taskfont" v-html="systemTemplateIsMultiple ? '&#xe627;' : '&#xe625;'"></i>
                                {{$L('多选')}}
                            </li>
                            <li
                                v-for="(item, index) in systemTemplateData"
                                :key="index"
                                :class="{selected:systemTemplateIsMultiple && systemTemplateMultipleData.indexOf(item)!==-1}"
                                @click="useSystemTemplate(item)">{{item.name}}</li>
                        </ul>
                    </div>
                </FormItem>
            </Form>
            <div slot="footer" class="adaption">
                <Button type="default" @click="showEditModal=false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="loadIng > 0" @click="handleSave">
                    {{ $L('保存') }}
                    {{ systemTemplateIsMultiple && systemTemplateMultipleData.length > 0 ? ` (${systemTemplateMultipleData.length})` : '' }}
                </Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import {mapState} from 'vuex'
import VMPreviewNostyle from "../../../../components/VMEditor/nostyle.vue";
import AllTaskTemplates from "./templates";
import {getLanguage} from "../../../../language";

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
            loadIng: 0,
            templates: [],
            showEditModal: false,
            editingTemplate: this.getEmptyTemplate(),
            formRules: {
                name: [
                    { required: true, message: this.$L('请输入模板名称'), trigger: 'blur' }
                ]
            },

            systemTemplateShow: false,
            systemTemplateData: [],
            systemTemplateIsMultiple: false,
            systemTemplateMultipleData: [],
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
                this.systemTemplateIsMultiple = false
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
            this.loadIng++
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
            } finally {
                this.loadIng--
            }
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
            let savePromises = []
            if (this.systemTemplateIsMultiple) {
                if (this.systemTemplateMultipleData.length === 0) {
                    $A.messageWarning('请选择示例模板')
                    return
                }
                savePromises = this.systemTemplateMultipleData.map(item => {
                    const template = { ...this.editingTemplate, id: null, name: item.name, title: item.title, content: item.content }
                    return this.handleSaveCall(template)
                })
            } else {
                savePromises.push(this.handleSaveCall(this.editingTemplate))
            }

            try {
                const results = await Promise.all(savePromises)
                $A.messageSuccess(results.length === 1 ? results[0].msg : '全部保存成功')
                this.showEditModal = false
                this.loadTemplates()
            } catch (error) {
                $A.messageError(error.msg || '保存失败')
            }
        },

        // 保存模板请求
        async handleSaveCall(data) {
            this.loadIng++
            try {
                return await this.$store.dispatch("call", {
                    url: 'project/task/template_save',
                    data,
                    method: 'post',
                    spinner: 300
                })
            } finally {
                this.loadIng--
            }
        },

        // 删除模板
        async handleDelete(template) {
            $A.modalConfirm({
                title: '确认删除',
                content: '确定要删除该模板吗？',
                onOk: async () => {
                    this.loadIng++
                    try {
                        const {msg} = await this.$store.dispatch("call", {
                            url: 'project/task/template_delete',
                            data: {
                                id: template.id
                            },
                            spinner: 300
                        })
                        $A.messageSuccess(msg || '删除成功')
                        this.loadTemplates()
                    } catch ({msg}) {
                        $A.messageError(msg || '删除失败')
                    } finally {
                        this.loadIng--
                    }
                }
            })
        },

        // 设置默认模板
        async handleSetDefault(template) {
            this.loadIng++
            try {
                const {msg} = await this.$store.dispatch("call", {
                    url: 'project/task/template_default',
                    data: {
                        id: template.id,
                        project_id: this.projectId
                    },
                    spinner: 300
                })
                $A.messageSuccess(msg || '设置成功')
                this.loadTemplates()
            } catch ({msg}) {
                $A.messageError(msg || '设置失败')
            } finally {
                this.loadIng--
            }
        },

        onSystemTemplate() {
            const lang = getLanguage()
            this.systemTemplateData = typeof AllTaskTemplates[lang] === "undefined" ? AllTaskTemplates['en'] : AllTaskTemplates[lang]
            this.systemTemplateShow = true
        },

        // 使用系统模板
        useSystemTemplate(item) {
            this.editingTemplate.name = item.name
            this.editingTemplate.title = item.title
            this.editingTemplate.content = item.content
            //
            if (this.systemTemplateIsMultiple) {
                const index = this.systemTemplateMultipleData.indexOf(item)
                if (index === -1) {
                    this.systemTemplateMultipleData.push(item)
                } else {
                    this.systemTemplateMultipleData.splice(index, 1)
                }
            }
        }
    }
}
</script>
