<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box">
                <h3>AgoraIO</h3>
                <div class="form-box">
                    <FormItem :label="$L('会议功能')" prop="open">
                        <RadioGroup v-model="formData.open">
                            <Radio label="open">{{ $L('开启') }}</Radio>
                            <Radio label="close">{{ $L('关闭') }}</Radio>
                        </RadioGroup>
                    </FormItem>
                    <template v-if="formData.open === 'open'">
                        <FormItem label="App ID " prop="appid">
                            <Input :maxlength="255" v-model="formData.appid"/>
                        </FormItem>
                        <FormItem label="App certificate" prop="app_certificate">
                            <Input :maxlength="255" v-model="formData.app_certificate" type="password"/>
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
    name: "SystemMeeting",
    data() {
        return {
            loadIng: 0,
            formData: {
                open: '',
                appid: '',
                app_certificate: '',
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
                url: 'system/setting/meeting?type=' + (save ? 'save' : 'all'),
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
        }
    }
}
</script>
