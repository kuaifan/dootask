<template>
    <div class="setting-item submit">
        <Form ref="formData" :model="formData" :rules="ruleData" :labelPosition="formLabelPosition" :labelWidth="formLabelWidth" @submit.native.prevent>
            <FormItem :label="$L('选择语言')" prop="language">
                <Select v-model="formData.language" :placeholder="$L('选项语言')">
                    <Option v-for="(item, index) in languageList" :value="index" :key="index">{{ item }}</Option>
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
import {languageList, languageName, setLanguage} from "../../../language";
import {mapState} from "vuex";

export default {
    data() {
        return {
            loadIng: 0,

            languageList,

            formData: {
                language: '',
            },

            ruleData: { },
        }
    },

    mounted() {
        this.initData();
    },

    computed: {
        ...mapState(['formLabelPosition', 'formLabelWidth']),
    },

    methods: {
        initData() {
            this.$set(this.formData, 'language', languageName);
            this.formData_bak = $A.cloneJSON(this.formData);
        },

        submitForm() {
            this.$refs.formData.validate((valid) => {
                if (valid) {
                    setLanguage(this.formData.language)
                }
            })
        },

        resetForm() {
            this.formData = $A.cloneJSON(this.formData_bak);
        }
    }
}
</script>
