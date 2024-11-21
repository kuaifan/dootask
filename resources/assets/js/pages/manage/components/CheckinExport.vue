<template>
    <Modal
        v-model="show"
        :title="$L('导出签到数据')"
        :mask-closable="false">
        <Form ref="export" :model="formData" v-bind="formOptions" @submit.native.prevent>
            <FormItem :label="$L('导出成员')">
                <UserSelect v-model="formData.userid" :multiple-max="100" avatar-name show-disable :title="$L('请选择成员')"/>
                <div class="form-tip">{{$L('每次最多选择导出100个成员')}}</div>
            </FormItem>
            <FormItem :label="$L('签到日期')">
                <DatePicker
                    v-model="formData.date"
                    type="daterange"
                    format="yyyy/MM/dd"
                    style="width:100%"
                    :placeholder="$L('请选择签到日期')"/>
                <div class="form-tip form-quick-select">
                    <span>{{$L('快捷选择')}}:</span>
                    <em @click="formData.date=dateShortcuts('prev')">{{$L('上个月')}}</em>
                    <em @click="formData.date=dateShortcuts('this')">{{$L('这个月')}}</em>
                </div>
            </FormItem>
            <FormItem :label="$L('班次时间')">
                <TimePicker
                    v-model="formData.time"
                    type="timerange"
                    format="HH:mm"
                    style="width:100%"
                    :placeholder="$L('请选择班次时间')"/>
                <div class="form-tip form-quick-select">
                    <span>{{$L('快捷选择')}}:</span>
                    <em @click="formData.time=['8:30', '18:00']">8:30-18:00</em>
                    <em @click="formData.time=['9:00', '18:00']">9:00-18:00</em>
                    <em @click="formData.time=['9:30', '18:00']">9:30-18:30</em>
                </div>
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
    name: "CheckinExport",
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
                date: [],
                time: [],
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
                url: 'system/checkin/export',
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
