<template>
    <div class="setting-component-item">
        <Form
            ref="formData"
            :model="formData"
            :rules="ruleData"
            v-bind="formOptions"
            @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{$L('权限设置')}}</h3>
                <div class="form-box">
                    <FormItem :label="$L('打包权限')" prop="permission_pack_type">
                        <RadioGroup v-model="formData.permission_pack_type">
                            <Radio label="all">{{ $L('允许所有人') }}</Radio>
                            <Radio label="admin">{{ $L('仅限管理员') }}</Radio>
                            <Radio label="appointAllow">{{ $L('指定允许') }}</Radio>
                            <Radio label="appointProhibit">{{ $L('指定禁止') }}</Radio>
                        </RadioGroup>
                        <div v-if="formData.permission_pack_type === 'all'" class="form-tip">{{$L('允许系统所有人员使用文件打包下载功能')}}</div>
                        <div v-else-if="formData.permission_pack_type === 'admin'" class="form-tip">{{$L('仅限管理员使用文件打包下载功能')}}</div>
                        <div v-else-if="formData.permission_pack_type === 'appointAllow'" class="form-tip">{{$L('指定允许的人员使用文件打包下载功能')}}</div>
                        <div v-else-if="formData.permission_pack_type === 'appointProhibit'" class="form-tip">{{$L('指定禁止的人员使用文件打包下载功能')}}</div>
                    </FormItem>
                    <FormItem v-if="['appointAllow', 'appointProhibit'].includes(formData.permission_pack_type)" :label="$L('指定人员')" prop="permission_pack_userid">
                        <UserSelect v-model="formData.permission_pack_userid" :multiple-max="200" avatar-name show-disable :title="$L('请选择指定人员')"/>
                        <div class="form-tip">{{$L('指定人员最多可选择200人')}}</div>
                    </FormItem>
                </div>
            </div>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{ $L('提交') }}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{ $L('重置') }}</Button>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import UserSelect from "../../../../components/UserSelect.vue";

export default {
    name: "SystemFileSetting",
    components: {UserSelect},
    data() {
        return {
            loadIng: 0,
            formData: {

            },
            ruleData: {},
        }
    },

    mounted() {
        this.systemSetting();
    },

    computed: {
        ...mapState(['formOptions']),
    },

    methods: {
        submitForm() {
            this.$refs.formData.validate((valid) => {
                if (valid) {
                    this.systemSetting(true);
                }
            })
        },

        resetForm() {
            this.formData = $A.cloneJSON(this.formDatum_bak);
        },

        systemSetting(save) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'system/setting/file?type=' + (save ? 'save' : 'all'),
                data: this.formData,
            }).then(({data}) => {
                if (save) {
                    $A.messageSuccess('修改成功');
                }
                this.formData = data;
                this.formDatum_bak = $A.cloneJSON(this.formData);
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
            }).finally(_ => {
                this.loadIng--;
            });
        },
    }
}
</script>
