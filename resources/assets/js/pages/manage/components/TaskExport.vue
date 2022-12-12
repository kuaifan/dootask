<template>
    <Modal
        v-model="show"
        :title="$L('导出任务统计')"
        :mask-closable="false">
        <Form ref="exportTask" :model="formData" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('导出成员')">
                <UserInput v-model="formData.userid" :multiple-max="20" :placeholder="$L('请选择成员')"/>
            </FormItem>
            <FormItem :label="$L('时间范围')">
                <DatePicker
                    v-model="formData.time"
                    type="daterange"
                    format="yyyy/MM/dd"
                    style="width:100%"
                    :placeholder="$L('请选择时间')"/>
            </FormItem>
            <FormItem prop="type" :label="$L('导出时间类型')">
                <RadioGroup v-model="formData.type">
                    <Radio label="taskTime">{{$L('任务时间')}}</Radio>
                    <Radio label="createdTime">{{$L('创建时间')}}</Radio>
                </RadioGroup>
            </FormItem>
        </Form>
        <div slot="footer" class="adaption">
            <Button type="default" @click="show=false">{{$L('取消')}}</Button>
            <Button type="primary" :loading="loadIng > 0" @click="onExport">{{$L('导出')}}</Button>
        </div>
    </Modal>
</template>

<script>
import UserInput from "../../../components/UserInput";
export default {
    name: "TaskExport",
    components: {UserInput},
    props: {
        value: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            show: this.value,
            loadIng: 0,
            formData: {
                userid: [],
                time: [],
                type:'taskTime',
            },
        }
    },

    watch: {
        value(v) {
            this.show = v;
        },
        show(v) {
            this.value !== v && this.$emit("input", v)
        }
    },

    methods: {
        onExport() {
            if (this.loadIng > 0) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/task/export',
                data: this.formData,
            }).then(({data}) => {
                this.show = false;
                this.$store.dispatch('downUrl', {
                    url: data.url
                });
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        }
    }
}
</script>
