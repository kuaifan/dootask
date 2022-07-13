<template>
    <div class="setting-item submit">
        <Form ref="formDatum" :model="formDatum" :rules="ruleDatum" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('新邮箱地址')" prop="newEmail">
                <div class="setting-email">
                    <Input v-if="isRegVerify == 1" v-model="formDatum.newEmail"
                           :class="count > 0 ? 'setting-send-input':'setting-input'" search @on-search="sendEmailCode"
                           :enter-button="$L(sendBtnText)" :placeholder="$L('输入新邮箱地址')"/>
                    <Input v-else class="setting-input" v-model="formDatum.newEmail" :placeholder="$L('输入新邮箱地址')"/>
                </div>
            </FormItem>
            <FormItem :label="$L('验证码')" prop="code" v-if="isRegVerify == 1">
                <Input v-model="formDatum.code" :placeholder="$L('输入邮箱验证码')"/>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{ $L('提交') }}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{ $L('重置') }}</Button>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            loadIng: 0,

            formDatum: {
                newEmail: '',
                code: '',
            },
            ruleDatum: {
                newEmail: [
                    {
                        validator: (rule, value, callback) => {
                            if (value.trim() === '') {
                                callback(new Error(this.$L('请输入新邮箱地址！')));
                            } else if (!$A.isEmail(value.trim())) {
                                callback(new Error(this.$L('请输入正确的邮箱地址！')));
                            } else {
                                callback();
                            }
                        },
                        required: true,
                        trigger: 'change'
                    },
                ],
            },
            count: 0,
            isSendButtonShow: true,
            isRegVerify: 0,
            sendBtnText: this.$L('发送验证码')
        }
    },

    mounted() {
        this.getRegVerify();
    },

    methods: {
        sendEmailCode() {
            this.$store.dispatch("call", {
                url: 'users/email/send',
                data: {
                    type: 2,
                    email: this.formDatum.newEmail
                },
                spinner: true
            }).then(_ => {
                this.isSendButtonShow = false;
                this.count = 120; //赋值120秒
                this.sendBtnText = this.count + ' 秒';
                let times = setInterval(() => {
                    this.count--; //递减
                    this.sendBtnText = this.count + ' 秒';
                    if (this.count <= 0) {
                        this.sendBtnText = this.$L('发送验证码')
                        clearInterval(times);
                    }
                }, 1000); //1000毫秒后执行
            }).catch(({msg}) => {
                $A.messageError(msg);
            })
        },

        submitForm() {
            this.$refs.formDatum.validate((valid) => {
                if (valid) {
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'users/email/edit',
                        data: this.formDatum,
                    }).then(({data}) => {
                        $A.messageSuccess('修改成功');
                        this.$store.dispatch("saveUserInfo", data);
                        this.$refs.formDatum.resetFields();
                        this.isSendButtonShow = true;
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                    }).finally(_ => {
                        this.loadIng--;
                    });
                }
            })
        },

        resetForm() {
            this.$refs.formDatum.resetFields();
        },

        getRegVerify() {
            this.$store.dispatch("call", {
                url: 'system/setting/email',
            }).then(({data}) => {
                this.isRegVerify = data.reg_verify === 'open';
            })
        },
    },
}
</script>

