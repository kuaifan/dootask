<template>
    <div class="setting-item submit license-setting">
        <Tabs v-model="mode">
        <TabPane :label="$L('离线授权')" name="offline">
        <template v-if="onlineActive">
            <div class="license-box">
                <ul class="online-info">
                    <li><em>{{$L('当前状态')}}:</em><span class="online-link" @click="mode = 'online'">{{$L('已绑定在线授权')}}</span></li>
                    <li><em>SN:</em><span>{{formData.doo_sn}}</span></li>
                    <li><em>MAC:</em><span>{{infoJoin(formData.macs)}}</span></li>
                </ul>
            </div>
            <div v-if="!offlineRebindShow" class="setting-footer">
                <Button type="primary" @click="offlineRebindShow = true">{{$L('绑定离线 License')}}</Button>
            </div>
            <template v-else>
                <Form :model="formData" v-bind="formOptions" @submit.native.prevent>
                    <FormItem label="License">
                        <Input v-model="offlineRebindLicense" type="textarea" :autosize="{minRows: 2,maxRows: 5}" :placeholder="$L('请输入License...')" />
                    </FormItem>
                </Form>
                <div class="setting-footer">
                    <Button :loading="loadIng > 0" type="primary" @click="offlineRebindSubmit">{{$L('提交')}}</Button>
                    <Button :loading="loadIng > 0" @click="offlineRebindCancel" style="margin-left: 8px">{{$L('取消')}}</Button>
                </div>
            </template>
        </template>
        <template v-else>
        <Form ref="formData" :model="formData" v-bind="formOptions" @submit.native.prevent>
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
                                <Icon class="information" :class="{error: !existIntersection(formData.doo_sn, formData.info.sn)}" type="ios-information-circle-outline" />
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
                                <Icon class="information" :class="{error: !existIntersection(formData.macs, formData.info.mac)}" type="ios-information-circle-outline" />
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
                        <li v-for="(tip, ti) in formData.error" :key="ti" class="warning">{{tip}}</li>
                    </ul>
                </div>
            </FormItem>
        </Form>
        <div class="setting-footer">
            <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
            <Button :loading="loadIng > 0" @click="resetForm" style="margin-left: 8px">{{$L('重置')}}</Button>
        </div>
        </template>
        </TabPane>
        <TabPane :label="$L('在线授权')" name="online">
            <div v-if="onlineActive" class="license-box">
                <ul class="online-info">
                    <li><em>{{$L('账号')}}:</em><span>{{online.account}}</span></li>
                    <li><em>{{$L('套餐')}}:</em><span>{{online.plan || '-'}}</span></li>
                    <li><em>{{$L('使用人数')}}:</em><span>{{online.people || $L('无限制')}}</span></li>
                    <li><em>{{$L('授权有效期')}}:</em><span>{{online.valid_until ? fmt(online.valid_until) : $L('永久')}}</span></li>
                    <li>
                        <em>{{$L('当前状态')}}:</em>
                        <span :class="{warning: online.status !== 'active'}">{{stageText(online.status)}}</span>
                    </li>
                </ul>
                <div class="setting-footer">
                    <Button :loading="onlineIng > 0" @click="onlineLogout">{{$L('退出在线授权')}}</Button>
                </div>
            </div>
            <template v-else>
                <Form :model="onlineForm" v-bind="formOptions" @submit.native.prevent>
                    <FormItem :label="$L('App Store账号')">
                        <Input v-model="onlineForm.account" :placeholder="$L('请输入App Store账号')" />
                    </FormItem>
                    <FormItem :label="$L('密码')">
                        <Input v-model="onlineForm.password" type="password" :placeholder="$L('请输入密码')" />
                    </FormItem>
                    <FormItem v-if="trialStep === 1" :label="$L('邮箱验证码')">
                        <Input v-model="onlineForm.code" :placeholder="$L('请输入验证码')" />
                        <div class="online-tip">{{$L('验证码已发送至')}} {{trialEmail}}</div>
                    </FormItem>
                </Form>
                <div class="setting-footer">
                    <Button :loading="onlineIng > 0" type="primary" @click="onlineLogin">{{$L('登录授权')}}</Button>
                    <Button v-if="trialStep === 0" :loading="onlineIng > 0" @click="trialSend" style="margin-left: 8px">{{$L('申请试用')}}</Button>
                    <Button v-else :loading="onlineIng > 0" type="success" @click="trialSubmit" style="margin-left: 8px">{{$L('确定试用')}}</Button>
                </div>
            </template>
        </TabPane>
        </Tabs>
    </div>
</template>

