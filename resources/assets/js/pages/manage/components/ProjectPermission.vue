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
            <Form :model="formData" label-width="100" label-position="right">
                <!-- 任务权限 -->
                <div class="project-permission-title" >{{$L('任务权限')}}:</div>
                <FormItem :label="$L('添加任务')">
                    <CheckboxGroup v-model="formData.task_add">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="3">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('修改任务')">
                    <CheckboxGroup v-model="formData.task_update">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('任务负责人') }}</Checkbox>
                        <Checkbox :label="3">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('标记完成')">
                    <CheckboxGroup v-model="formData.task_update_complete">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('任务负责人') }}</Checkbox>
                        <Checkbox :label="3">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('归档任务')">
                    <CheckboxGroup v-model="formData.task_archived">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('任务负责人') }}</Checkbox>
                        <Checkbox :label="3">{{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('删除任务')">
                    <CheckboxGroup v-model="formData.task_remove">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('任务负责人') }}</Checkbox>
                        <Checkbox :label="3"> {{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('任务移动')">
                    <CheckboxGroup v-model="formData.task_move">
                        <Checkbox :label="1" disabled>{{ $L('项目负责人') }}</Checkbox>
                        <Checkbox :label="2">{{ $L('任务负责人') }}</Checkbox>
                        <Checkbox :label="3"> {{ $L('项目成员') }}</Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <!-- 面板显示 -->
                <div class="project-permission-title" >{{$L('面板显示')}}:</div>
                <FormItem :label="$L('显示已完成')">
                    <RadioGroup v-model="formData.panel_show_task_complete">
                        <Radio :label="1">{{ $L('默认显示') }}</Radio>
                        <Radio :label="0">{{ $L('默认不显示') }}</Radio>
                    </RadioGroup>
                    <div class="form-placeholder">{{ $L('项目面板默认显示已完成的任务') }}</div>
                </FormItem>
            </Form>
            <div slot="footer" class="ivu-modal-footer adaption">
                <Button type="default" @click="onClose()">{{$L('取消')}}</Button>
                <Button type="primary" @click="updateData()">{{$L('修改')}}</Button>
            </div>
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
            formData: {
                task_add: [1,3],
                task_update: [1,2],
                task_update_complete: [1,2],
                task_archived: [1,2],
                task_remove: [1,2],
                task_move: [1,2],
                panel_show_task_complete: 1
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
