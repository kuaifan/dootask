<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{ $L('邮箱服务器设置') }}</h3>
                <div class="form-box">
                    <FormItem :label="$L('SMTP服务器')" prop="smtp_server">
                        <Input v-model="formData.smtp_server"/>
                    </FormItem>
                    <FormItem :label="$L('端口')" prop="port">
                        <Input :maxlength="20" v-model="formData.port"/>
                    </FormItem>
                    <FormItem :label="$L('帐号')" prop="account">
                        <Input :maxlength="128" v-model="formData.account"/>
                    </FormItem>
                    <FormItem :label="$L('密码')" prop="password">
                        <Input :maxlength="128" v-model="formData.password" type="password"/>
                    </FormItem>
                    <FormItem>
                        <Button @click="checkEmailSend">{{ $L('邮件发送测试') }}</Button>
                    </FormItem>
                </div>
            </div>

            <div class="block-setting-space"></div>

            <div class="block-setting-box">
                <h3>{{ $L('邮件通知设置') }}</h3>
                <div class="form-box">
                    <FormItem :label="$L('开启注册验证')" prop="reg_verify">
                        <RadioGroup v-model="formData.reg_verify">
                            <Radio label="open">{{ $L('开启') }}</Radio>
                            <Radio label="close">{{ $L('关闭') }}</Radio>
                        </RadioGroup>
                        <div v-if="formData.reg_verify == 'open'" class="form-tip">
                            {{$L('开启后')}}:<br/>
                            ① {{$L('帐号需验证通过才可登录')}}<br/>
                            ② {{$L('修改邮箱和删除帐号需要邮箱验证码')}}
                        </div>
                    </FormItem>
                    <FormItem :label="$L('消息提醒')" prop="notice_msg">
                        <RadioGroup v-model="formData.notice_msg">
                            <Radio label="open">{{ $L('开启') }}</Radio>
                            <Radio label="close">{{ $L('关闭') }}</Radio>
                        </RadioGroup>
                        <Form v-if="formData.notice_msg == 'open'" @submit.native.prevent class="block-setting-msg-unread">
                            <FormItem :label="$L('未读个人消息')" prop="msg_unread_user_minute">
                                <div class="input-number-box">
                                    <InputNumber v-model="formData.msg_unread_user_minute" :min="0" :step="1"/>
                                    <label>{{ $L('分钟') }}(m)</label>
                                </div>
                            </FormItem>
                            <FormItem :label="$L('未读群聊消息')" prop="msg_unread_group_minute">
                                <div class="input-number-box">
                                    <InputNumber v-model="formData.msg_unread_group_minute" :min="0" :step="1"/>
                                    <label>{{ $L('分钟') }}(m)</label>
                                </div>
                            </FormItem>
                            <div class="form-tip">{{$L('填写-1则不通知，误差±10分钟')}}</div>
                        </Form>
                    </FormItem>
                </div>
            </div>

            <div class="block-setting-space"></div>

            <div class="block-setting-box">
                <h3>{{ $L('忽略邮箱地址') }}</h3>
                <div class="form-box">
                    <FormItem :label="$L('忽略邮箱')" prop="ignore_addr">
                        <Input v-model="formData.ignore_addr" type="textarea" :autosize="{ minRows: 3, maxRows: 50 }" />
                        <div class="form-tip">{{$L('不会向忽略的邮箱地址发送邮件，可使用换行分割多个地址。')}}</div>
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
    name: "SystemEmailSetting",
    data() {
        return {
            loadIng: 0,
            formData: {
                smtp_server: '',
                port: '',
                account: '',
                password: '',
                reg_verify: 'colse',
                notice_msg: 'open',
                msg_unread_user_minute: -1,
                msg_unread_group_minute: -1,
                ignore_addr: '',
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
                url: 'system/setting/email?type=' + (save ? 'save' : 'all'),
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

        checkEmailSend() {
            $A.modalInput({
                title: "测试邮件",
                placeholder: "请输入收件人地址",
                onOk: (value) => {
                    if (!value) {
                        return '请输入收件人地址'
                    }
                    if (!$A.isEmail(value)) {
                        return '请输入正确的收件人地址'
                    }
                    return new Promise((resolve, reject) => {
                        this.$store.dispatch("call", {
                            url: 'system/email/check',
                            data: Object.assign(this.formData, {
                                to: value
                            }),
                        }).then(({msg}) => {
                            resolve(msg)
                        }).catch(({msg}) => {
                            reject(msg)
                        });
                    })
                }
            });
        }
    }
}
</script>
