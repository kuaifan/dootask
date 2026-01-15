<template>
    <div class="report-detail">
        <div class="report-title user-select-auto">
            {{ currentDetail.title }}
            <Icon v-if="loadIng > 0" type="ios-loading" class="icon-loading"/>
        </div>
        <div v-if="currentDetail.id" class="report-detail-context">
            <ul>
                <li>
                    <div class="report-label">
                        {{ $L("汇报人") }}
                    </div>
                    <div class="report-value">
                        <UserAvatar :userid="currentDetail.userid" :size="28" clickOpenDetail/>
                    </div>
                </li>
                <li>
                    <div class="report-label">
                        {{ $L("提交时间") }}
                    </div>
                    <div class="report-value">
                        {{ currentDetail.created_at }}
                    </div>
                </li>
                <li>
                    <div class="report-label">
                        {{ $L("汇报对象") }}
                    </div>
                    <div class="report-value">
                        <template v-if="currentDetail.receives_user && currentDetail.receives_user.length === 0">-</template>
                        <template v-else>
                            <UserAvatar v-for="(item, key) in currentDetail.receives_user" :key="key" :userid="item.userid" :size="28" clickOpenDetail/>
                        </template>
                    </div>
                </li>
                <li v-if="currentDetail.report_link" :title="$L('分享时间') + '：' + currentDetail.report_link.created_at">
                    <div class="report-label">
                        {{ $L("分享人") }}
                    </div>
                    <div class="report-value">
                        <UserAvatar :userid="currentDetail.report_link.userid" :size="28" clickOpenDetail/>
                    </div>
                </li>
            </ul>
            <div ref="reportContent" @click="onClick" class="report-content user-select-auto" v-html="currentDetail.content"></div>
            <div v-if="currentDetail.id" class="report-ai-analysis">
                <div class="analysis-header">
                    <div class="analysis-title">{{ $L("AI 分析") }}</div>
                    <Button
                        type="primary"
                        size="small"
                        :loading="analysisSaving"
                        @click="onAnalyze">
                        {{ aiAnalysis ? $L("重新分析") : $L("生成分析") }}
                    </Button>
                </div>
                <div v-if="aiAnalysis" class="analysis-content">
                    <div v-if="aiAnalysis.updated_at" class="analysis-meta">
                        {{ $L("最后更新：") }}{{ aiAnalysis.updated_at }}
                    </div>
                    <div class="analysis-body user-select-auto">
                        <VMPreview :value="aiAnalysis.text"/>
                    </div>
                </div>
                <div v-else class="analysis-empty">
                    {{ $L("暂无 AI 分析，点击右侧按钮生成。") }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
const VMPreview = () => import('../../../components/VMEditor/preview');
import {mapState} from "vuex";
import emitter from "../../../store/events";
import {extractPlainText} from "../../../utils/text";
import {REPORT_ANALYSIS_SYSTEM_PROMPT, withLanguagePreferencePrompt} from "../../../utils/ai";

export default {
    name: "ReportDetail",
    components: {VMPreview},
    props: {
        data: {
            default: {},
        },
        type: {
            default: 'view',
        },
    },
    data() {
        return {
            loadIng: 0,
            analysisSaving: false,
            aiAnalysis: null,
            detail: null,
        }
    },
    computed: {
        ...mapState(['formOptions', 'userInfo']),
        currentDetail() {
            return this.detail || this.data || {};
        }
    },
    watch: {
        'data.id': {
            handler(id) {
                if (id > 0) {
                    this.analysisSaving = false;
                    this.aiAnalysis = this.data?.ai_analysis || null;
                    this.detail = null;
                    if (this.type === 'view') {
                        this.sendRead();
                        this.fetchDetail();
                    }
                } else {
                    this.analysisSaving = false;
                    this.aiAnalysis = null;
                    this.detail = null;
                }
            },
            immediate: true
        },
    },
    methods: {
        sendRead() {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'report/read',
                data: {
                    ids: [this.data.id]
                },
            }).then(() => {
                //
            }).catch(() => {
                //
            }).finally(_ => {
                this.loadIng--;
            });
        },
        onClick({target}) {
            if (target.nodeName === "IMG") {
                const list = $A.getTextImagesInfo(this.$refs.reportContent?.outerHTML);
                this.$store.dispatch("previewImage", {index: target.currentSrc, list})
            }
        },
        fetchDetail() {
            if (!this.data.id) {
                return;
            }
            this.$store.dispatch("call", {
                url: 'report/detail',
                data: {
                    id: this.data.id,
                },
            }).then(({data}) => {
                this.detail = data;
                this.aiAnalysis = data?.ai_analysis || null;
            }).catch(({msg}) => {
                msg && $A.messageError(msg);
            });
        },
        onAnalyze() {
            if (this.analysisSaving) {
                return;
            }
            if (!this.currentDetail.id) {
                $A.messageWarning("当前没有可分析的汇报");
                return;
            }
            const plain = extractPlainText(this.currentDetail.content, null, true);
            if (!plain) {
                $A.messageWarning("汇报内容为空，无法分析");
                return;
            }
            emitter.emit('openAIAssistant', {
                sessionKey: 'report-analysis',
                title: this.$L('AI 汇报分析'),
                placeholder: this.$L('补充你想聚焦的风险、成果或建议，留空直接生成分析'),
                onBeforeSend: this.handleReportAnalysisBeforeSend,
                onApply: this.handleReportAnalysisApply,
                autoSubmit: true,
                applyButtonText: this.$L('保存分析'),
            });
        },

        handleReportAnalysisBeforeSend(context = []) {
            const prepared = [
                ['system', withLanguagePreferencePrompt(REPORT_ANALYSIS_SYSTEM_PROMPT)]
            ];
            const contextPrompt = this.buildReportAnalysisContextData();
            if (contextPrompt) {
                let assistantContext = [
                    '以下是工作汇报详情，请据此输出结构化的分析：',
                    contextPrompt,
                ].join('\n');
                if ($A.getObject(context, [0,0]) === 'human') {
                    assistantContext += "\n----\n请结合以上背景和以下补充说明完成分析：++++";
                }
                prepared.push(['human', assistantContext]);
            }
            if (context.length > 0) {
                prepared.push(...context);
            }
            return prepared;
        },

        handleReportAnalysisApply({rawOutput, model}) {
            const text = (rawOutput || '').trim();
            if (!text) {
                $A.messageWarning("AI 未生成内容");
                return;
            }
            if (!this.currentDetail.id) {
                $A.messageWarning("当前没有可分析的汇报");
                return;
            }
            this.analysisSaving = true;
            const payload = {
                id: this.currentDetail.id,
                text,
                model: model || '',
            };
            return this.$store.dispatch("call", {
                url: 'report/analysave',
                method: 'post',
                data: payload,
            }).then(({data}) => {
                const analysis = data || {
                    text,
                    updated_at: $A.dayjs().format('YYYY-MM-DD HH:mm:ss'),
                };
                this.aiAnalysis = analysis;
                if (this.detail) {
                    this.$set(this.detail, 'ai_analysis', analysis);
                }
                $A.messageSuccess("AI 分析已更新");
            }).catch(({msg}) => {
                $A.messageError(msg || '保存 AI 分析失败');
                return Promise.reject(msg);
            }).finally(() => {
                this.analysisSaving = false;
            });
        },

        buildReportAnalysisContextData() {
            const detail = this.currentDetail || {};
            if (!detail.id) {
                return '';
            }
            const sections = [];
            const meta = [];
            const title = (detail.title || '').trim();
            if (title) {
                meta.push(`标题：${title}`);
            }
            const typeLabel = this.resolveReportTypeLabel(detail.type || detail.type_val);
            if (typeLabel) {
                meta.push(`类型：${typeLabel}`);
            }
            if (detail.sign) {
                meta.push(`周期：${detail.sign}`);
            }
            if (detail.created_at) {
                meta.push(`提交时间：${detail.created_at}`);
            }
            const submitter = this.resolveUserName(detail.user || detail);
            if (submitter) {
                meta.push(`汇报人：${submitter}`);
            }
            const receivers = Array.isArray(detail.receives_user)
                ? detail.receives_user
                    .map(item => this.resolveUserName(item))
                    .filter(Boolean)
                : [];
            if (receivers.length > 0) {
                meta.push(`接收人：${receivers.join('、')}`);
            }
            if (meta.length > 0) {
                sections.push('## 汇报信息');
                meta.forEach(line => sections.push(`- ${line}`));
            }

            const viewerMeta = [];
            const viewerName = this.resolveUserName(this.userInfo);
            if (viewerName) {
                viewerMeta.push(`查看人：${viewerName}`);
            }
            const viewerRole = this.resolveViewerRole();
            if (viewerRole) {
                viewerMeta.push(`角色：${viewerRole}`);
            }
            if (viewerMeta.length > 0) {
                sections.push('## 查看上下文');
                viewerMeta.forEach(line => sections.push(`- ${line}`));
            }

            const bodyText = extractPlainText(detail.content, 8000, true);
            if (bodyText) {
                sections.push('## 汇报正文');
                sections.push(bodyText);
            }

            const previous = this.aiAnalysis?.text || detail.ai_analysis?.text;
            if (previous) {
                sections.push('## 历史分析供参考');
                sections.push(previous);
            }

            return sections.join('\n').trim();
        },

        resolveReportTypeLabel(type) {
            const map = {
                weekly: this.$L('周报'),
                daily: this.$L('日报'),
            };
            return map[type] || (typeof type === 'string' ? type : '');
        },

        resolveUserName(user) {
            if (!user) {
                return '';
            }
            if (typeof user === 'string') {
                return user;
            }
            const name = user.nickname || user.realname || user.name || user.username || '';
            if (name) {
                return name;
            }
            if (user.userid) {
                return `${this.$L('用户')} ${user.userid}`;
            }
            return '';
        },

        resolveViewerRole() {
            const info = this.userInfo || {};
            if (Array.isArray(info.identity) && info.identity.length > 0) {
                return info.identity.join('/');
            }
            return info.profession || info.job || info.position || '';
        },
    }
}
</script>
