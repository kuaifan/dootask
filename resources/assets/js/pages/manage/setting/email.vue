<template>
    <div class="setting-item submit">
        <Loading v-if="configLoad > 0"/>
        <Form v-else ref="formDatum" :model="formDatum" :rules="ruleDatum" :labelPosition="formLabelPosition" :labelWidth="formLabelWidth" @submit.native.prevent>
            <Alert v-if="isLdap" type="warning">{{$L('LDAP 用户禁止修改邮箱地址')}}</Alert>
            <FormItem :label="$L('新邮箱地址')" prop="newEmail">
                <Input v-if="isRegVerify == 1" v-model="formDatum.newEmail"
                       :class="count > 0 ? 'setting-send-input':'setting-input'" search @on-search="sendEmailCode"
                       :enter-button="$L(sendBtnText)"
                       :disabled="isLdap"
                       :placeholder="$L('输入新邮箱地址')"/>
                <Input v-else class="setting-input" v-model="formDatum.newEmail"
                       :disabled="isLdap"
                       :placeholder="$L('输入新邮箱地址')"/>
            </FormItem>
            <FormItem :label="$L('验证码')" prop="code" v-if="isRegVerify == 1">
                <Input v-model="formDatum.code" :placeholder="$L('输入邮箱验证码')"/>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" :disabled="isLdap" @click="submitForm">{{ $L('提交') }}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{ $L('重置') }}</Button>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    data() {
        return {
            loadIng: 0,
            configLoad: 0,

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

    computed: {
        ...mapState(['formLabelPosition', 'formLabelWidth']),

        isLdap() {
            return this.$store.state.userInfo.identity.includes("ldap")
        }
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
                        this.count = 0;
                        this.sendBtnText = this.$L('发送验证码');
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
            this.configLoad++
            this.$store.dispatch("call", {
                url: 'system/setting/email',
            }).then(({data}) => {
                this.isRegVerify = data.reg_verify === 'open';
            }).finally(_ => {
                this.configLoad--
            })
        },
    },
}
</script>

