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
                        <Input
                            v-if="formDatum.reg_identity == 'temp'"
                            v-model="formDatum.temp_account_alias"
                            style="width:220px;margin-top:6px"
                            :placeholder="$L('临时帐号')">
                            <span slot="prepend">{{$L('临时帐号')}} {{$L('别名')}}</span>
                        </Input>
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
                        <ETooltip v-if="formDatum.auto_archived=='open'" placement="right" :disabled="$isEEUiApp || windowTouch">
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
                <h3>{{ $L('任务相关') }}</h3>
                <div class="form-box">
                    <FormItem :label="$L('可见性选项')" prop="taskVisible">
                        <RadioGroup v-model="formDatum.task_visible">
                            <Radio label="open">{{$L('保持')}}</Radio>
                            <Radio label="close">{{$L('自动')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.task_visible == 'open'" class="form-tip">{{$L('保持：任务详情页可见性选项保持显示。')}}</div>
                        <div v-else-if="formDatum.task_visible == 'close'" class="form-tip">{{$L('自动：默认值情况下显示在合并项目，设置时保持显示。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('任务默认时间')" prop="taskDefaultTime">
                        <TimePicker
                            v-model="formDatum.task_default_time"
                            type="timerange"
                            format="HH:mm"
                            :placeholder="$L('请选择默认时间')"
                            transfer/>
                        <div class="form-tip">{{$L('添加任务计划时间默认时分。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('未领任务提醒')" prop="autoArchived">
                        <RadioGroup :value="formDatum.unclaimed_task_reminder" @on-change="formTaskReminder">
                            <Radio label="open">{{$L('开启')}}</Radio>
                            <Radio label="close">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div class="form-tip">{{$L('开启后每天按设定的提醒时间在项目群聊中发送未领取任务通知。')}}</div>
                        <TimePicker v-if="formDatum.unclaimed_task_reminder=='open'"
                            v-model="formDatum.unclaimed_task_reminder_time"
                            format="HH:mm"
                            :placeholder="$L('请选择提醒时间')"
                            transfer/>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box">
                <h3>{{ $L('消息相关') }}</h3>
                <div class="form-box">
                    <FormItem :label="$L('自动进入全员群')" prop="allGroupAutoin">
                        <RadioGroup v-model="formDatum.all_group_autoin">
                            <Radio label="yes">{{$L('自动')}}</Radio>
                            <Radio label="no">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.all_group_autoin == 'yes'" class="form-tip">{{$L('自动：注册成功后自动进入全员群。')}}</div>
                        <div v-else-if="formDatum.all_group_autoin == 'no'" class="form-tip">{{$L('关闭：其他成员通过@邀请进入。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('全员群组禁言')" prop="allGroupMute">
                        <RadioGroup v-model="formDatum.all_group_mute">
                            <Radio label="open">{{$L('开放')}}</Radio>
                            <Radio label="close">{{$L('禁言')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.all_group_mute == 'open'" class="form-tip">{{$L('开放：所有人都可以在全员群组发言。')}}</div>
                        <div v-else-if="formDatum.all_group_mute == 'close'" class="form-tip">{{$L('禁言：除管理员外所有人都禁止在全员群组发言。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('私聊禁言')" prop="userPrivateChatMute">
                        <RadioGroup v-model="formDatum.user_private_chat_mute">
                            <Radio label="open">{{$L('开放')}}</Radio>
                            <Radio label="close">{{$L('禁言')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.user_private_chat_mute == 'open'" class="form-tip">{{$L('开放：所有人都可以相互发起个人聊天。')}}</div>
                        <div v-else-if="formDatum.user_private_chat_mute == 'close'" class="form-tip">{{$L('禁言：除管理员外所有人都禁止发起个人聊天。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('群聊禁言')" prop="userGroupChatMute">
                        <RadioGroup v-model="formDatum.user_group_chat_mute">
                            <Radio label="open">{{$L('开放')}}</Radio>
                            <Radio label="close">{{$L('禁言')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.user_group_chat_mute == 'open'" class="form-tip">{{$L('开放：允许个人群组聊天发言。')}}</div>
                        <div v-else-if="formDatum.user_group_chat_mute == 'close'" class="form-tip form-list">
                            <ol>
                                <li>{{$L('除管理员外禁止个人群组聊天发言。')}}</li>
                                <li>{{$L('注意，仅禁止个人群组，其他类型的群组不禁止，比如：部门群聊、项目群聊等系统群聊。')}}</li>
                            </ol>
                        </div>
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
                    <FormItem :label="$L('语音转文字')" prop="voice2text">
                        <RadioGroup v-model="formDatum.voice2text">
                            <Radio label="open">{{$L('开启')}}</Radio>
                            <Radio label="close">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.voice2text == 'open'" class="form-tip">{{$L('长按语音消息可转换成文字。')}} ({{$L('需要在应用中开启 ChatGPT AI 机器人')}})</div>
                        <div v-else class="form-tip">{{$L('关闭语音转文字功能。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('端到端加密')" prop="e2eMessage">
                        <RadioGroup v-model="formDatum.e2e_message">
                            <Radio label="open">{{$L('开启')}}</Radio>
                            <Radio label="close">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div v-if="formDatum.e2e_message == 'open'" class="form-tip">{{$L('使用端到端加密传输数据。')}}</div>
                        <div v-else class="form-tip">{{$L('关闭端到端加密传输数据。')}}</div>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box">
                <h3>{{ $L('其他设置') }}</h3>
                <div class="form-box">
                    <FormItem :label="$L('图片优化')" prop="image_compress">
                        <RadioGroup v-model="formDatum.image_compress">
                            <Radio label="open">{{$L('开启')}}</Radio>
                            <Radio label="close">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div class="form-tip">{{$L('数码相机4M的图片，优化后仅有700KB左右，而且肉眼基本看不出区别。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('保存网络图片')" prop="image_save_local">
                        <RadioGroup v-model="formDatum.image_save_local">
                            <Radio label="open">{{$L('开启')}}</Radio>
                            <Radio label="close">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div class="form-tip">{{$L('是否将消息中的网络图片保存到本地服务器。')}}</div>
                    </FormItem>
                    <FormItem :label="$L('文件上传限制')" prop="fileUploadLimit">
                        <div style="width: 192px;">
                            <Input type="number" number v-model="formDatum.file_upload_limit" :placeholder="$L('默认不限制')">
                                <template #append>
                                <span>MB</span>
                                </template>
                            </Input>
                        </div>
                        <div class="form-tip">{{$L('包含消息发送的文件')}}</div>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box" v-if="$A.isDooServer()">
                <h3>{{ $L('特殊设置') }}</h3>
                <div class="form-box">
                    <FormItem :label="$L('是否启动首页')" prop="startHome">
                        <RadioGroup v-model="formDatum.start_home">
                            <Radio label="open">{{$L('开启')}}</Radio>
                            <Radio label="close">{{$L('关闭')}}</Radio>
                        </RadioGroup>
                        <div class="form-tip">{{$L('仅支持网页版。')}}</div>
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

        formTaskReminder(value) {
            this.formDatum = { ...this.formDatum, unclaimed_task_reminder: value };
        },

        systemSetting(save) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'system/setting?type=' + (save ? 'save' : 'all'),
                method: 'post',
                data: this.formDatum,
            }).then(({data}) => {
                if (save) {
                    $A.messageSuccess('修改成功');
                }
                this.formDatum = data;
                this.formDatum_bak = $A.cloneJSON(this.formDatum);
                this.$store.state.systemConfig = Object.assign(this.formDatum_bak, {
                    __state: "success",
                })
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
