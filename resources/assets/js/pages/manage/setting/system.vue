<template>
    <div class="setting-item submit">
        <Form ref="formDatum" :model="formDatum" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('允许注册')" prop="reg">
                <RadioGroup v-model="formDatum.reg">
                    <Radio label="open">{{$L('允许')}}</Radio>
                    <Radio label="close">{{$L('禁止')}}</Radio>
                </RadioGroup>
            </FormItem>
            <FormItem :label="$L('登录验证码')" prop="loginCode">
                <RadioGroup v-model="formDatum.login_code">
                    <Radio label="auto">{{$L('自动')}}</Radio>
                    <Radio label="open">{{$L('开启')}}</Radio>
                    <Radio label="close">{{$L('关闭')}}</Radio>
                </RadioGroup>
                <div v-if="formDatum.login_code == 'auto'" class="form-tip">{{$L('自动：密码输入错误后必须添加验证码。')}}</div>
            </FormItem>
            <FormItem :label="$L('密码策略')" prop="passwordPolicy">
                <RadioGroup v-model="formDatum.password_policy">
                    <Radio label="simple">{{$L('简单')}}</Radio>
                    <Radio label="complex">{{$L('复杂')}}</Radio>
                </RadioGroup>
                <div v-if="formDatum.password_policy == 'simple'" class="form-tip">{{$L('简单：大于或等于6个字符。')}}</div>
                <div v-else-if="formDatum.password_policy == 'complex'" class="form-tip">{{$L('复杂：大于或等于6个字符，包含数字、字母大小写或者特殊字符。')}}</div>
            </FormItem>
            <FormItem :label="$L('聊天昵称')" prop="chatNickname">
                <RadioGroup v-model="formDatum.chat_nickname">
                    <Radio label="optional">{{$L('可选')}}</Radio>
                    <Radio label="required">{{$L('必填')}}</Radio>
                </RadioGroup>
                <div v-if="formDatum.chat_nickname == 'required'" class="form-tip">{{$L('必填：发送聊天内容前必须设置昵称。')}}</div>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{$L('重置')}}</Button>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            loadIng: 0,

            formDatum: {},
        }
    },

    mounted() {
        this.systemSetting();
    },

    methods: {
        submitForm() {
            this.$refs.formDatum.validate((valid) => {
                if (valid) {
                    this.systemSetting(true);
                }
            })
        },

        resetForm() {
            this.formDatum = $A.cloneJSON(this.formDatum_bak);
        },

        systemSetting(save) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'system/setting?type=' + (save ? 'save' : 'get'),
                data: this.formDatum,
            }).then(({data}) => {
                if (save) {
                    $A.messageSuccess('修改成功');
                }
                this.loadIng--;
                this.formDatum = data;
                this.formDatum_bak = $A.cloneJSON(this.formDatum);
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
                this.loadIng--;
            });
        }
    }
}
</script>