<style lang="scss" scoped>
.license-box {
    padding-top: 6px;
    > ul {
        &.online-info {
            padding-left: 24px;
            .online-link {
                cursor: pointer;
                color: #2d8cf0;
                &:hover {
                    text-decoration: underline;
                }
            }
        }
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
                &.error {
                    color: #ed4014;
                }
            }
        }
    }
}
.online-tip {
    font-size: 12px;
    line-height: 20px;
    margin-top: 4px;
    opacity: 0.6;
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
                error: [],
                online: {},
            },

            mode: 'offline',
            tabInited: false,
            offlineRebindShow: false,
            offlineRebindLicense: '',
            onlineIng: 0,
            onlineForm: {
                account: '',
                password: '',
                code: '',
            },
            trialStep: 0,
            trialEmail: '',
        }
    },
    mounted() {
        this.onlineRefresh();
    },
    computed: {
        ...mapState(['userInfo', 'formOptions']),

        online() {
            return this.formData.online || {};
        },

        onlineActive() {
            return this.online.mode === 'online';
        },

        // 已绑定离线授权 = 存在已保存的 license 且当前非在线托管
        offlineBound() {
            return !this.onlineActive && !!String(this.formData.license || '').trim();
        },

        // 绑定在线前是否需要二次确认替换离线授权：
        // 仅当当前离线 license 是「真实有效且绑定本机的付费授权」时才提示。
        // 默认/试用 3 人版（people 1~3）或 sn/mac 不匹配 → 该授权在本机本就无意义，替换无需提示。
        offlineReplaceNeedConfirm() {
            if (!this.offlineBound) {
                return false;
            }
            const info = this.formData.info || {};
            const people = parseInt(info.people) || 0;
            const isTrialThree = people >= 1 && people <= 3;
            const snOk = this.existIntersection(this.formData.doo_sn, info.sn);
            const macOk = this.existIntersection(this.formData.macs, info.mac);
            return !isTrialThree && snOk && macOk;
        },
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

        offlineRebindCancel() {
            this.offlineRebindShow = false;
            this.offlineRebindLicense = '';
        },

        // 已绑定在线时，从离线页面提交新的离线 License：二次确认 → 成功保存，失败仅提示不保存
        offlineRebindSubmit() {
            if (!String(this.offlineRebindLicense).trim()) {
                $A.messageError('请输入License');
                return;
            }
            $A.modalConfirm({
                title: '绑定离线授权',
                content: '当前已绑定在线授权，绑定离线后将替换当前授权并释放在线座位，是否继续？',
                onOk: () => {
                    this.loadIng++;
                    return this.$store.dispatch("call", {
                        url: 'system/license',
                        data: {type: 'save', license: this.offlineRebindLicense},
                        method: 'post',
                    }).then(({data}) => {
                        $A.messageSuccess('修改成功');
                        this.formData = data;
                        this.formData_bak = $A.cloneJSON(this.formData);
                        this.offlineRebindCancel();
                    }).catch(({msg}) => {
                        $A.modalError(msg);   // 失败仅提示，不保存
                    }).finally(_ => {
                        this.loadIng--;
                    });
                }
            });
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
                // 首次加载：若已是在线授权则默认切到在线 Tab
                if (!this.tabInited) {
                    this.tabInited = true;
                    if (data.online && data.online.mode === 'online') {
                        this.mode = 'online';
                    }
                }
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
            }).finally(_ => {
                this.loadIng--;
            });
        },

        onlineRefresh() {
            // 进入授权页静默刷新在线授权：成功后端会更新数据，失败不提示；无论结果都拉取最新展示
            this.$store.dispatch("call", {
                url: 'license/refresh',
                method: 'post',
            }).catch(() => {
                // 刷新失败：静默
            }).finally(() => {
                this.systemSetting();
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
        },

        existIntersection(val1, val2) {
            if (!$A.isArray(val1)) {
                val1 = [val1]
            }
            if (!$A.isArray(val2)) {
                val2 = [val2]
            }
            return val1.some(v => val2.includes(v))
        },

        fmt(dt) {
            return dt ? $A.dayjs(dt).format('YYYY-MM-DD HH:mm') : '-';
        },

        stageText(status) {
            return {
                active: this.$L('生效中'),
                reminder: this.$L('即将到期'),
                frozen: this.$L('已冻结'),
                revoked: this.$L('已吊销'),
            }[status] || status || '-';
        },

        onlineCall(url, data, successMsg) {
            this.onlineIng++;
            return this.$store.dispatch("call", {
                url,
                data,
                method: 'post',
            }).then((res) => {
                if (successMsg) {
                    $A.messageSuccess(successMsg);
                }
                return res;
            }).catch(({msg}) => {
                $A.modalError(msg);
                return Promise.reject(msg);
            }).finally(_ => {
                this.onlineIng--;
            });
        },

        // 已绑定离线时，绑定在线前二次确认（仅当离线授权为真实有效绑定本机的付费授权）
        confirmReplaceOffline(onOk) {
            if (this.offlineReplaceNeedConfirm) {
                $A.modalConfirm({
                    title: '绑定在线授权',
                    content: '当前已绑定离线授权，绑定在线后将替换当前授权，是否继续？',
                    onOk,
                });
            } else {
                onOk();
            }
        },

        onlineLogin() {
            if (!this.onlineForm.account || !this.onlineForm.password) {
                $A.messageError('请输入账号和密码');
                return;
            }
            this.confirmReplaceOffline(() => {
                this.onlineCall('license/login', {
                    account: this.onlineForm.account,
                    password: this.onlineForm.password,
                }, '授权成功').then(_ => {
                    this.resetOnlineForm();
                    this.systemSetting();
                });
            });
        },

        trialSend() {
            if (!this.onlineForm.account || !this.onlineForm.password) {
                $A.messageError('请输入账号和密码');
                return;
            }
            this.onlineCall('license/trial/send', {
                account: this.onlineForm.account,
                password: this.onlineForm.password,
            }).then(({data}) => {
                this.trialStep = 1;
                this.trialEmail = data?.email || '';
            });
        },

        trialSubmit() {
            if (!this.onlineForm.code) {
                $A.messageError('请输入验证码');
                return;
            }
            this.confirmReplaceOffline(() => {
                this.onlineCall('license/trial', {
                    account: this.onlineForm.account,
                    password: this.onlineForm.password,
                    code: this.onlineForm.code,
                }, '试用已开通').then(_ => {
                    this.resetOnlineForm();
                    this.systemSetting();
                });
            });
        },

        onlineLogout() {
            $A.modalConfirm({
                content: '确定退出在线授权？',
                onOk: () => {
                    this.onlineCall('license/logout', {}, '已退出在线授权').then(_ => {
                        this.systemSetting();
                    });
                }
            });
        },

        resetOnlineForm() {
            this.onlineForm = {account: '', password: '', code: ''};
            this.trialStep = 0;
            this.trialEmail = '';
        },
    }
}
</script>
