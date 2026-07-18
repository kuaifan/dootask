<template>
    <Modal
        :value="value"
        :title="modalTitle"
        :mask-closable="false"
        width="520"
        @input="$emit('input', $event)">
        <div class="department-owner-view-modal">
            <Alert type="info" show-icon>
                <template v-if="scopeOnly">{{$L('选择需要统计的部门，包含所选部门及其所有下级部门。')}}</template>
                <template v-else>{{$L('部门范围与负责人仪表盘保持一致；开启后，可只读查看范围内成员参与的项目和任务。')}}</template>
            </Alert>
            <div v-if="!scopeOnly" class="department-owner-view-switch">
                <div>
                    <strong>{{$L('在项目列表中启用')}}</strong>
                    <span class="department-owner-view-switch-desc">{{$L('开启后在项目列表中显示额外的只读项目')}}</span>
                </div>
                <iSwitch v-model="draftProjectViewEnabled"/>
            </div>
            <template v-if="scopeOnly || draftProjectViewEnabled">
                <div v-if="managedDepartments.length > 1" class="department-owner-view-actions">
                    <a href="javascript:void(0)" @click="draftIds=[]">{{$L('清空')}}</a>
                    <a
                        href="javascript:void(0)"
                        @click="draftIds=managedDepartments.map(item => item.id)">{{$L('全选')}}</a>
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
                        <Checkbox
                            class="department-owner-view-checkbox"
                            :label="dept.id"
                            @click.native.stop><span></span></Checkbox>
                    </div>
                </CheckboxGroup>
            </template>
        </div>
        <div slot="footer" class="adaption">
            <Button type="default" :disabled="applyLoading" @click="$emit('input', false)">{{$L('取消')}}</Button>
            <Button type="primary" :disabled="draftIds.length === 0" :loading="applyLoading" @click="apply">{{$L('确定')}}</Button>
        </div>
    </Modal>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "DepartmentOwnerView",
    props: {
        value: Boolean,
        scopeOnly: Boolean,
    },
    data() {
        return {
            draftIds: [],
            draftProjectViewEnabled: false,
            applyLoading: false,
        }
    },
    computed: {
        ...mapState(['userInfo', 'cacheDepartmentOwnerIds', 'departmentOwnerProjectViewEnabled']),
        modalTitle() {
            return this.scopeOnly ? this.$L('选择团队范围') : this.$L('项目负责人视角');
        },
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
                    const cachedIds = (this.cacheDepartmentOwnerIds || []).slice();
                    this.draftIds = cachedIds.length > 0
                        ? cachedIds
                        : this.managedDepartments.map(item => item.id);
                    this.draftProjectViewEnabled = this.departmentOwnerProjectViewEnabled;
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
                await this.$store.dispatch("setDepartmentOwnerIds", this.scopeOnly ? this.draftIds : {
                    ids: this.draftIds,
                    projectViewEnabled: this.draftProjectViewEnabled,
                });
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
    .department-owner-view-switch {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin: 14px 8px 4px;
        > div {
            display: flex;
            flex-direction: column;
            gap: 3px;
            min-width: 0;
        }
        strong {
            font-weight: 500;
        }
        .department-owner-view-switch-desc {
            color: #999;
            font-size: 12px;
            line-height: 18px;
        }
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
