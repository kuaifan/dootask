<template>
    <div class="project-permission">
        <div class="permission-title">
            {{$L('权限设置')}}
            <div class="title-icon">
                <Loading v-if="loadIng > 0"/>
                <Icon v-else type="ios-refresh" @click="getData"/>
            </div>
        </div>
        <div class="permission-content">

            <Form :model="formData" label-width="100" label-position="right">
                <!-- 任务权限 -->
                <div class="project-permission-title" >{{$L('任务权限')}}:</div>
                <FormItem :label="$L('添加任务')">
                    <CheckboxGroup v-model="formData.task_add">
                        <Checkbox :label="$L('项目负责人')" :value="1" disabled></Checkbox>
                        <Checkbox :label="$L('项目成员')" :value="3"></Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('修改任务')">
                    <CheckboxGroup v-model="formData.task_edit">
                        <Checkbox :label="$L('项目负责人')" :value="1" disabled></Checkbox>
                        <Checkbox :label="$L('任务负责人')" :value="2"></Checkbox>
                        <Checkbox :label="$L('项目成员')" :value="3"></Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('标记完成')">
                    <CheckboxGroup v-model="formData.task_mark_complete">
                        <Checkbox :label="$L('项目负责人')" :value="1" disabled></Checkbox>
                        <Checkbox :label="$L('任务负责人')" :value="2"></Checkbox>
                        <Checkbox :label="$L('项目成员')" :value="3"></Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('归档任务')">
                    <CheckboxGroup v-model="formData.task_archiving">
                        <Checkbox :label="$L('项目负责人')" :value="1" disabled></Checkbox>
                        <Checkbox :label="$L('任务负责人')" :value="2"></Checkbox>
                        <Checkbox :label="$L('项目成员')" :value="3"></Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <FormItem :label="$L('删除任务')">
                    <CheckboxGroup v-model="formData.task_delete">
                        <Checkbox :label="$L('项目负责人')" :value="1" disabled></Checkbox>
                        <Checkbox :label="$L('任务负责人')" :value="2"></Checkbox>
                        <Checkbox :label="$L('项目成员')" :value="3"></Checkbox>
                    </CheckboxGroup>
                </FormItem>
                <!-- 面板显示 -->
                <div class="project-permission-title" >{{$L('面板显示')}}:</div>
                <FormItem :label="$L('显示已完成')">
                    <RadioGroup v-model="formData.panel_display">
                        <Radio :label="$L('默认显示')" :value="1" ></Radio>
                        <Radio :label="$L('默认不显示')" :value="0"></Radio>
                    </RadioGroup>
                    <div class="form-placeholder">{{ $L('项目面板默认显示已完成的任务') }}</div>
                </FormItem>
            </Form>

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
                task_edit: [1],
                task_mark_complete: [1],
                task_archiving: [1],
                task_delete: [1],
                panel_display: 0
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
                url: 'project/flow/list',
                data: {
                    project_id: this.projectId,
                },
            }).then(({data}) => {
                // this.list = data.map(item => {
                //     item.project_flow_bak = JSON.stringify(item.project_flow_item)
                //     return item;
                // });
                // this.openIndex = this.list.length === 1 ? ("index_" + this.list[0].id) : ""
                // this.$nextTick(this.syncScroller);
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

    }
}
</script>
