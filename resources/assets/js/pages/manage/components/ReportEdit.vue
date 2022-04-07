<template>
    <Form class="report-edit" label-width="auto" @submit.native.prevent>
        <FormItem :label="$L('汇报类型')">
            <RadioGroup
                type="button"
                button-style="solid"
                v-model="reportData.type"
                @on-change="typeChange"
                class="report-radiogroup"
                :readonly="id > 0">
                <Radio label="weekly" :disabled="id > 0 && reportData.type =='daily'">{{ $L("周报") }}</Radio>
                <Radio label="daily" :disabled="id > 0 && reportData.type =='weekly'">{{ $L("日报") }}</Radio>
            </RadioGroup>
            <ButtonGroup v-if="id === 0" class="report-buttongroup">
                <ETooltip :content="prevCycleText" placement="bottom">
                    <Button type="primary" @click="prevCycle">
                        <Icon type="ios-arrow-back" />
                    </Button>
                </ETooltip>
                <div class="report-buttongroup-vertical"></div>
                <ETooltip :disabled="reportData.offset >= 0" :content="nextCycleText" placement="bottom">
                    <Button type="primary" @click="nextCycle" :disabled="reportData.offset >= 0">
                        <Icon type="ios-arrow-forward" />
                    </Button>
                </ETooltip>
            </ButtonGroup>
        </FormItem>
        <FormItem :label="$L('汇报名称')">
            <Input v-model="reportData.title" disabled/>
        </FormItem>
        <FormItem :label="$L('汇报对象')">
            <div class="report-users">
                <UserInput
                    v-model="reportData.receive"
                    :disabledChoice="[userId]"
                    :placeholder="$L('选择接收人')"
                    :transfer="false"/>
                <a class="report-user-link" href="javascript:void(0);" @click="getLastSubmitter">
                    <Icon type="ios-share-outline" />{{ $L("使用我上次的汇报对象") }}
                </a>
            </div>
        </FormItem>
        <FormItem :label="$L('汇报内容')" class="report-content-editor">
            <TEditor v-model="reportData.content" height="100%"/>
        </FormItem>
        <FormItem class="report-foot">
            <Button type="primary" @click="handleSubmit" class="report-bottom">{{$L(id > 0 ? '修改' : '提交')}}</Button>
        </FormItem>
    </Form>
</template>

<script>
import UserInput from "../../../components/UserInput"
import {mapState} from "vuex";

const TEditor = () => import('../../../components/TEditor');
export default {
    name: "ReportEdit",
    components: {
        TEditor, UserInput
    },
    props: {
        id: {
            default: 0,
        }
    },
    data() {
        return {
            reportData: {
                title: "",
                content: "",
                type: "weekly",
                receive: [],
                id: 0,
                offset: 0 // 以当前日期为基础的周期偏移量。例如选择了上一周那么就是 -1，上一天同理。
            },
            prevCycleText: this.$L("上一周"),
            nextCycleText: this.$L("下一周"),
        };
    },
    watch: {
        id: {
            handler(val) {
                if (val > 0) {
                    this.getDetail(val);
                } else {
                    this.reportData.offset = 0;
                    this.reportData.type = "weekly";
                    this.reportData.receive = [];
                    this.getTemplate();
                }
            },
            immediate: true
        },
    },
    computed: {
        ...mapState(["userId"])
    },
    mounted() {
        //
    },
    methods: {
        handleSubmit() {
            if (this.reportData.receive.length === 0) {
                $A.messageError(this.$L("请选择接收人"));
                return false;
            }
            if (this.id === 0 && this.reportData.id > 0) {
                $A.modalConfirm({
                    title: '覆盖提交',
                    content: '你已提交过此日期的报告，是否覆盖提交？',
                    loading: true,
                    onOk: () => {
                        this.doSubmit(true);
                    }
                });
            } else {
                this.doSubmit();
            }
        },

        doSubmit(isModal = false) {
            this.$store.dispatch("call", {
                url: 'report/store',
                data: this.reportData,
                method: 'post',
            }).then(({data, msg}) => {
                isModal && this.$Modal.remove();
                // data 结果数据
                this.reportData.offset = 0;
                this.reportData.type = "weekly";
                this.reportData.receive = [];
                this.getTemplate();
                // msg 结果描述
                $A.messageSuccess(msg);
                this.$emit("saveSuccess", data);
            }).catch(({msg}) => {
                isModal && this.$Modal.remove();
                // msg 错误原因
                $A.messageError(msg);
            });
        },

        getTemplate() {
            this.$store.dispatch("call", {
                url: 'report/template',
                data: {
                    type: this.reportData.type,
                    offset: this.reportData.offset,
                    id: this.id
                },
            }).then(({data}) => {
                // data 结果数据
                if (data.id) {
                    this.reportData.id = data.id;
                    if (this.id > 0) {
                        this.getDetail(data.id);
                    } else {
                        this.reportData.title = data.title;
                        this.reportData.content = data.content;
                    }
                } else {
                    this.reportData.id = 0;
                    this.reportData.title = data.title;
                    this.reportData.content = data.content;
                }
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
            });
        },

        typeChange(value) {
            // 切换汇报类型后偏移量归零
            this.reportData.offset = 0;
            if (value === "weekly") {
                this.prevCycleText = this.$L("上一周");
                this.nextCycleText = this.$L("下一周");
            } else {
                this.prevCycleText = this.$L("上一天");
                this.nextCycleText = this.$L("下一天");
            }
            this.getTemplate();
        },

        getDetail(reportId) {
            this.$store.dispatch("call", {
                url: 'report/detail',
                data: {
                    id: reportId
                },
            }).then(({data}) => {
                // data 结果数据
                this.reportData.title = data.title;
                this.reportData.content = data.content;
                this.reportData.receive = data.receives_user.map(({userid}) => userid);
                this.reportData.type = data.type_val;
                this.reportData.id = reportId;
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
            });
        },

        prevCycle() {
            this.reportData.offset -= 1;
            this.reReportData();
            this.getTemplate();
        },

        nextCycle() {
            // 周期偏移量不允许大于0
            if (this.reportData.offset < 0) {
                this.reportData.offset += 1;
            }
            this.reReportData();
            this.getTemplate();
        },

        // 获取上一次接收人
        getLastSubmitter() {
            this.$store.dispatch("call", {
                url: 'report/last_submitter',
            }).then(({data}) => {
                this.reportData.receive = data;
            }).catch(({msg}) => {
                $A.messageError(msg);
            });
        },

        reReportData() {
            this.reportData.title = "";
            this.reportData.content = "";
            this.reportData.receive = [];
            this.reportData.id = 0;
        }
    }
}
</script>
