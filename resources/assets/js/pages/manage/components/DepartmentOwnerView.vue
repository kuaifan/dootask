<template>
    <Modal
        :value="value"
        :title="$L('负责人视角')"
        :mask-closable="false"
        width="520"
        @input="$emit('input', $event)">
        <div class="department-owner-view-modal">
            <Alert type="info" show-icon>
                {{$L('可查看所选部门及所有下级部门成员参与的项目和任务，仅支持只读查看。')}}
            </Alert>
            <div v-if="managedDepartments.length > 1" class="department-owner-view-actions">
                <a href="javascript:void(0)" @click="draftIds=[]">{{$L('清空')}}</a>
                <a href="javascript:void(0)" @click="draftIds=managedDepartments.map(item => item.id)">{{$L('全选')}}</a>
                <a href="javascript:void(0)" @click="reverseDraft">{{$L('反选')}}</a>
            </div>
            <CheckboxGroup v-model="draftIds" class="department-owner-view-list">
                <div
                    v-for="dept in managedDepartments"
                    :key="dept.id"
                    :class="['department-owner-view-item', draftIds.includes(dept.id) ? 'active' : '']"
                    @click="toggleDraft(dept.id)">
                    <div class="department-owner-view-icon">
                        <i class="taskfont">&#xe75c;</i>
                    </div>
                    <div class="department-owner-view-name">{{dept.name}}</div>
                    <Checkbox class="department-owner-view-checkbox" :label="dept.id" @click.native.stop><span></span></Checkbox>
                </div>
            </CheckboxGroup>
        </div>
        <div slot="footer" class="adaption">
            <Button type="default" :disabled="applyLoading" @click="$emit('input', false)">{{$L('取消')}}</Button>
            <Button type="primary" :loading="applyLoading" @click="apply">{{$L('确定')}}</Button>
        </div>
    </Modal>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "DepartmentOwnerView",
    props: {
        value: Boolean,
    },
    data() {
        return {
            draftIds: [],
            applyLoading: false,
        }
    },
    computed: {
        ...mapState(['userInfo', 'cacheDepartmentOwnerIds']),
        managedDepartments() {
            return (this.userInfo.managed_departments || []).map(item => ({
                ...item,
                id: parseInt(item.id)
            }));
        },
    },
    watch: {
        value: {
            immediate: true,
            handler(show) {
                if (show) {
                    this.draftIds = (this.cacheDepartmentOwnerIds || []).slice();
                } else {
                    this.applyLoading = false;
                }
            }
        }
    },
    methods: {
        toggleDraft(id) {
            id = parseInt(id);
            const index = this.draftIds.indexOf(id);
            if (index > -1) {
                this.draftIds.splice(index, 1);
            } else {
                this.draftIds.push(id);
            }
        },
        reverseDraft() {
            const selected = this.draftIds.map(id => parseInt(id));
            this.draftIds = this.managedDepartments
                .map(item => item.id)
                .filter(id => !selected.includes(id));
        },
        async apply() {
            if (this.applyLoading) {
                return;
            }
            this.applyLoading = true;
            try {
                await this.$store.dispatch("setDepartmentOwnerIds", this.draftIds);
                this.$emit('input', false);
            } catch (e) {
                $A.modalError(e?.msg || this.$L('切换失败'));
            } finally {
                this.applyLoading = false;
            }
        },
    }
}
</script>

<style lang="scss" scoped>
.department-owner-view-modal {
    .department-owner-view-actions {
        display: flex;
        justify-content: flex-end;
        gap: 14px;
        margin: 12px 8px 0;
    }
    .department-owner-view-list {
        display: flex;
        flex-direction: column;
        margin-top: 10px;
    }
    .department-owner-view-item {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        cursor: pointer;
    }
    .department-owner-view-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background-color: #5BC7B0;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 10px;
    }
    .department-owner-view-name {
        flex: 1;
    }
    .department-owner-view-checkbox {
        margin-right: 0;
    }
}
</style>
