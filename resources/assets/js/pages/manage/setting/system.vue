<template>
    <div class="setting-item submit">
        <Form ref="formDatum" :model="formDatum" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('允许注册')" prop="reg">
                <RadioGroup v-model="formDatum.reg">
                    <Radio label="open">{{$L('允许')}}</Radio>
                    <Radio label="close">{{$L('禁止')}}</Radio>
                </RadioGroup>
            </FormItem>
            <FormItem :label="$L('登录验证码')" prop="loginCode">
                <RadioGroup v-model="formDatum.login_code">
                    <Radio label="auto">{{$L('自动')}}</Radio>
                    <Radio label="open">{{$L('开启')}}</Radio>
                    <Radio label="close">{{$L('关闭')}}</Radio>
                </RadioGroup>
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

        systemSetting(save) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'system/setting?type=' + (save ? 'save' : 'get'),
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
