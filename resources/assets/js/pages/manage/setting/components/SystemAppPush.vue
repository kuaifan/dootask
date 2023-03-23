<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{ $L('友盟推送') }}</h3>
                <div class="form-box">
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
                    </template>
                </div>
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
    }
}
</script>
