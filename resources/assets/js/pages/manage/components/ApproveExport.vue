<template>
    <Modal
        v-model="show"
        :title="$L('导出审批数据')"
        :mask-closable="false">
        <Form ref="exportTask" :model="formData" label-width="auto" @submit.native.prevent>
            <FormItem :label="$L('审批类型')">
                <Select v-model="formData.proc_def_name" @on-open-change="getProcName">
                    <Option v-for="(item, key) in procList" :value="item.name" :key="key" >{{ item.name }}</Option>
                </Select>
            </FormItem>
            <FormItem :label="$L('时间范围')">
                <DatePicker
                    v-model="formData.date"
                    type="daterange"
                    format="yyyy/MM/dd"
                    style="width:100%"
                    :placeholder="$L('请选择时间')"/>
                <div class="form-tip checkin-export-quick-select">
                    <span>{{$L('快捷选择')}}:</span>
                    <em @click="formData.date=dateShortcuts('prev')">{{$L('上个月')}}</em>
                    <em @click="formData.date=dateShortcuts('this')">{{$L('这个月')}}</em>
                </div>
            </FormItem>
            <FormItem prop="type" :label="$L('导出类型')">
                <RadioGroup v-model="formData.is_finished">
                    <Radio label="0">{{$L('未完成')}}</Radio>
                    <Radio label="1">{{$L('已完成')}}</Radio>
                </RadioGroup>
            </FormItem>
        </Form>
        <div slot="footer" class="adaption">
            <Button type="default" @click="show=false">{{$L('取消')}}</Button>
            <Button type="primary" :loading="loadIng > 0" @click="onExport">{{$L('导出')}}</Button>
        </div>
    </Modal>
</template>

<style lang="scss">
.checkin-export-quick-select {
    > span {
        margin-right: 4px;
    }
    > em {
        margin-right: 4px;
        cursor: pointer;
        color: #2b85e4;
        font-style: normal;
        &:hover {
            text-decoration: underline;
        }
    }
}
</style>
<script>
export default {
    name: "ApproveExport",
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
                proc_def_name: '',
                date: [],
                is_finished:'1',
            },
            procList:[],
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
        dateShortcuts(act) {
            if (act === 'prev') {
                return [$A.getSpecifyDate('上个月'), $A.getSpecifyDate('上个月结束')];
            } else if (act === 'this') {
                return [$A.getSpecifyDate('本月'), $A.getSpecifyDate('本月结束')]
            }
        },

        getProcName(){
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'approve/procdef/all',
                method: 'post'
            }).then(({data}) => {
                this.procList = data['rows'];

            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        onExport() {
            if (this.loadIng > 0) {
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'approve/export',
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
