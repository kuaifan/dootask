<template>
    <div class="report-edit-wrapper">
        <Form class="report-edit" v-bind="formOptions" @submit.native.prevent>
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
                    <ETooltip :disabled="$isEEUIApp || windowTouch" :content="prevCycleText" placement="bottom">
                        <Button type="primary" @click="prevCycle">
                            <Icon type="ios-arrow-back" />
                        </Button>
                    </ETooltip>
                    <div class="report-buttongroup-vertical"></div>
                    <ETooltip :disabled="$isEEUIApp || windowTouch || reportData.offset >= 0" :content="nextCycleText" placement="bottom">
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
                    <UserSelect v-model="reportData.receive" :disabledChoice="[userId]" :title="$L('选择接收人')"/>
                    <a class="report-user-link" href="javascript:void(0);" @click="getLastSubmitter">
                        <Icon v-if="receiveLoad > 0" type="ios-loading" class="icon-loading"/>
                        <Icon v-else type="ios-share-outline" />
                        {{ $L("使用我上次的汇报对象") }}
                    </a>
                </div>
            </FormItem>
            <FormItem :label="$L('汇报内容')" class="report-content-editor">
                <TEditor ref="reportEditor" v-model="reportData.content" height="100%"/>
            </FormItem>
            <FormItem class="report-foot">
                <div class="report-bottoms">
                    <Button type="primary" @click="handleSubmit" :loading="loadIng > 0" class="report-bottom">{{$L(id > 0 ? '修改' : '提交')}}</Button>
                    <Button
                        type="default"
                        class="report-bottom"
                        @click="onOrganize">
                        <Icon type="md-construct" />
                        {{ $L("AI 整理汇报") }}
                    </Button>
                </div>
            </FormItem>
        </Form>
    </div>
</template>

<script>
import UserSelect from "../../../components/UserSelect.vue";
import {mapState} from "vuex";
import emitter from "../../../store/events";
import {MarkdownConver} from "../../../utils/markdown";
import {extractPlainText} from "../../../utils/text";
import {REPORT_AI_SYSTEM_PROMPT, withLanguagePreferencePrompt} from "../../../utils/ai";

const TEditor = () => import('../../../components/TEditor');
export default {
    name: "ReportEdit",
    components: {
        UserSelect,
        TEditor
    },
    props: {
        id: {
            default: 0,
        }
    },
    data() {
        return {
            loadIng: 0,
            receiveLoad: 0,

            reportData: {
                sign: "",
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
        ...mapState(['formOptions']),
    },
    methods: {
        handleSubmit() {
            if (this.id === 0 && this.reportData.id > 0) {
                $A.modalConfirm({
                    title: '覆盖提交',
                    content: '你已提交过此日期的报告，是否覆盖提交？',
                    onOk: () => {
                        this.doSubmit();
                    }
                });
            } else {
                this.doSubmit();
            }
        },

        doSubmit() {
            this.loadIng++;
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
                // msg 结果描述
                !this.$isSubElectron && $A.messageSuccess(msg);
                this.$emit("saveSuccess", {data, msg});
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        getTemplate() {
            this.loadIng++;
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
                        this.reportData.sign = data.sign;
                        this.reportData.title = data.title;
                        this.reportData.content = data.content;
                    }
                } else {
                    this.reportData.id = 0;
                    this.reportData.sign = data.sign;
                    this.reportData.title = data.title;
                    this.reportData.content = data.content;
                }
            }).catch(({msg}) => {
                // msg 错误原因
                $A.messageError(msg);
            }).finally(_ => {
                this.loadIng--;
            });
        },

        typeChange(value) {
            // 切换汇报类型后偏移量归零
            this.reportData.offset = 0;
            if (value === "weekly") {
                this.prevCycleText = this.$L("上一周");
                this.nextCycleText = this.$L("下一周");
            } else {
                this.prevCycleText = this.$L("前一天");
                this.nextCycleText = this.$L("后一天");
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
            setTimeout(_ => {
                this.receiveLoad++;
            }, 300)
            this.$store.dispatch("call", {
                url: 'report/last_submitter',
            }).then(({data}) => {
                this.reportData.receive = data;
                if (data.length === 0) {
                    $A.messageWarning("没有上次的汇报对象");
                }
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(_ => {
                this.receiveLoad--;
            });
        },

        reReportData() {
            this.reportData.title = "";
            this.reportData.content = "";
            this.reportData.receive = [];
            this.reportData.id = 0;
        },

        onOrganize() {
            if (!this.reportData.content || !this.reportData.content.trim()) {
                $A.messageWarning("请先填写汇报内容");
                return;
            }
            emitter.emit('openAIAssistant', {
                sessionKey: 'report-edit',
                title: this.$L('AI 整理汇报'),
                placeholder: this.$L('补充你想强调的重点或特殊说明，AI 将在此基础上整理汇报'),
                onBeforeSend: this.handleReportAIBeforeSend,
                onApply: this.handleReportAIApply,
                autoSubmit: true,
            });
        },

        buildReportAIContextData() {
            const sections = [];
            const meta = [];
            const title = (this.reportData.title || '').trim();
            if (title) {
                meta.push(`标题：${title}`);
            }
            if (this.reportData.sign) {
                meta.push(`周期：${this.reportData.sign}`);
            }
            if (this.reportData.type) {
                const typeMap = {weekly: this.$L('周报'), daily: this.$L('日报')};
                meta.push(`类型：${typeMap[this.reportData.type] || this.reportData.type}`);
            }
            if (meta.length > 0) {
                sections.push('## 汇报信息');
                sections.push(...meta);
            }

            const plain = extractPlainText(this.reportData.content, 8000, true);
            if (plain) {
                sections.push('## 当前汇报正文');
                sections.push(plain);
            }

            return sections.join('\n').trim();
        },

        handleReportAIBeforeSend(context = []) {
            const prepared = [
                ['system', withLanguagePreferencePrompt(REPORT_AI_SYSTEM_PROMPT)]
            ];
            const contextPrompt = this.buildReportAIContextData();
            if (contextPrompt) {
                let assistantContext = [
                    '以下是当前汇报草稿，请在此基础上整理结构、补充要点：',
                    contextPrompt,
                ].join('\n');
                if ($A.getObject(context, [0,0]) === 'human') {
                    assistantContext += "\n----\n请根据以上背景再结合用户输入给出结果：++++";
                }
                prepared.push(['human', assistantContext]);
            }
            if (context.length > 0) {
                prepared.push(...context);
            }
            return prepared;
        },

        handleReportAIApply({rawOutput}) {
            if (!rawOutput) {
                $A.messageWarning("AI 未生成内容");
                return;
            }
            const html = MarkdownConver(rawOutput).trim();
            if (!html) {
                $A.modalError("AI 内容解析失败，请重试");
                return;
            }
            this.reportData.content = html;
            this.$nextTick(() => {
                const editor = this.$refs.reportEditor;
                if (editor && typeof editor.focus === 'function') {
                    editor.focus();
                }
            });
            $A.messageSuccess("已应用整理结果");
        }
    }
}
</script>
