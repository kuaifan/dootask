<template>
    <div class="setting-component-item">
        <Form ref="formData" :model="formData" :rules="ruleData" label-width="auto" @submit.native.prevent>
            <div class="notify-collapse-action">
                <a href="javascript:void(0)" @click="openAll">{{$L('全部展开')}}</a>
                <a href="javascript:void(0)" @click="closeAll">{{$L('全部收起')}}</a>
            </div>
            <Collapse v-model="collapseAction">
                <Panel v-for="item in modeLists" v-if="item.params.length > 0" :name="item.mode" :key="item.mode">
                    {{$L(item.label)}}
                    <div slot="content" class="notify-collapse-content">
                        <FormItem v-for="param in item.params" :label="$L(param.label)" :key="`${item.mode}_${param.key}`">
                            <Input v-model="formData[`${item.mode}_${param.key}`]" :type="param.inputType || 'text'" :maxlength="128"/>
                            <div v-if="item.mode === 'telegram'" class="form-tip">{{$L('打开 Telegram Bot 发送 “邮箱#密码” (例如:admin@admin.com#123456) 订阅节点异常日志等。')}}</div>
                        </FormItem>
                        <FormItem v-if="item.applyUrl" :label="$L('申请通道')">
                            <div class="apply-url"><a :href="item.applyUrl" target="_blank">{{item.applyUrl}}</a></div>
                        </FormItem>
                    </div>
                </Panel>
            </Collapse>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{ $L('提交') }}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{ $L('重置') }}</Button>
        </div>
    </div>
</template>

<script>
export default {
    name: "NotifyConfig",
    props: {
        modeLists: {
            type: Array,
            default: () => []
        },
    },
    data() {
        return {
            collapseAction: ['mail'],

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
                url: 'system/notify/config?type=' + (save ? 'save' : 'all'),
                data: this.formData,
            }).then(({data}) => {
                if (save) {
                    $A.messageSuccess('修改成功');
                }
                this.loadIng--;
                this.formData = data;
                this.formDatum_bak = $A.cloneJSON(this.formData);
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
                this.loadIng--;
            });
        },

        openAll() {
            this.collapseAction = this.modeLists.map(item => item.mode);
        },

        closeAll() {
            this.collapseAction = [];
        }
    }
}
</script>
