<template>
    <div class="setting-item submit">
        <Loading v-if="configLoad > 0"/>
        <Form v-else ref="formDatum" :model="formDatum" :rules="ruleDatum" :labelPosition="formLabelPosition" :labelWidth="formLabelWidth" @submit.native.prevent>
            <FormItem :label="$L('帐号')" prop="email">
                <Input v-if="isRegVerify == 1" v-model="formDatum.email"
                       :class="count > 0 ? 'setting-send-input':'setting-input'" search @on-search="sendEmailCode"
                       :enter-button="$L(sendBtnText)" :placeholder="$L('请输入邮箱')"/>
                <Input v-else class="setting-input" v-model="formDatum.email" :placeholder="$L('请输入邮箱帐号')"/>
            </FormItem>
            <FormItem :label="$L('邮箱验证码')" prop="code" v-if="isRegVerify == 1">
                <Input v-model="formDatum.code" :placeholder="$L('请输入邮箱验证码')"/>
            </FormItem>
            <FormItem :label="$L('登录密码')" prop="code" v-else>
                <Input v-model="formDatum.password" type="password" :placeholder="$L('请输入登录密码')"/>
            </FormItem>
            <FormItem :label="$L('删除原因')">
                <Input v-model="formDatum.reason" type="textarea" :autosize="{minRows: 4,maxRows: 8}"
                       :placeholder="$L('请输入删除原因')"></Input>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm('warning')">{{ $L('提交') }}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{ $L('重置') }}</Button>
        </div>
        <Modal
            v-model="warningShow"
            :title="$L(`删除${appTitle}帐号`)"
            class="page-setting-delete-box">
            <div class="big-text">{{ $L('帐号删除后，该帐号将无法正常登录且无法恢复，帐号下的所有数据也将被删除。') }}</div>
            <div class="small-text">
                <div>{{ $L('删除前，请确认以下事项：') }}</div>
                <div>{{ $L('1、您将无法查看该帐号内的任何信息，包括帐号信息、文件记录、聊天记录、项目信息、团队成员信息等。') }}</div>
                <div>{{ $L('2、若你是团队的所有者，请在删除您的帐号前转移所有权。例如该帐号所创建的项目（可将项目移交他人或删除项目）以及文件夹。') }}</div>
                <div>{{ $L('3、您将退出所有群聊，无法查到过往消息和人员。') }}</div>
                <div>{{ $L('4、请保证帐号未被暂停使用。') }}</div>
            </div>
            <div slot="footer" class="button-box">
                <Button type="primary" :loading="loadIng > 0" @click="submitForm('confirm')">{{ $L('已清楚风险，确定删除') }}
                </Button>
            </div>
        </Modal>
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
                email: '',
                code: '',
                reason: '',
                password: '',
            },
            ruleDatum: {
                email: [
                    {
                        validator: (rule, value, callback) => {
                            if (value.trim() === '') {
                                callback(new Error(this.$L('请输入邮箱帐号！')));
                            } else if (!$A.isEmail(value.trim())) {
                                callback(new Error(this.$L('请输入正确邮箱帐号！')));
                            } else {
                                callback();
                            }
                        },
                        required: true,
                        trigger: 'change'
                    },
                ],
                code: [
                    {
                        validator: (rule, value, callback) => {
                            if (value.trim() === '' && this.isRegVerify == 1) {
                                callback(new Error(this.$L('请输入邮箱验证码')));
                            } else {
                                callback();
                            }
                        },
                        required: true,
                        trigger: 'change'
                    },
                ],
                password: [
                    {
                        validator: (rule, value, callback) => {
                            if (value.trim() === '' && this.isRegVerify != 1) {
                                callback(new Error(this.$L('请输入登录密码')));
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
            codeShow: false,
            isRegVerify: 0,
            warningShow: false,
            sendBtnText: this.$L('发送验证码')
        }
    },

    mounted() {
        this.formDatum.email = this.userInfo.email
        this.getRegVerify();
    },

    computed: {
        ...mapState(['userInfo', 'formLabelPosition', 'formLabelWidth']),

        appTitle() {
            return window.systemInfo.title || "DooTask";
        },
    },

    methods: {
        sendEmailCode() {
            if (this.count > 0) {
                return
            }
            this.$store.dispatch("call", {
                url: 'users/email/send',
                data: {
                    type: 3,
                    email: this.formDatum.email
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

        submitForm(type) {
            this.$refs.formDatum.validate((valid) => {
                if (valid) {
                    this.loadIng++;
                    this.formDatum.type = type;
                    this.$store.dispatch("call", {
                        url: 'users/delete/account',
                        data: this.formDatum,
                    }).then(({data}) => {
                        if (type === 'warning') {
                            this.warningShow = true;
                        } else {
                            $A.messageSuccess('删除成功');
                            this.warningShow = false;
                            this.$store.dispatch("saveUserInfo", data);
                            this.isSendButtonShow = true;
                            this.$refs.formDatum.resetFields();
                        }
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

