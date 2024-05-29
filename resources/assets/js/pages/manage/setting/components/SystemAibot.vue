<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box" v-if="type=='all' || type=='ChatGPT'">
                <h3>ChatGPT</h3>
                <div class="form-box">
                    <FormItem label="API Key" prop="openai_key">
                        <Input :maxlength="255" v-model="formData.openai_key" type="password" placeholder="OpenAI API Key"/>
                        <div class="form-tip">{{$L('访问OpenAI网站查看')}}: <a href="https://platform.openai.com/account/api-keys" target="_blank">https://platform.openai.com/account/api-keys</a></div>
                    </FormItem>
                    <FormItem :label="$L('模型')" prop="openai_model">
                        <Select v-model="formData.openai_model" placement="top" transfer>
                            <Option value="gpt-3.5-turbo">gpt-3.5-turbo</Option>
                            <Option value="gpt-4">gpt-4</Option>
                        </Select>
                        <div class="form-tip">{{$L('查看说明')}} <a href="https://platform.openai.com/docs/models" target="_blank">https://platform.openai.com/docs/models</a></div>
                    </FormItem>
                    <FormItem :label="$L('使用代理')" prop="openai_agency">
                        <Input :maxlength="500" v-model="formData.openai_agency" :placeholder="$L('支持 http 或 socks 代理')"/>
                        <div class="form-tip">{{$L('例如：http://proxy.com 或 socks5://proxy.com')}}</div>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box" v-if="type=='all' || type=='Claude'">
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
            <div class="block-setting-box" v-if="type=='all' || type=='Wenxin'">
                <h3>文心一言 (Wenxin)</h3>
                <div class="form-box">
                    <FormItem label="API Key" prop="wenxin_key">
                        <Input :maxlength="255" v-model="formData.wenxin_key" type="password" placeholder="API Key"/>
                        <div class="form-tip">{{$L('获取方式')}} <a href="https://ai.baidu.com/ai-doc/REFERENCE/Ck3dwjgn3#3-%E8%8E%B7%E5%8F%96%E5%AF%86%E9%92%A5" target="_blank">https://ai.baidu.com/ai-doc/REFERENCE/Ck3dwjgn3</a></div>
                    </FormItem>
                    <FormItem label="API Secret" prop="wenxin_secret">
                        <Input :maxlength="500" v-model="formData.wenxin_secret"  type="password" placeholder="API Secret"/>
                        <div class="form-tip">{{$L('获取方式')}} <a href="https://ai.baidu.com/ai-doc/REFERENCE/Ck3dwjgn3#3-%E8%8E%B7%E5%8F%96%E5%AF%86%E9%92%A5" target="_blank">https://ai.baidu.com/ai-doc/REFERENCE/Ck3dwjgn3</a></div>
                    </FormItem>
                    <FormItem :label="$L('模型')" prop="wenxin_model">
                        <Select v-model="formData.wenxin_model" placement="top" transfer>
                            <Option value="completions_pro">ERNIE-Bot 4.0</Option>
                            <Option value="completions">ERNIE-Bot</Option>
                            <Option value="eb-instant">ERNIE-Bot-turbo</Option>
                            <Option value="llama_2_7b">Llama-2-7b-chat</Option>
                            <Option value="llama_2_13b">Llama-2-13B-Chat</Option>
                        </Select>
                        <div class="form-tip">{{$L('查看说明')}} <a href="https://cloud.baidu.com/doc/WENXINWORKSHOP/s/vliu6vq7u" target="_blank">https://cloud.baidu.com/doc/WENXINWORKSHOP/s/vliu6vq7u</a></div>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box" v-if="type=='all' || type=='Qianwen'">
                <h3>通义千问 (Qianwen)</h3>
                <div class="form-box">
                    <FormItem label="API Key" prop="qianwen_key">
                        <Input :maxlength="255" v-model="formData.qianwen_key" type="password" placeholder="API Key"/>
                        <div class="form-tip">{{$L('获取方式')}} <a href="https://help.aliyun.com/document_detail/611472.html" target="_blank">https://help.aliyun.com/document_detail/611472.html</a></div>
                    </FormItem>
                    <FormItem :label="$L('模型')" prop="qianwen_model">
                        <Select v-model="formData.qianwen_model" placement="top" transfer>
                            <Option value="qwen-v1">qwen-v1</Option>
                            <Option value="qwen-plus-v1">qwen-plus-v1</Option>
                        </Select>
                        <div class="form-tip">{{$L('查看说明')}} <a href="https://help.aliyun.com/document_detail/2399481.html" target="_blank">https://help.aliyun.com/document_detail/2399481.html</a></div>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box" v-if="type=='all' || type=='Gemini'">
                <h3>Gemini</h3>
                <div class="form-box">
                    <FormItem label="API Key" prop="gemini_key">
                        <Input :maxlength="255" v-model="formData.gemini_key" type="password" placeholder="API Key"/>
                        <div class="form-tip">{{$L('获取方式')}} <a href="https://ai.google.dev/tutorials/setup?hl=zh-cn" target="_blank">https://ai.google.dev/tutorials/setup?hl=zh-cn</a></div>
                    </FormItem>
                    <FormItem :label="$L('模型')" prop="gemini_model">
                        <Select v-model="formData.gemini_model" placement="top" transfer>
                            <Option value="gemini-1.0-pro">gemini-1.0-pro</Option>
                        </Select>
                        <div class="form-tip">{{$L('查看说明')}} <a href="https://ai.google.dev/models?hl=zh-cn" target="_blank">https://ai.google.dev/models?hl=zh-cn</a></div>
                    </FormItem>
                    <FormItem :label="$L('使用代理')" prop="gemini_agency">
                        <Input :maxlength="500" v-model="formData.gemini_agency" :placeholder="$L('支持 http 或 socks 代理')"/>
                        <div class="form-tip">{{$L('例如：http://proxy.com 或 socks5://proxy.com')}}</div>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box" v-if="type=='all' || type=='Zhipu'">
                <h3>智谱清言</h3>
                <div class="form-box">
                    <FormItem label="API Key" prop="zhipu_key">
                        <Input :maxlength="255" v-model="formData.zhipu_key" type="password" placeholder="API Key"/>
                        <div class="form-tip">{{$L('获取方式')}} <a href="https://open.bigmodel.cn/usercenter/apikeys" target="_blank">https://open.bigmodel.cn/usercenter/apikeys</a></div>
                    </FormItem>
                    <FormItem :label="$L('模型')" prop="zhipu_model">
                        <Select v-model="formData.zhipu_model" placement="top" transfer>
                            <Option value="glm-4">glm-4</Option>
                            <Option value="glm-4v">glm-4v</Option>
                            <Option value="glm-3-turbo">glm-3-turbo</Option>
                        </Select>
                        <div class="form-tip">{{$L('查看说明')}} <a href="https://open.bigmodel.cn/dev/howuse/model" target="_blank">https://open.bigmodel.cn/dev/howuse/model</a></div>
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
    props: {
        type: {
            default: 'all'
        }
    },
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
