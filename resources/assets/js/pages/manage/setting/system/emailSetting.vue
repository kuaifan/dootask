<template>
    <div class="setting-item submit">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <h3>{{ $L('邮箱服务器设置') }}</h3>
            <FormItem :label="$L('SMTP服务器')" prop="smtp_server">
                <Input v-model="formData.smtp_server"/>
            </FormItem>
            <FormItem :label="$L('端口')" prop="port">
                <Input :maxlength="20" v-model="formData.port"/>
            </FormItem>
            <FormItem :label="$L('账号')" prop="account">
                <Input :maxlength="20" v-model="formData.account"/>
            </FormItem>
            <FormItem :label="$L('密码')" prop="password">
                <Input :maxlength="20" v-model="formData.password"/>
            </FormItem>

            <h3>{{ $L('邮件通知设置') }}</h3>
            <FormItem :label="$L('开启注册验证')" prop="reg_verify">
                <RadioGroup v-model="formData.reg_verify">
                    <Radio label="open">{{ $L('开启') }}</Radio>
                    <Radio label="close">{{ $L('关闭') }}</Radio>
                </RadioGroup>
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
                    <InputNumber v-model="formData.task_remind_hours"/>
                    <label>{{ $L('小时') }}</label>
                </FormItem>
                <FormItem :label="$L('第二次任务提醒:')" prop="task_remind_hours2">
                    <label>{{ $L('到期前') }}</label>
                    <InputNumber v-model="formData.task_remind_hours2"/>
                    <label>{{ $L('小时') }}</label>
                </FormItem>
            </template>
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

        formArchived(value) {
            this.formData = {...this.formData, auto_archived: value};
        },

        systemSetting(save) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'system/emailSetting?type=' + (save ? 'save' : 'all'),
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
        }
    }
}
</script>

<style scoped>

</style>
