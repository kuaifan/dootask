<template>
    <Form class="report-box" label-position="top" @submit.native.prevent>
        <Row class="report-row">
            <Col span="2">
                <p class="report-titles">{{ $L("汇报类型") }}</p>
            </Col>
            <Col span="12">
                <RadioGroup type="button" button-style="solid" v-model="reportData.type" @on-change="typeChange" class="report-radiogroup" :readonly="id > 0">
                    <Radio label="weekly" :disabled="id > 0 && reportData.type =='daily'">{{ $L("周报") }}</Radio>
                    <Radio label="daily" :disabled="id > 0 && reportData.type =='weekly'">{{ $L("日报") }}</Radio>
                </RadioGroup>
                <ButtonGroup class="report-buttongroup" v-if="id === 0">
                    <ETooltip class="report-poptip" :content="prevCycleText" placement="bottom">
                        <Button type="primary" @click="prevCycle">
                            <Icon type="ios-arrow-back" />
                        </Button>
                    </ETooltip>
                    <div class="report-buttongroup-vertical"></div>
                    <ETooltip class="report-poptip" :disabled="reportData.offset >= 0" :content="nextCycleText" placement="bottom">
                        <Button type="primary" @click="nextCycle" :disabled="reportData.offset >= 0">
                            <Icon type="ios-arrow-forward" />
                        </Button>
                    </ETooltip>
                </ButtonGroup>
            </Col>
        </Row>
        <Row class="report-row">
            <Col span="2">
                <p class="report-titles">{{ $L("汇报名称") }}</p>
            </Col>
            <Col span="22">
                <Input v-model="reportData.title" disabled/>
            </Col>
        </Row>
        <Row class="report-row">
            <Col span="2">
                <p class="report-titles">{{ $L("汇报对象") }}</p>
            </Col>
            <Col span="22">
                <div class="report-users">
                    <UserInput
                        v-model="reportData.receive"
                        :disabledChoice="[userId]"
                        :placeholder="$L('选择接收人')"
                        :transfer="false"/>
                    <a class="report-row-a" href="javascript:void(0);" @click="getLastSubmitter">
                        <Icon class="report-row-a-icon" type="ios-share-outline" />{{ $L("使用我上次的汇报对象") }}
                    </a>
                </div>
            </Col>
        </Row>
        <Row class="report-row report-row-content">
            <Col span="2">
                <p class="report-titles">{{ $L("汇报内容") }}</p>
            </Col>
            <Col span="22">
                <FormItem class="report-row-content-editor">
                    <TEditor v-model="reportData.content" height="100%"/>
                </FormItem>
            </Col>
        </Row>
        <Row class="report-row report-row-foot">
            <Col span="2"></Col>
            <Col span="4">
                <Button type="primary" @click="handleSubmit" class="report-bottom">{{$L(id > 0 ? '修改' : '提交')}}</Button>
            </Col>
        </Row>
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
            disabledType: false,
            prevCycleText: "",
            nextCycleText: "",
        };
    },
    watch: {
        id(val) {
            if (this.id > 0) {
                this.getDetail(val);
            }else{
                this.reportData.offset = 0;
                this.reportData.type = "weekly";
                this.reportData.receive = [];
                this.getTemplate();
            }
        },
    },
    computed: {
        ...mapState(["userId"])
    },
    mounted() {
        this.getTemplate();
    },
    methods: {
        initLanguage () {
            this.prevCycleText = this.$L("上一周");
            this.nextCycleText = this.$L("下一周");
        },

        handleSubmit: function () {
            if (this.reportData.receive.length === 0) {
                $A.messageError(this.$L("请选择接收人"));
                return false;
            }
            if (this.id === 0 && this.reportData.id > 1) {
                $A.modalConfirm({
                    title: '覆盖提交',
                    content: '你已提交过此日期的报告，是否覆盖提交？',
                    loading: true,
                    append: this.$el,
                    onOk: () => {
                        this.doSubmit();
                    }
                });
            } else {
                this.doSubmit();
            }
        },

        doSubmit() {
            this.$store.dispatch("call", {
                url: 'report/store',
                data: this.reportData,
                method: 'post',
            }).then(({data, msg}) => {
                // data 结果数据
                this.reportData.offset = 0;
                this.reportData.type = "weekly";
                this.reportData.receive = [];
                this.getTemplate();
                this.disabledType = false;
                this.$Modal.remove();
                // msg 结果描述
                $A.messageSuccess(msg);
                this.$emit("saveSuccess");
            }).catch(({msg}) => {
                this.$Modal.remove();
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
            }).then(({data, msg}) => {
                // data 结果数据
                if (data.id) {
                    this.reportData.id = data.id;
                    if(this.id > 0){
                        this.getDetail(data.id);
                    }else{
                        this.reportData.title = data.title;
                        this.reportData.content = data.content;
                    }
                } else {
                    this.reportData.id = 0;
                    this.reportData.title = data.title;
                    this.reportData.content = data.content;
                }
                // msg 结果描述
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
            });
        },

        typeChange(value) {
            // 切换汇报类型后偏移量归零
            this.reportData.offset = 0;
            if ( value === "weekly" ) {
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
            }).then(({data, msg}) => {
                // data 结果数据
                this.reportData.title = data.title;
                this.reportData.content = data.content;
                this.reportData.receive = data.receives_user.map(({userid}) => userid);
                this.reportData.type = data.type_val;
                this.reportData.id = reportId;
                this.disabledType = true;
                // msg 结果描述
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
            });
        },

        prevCycle() {
            this.reportData.offset -= 1;
            this.disabledType = false;
            this.reReportData();
            this.getTemplate();
        },

        nextCycle() {
            // 周期偏移量不允许大于0
            if ( this.reportData.offset < 0 ) {
                this.reportData.offset += 1;
            }
            this.disabledType = false;
            this.reReportData();
            this.getTemplate();
        },

        // 获取上一次接收人
        getLastSubmitter() {
            this.$store.dispatch("call", {
                url: 'report/last_submitter',
            }).then(({data, msg}) => {
                // data 结果数据
                this.reportData.receive = data;
                // msg 结果描述
            }).catch(({msg}) => {
                // msg 错误原因
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

<style scoped>

</style>
