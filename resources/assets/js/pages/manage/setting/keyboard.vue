<template>
    <div class="setting-item submit">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('截图快捷键')" prop="screenshot">
                <div class="input-box">
                    <Checkbox v-model="formData.screenshot_mate">{{mateName}}</Checkbox>
                    <div class="input-box-push">+</div>
                    <Checkbox v-model="formData.screenshot_shift">Shift</Checkbox>
                    <div class="input-box-push">+</div>
                    <Input class="input-box-key" :disabled="screenshotDisabled" :value="formData.screenshot_key" @on-keydown="onKeydown" :maxlength="1"/>
                </div>
                <div v-if="screenshotDisabled" class="form-tip red">{{$L('至少选择一个功能键！')}}</div>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" :disabled="screenshotDisabled" type="primary" @click="submitForm">{{$L('保存')}}</Button>
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

    computed: {
        screenshotDisabled() {
            return !this.formData.screenshot_mate && !this.formData.screenshot_shift;
        }
    },

    methods: {
        initData() {
            this.formData = Object.assign({
                screenshot_mate: true,
                screenshot_shift: true,
                screenshot_key: '',
            }, $A.jsonParse(window.localStorage.getItem("__keyboard:data__")) || {});
            //
            this.formData_bak = $A.cloneJSON(this.formData);
        },

        onKeydown({key, keyCode}) {
            if (keyCode !== 8) {
                key = /^[A-Za-z0-9]?$/.test(key) ? key.toUpperCase() : ""
                if (key) {
                    this.formData.screenshot_key = key
                }
            }
        },

        submitForm() {
            this.$refs.formData.validate((valid) => {
                if (valid) {
                    window.localStorage.setItem("__keyboard:data__", $A.jsonStringify(this.formData));
                    $A.bindScreenshotKey(this.formData);
                    $A.messageSuccess('保存成功');
                }
            })
        },

        resetForm() {
            this.formData = $A.cloneJSON(this.formData_bak);
        }
    }
}
</script>
