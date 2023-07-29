<template>
    <div class="setting-item submit">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('截图快捷键')" prop="screenshot_key">
                <div class="input-box">
                    {{mateName}}<div class="input-box-push">+</div>Shift<div class="input-box-push">+</div><Input class="input-box-key" v-model="formData.screenshot_key" :maxlength="2"/>
                </div>
            </FormItem>
            <FormItem :label="$L('新建项目')">
                <div class="input-box">
                    {{mateName}}<div class="input-box-push">+</div>B
                </div>
            </FormItem>
            <FormItem :label="$L('新建任务')">
                <div class="input-box">
                    {{mateName}}<div class="input-box-push">+</div>N (K)
                </div>
            </FormItem>
            <FormItem :label="$L('新会议')">
                <div class="input-box">
                    {{mateName}}<div class="input-box-push">+</div>J
                </div>
            </FormItem>
            <FormItem :label="$L('设置')">
                <div class="input-box">
                    {{mateName}}<div class="input-box-push">+</div>,
                </div>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('保存')}}</Button>
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
        width: 60px;
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
                screenshot_key: '',
            },

            ruleData: {
                screenshot_key: [
                    {
                        validator: (rule, value, callback) => {
                            value = value.trim();
                            value = value.substring(value.length - 1)
                            if (value && !/^[A-Za-z0-9]?$/.test(value)) {
                                callback(new Error(this.$L('只能输入字母或数字')));
                            } else {
                                callback();
                            }
                            this.$nextTick(_ => {
                                this.$set(this.formData, rule.field, value.toUpperCase())
                            })
                        },
                        trigger: 'change'
                    },
                ],
            },
        }
    },

    mounted() {
        this.initData();
    },

    methods: {
        initData() {
            this.formData = Object.assign({
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
                    console.log(this.formData);
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
