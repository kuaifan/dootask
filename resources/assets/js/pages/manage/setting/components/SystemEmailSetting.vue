<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="email-setting-box">
                <h3>{{ $L('邮箱服务器设置') }}</h3>
                <FormItem :label="$L('SMTP服务器')" prop="smtp_server">
                    <Input v-model="formData.smtp_server"/>
                </FormItem>
                <FormItem :label="$L('端口')" prop="port">
                    <Input :maxlength="20" v-model="formData.port"/>
                </FormItem>
                <FormItem :label="$L('账号')" prop="account">
                    <Input :maxlength="128" v-model="formData.account"/>
                </FormItem>
                <FormItem :label="$L('密码')" prop="password">
                    <Input :maxlength="128" v-model="formData.password" type="password"/>
                </FormItem>
                <FormItem>
                    <Button @click="checkEmailSend">{{ $L('邮件发送测试') }}</Button>
                </FormItem>
            </div>

            <div class="email-setting-placeholder"></div>

            <div class="email-setting-box">
                <h3>{{ $L('邮件通知设置') }}</h3>
                <FormItem :label="$L('开启注册验证')" prop="reg_verify">
                    <RadioGroup v-model="formData.reg_verify">
                        <Radio label="open">{{ $L('开启') }}</Radio>
                        <Radio label="close">{{ $L('关闭') }}</Radio>
                    </RadioGroup>
                    <div v-if="formData.reg_verify == 'open'" class="form-tip">{{$L('开启后账号需验证通过才可登录')}}</div>
                </FormItem>
                <FormItem :label="$L('开启通知')" prop="notice">
                    <RadioGroup v-model="formData.notice">
                        <Radio label="open">{{ $L('开启') }}</Radio>
                        <Radio label="close">{{ $L('关闭') }}</Radio>
                    </RadioGroup>
                </FormItem>
                <template v-if="formData.notice == 'open'">
                    <FormItem :label="$L('任务提醒:')" prop="task_remind_hours">
                        <label>{{ $L('到期前') }}</label>
                        <InputNumber v-model="formData.task_remind_hours" :min="0.5" :step="0.5" @on-change="hoursChange"/>
                        <label>{{ $L('小时') }}</label>
                    </FormItem>
                    <FormItem :label="$L('第二次任务提醒:')" prop="task_remind_hours2">
                        <label>{{ $L('到期后') }}</label>
                        <InputNumber v-model="formData.task_remind_hours2" :min="0.5" :step="0.5" @on-change="hours2Change"/>
                        <label>{{ $L('小时') }}</label>
                    </FormItem>
                </template>
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
                notice: 'open',
                task_remind_hours: 0,
                task_remind_hours2: 0,
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
                this.loadIng--;
                this.formData = data;
                this.formDatum_bak = $A.cloneJSON(this.formData);
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
                this.loadIng--;
            });
        },

        hoursChange(e) {
            let newNum = e * 10;
            if (newNum % 5 !== 0) {
                setTimeout(() => {
                    this.formData.task_remind_hours = 1;
                })
                $A.messageError('任务提醒只能是0.5的倍数');
            }
        },

        hours2Change(e) {
            let newNum = e * 10;
            if (newNum % 5 !== 0) {
                setTimeout(() => {
                    this.formData.task_remind_hours2 = 1;
                })
                $A.messageError('第二次任务提醒只能是0.5的倍数');
            }
        },

        checkEmailSend() {
            $A.modalInput({
                title: "测试邮件",
                placeholder: "请输入收件人地址",
                onOk: (value, cb) => {
                    if (!value) {
                        cb()
                        return
                    }
                    if (!$A.isEmail(value)) {
                        $A.modalError("请输入正确的收件人地址", 301)
                        cb()
                        return
                    }
                    this.$store.dispatch("call", {
                        url: 'system/email/check',
                        data: Object.assign(this.formData, {
                            to: value
                        }),
                    }).then(({msg}) => {
                        $A.messageSuccess(msg)
                        cb()
                    }).catch(({msg}) => {
                        $A.modalError(msg, 301)
                        cb()
                    });
                }
            });
        }
    }
}
</script>
