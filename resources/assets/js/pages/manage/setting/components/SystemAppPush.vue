<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{ $L('友盟推送') }}</h3>
                <FormItem :label="$L('开启推送')" prop="push">
                    <RadioGroup v-model="formData.push">
                        <Radio label="open">{{ $L('开启') }}</Radio>
                        <Radio label="close">{{ $L('关闭') }}</Radio>
                    </RadioGroup>
                </FormItem>
                <template v-if="formData.push === 'open'">
                    <Divider orientation="left">iOS {{$L('参数配置')}}</Divider>
                    <FormItem label="Appkey" prop="ios_appkey">
                        <Input :maxlength="255" v-model="formData.ios_key"/>
                    </FormItem>
                    <FormItem label="App Master Secret" prop="secret">
                        <Input :maxlength="255" v-model="formData.ios_secret" type="password"/>
                    </FormItem>
                    <Divider orientation="left">Android {{$L('参数配置')}}</Divider>
                    <FormItem label="Appkey" prop="android_appkey">
                        <Input :maxlength="255" v-model="formData.android_key"/>
                    </FormItem>
                    <FormItem label="App Master Secret" prop="secret">
                        <Input :maxlength="255" v-model="formData.android_secret" type="password"/>
                    </FormItem>
                    <Divider orientation="left">{{$L('推送内容')}}</Divider>
                    <FormItem :label="$L('消息提醒')" prop="push_msg">
                        <RadioGroup v-model="formData.push_msg">
                            <Radio label="open">{{ $L('开启') }}</Radio>
                            <Radio label="close">{{ $L('关闭') }}</Radio>
                        </RadioGroup>
                    </FormItem>
                    <FormItem :label="$L('任务提醒')" prop="push_task">
                        <RadioGroup v-model="formData.push_task">
                            <Radio label="open">{{ $L('开启') }}</Radio>
                            <Radio label="close">{{ $L('关闭') }}</Radio>
                        </RadioGroup>
                        <Form v-if="formData.push_task == 'open'" @submit.native.prevent>
                            <FormItem :label="$L('任务开始')" prop="task_start_minute">
                                <div class="input-number-box">
                                    <InputNumber v-model="formData.task_start_minute" :min="0" :step="1"/>
                                    <label>{{ $L('分钟') }}(m)</label>
                                </div>
                            </FormItem>
                            <FormItem :label="$L('距离到期')" prop="task_remind_hours">
                                <div class="input-number-box">
                                    <InputNumber v-model="formData.task_remind_hours" :min="0" :step="0.5" @on-change="hoursChange($event, 'task_remind_hours')"/>
                                    <label>{{ $L('小时') }}(h)</label>
                                </div>
                            </FormItem>
                            <FormItem :label="$L('到期超时')" prop="task_remind_hours2">
                                <div class="input-number-box">
                                    <InputNumber v-model="formData.task_remind_hours2" :min="0" :step="0.5" @on-change="hoursChange($event, 'task_remind_hours2')"/>
                                    <label>{{ $L('小时') }}(h)</label>
                                </div>
                            </FormItem>
                            <div class="form-tip">{{$L('填写-1则不通知，误差±10分钟')}}</div>
                        </Form>
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
    name: "SystemAppPush",
    data() {
        return {
            loadIng: 0,
            formData: {
                push: '',
                ios_key: '',
                ios_secret: '',
                android_key: '',
                android_secret: '',
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
                url: 'system/setting/apppush?type=' + (save ? 'save' : 'all'),
                data: this.formData,
            }).then(({data}) => {
                if (save) {
                    $A.messageSuccess('修改成功');
                }
                this.formData = data;
                this.formDatum_bak = $A.cloneJSON(this.formData);
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
            }).finally(_ => {
                this.loadIng--;
            });
        },

        hoursChange(e, key) {
            let newNum = e * 10;
            if (newNum % 5 !== 0) {
                setTimeout(() => {
                    this.$set(this.formData, key, Math.round(e))
                })
                $A.messageError('任务提醒只能是0.5的倍数');
            }
        },
    }
}
</script>
