<template>
    <div class="report-detail">
        <div class="report-title user-select-auto">
            {{ data.title }}
            <Icon v-if="loadIng > 0" type="ios-loading" class="icon-loading"/>
        </div>
        <div v-if="data.id" class="report-detail-context">
            <ul>
                <li>
                    <div class="report-label">
                        {{ $L("汇报人") }}
                    </div>
                    <div class="report-value">
                        <UserAvatar :userid="data.userid" :size="28"/>
                    </div>
                </li>
                <li>
                    <div class="report-label">
                        {{ $L("提交时间") }}
                    </div>
                    <div class="report-value">
                        {{ data.created_at }}
                    </div>
                </li>
                <li>
                    <div class="report-label">
                        {{ $L("汇报对象") }}
                    </div>
                    <div class="report-value">
                        <template v-if="data.receives_user && data.receives_user.length === 0">-</template>
                        <UserAvatar v-else v-for="(item, key) in data.receives_user" :key="key" :userid="item.userid" :size="28"/>
                    </div>
                </li>
                <li v-if="data.report_link" :title="$L('分享时间') + '：' + data.report_link.created_at">
                    <div class="report-label">
                        {{ $L("分享人") }}
                    </div>
                    <div class="report-value">
                        <UserAvatar :userid="data.report_link.userid" :size="28"/>
                    </div>
                </li>
            </ul>
            <div ref="reportContent" @click="onClick" class="report-content user-select-auto" v-html="data.content"></div>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    name: "ReportDetail",
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
        }
    },
    computed: {
        ...mapState(['formOptions']),
    },
    watch: {
        'data.id': {
            handler(id) {
                if (id > 0 && this.type === 'view') {
                    this.sendRead();
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
        }
    }
}
</script>
