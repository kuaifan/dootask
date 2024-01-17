<template>
    <div class="setting-item submit">
        <Form ref="formData" :model="formData" :rules="ruleData" :labelPosition="formLabelPosition" :labelWidth="formLabelWidth" @submit.native.prevent>
            <FormItem :label="$L('选择主题')" prop="theme">
                <Select v-model="formData.theme" :placeholder="$L('选项主题')">
                    <Option v-for="(item, index) in themeList" :value="item.value" :key="index">{{$L(item.name)}}</Option>
                </Select>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{$L('重置')}}</Button>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    data() {
        return {
            loadIng: 0,

            formData: {
                theme: '',
            },

            ruleData: { },
        }
    },

    mounted() {
        this.initData();
    },

    computed: {
        ...mapState([
            'themeConf',
            'themeList',
            'formLabelPosition',
            'formLabelWidth'
        ])
    },

    methods: {
        initData() {
            this.$set(this.formData, 'theme', this.themeConf);
            this.formData_bak = $A.cloneJSON(this.formData);
        },

        submitForm() {
            this.$refs.formData.validate((valid) => {
                if (valid) {
                    this.$store.dispatch("setTheme", this.formData.theme).then(res => {
                        res && $A.messageSuccess('保存成功');
                    })
                }
            })
        },

        resetForm() {
            this.formData = $A.cloneJSON(this.formData_bak);
        }
    }
}
</script>
