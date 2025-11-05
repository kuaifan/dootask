<template>
    <div class="setting-component-item">
        <Form
            ref="formData"
            :model="formData"
            :rules="ruleData"
            v-bind="formOptions"
            @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{ $L('AI 助手') }}</h3>
                <div class="form-box">
                    <Alert type="success" style="padding-right:16px">
                        <ul class="tip-list">
                            <li>{{$L('此功能并非聊天机器人，而是用于辅助工作。比如：语音转文字、聊天翻译、整理分析工作报告等。')}}</li>
                            <li>{{$L('如果需要聊天机器人，请在「应用」中使用「AI 机器人」插件。')}}</li>
                        </ul>
                    </Alert>
                    <p>&nbsp;</p>
                    <FormItem :label="$L('AI 提供商')" prop="ai_provider">
                        <Select v-model="formData.ai_provider">
                            <Option value="openai">OpenAI</Option>
                        </Select>
                        <div class="form-tip">{{$L('支持：OpenAI')}}</div>
                    </FormItem>
                    <FormItem :label="$L('API 密钥')" prop="ai_api_key">
                        <Input v-model="formData.ai_api_key" type="password" :placeholder="$L('请输入 API 密钥')"/>
                        <div class="form-tip">{{$L('请输入 API 密钥，留空表示不启用 AI 助手')}}</div>
                    </FormItem>
                    <FormItem label="API URL" prop="ai_api_url">
                        <Input v-model="formData.ai_api_url" :placeholder="$L('请输入 API URL')"/>
                        <div class="form-tip">{{$L('选填，请输入 API URL')}}</div>
                    </FormItem>
                    <FormItem :label="$L('代理')" prop="ai_proxy">
                        <Input v-model="formData.ai_proxy" :placeholder="$L('请输入代理')"/>
                        <div class="form-tip">{{$L('选填，支持 http、https、socks5 协议')}}</div>
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

<style lang="less" scoped>
.tip-list {
    list-style: disc;
    padding-left: 12px;
    line-height: 22px;
}
</style>
<script>
import {mapState} from "vuex";

export default {
    name: "SystemAiAssistant",
    data() {
        return {
            loadIng: 0,
            formData: {},
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
                url: 'system/setting/ai?type=' + (save ? 'save' : 'all'),
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
