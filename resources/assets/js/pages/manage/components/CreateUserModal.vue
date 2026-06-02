<template>
    <Modal
        v-model="show"
        :title="$L('创建用户')"
        :mask-closable="false"
        @on-cancel="onCancel">
        <Form ref="form" :model="formData" :label-width="80" @submit.native.prevent>
            <FormItem :label="$L('邮箱')" required>
                <Input v-model="formData.email" :placeholder="$L('请输入邮箱')" clearable/>
                <Checkbox v-model="formData.email_verity" style="margin-top:8px">{{$L('标记邮箱为已认证')}}</Checkbox>
            </FormItem>
            <FormItem :label="$L('昵称')" required>
                <Input v-model="formData.nickname" :placeholder="$L('请输入昵称')" clearable/>
            </FormItem>
            <FormItem :label="$L('初始密码')" required>
                <Input v-model="formData.password" type="password" password :placeholder="$L('请输入初始密码')" clearable/>
                <Checkbox v-model="formData.changepass" style="margin-top:8px">{{$L('员工首次登录需修改密码')}}</Checkbox>
            </FormItem>
            <FormItem :label="$L('职位')">
                <Input v-model="formData.profession" :maxlength="20" :placeholder="$L('请输入职位/职称')" clearable/>
            </FormItem>
            <FormItem :label="$L('所属部门')">
                <Select
                    v-model="formData.department"
                    multiple
                    :multiple-max="10"
                    :multiple-max-before="onMultipleMaxBefore"
                    :placeholder="$L('留空为默认部门')">
                    <Option
                        v-for="(item, index) in departmentList"
                        :value="item.id"
                        :key="index"
                        :label="item.chains.join(' - ')">
                        <div :class="`department-level-name level-${item.level - 1}`">{{ item.name }}</div>
                    </Option>
                </Select>
            </FormItem>
        </Form>
        <div slot="footer">
            <Button type="default" @click="show=false">{{$L('取消')}}</Button>
            <Button type="primary" :loading="loading" @click="onSubmit">{{$L('创建')}}</Button>
        </div>
    </Modal>
</template>

<script>
export default {
    name: 'CreateUserModal',
    props: {
        value: {
            type: Boolean,
            default: false
        },
        departmentList: {
            type: Array,
            default: () => []
        }
    },
    data() {
        return {
            show: false,
            loading: false,
            formData: {email: '', nickname: '', password: '', changepass: true, email_verity: true, profession: '', department: []},
        }
    },
    watch: {
        value(val) {
            this.show = val;
            if (val) {
                this.formData = {email: '', nickname: '', password: '', changepass: true, email_verity: true, profession: '', department: []};
            }
        },
        show(val) {
            this.$emit('input', val);
        },
        // 选择子部门时自动补选其所有上级部门（与 UserEditModal 行为一致）
        'formData.department': {
            handler(value, oldValue = []) {
                if (!Array.isArray(value) || value.length === 0 || this.departmentList.length === 0) {
                    return;
                }
                const previous = Array.isArray(oldValue) ? new Set(oldValue) : new Set();
                const selected = new Set(value);
                const hasNewSelection = Array.from(selected).some(id => !previous.has(id));
                if (!hasNewSelection) {
                    return;
                }
                const departmentMap = this.departmentList.reduce((acc, item) => {
                    acc[item.id] = item;
                    return acc;
                }, {});
                const needAdd = new Set();
                value.forEach((id) => {
                    let cursor = departmentMap[id];
                    while (cursor && cursor.parent_id && cursor.parent_id > 0) {
                        if (!selected.has(cursor.parent_id)) {
                            needAdd.add(cursor.parent_id);
                        }
                        cursor = departmentMap[cursor.parent_id];
                    }
                });
                if (needAdd.size > 0) {
                    const merged = Array.from(new Set([...value, ...needAdd])).sort((a, b) => a - b);
                    if (merged.length !== value.length || merged.some((id, index) => id !== value[index])) {
                        this.$set(this.formData, 'department', merged);
                    }
                }
            },
            deep: true
        }
    },
    methods: {
        onCancel() {
            this.show = false;
        },
        onSubmit() {
            const {email, nickname, password, changepass, email_verity, profession, department} = this.formData;
            if (!email || !nickname || !password) {
                $A.messageWarning('邮箱、昵称、初始密码均为必填');
                return;
            }
            this.loading = true;
            this.$store.dispatch("call", {
                url: 'users/createuser',
                data: {email, nickname, password, changepass: changepass ? 1 : 0, email_verity: email_verity ? 1 : 0, profession, department},
            }).then(() => {
                this.loading = false;
                $A.messageSuccess('创建成功');
                this.show = false;
                this.$emit('created');
            }).catch(({msg}) => {
                this.loading = false;
                $A.modalError(msg);
            });
        },
        onMultipleMaxBefore(num) {
            $A.messageError(this.$L('最多选择(*)个部门', num));
            return false;
        },
    }
}
</script>
