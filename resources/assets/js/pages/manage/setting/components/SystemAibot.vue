<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{ $L('ChatGTP') }}</h3>
                <div class="form-box">
                    <FormItem label="Key" prop="openai_key">
                        <Input :maxlength="255" v-model="formData.openai_key"/>
                    </FormItem>
                    <FormItem :label="$L('使用代理')" prop="openai_agency">
                        <Input :maxlength="500" v-model="formData.openai_agency" :placeholder="$L('支持 http 或 socks 代理')"/>
                    </FormItem>
                </div>
            </div>
            <div class="block-setting-box">
                <h3>{{ $L('Claude') }}</h3>
                <div class="form-box">
                    <FormItem label="Token" prop="claude_token">
                        <Input :maxlength="255" v-model="formData.claude_token"/>
                    </FormItem>
                    <FormItem :label="$L('使用代理')" prop="claude_agency">
                        <Input :maxlength="500" v-model="formData.claude_agency" :placeholder="$L('支持 http 或 socks 代理')"/>
                    </FormItem>
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
    name: "SystemAibot",
    data() {
        return {
            loadIng: 0,
            formData: {},
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
                url: 'system/setting/aibot?type=' + (save ? 'save' : 'all'),
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
