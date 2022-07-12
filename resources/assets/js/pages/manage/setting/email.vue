<template>
    <div class="setting-item submit">
        <Form ref="formDatum" :model="formDatum" :rules="ruleDatum" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('新邮箱地址')" prop="newEmail">
                <Row>
                    <Col span="6">
                        <Input v-model="formDatum.newEmail"></Input>
                    </Col>
                    <Col span="6" v-if="isRegVerify == 1">
                        <Button v-if="isUpdateShow" @click="sendEmailCode" type="primary">{{ $L('发送验证码') }}</Button>
                        <Button v-if="!isUpdateShow" disabled><span>{{ count }}</span>{{ $L('秒') }}</Button>
                    </Col>
                </Row>
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
            ruleDatum: {},
            count: 0,
            isUpdateShow: true,
            codeShow: false,
            isRegVerify: 0,
        }
    },
    mounted() {
        this.getRegVerify();
    },
    methods: {
        initLanguage() {
            this.ruleDatum = {
                newEmail: [
                    {
                        validator: (rule, value, callback) => {
                            if (value === '') {
                                callback(new Error(this.$L('请输入新邮箱地址！')));
                            } else if (!$A.isEmail(value)) {
                                callback(new Error(this.$L('请输入正确的邮箱地址！')));
                            } else {
                                callback();
                            }
                        },
                        required: true,
                        trigger: 'change'
                    },
                ],
            };
        },
        sendEmailCode() {
            this.$store.dispatch("call", {
                url: 'users/send/email',
                data: {type: 2, email: this.formDatum.newEmail}
            }).then(({}) => {
                this.isUpdateShow = false;
                this.count = 120; //赋值120秒
                let times = setInterval(() => {
                    this.count--; //递减
                    if (this.count <= 0) {
                        this.isUpdateShow = true;
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
                        url: 'users/editemail',
                        data: this.formDatum,
                    }).then(({data}) => {
                        $A.messageSuccess('修改成功');
                        this.$store.dispatch("saveUserInfo", data);
                        this.$refs.formDatum.resetFields();
                        this.isUpdateShow = true;
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
                url: 'system/get/regverify',
            }).then(({data}) => {
                this.isRegVerify = data;
            }).catch(() => {
            })
        },
    },
}
</script>

