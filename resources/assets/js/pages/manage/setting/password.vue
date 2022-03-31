<template>
    <div class="setting-item submit">
        <Form ref="formDatum" :model="formDatum" :rules="ruleDatum" label-width="auto" @submit.native.prevent>
            <Alert v-if="userInfo.changepass" type="warning" showIcon style="margin-bottom:32px">{{$L('请先修改登录密码！')}}</Alert>
            <FormItem :label="$L('旧密码')" prop="oldpass">
                <Input v-model="formDatum.oldpass" type="password"></Input>
            </FormItem>
            <FormItem :label="$L('新密码')" prop="newpass">
                <Input v-model="formDatum.newpass" type="password"></Input>
            </FormItem>
            <FormItem :label="$L('确认新密码')" prop="checkpass">
                <Input v-model="formDatum.checkpass" type="password"></Input>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{$L('重置')}}</Button>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    data() {
        return {
            loadIng: 0,

            formDatum: {
                oldpass: '',
                newpass: '',
                checkpass: '',
            },

            ruleDatum: { },
        }
    },
    computed: {
        ...mapState(['userInfo']),
    },
    methods: {
        initLanguage() {
            this.ruleDatum = {
                oldpass: [
                    { required: true, message: this.$L('请输入旧密码！'), trigger: 'change' },
                    { type: 'string', min: 6, message: this.$L('密码长度至少6位！'), trigger: 'change' }
                ],
                newpass: [
                    {
                        validator: (rule, value, callback) => {
                            if (value === '') {
                                callback(new Error(this.$L('请输入新密码！')));
                            } else {
                                if (this.formDatum.checkpass !== '') {
                                    this.$refs.formDatum.validateField('checkpass');
                                }
                                callback();
                            }
                        },
                        required: true,
                        trigger: 'change'
                    },
                    { type: 'string', min: 6, message: this.$L('密码长度至少6位！'), trigger: 'change' }
                ],
                checkpass: [
                    {
                        validator: (rule, value, callback) => {
                            if (value === '') {
                                callback(new Error(this.$L('请重新输入新密码！')));
                            } else if (value !== this.formDatum.newpass) {
                                callback(new Error(this.$L('两次密码输入不一致！')));
                            } else {
                                callback();
                            }
                        },
                        required: true,
                        trigger: 'change'
                    }
                ],
            };
        },

        submitForm() {
            this.$refs.formDatum.validate((valid) => {
                if (valid) {
                    this.loadIng++;
                    this.$store.dispatch("call", {
                        url: 'users/editpass',
                        data: this.formDatum,
                    }).then(({data}) => {
                        $A.messageSuccess('修改成功');
                        this.loadIng--;
                        this.$store.dispatch("saveUserInfo", data);
                        this.$refs.formDatum.resetFields();
                    }).catch(({msg}) => {
                        $A.modalError(msg);
                        this.loadIng--;
                    });
                }
            })
        },

        resetForm() {
            this.$refs.formDatum.resetFields();
        }
    }
}
</script>
