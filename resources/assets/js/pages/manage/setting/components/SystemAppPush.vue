<template>
    <div class="setting-component-item">
        <Form
            ref="formData"
            :model="formData"
            :rules="ruleData"
            v-bind="formOptions"
            @submit.native.prevent>
            <div class="block-setting-box">
                <h3>{{ $L('DooPush 推送') }}</h3>
                <div class="form-box">
                    <FormItem :label="$L('推送服务')" prop="doopush">
                        <RadioGroup v-model="formData.doopush">
                            <Radio label="system">{{ $L('系统配置') }}</Radio>
                            <Radio label="custom">{{ $L('自定义') }}</Radio>
                            <Radio label="close">{{ $L('关闭') }}</Radio>
                        </RadioGroup>
                        <div v-if="formData.doopush === 'system'" class="form-tip">{{ $L('使用官方推送服务，无需配置。') }}</div>
                        <div v-else-if="formData.doopush === 'custom'" class="form-tip">{{ $L('使用自己的 DooPush 应用，需搭配自行打包的 App。') }}</div>
                    </FormItem>
                    <template v-if="formData.doopush === 'custom'">
                        <FormItem :label="$L('App ID')" prop="doopush_app_id">
                            <Input :maxlength="255" v-model="formData.doopush_app_id"/>
                        </FormItem>
                        <FormItem :label="$L('App Secret')" prop="doopush_app_secret">
                            <Input :maxlength="255" v-model="formData.doopush_app_secret" type="password"/>
                        </FormItem>
                        <FormItem :label="$L('服务地址')" prop="doopush_base_url">
                            <Input :maxlength="255" v-model="formData.doopush_base_url" placeholder="https://doopush.com/api/v1"/>
                        </FormItem>
                    </template>
                </div>
            </div>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{ $L('提交') }}</Button>
            <Button :loading="loadIng > 0" @click="resetForm">{{ $L('重置') }}</Button>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "SystemAppPush",
    data() {
        return {
            loadIng: 0,
            formData: {
                doopush: 'system',
                doopush_app_id: '',
                doopush_app_secret: '',
                doopush_base_url: '',
            },
            ruleData: {},
        }
    },

    mounted() {
        this.systemSetting();
    },

    computed: {
        ...mapState(['formOptions']),
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
