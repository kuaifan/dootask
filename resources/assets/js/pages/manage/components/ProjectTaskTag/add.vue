<template>
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
            <FormItem prop="color" :label="$L('标签颜色')">
                <ColorPicker v-model="editingTag.color" :disabled="systemTagIsMultiple" recommend transfer/>
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
                            :class="{tag: true, selected:systemTagIsMultiple && systemTagMultipleData.indexOf(item)!==-1}"
                            @click="useSystemTag(item)">
                            <Tags :tags="item"></Tags>
                        </li>
                    </ul>
                </div>
            </FormItem>
        </Form>
        <div slot="footer" class="adaption">
            <Button type="default" @click="showEditModal=false">{{$L('取消')}}</Button>
            <Button type="primary" :loading="loadIng > 0" @click="handleSave">
                {{ $L('保存') }}
                {{ systemTagIsMultiple && systemTagMultipleData.length > 0 ? ` (${systemTagMultipleData.length})` : '' }}
            </Button>
        </div>
    </Modal>
</template>

<script>
import { mapState } from 'vuex'
import {getLanguage} from "../../../../language";
import {systemTags} from "./utils";
import Tags from "./tags.vue";

export default {
    name: "TaskTagAdd",
    components: {Tags},
    props: {
        projectId: {
            type: [Number, String],
            required: true
        }
    },
    data() {
        return {
            loadIng: 0,

            showEditModal: false,
            editingTag: {},
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
        onOpen(tag = null) {
            if (tag === null) {
                tag = this.getEmptyTag()
            }
            this.editingTag = { ...tag }
            this.showEditModal = true
        },

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
                this.$emit('on-save', results)
            } catch (error) {
                $A.messageError(error.msg || '保存失败')
            }
        },

        // 保存标签请求
        async handleSaveCall(data) {
            this.loadIng++
            try {
                return await this.$store.dispatch("call", {
                    url: 'project/tag/save',
                    data,
                    method: 'post',
                    spinner: 300
                })
            } finally {
                this.loadIng--
            }
        },

        onSystemTag() {
            const lang = getLanguage()
            this.systemTagData = typeof systemTags[lang] === "undefined" ? systemTags['en'] : systemTags[lang]
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
