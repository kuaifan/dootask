<template>
    <Form class="report-box" label-position="top" @submit.native.prevent>
        <Row class="report-row report-row-header" >
            <Col span="2"><p class="report-titles">{{ $L("汇报类型") }}</p></Col>
            <Col span="6">
                <RadioGroup type="button" button-style="solid" v-model="reportData.type" @on-change="typeChange" class="report-radiogroup">
                    <Radio label="weekly">{{ $L("周报") }}</Radio>
                    <Radio label="daily">{{ $L("日报") }}</Radio>
                </RadioGroup>
            </Col>
            <Col span="6">
                <ButtonGroup class="report-buttongroup">
                    <Tooltip class="report-poptip" trigger="hover"  :content="prevCycleText" placement="bottom">
                        <Button  type="primary" @click="prevCycle">
                            <Icon type="ios-arrow-back" />
                        </Button>
                    </Tooltip>
                    <div class="report-buttongroup-shu"></div>
                    <Tooltip class="report-poptip" trigger="hover"  :content="nextCycleText" placement="bottom">
                        <Button  type="primary" @click="nextCycle" :disabled="reportData.offset >= 0">
                            <Icon type="ios-arrow-forward" />
                        </Button>
                    </Tooltip>
                </ButtonGroup>
            </Col>
        </Row>
        <Row  class="report-row report-row-header">
            <Col span="2"><p class="report-titles">{{ $L("汇报名称") }}</p></Col>
            <Col span="22">
                <Input v-model="reportData.title" disabled placeholder=""></Input>
            </Col>
        </Row>
        <Row  class="report-row report-row-header">
            <Col  span="2"><p class="report-titles">{{ $L("汇报对象") }}</p></Col>
            <Col  span="16">
                <UserInput
                    v-if="userInputShow"
                    v-model="reportData.receive"
                    :placeholder="$L('选择接收人')" />
            </Col>
            <Col  span="6"><a class="report-row-a" href="javascript:void(0);" @click="getLastSubmitter"><Icon class="report-row-a-icon" type="ios-share-outline" />{{ $L("使用上一次提交的接收人") }}</a></Col>
        </Row>
        <Row  class="report-row report-row-content">
            <Col  span="2"><p class="report-titles">{{ $L("汇报内容") }}</p></Col>
            <Col span="22">
                <FormItem>
                    <TEditor v-model="reportData.content" height="550px"/>
                </FormItem>
            </Col>
        </Row>
        <Row  class="report-row report-row-foot">
            <Col  span="2"></Col>
            <Col span="4">
                <FormItem>
                    <Button type="primary" @click="handleSubmit" class="report-bottom">提交</Button>
                    <!--            <Button type="primary" @click="prevCycle">{{ prevCycleText }}</Button>-->
                    <!--            <Button type="primary" @click="nextCycle" :disabled="reportData.offset >= 0">{{ nextCycleText }}</Button>-->
                </FormItem>
            </Col>
            <Col span="4">
                <FormItem>
                    <Button type="primary" class="report-bottom-save">保存</Button>
                    <!--            <Button type="primary" @click="prevCycle">{{ prevCycleText }}</Button>-->
                    <!--            <Button type="primary" @click="nextCycle" :disabled="reportData.offset >= 0">{{ nextCycleText }}</Button>-->
                </FormItem>
            </Col>
        </Row>
    </Form>
</template>

<script>
import UserInput from "../../../components/UserInput"

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
                title: ""
                , content: ""
                , type: "weekly"
                , receive: []
                , id: 0
                , offset: 0 // 以当前日期为基础的周期偏移量。例如选择了上一周那么就是 -1，上一天同理。
            },
            disabledType: false,
            userInputShow: true,
            prevCycleText: "",
            nextCycleText: "",
        };
    },
    watch: {
        id(val) {
            if (this.id > 0) this.getDetail(val);
        },
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
            this.$store.dispatch("call", {
                url: 'report/store',
                data: this.reportData,
                method: 'post',
            }).then(({data, msg}) => {
                // data 结果数据
                this.reportData.content = "";
                this.reportData.receive = [];
                this.disabledType = false;
                // msg 结果描述
                $A.messageSuccess(msg);
                this.$emit("saveSuccess");
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
            });
        },
        getTemplate() {
            this.$store.dispatch("call", {
                url: 'report/template',
                data: {
                    type: this.reportData.type,
                    offset: this.reportData.offset
                },
                method: 'get',
            }).then(({data, msg}) => {
                // data 结果数据
                if (data.id) {
                    this.getDetail(data.id);
                } else {
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

            if (this.id <= 0)
                this.getTemplate();
        },
        getDetail(reportId) {
            this.userInputShow = false;
            this.$store.dispatch("call", {
                url: 'report/detail',
                data: {
                    id: reportId
                },
                method: 'get',
            }).then(({data, msg}) => {
                // data 结果数据
                this.reportData.title = data.title;
                this.reportData.content = data.content;
                this.reportData.receive = data.receives;
                this.reportData.type = data.type_val;
                this.reportData.id = reportId;
                this.disabledType = true;
                this.userInputShow = true;
                // msg 结果描述
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
                this.userInputShow = true;
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
            this.userInputShow = false;
            this.$store.dispatch("call", {
                url: 'report/last_submitter',
                method: 'get',
            }).then(({data, msg}) => {
                // data 结果数据
                this.reportData.receive = data;
                this.userInputShow = true;
                // msg 结果描述
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
                this.userInputShow = true;
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
