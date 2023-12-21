<template>
    <div class="project-permission">
        <div class="permission-title">
            {{$L('权限设置')}}
            <div class="title-icon">
                <Loading v-if="loadIng > 0"/>
                <Icon v-else type="ios-refresh" @click="getData()"/>
            </div>
        </div>
        <div class="permission-content">
            <Form :model="formData" label-width="90" label-position="right">
                <!-- 项目权限 -->
                <div class="project-permission-title" >{{$L('任务列权限')}}:</div>
                <FormItem :label="$L('添加列')">
                    <CheckboxGroup v-model="formData.task_list_add">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('修改列')">
                    <CheckboxGroup v-model="formData.task_list_update">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('删除列')">
                    <CheckboxGroup v-model="formData.task_list_remove">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('排序列')">
                    <CheckboxGroup v-model="formData.task_list_sort">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <!-- 任务权限 -->
                <div class="project-permission-title" >{{$L('任务权限')}}:</div>
                <FormItem :label="$L('添加任务')">
                    <CheckboxGroup v-model="formData.task_add">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('修改任务')">
                    <CheckboxGroup v-model="formData.task_update">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="3">{{ $L('任务负责人') }}</Checkbox>
                        <Checkbox :label="4">{{ $L('任务协助人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('修改状态')">
                    <CheckboxGroup v-model="formData.task_status">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="3">{{ $L('任务负责人') }}</Checkbox>
                        <Checkbox :label="4">{{ $L('任务协助人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('归档任务')">
                    <CheckboxGroup v-model="formData.task_archived">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="3">{{ $L('任务负责人') }}</Checkbox>
                        <Checkbox :label="4">{{ $L('任务协助人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('删除任务')">
                    <CheckboxGroup v-model="formData.task_remove">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="3">{{ $L('任务负责人') }}</Checkbox>
                        <Checkbox :label="4">{{ $L('任务协助人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('移动任务')">
                    <CheckboxGroup v-model="formData.task_move">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="3">{{ $L('任务负责人') }}</Checkbox>
                        <Checkbox :label="4">{{ $L('任务协助人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
            </Form>
        </div>
        <div slot="footer" class="project-permission-footer">
            <Button type="primary" @click="updateData" :loading="loadIng > 0">{{$L('修改')}}</Button>
            <Button type="default" @click="onClose">{{$L('取消')}}</Button>
        </div>
    </div>
</template>

<script>

export default {
    name: "ProjectPermission",
    props: {
        projectId: {
            type: Number,
            default: 0
        },
    },
    data() {
        return {
            loadIng: 0,
            formData: {
                project_task_list: [],
                task_add: [],
                task_update: [],
                task_status: [],
                task_archived: [],
                task_remove: [],
                task_move: []
            }
        }
    },

    mounted() {

    },


    watch: {
        projectId: {
            handler(val) {
                if (val) {
                    this.getData()
                }
            },
            immediate: true
        },
    },

    methods: {
        getData() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/permission',
                data: {
                    project_id: this.projectId,
                },
            }).then(({data}) => {
                this.formData = data.permissions;
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        updateData() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/permission/update',
                method: 'post',
                data: {
                    project_id: this.projectId,
                    ...this.formData
                },
            }).then(({data}) => {
                this.formData = data.permissions;
                this.$Message.success(this.$L('修改成功'));
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        onClose() {
            this.$emit('close')
        },
    }
}
</script>
