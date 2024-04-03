<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box">
                <h3>AgoraIO 声网</h3>
                <div class="form-box">
                    <FormItem :label="$L('会议功能')" prop="open">
                        <RadioGroup v-model="formData.open">
                            <Radio label="open">{{ $L('开启') }}</Radio>
                            <Radio label="close">{{ $L('关闭') }}</Radio>
                        </RadioGroup>
                    </FormItem>
                    <template v-if="formData.open === 'open'">
                        <Divider orientation="left">{{$L('基本配置')}}</Divider>
                        <div class="form-tip form-list">
                            <ul>
                                <li>{{$L('基本配置')}}: {{$L('用于生成会议频道。')}}</li>
                                <li>{{$L('获取方式')}}: <a href="https://docportal.shengwang.cn/cn/Agora%20Platform/token_server#%E8%8E%B7%E5%8F%96-app-id-%E5%8F%8A-app-%E8%AF%81%E4%B9%A6" target="_blank">Open Link</a></li>
                            </ul>
                        </div>
                        <FormItem label="App ID " prop="appid">
                            <Input :maxlength="255" v-model="formData.appid"/>
                        </FormItem>
                        <FormItem label="App certificate" prop="app_certificate">
                            <Input :maxlength="255" v-model="formData.app_certificate" type="password"/>
                        </FormItem>
                        <div class="clearfix"></div>
                        <Divider orientation="left">RESTful Api（{{$L('可选')}}）</Divider>
                        <div class="form-tip form-list">
                            <ul>
                                <li>RESTful Api: {{$L('用于频道管理。')}} ({{$L('比如')}}: {{$L('结束会议室')}})</li>
                                <li>{{$L('获取方式')}} <a href="https://doc.shengwang.cn/doc/rtc/restful/get-started/enable-service#%E8%8E%B7%E5%8F%96%E5%AE%A2%E6%88%B7-id-%E5%92%8C%E5%AE%A2%E6%88%B7%E5%AF%86%E9%92%A5" target="_blank">Open Link</a></li>
                            </ul>
                        </div>
                        <FormItem label="key " prop="api_key">
                            <Input :maxlength="255" v-model="formData.api_key"/>
                        </FormItem>
                        <FormItem label="secret" prop="secret">
                            <Input :maxlength="255" v-model="formData.api_secret" type="password"/>
                        </FormItem>
                    </template>
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
    name: "SystemMeeting",
    data() {
        return {
            loadIng: 0,
            formData: {
                open: '',
                appid: '',
                app_certificate: '',
            },
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
                url: 'system/setting/meeting?type=' + (save ? 'save' : 'all'),
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
