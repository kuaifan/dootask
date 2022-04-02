<template>
    <div class="setting-component-item">
        <Form ref="formDatum" :model="formDatum" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('允许注册')" prop="reg">
                <RadioGroup v-model="formDatum.reg">
                    <Radio label="open">{{$L('允许')}}</Radio>
                    <Radio label="invite">{{$L('邀请码')}}</Radio>
                    <Radio label="close">{{$L('禁止')}}</Radio>
                </RadioGroup>
                <div v-if="formDatum.reg == 'open'" class="form-tip">{{$L('允许：开放注册功能。')}}</div>
                <template v-else-if="formDatum.reg == 'invite'">
                    <div class="form-tip">{{$L('邀请码：注册时需填写下方邀请码。')}}</div>
                    <Input v-model="formDatum.reg_invite" style="width:200px;margin-top:6px">
                        <span slot="prepend">{{$L('邀请码')}}</span>
                    </Input>
                </template>
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
            <FormItem :label="$L('邀请项目')" prop="projectInvite">
                <RadioGroup v-model="formDatum.project_invite">
                    <Radio label="open">{{$L('开启')}}</Radio>
                    <Radio label="close">{{$L('关闭')}}</Radio>
                </RadioGroup>
                <div v-if="formDatum.project_invite == 'open'" class="form-tip">{{$L('开启：项目管理员可生成链接邀请成员加入项目。')}}</div>
            </FormItem>
            <FormItem :label="$L('聊天昵称')" prop="chatNickname">
                <RadioGroup v-model="formDatum.chat_nickname">
                    <Radio label="optional">{{$L('可选')}}</Radio>
                    <Radio label="required">{{$L('必填')}}</Radio>
                </RadioGroup>
                <div v-if="formDatum.chat_nickname == 'required'" class="form-tip">{{$L('必填：发送聊天内容前必须设置昵称。')}}</div>
                <div v-else class="form-tip">{{$L('如果必填，发送聊天前必须设置昵称。')}}</div>
            </FormItem>
            <FormItem :label="$L('自动归档任务')" prop="autoArchived">
                <RadioGroup :value="formDatum.auto_archived" @on-change="formArchived">
                    <Radio label="open">{{$L('开启')}}</Radio>
                    <Radio label="close">{{$L('关闭')}}</Radio>
                </RadioGroup>
                <div class="form-tip">{{$L('任务完成后自动归档。')}}</div>
                <ETooltip v-if="formDatum.auto_archived=='open'" placement="right">
                    <div class="setting-auto-day">
                        <Input v-model="formDatum.archived_day" type="number">
                            <span slot="append">{{$L('天')}}</span>
                        </Input>
                    </div>
                    <div slot="content">{{$L('任务完成 % 天后自动归档。', formDatum.archived_day)}}</div>
                </ETooltip>
            </FormItem>
            <FormItem :label="$L('是否启动首页')" prop="startHome">
                <RadioGroup v-model="formDatum.start_home">
                    <Radio label="open">{{$L('开启')}}</Radio>
                    <Radio label="close">{{$L('关闭')}}</Radio>
                </RadioGroup>
                <div class="form-tip">{{$L('仅支持网页版。')}}</div>
                <Input
                    v-if="formDatum.start_home == 'open'"
                    v-model="formDatum.home_footer"
                    type="textarea"
                    style="margin:8px 0 -8px"
                    :rows="2"
                    :autosize="{ minRows: 2, maxRows: 8 }"
                    :placeholder="$L('首页底部：首页底部网站备案号等信息')"/>
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
    name: 'SystemSetting',

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

        formArchived(value) {
            this.formDatum = { ...this.formDatum, auto_archived: value };
        },

        systemSetting(save) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'system/setting?type=' + (save ? 'save' : 'all'),
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
