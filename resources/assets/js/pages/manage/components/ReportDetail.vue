<template>
    <div class="report-detail">
        <div class="report-title">
            {{ data.title }}
            <Icon v-if="loadIng > 0" type="ios-loading" class="icon-loading"></Icon>
        </div>
        <div v-if="formLabelWidth === 'auto'" class="report-detail-context">
            <Form class="report-form auto-form" label-width="auto" inline>
                <FormItem :label="$L('汇报人')">
                    <UserAvatar :userid="data.userid" :size="28"/>
                </FormItem>
                <FormItem :label="$L('提交时间')">
                    {{ data.created_at }}
                </FormItem>
                <FormItem :label="$L('汇报对象')">
                    <template v-if="data.receives_user.length === 0">-</template>
                    <UserAvatar v-else v-for="(item, key) in data.receives_user" :key="key" :userid="item.userid" :size="28"/>
                </FormItem>
            </Form>
            <Form class="report-form auto-form" label-width="auto">
                <FormItem :label="$L('汇报内容')">
                    <div class="report-content" v-html="data.content"></div>
                </FormItem>
            </Form>
        </div>
        <div v-else class="report-detail-context">
            <Form class="report-form" :labelPosition="formLabelPosition" :labelWidth="formLabelWidth">
                <template v-if="formLabelWidth !== 'auto'">
                    <FormItem :label="$L('汇报人')">
                        <UserAvatar :userid="data.userid" :size="28"/>
                    </FormItem>
                    <FormItem :label="$L('提交时间')">
                        {{ data.created_at }}
                    </FormItem>
                    <FormItem :label="$L('汇报对象')">
                        <template v-if="data.receives_user.length === 0">-</template>
                        <UserAvatar v-else v-for="(item, key) in data.receives_user" :key="key" :userid="item.userid" :size="28"/>
                    </FormItem>
                </template>
                <FormItem :label="$L('汇报内容')">
                    <div class="report-content" v-html="data.content"></div>
                </FormItem>
            </Form>
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
        }
    },
    data() {
        return {
            loadIng: 0,
        }
    },
    computed: {
        ...mapState(['formLabelPosition', 'formLabelWidth']),
    },
    watch: {
        'data.id': {
            handler(id) {
                if (id > 0) this.sendRead();
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
    }
}
</script>
