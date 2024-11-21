<template>
    <Modal
        v-model="show"
        :title="$L('导出任务统计')"
        :mask-closable="false">
        <Form ref="exportTask" :model="formData" v-bind="formOptions"  @submit.native.prevent>
            <FormItem :label="$L('导出成员')">
                <UserSelect v-model="formData.userid" :multiple-max="100" avatar-name show-disable :title="$L('请选择成员')"/>
                <div class="form-tip">{{$L('每次最多选择导出100个成员')}}</div>
            </FormItem>
            <FormItem :label="$L('时间范围')">
                <DatePicker
                    v-model="formData.time"
                    type="daterange"
                    format="yyyy/MM/dd"
                    style="width:100%"
                    :placeholder="$L('请选择时间')"/>
                <div class="form-tip form-quick-select">
                    <span>{{$L('快捷选择')}}:</span>
                    <em @click="formData.time=dateShortcuts('prev')">{{$L('上个月')}}</em>
                    <em @click="formData.time=dateShortcuts('this')">{{$L('这个月')}}</em>
                </div>
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
import UserSelect from "../../../components/UserSelect.vue";
import {mapState} from "vuex";
export default {
    name: "TaskExport",
    components: {UserSelect},
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

    computed: {
        ...mapState(['formOptions']),
    },

    methods: {
        dateShortcuts(act) {
            if (act === 'prev') {
                return [
                    $A.daytz().subtract(1, 'month').startOf('month').format('YYYY-MM-DD'),
                    $A.daytz().subtract(1, 'month').endOf('month').format('YYYY-MM-DD'),
                ];
            } else if (act === 'this') {
                return [
                    $A.daytz().startOf('month').format('YYYY-MM-DD'),
                    $A.daytz().endOf('month').format('YYYY-MM-DD'),
                ]
            }
        },

        onExport() {
            if (this.loadIng > 0) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'project/task/export',
                data: this.formData,
            }).then(() => {
                this.show = false;
                $A.modalSuccess('正在打包，请留意系统消息。');
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        }
    }
}
</script>
