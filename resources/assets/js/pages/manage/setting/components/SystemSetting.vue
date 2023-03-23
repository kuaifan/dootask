<template>
    <div class="setting-component-item">
        <Form ref="formDatum" :model="formDatum" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{ $L('帐号相关') }}</h3>
                <div class="form-box">
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
                    <FormItem v-if="['open', 'invite'].includes(formDatum.reg)" :label="$L('注册身份')" prop="reg_identity">
                        <RadioGroup v-model="formDatum.reg_identity">
                            <Radio label="normal">{{$L('正常帐号')}}</Radio>
                            <Radio label="temp">{{$L('临时帐号')}}</Radio>
                        </RadioGroup>
                        <div class="form-tip form-list">
                            <p>{{$L('临时帐号')}}：</p>
                            <ol>
                                <li>{{$L('禁止查看共享所有人的文件。')}}</li>
                                <li>{{$L('禁止发起会话。')}}</li>
                                <li>{{$L('禁止创建群聊。')}}</li>
                                <li>{{$L('禁止拨打电话。')}}</li>
                            </ol>
                        </div>
                    </FormItem>
                    <FormItem :label="$L('登录验证码')" prop="loginCode">
                        <RadioGroup v-model="formDatum.login_code">
                            <Radio label="auto">{{$L('自动')}}</Radio>
                            <Radio label="open">{{$L('开启')}}</Radio>
                            <Radio label="close">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.login_code == 'auto'" class="form-tip">{{$L('自动：密码输入错误后必须添加验证码。')}}</div>
                        <div v-else-if="formDatum.login_code == 'open'" class="form-tip">{{$L('开启：每次登录都需要图形验证码。')}}</div>
                        <div v-else-if="formDatum.login_code == 'close'" class="form-tip">{{$L('关闭：不需要输入图形验证。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('密码策略')" prop="passwordPolicy">
                        <RadioGroup v-model="formDatum.password_policy">
                            <Radio label="simple">{{$L('简单')}}</Radio>
                            <Radio label="complex">{{$L('复杂')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.password_policy == 'simple'" class="form-tip">{{$L('简单：大于或等于6个字符。')}}</div>
                        <div v-else-if="formDatum.password_policy == 'complex'" class="form-tip">{{$L('复杂：大于或等于6个字符，包含数字、字母大小写或者特殊字符。')}}</div>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box">
                <h3>{{ $L('项目相关') }}</h3>
                <div class="form-box">
                    <FormItem :label="$L('邀请项目')" prop="projectInvite">
                        <RadioGroup v-model="formDatum.project_invite">
                            <Radio label="open">{{$L('开启')}}</Radio>
                            <Radio label="close">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.project_invite == 'open'" class="form-tip">{{$L('开启：项目管理员可生成链接邀请成员加入项目。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('自动归档任务')" prop="autoArchived">
                        <RadioGroup :value="formDatum.auto_archived" @on-change="formArchived">
                            <Radio label="open">{{$L('开启')}}</Radio>
                            <Radio label="close">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div class="form-tip">{{$L('任务完成后自动归档。')}}</div>
                        <ETooltip v-if="formDatum.auto_archived=='open'" placement="right" :disabled="windowSmall || $isEEUiApp">
                            <div class="setting-auto-day">
                                <Input v-model="formDatum.archived_day" type="number">
                                    <span slot="append">{{$L('天')}}</span>
                                </Input>
                            </div>
                            <div slot="content">{{$L('任务完成 (*) 天后自动归档。', formDatum.archived_day)}}</div>
                        </ETooltip>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box">
                <h3>{{ $L('消息相关') }}</h3>
                <div class="form-box">
                    <FormItem :label="$L('全员群组禁言')" prop="allGroupMute">
                        <RadioGroup v-model="formDatum.all_group_mute">
                            <Radio label="open">{{$L('开放')}}</Radio>
                            <Radio label="user">{{$L('成员禁言')}}</Radio>
                            <Radio label="all">{{$L('全部禁言')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.all_group_mute == 'open'" class="form-tip">{{$L('开放：所有人都可以发言。')}}</div>
                        <div v-else-if="formDatum.all_group_mute == 'user'" class="form-tip">{{$L('成员禁言：仅管理员可以发言。')}}</div>
                        <div v-else-if="formDatum.all_group_mute == 'all'" class="form-tip">{{$L('全部禁言：所有人都禁止发言。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('自动进入全员群')" prop="allGroupAutoin">
                        <RadioGroup v-model="formDatum.all_group_autoin">
                            <Radio label="yes">{{$L('自动')}}</Radio>
                            <Radio label="no">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.all_group_autoin == 'yes'" class="form-tip">{{$L('自动：注册成功后自动进入全员群。')}}</div>
                        <div v-else-if="formDatum.all_group_autoin == 'no'" class="form-tip">{{$L('关闭：其他成员通过@邀请进入。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('聊天资料')" prop="chatInformation">
                        <RadioGroup v-model="formDatum.chat_information">
                            <Radio label="optional">{{$L('可选')}}</Radio>
                            <Radio label="required">{{$L('必填')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.chat_information == 'required'" class="form-tip">{{$L('必填：发送聊天内容前必须设置昵称、电话。')}}</div>
                        <div v-else class="form-tip">{{$L('如果必填，发送聊天前必须设置昵称、电话。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('匿名消息')" prop="anonMessage">
                        <RadioGroup v-model="formDatum.anon_message">
                            <Radio label="open">{{$L('开启')}}</Radio>
                            <Radio label="close">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.anon_message == 'open'" class="form-tip">{{$L('允许匿名发送消息给其他成员。')}}</div>
                        <div v-else class="form-tip">{{$L('禁止匿名发送消息。')}}</div>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box">
                <h3>{{ $L('其他设置') }}</h3>
                <div class="form-box">
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
                </div>
            </div>
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
                this.formDatum = data;
                this.formDatum_bak = $A.cloneJSON(this.formDatum);
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
            }).finally(_ => {
                this.loadIng--;
            });
        }
    }
}
</script>
