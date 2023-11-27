<template>
    <div class="setting-item submit">
        <Form ref="formData" :model="formData" :labelPosition="formLabelPosition" :labelWidth="formLabelWidth" @submit.native.prevent>
            <FormItem label="License" prop="license">
                <Input v-model="formData.license" type="textarea" :autosize="{minRows: 2,maxRows: 5}" :placeholder="$L('请输入License...')" />
            </FormItem>
            <FormItem>
                <div class="license-box">
                    <ul v-if="formData.info.sn">
                        <li>
                            <em>SN:</em>
                            <span>{{formData.info.sn}}</span>
                            <ETooltip max-width="auto" placement="right">
                                <div slot="content">{{$L('当前环境')}}: {{formData.doo_sn}}</div>
                                <Icon class="information" type="ios-information-circle-outline" />
                            </ETooltip>
                        </li>
                        <li>
                            <em>IP:</em>
                            <span>{{infoJoin(formData.info.ip)}}</span>
                        </li>
                        <li>
                            <em>{{$L('域名')}}:</em>
                            <span>{{infoJoin(formData.info.domain)}}</span>
                        </li>
                        <li>
                            <em>MAC:</em>
                            <span>{{infoJoin(formData.info.mac)}}</span>
                            <ETooltip max-width="auto" placement="right">
                                <div slot="content">{{$L('当前环境')}}: {{infoJoin(formData.macs, '-')}}</div>
                                <Icon class="information" type="ios-information-circle-outline" />
                            </ETooltip>
                        </li>
                        <li>
                            <em>{{$L('使用人数')}}:</em>
                            <span>{{formData.info.people || $L('无限制')}} ({{$L('已使用')}}: {{formData.user_count}})</span>
                            <ETooltip max-width="auto" placement="right">
                                <div slot="content">{{$L('限制注册人数')}}</div>
                                <Icon class="information" type="ios-information-circle-outline" />
                            </ETooltip>
                        </li>
                        <li>
                            <em>{{$L('创建时间')}}:</em>
                            <span>{{formData.info.created_at}}</span>
                        </li>
                        <li>
                            <em>{{$L('到期时间')}}:</em>
                            <span>{{formData.info.expired_at || $L('永久')}}</span>
                            <ETooltip v-if="formData.info.expired_at" max-width="auto" placement="right">
                                <div slot="content">{{$L('到期后限制注册帐号')}}</div>
                                <Icon class="information" type="ios-information-circle-outline" />
                            </ETooltip>
                        </li>
                    </ul>
                    <ul v-else>
                        <li>
                            {{$L('加载中...')}}
                        </li>
                    </ul>
                </div>
            </FormItem>
            <FormItem :label="$L('当前环境')" v-if="formData.error?.length > 0">
                <div class="license-box">
                    <ul>
                        <li>
                            <em>SN:</em>
                            <span>{{formData.doo_sn}}</span>
                        </li>
                        <li>
                            <em>MAC:</em>
                            <span>{{infoJoin(formData.macs)}}</span>
                        </li>
                        <li v-for="tip in formData.error" class="warning">{{tip}}</li>
                    </ul>
                </div>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{$L('重置')}}</Button>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.license-box {
    padding-top: 6px;
    > ul {
        > li {
            list-style: none;
            font-size: 14px;
            line-height: 22px;
            padding-bottom: 6px;
            display: flex;
            &.warning {
                font-weight: 500;
                color: #ed4014;
            }
            > em {
                flex-shrink: 0;
                font-style: normal;
                opacity: 0.8;
            }
            > span {
                padding-left: 6px;
            }
            .information {
                display: flex;
                align-items: center;
                justify-content: center;
                margin-left: 6px;
            }
        }
    }
}
</style>
<script>
import {mapState} from "vuex";

export default {
    data() {
        return {
            loadIng: 0,

            formData: {
                license: '',
                info: {},
                macs: [],
                doo_sn: '',
                user_count: 0,
                error: []
            },
        }
    },
    mounted() {
        this.systemSetting();
    },
    computed: {
        ...mapState(['userInfo', 'formLabelPosition', 'formLabelWidth']),
    },
    methods: {
        submitForm() {
            this.$refs.formData.validate((valid) => {
                if (valid) {
                    this.systemSetting(true);
                }
            })
        },

        resetForm() {
            this.formData = $A.cloneJSON(this.formData_bak);
        },

        systemSetting(save) {
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'system/license',
                data: Object.assign(this.formData, {
                    type: save ? 'save' : 'get'
                }),
                method: 'post',
            }).then(({data}) => {
                if (save) {
                    $A.messageSuccess('修改成功');
                }
                this.formData = data;
                this.formData_bak = $A.cloneJSON(this.formData);
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
            }).finally(_ => {
                this.loadIng--;
            });
        },

        infoJoin(val, def = null) {
            if ($A.isArray(val)) {
                val = val.join(",")
            }
            if (val) {
                return val
            }
            return def === null ? this.$L("无限制") : def
        }
    }
}
</script>
