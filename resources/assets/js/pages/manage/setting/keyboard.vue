<template>
    <div class="setting-item submit">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('截图快捷键')" prop="screenshot">
                <div class="input-box">
                    <Checkbox v-model="formData.screenshot_mate">{{mateName}}</Checkbox>
                    <div class="input-box-push">+</div>
                    <Checkbox v-model="formData.screenshot_shift">Shift</Checkbox>
                    <div class="input-box-push">+</div>
                    <Input class="input-box-key" :value="formData.screenshot_key" @on-keyup="onKeydown" :maxlength="1"/>
                </div>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{$L('重置')}}</Button>
        </div>
    </div>
</template>

<style scoped lang="scss">
.input-box {
    display: flex;
    align-items: center;
    .input-box-push {
        opacity: 0.5;
        padding: 0 12px 0 8px;
    }
    .input-box-key {
        width: 80px;
    }
}
</style>
<script>
export default {
    data() {
        return {
            loadIng: 0,

            mateName: /macintosh|mac os x/i.test(navigator.userAgent) ? 'Command' : 'Ctrl',


            formData: {
                screenshot_mate: true,
                screenshot_shift: true,
                screenshot_key: '',
            },

            ruleData: { },
        }
    },

    mounted() {
        this.initData();
    },

    methods: {
        initData() {
            this.formData = Object.assign({
                screenshot_mate: true,
                screenshot_shift: true,
                screenshot_key: '',
            }, $A.jsonParse(window.localStorage['__keyboard:data__'] || {}));
            //
            this.formData_bak = $A.cloneJSON(this.formData);
        },

        onKeydown({key}) {
            this.formData.screenshot_key = key && key.length === 1 ? key.toUpperCase() : ""
        },

        submitForm() {
            this.$refs.formData.validate((valid) => {
                if (valid) {
                    window.localStorage['__keyboard:data__'] = $A.jsonStringify(this.formData);
                    $A.bindScreenshotKey(this.formData);
                }
            })
        },

        resetForm() {
            this.formData = $A.cloneJSON(this.formData_bak);
        }
    }
}
</script>
