<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box">
                <h3>ChatGTP</h3>
                <div class="form-box">
                    <FormItem label="API Key" prop="openai_key">
                        <Input :maxlength="255" v-model="formData.openai_key" type="password" placeholder="OpenAI API Key"/>
                        <div class="form-tip">{{$L('访问OpenAI网站查看：')}}<a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a></div>
                    </FormItem>
                    <FormItem :label="$L('使用代理')" prop="openai_agency">
                        <Input :maxlength="500" v-model="formData.openai_agency" :placeholder="$L('支持 http 或 socks 代理')"/>
                        <div class="form-tip">{{$L('例如：http://proxy.com 或 socks5://proxy.com')}}</div>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box">
                <h3>Claude</h3>
                <div class="form-box">
                    <FormItem label="Token" prop="claude_token">
                        <Input :maxlength="255" v-model="formData.claude_token" type="password" placeholder="Claude Token"/>
                        <div class="form-tip">{{$L('登录')}} <a href="https://claude.ai" target="_blank">https://claude.ai</a> {{$L('查看 Cookie 中的 sessionKey 便是')}}</div>
                    </FormItem>
                    <FormItem :label="$L('使用代理')" prop="claude_agency">
                        <Input :maxlength="500" v-model="formData.claude_agency" :placeholder="$L('支持 http 或 socks 代理')"/>
                        <div class="form-tip">{{$L('例如：http://proxy.com 或 socks5://proxy.com')}}</div>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box">
                <h3>Wenxin</h3>
                <div class="form-box">
                    <FormItem label="API Key" prop="wenxin_key">
                        <Input :maxlength="255" v-model="formData.wenxin_key" type="password" placeholder="API Key"/>
                        <div class="form-tip">{{$L('登录')}} <a href="https://console.bce.baidu.com/qianfan/ais/console/onlineTest" target="_blank">https://console.bce.baidu.com/qianfan/ais/console/onlineTest</a> {{$L('查看')}}</div>
                    </FormItem>
                    <FormItem label="API Secret" prop="wenxin_secret">
                        <Input :maxlength="500" v-model="formData.wenxin_secret"  type="password" placeholder="API Secret"/>
                    </FormItem>
                    <FormItem label="模型" prop="wenxin_model">
                        <Select v-model="formData.wenxin_model"  placement="top">
                            <Option value="ERNIE-Bot">ERNIE-Bot</Option>
                            <Option value="ERNIE-Bot-turbo">ERNIE-Bot-turbo</Option>
                        </Select>
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
export default {
    name: "SystemAibot",
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
                url: 'system/setting/aibot?type=' + (save ? 'save' : 'all'),
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
        }
    }
}
</script>
