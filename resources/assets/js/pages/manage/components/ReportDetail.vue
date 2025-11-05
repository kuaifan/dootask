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
                        :loading="aiLoading"
                        @click="onAnalyze">
                        {{ aiAnalysis ? $L("重新分析") : $L("生成分析") }}
                    </Button>
                </div>
                <div v-if="aiLoading" class="analysis-loading">
                    <Icon type="ios-loading" class="icon-loading"/>
                    <span>{{ $L("AI 正在生成分析...") }}</span>
                </div>
                <div v-else-if="aiAnalysis" class="analysis-content">
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
            aiLoading: false,
            aiAnalysis: null,
            detail: null,
        }
    },
    computed: {
        ...mapState(['formOptions']),
        currentDetail() {
            return this.detail || this.data || {};
        }
    },
    watch: {
        'data.id': {
            handler(id) {
                if (id > 0) {
                    this.aiAnalysis = this.data?.ai_analysis || null;
                    this.detail = null;
                    if (this.type === 'view') {
                        this.sendRead();
                        this.fetchDetail();
                    }
                } else {
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
            if (!this.currentDetail.id || this.aiLoading) {
                return;
            }
            this.aiLoading = true;
            this.$store.dispatch("call", {
                url: 'report/ai_analyze',
                method: 'post',
                data: {
                    id: this.currentDetail.id,
                },
                timeout: 60 * 1000,
            }).then(({data}) => {
                this.aiAnalysis = data;
                if (this.detail) {
                    this.detail.ai_analysis = data;
                }
            }).catch(({msg}) => {
                $A.messageError(msg);
            }).finally(() => {
                this.aiLoading = false;
            });
        },
    }
}
</script>