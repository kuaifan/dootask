<template>
    <div class="setting-item submit">
        <Form ref="formData" :model="formData" v-bind="formOptions" @submit.native.prevent>
            <FormItem :label="$L('显示悬浮按钮')">
                <iSwitch
                    v-model="formData.floatButtonVisible"
                    :disabled="loading"/>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" :disabled="loading" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" :disabled="loading" @click="resetForm">{{$L('重置')}}</Button>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";
import emitter from "../../../store/events";
import {
    loadFloatButtonVisible,
    saveFloatButtonVisible,
} from "../../../components/AIAssistant/float-button-preference";

export default {
    data() {
        return {
            loadIng: 0,
            loading: true,
            formData: {
                floatButtonVisible: true,
            },
        };
    },

    computed: {
        ...mapState(['userId', 'formOptions']),
    },

    mounted() {
        this.loadPreference();
    },

    methods: {
        async loadPreference() {
            try {
                this.$set(this.formData, 'floatButtonVisible', await loadFloatButtonVisible(this.userId));
                this.formData_bak = $A.cloneJSON(this.formData);
            } finally {
                this.loading = false;
            }
        },

        async submitForm() {
            const visible = this.formData.floatButtonVisible;
            this.loadIng++;
            try {
                await saveFloatButtonVisible(this.userId, visible);
                emitter.emit('aiAssistantFloatButtonVisibilityChanged', {
                    userId: this.userId,
                    visible,
                });
                $A.messageSuccess('保存成功');
            } catch (error) {
                $A.modalError(error.message);
            } finally {
                this.loadIng--;
            }
        },

        resetForm() {
            this.formData = $A.cloneJSON(this.formData_bak);
        },
    },
};
</script>
