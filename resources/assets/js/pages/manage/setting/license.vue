<template>
    <div class="setting-item submit license-setting">
        <Tabs v-model="mode">
        <TabPane :label="$L('在线授权')" name="online">
        <div class="setting-component-item">
            <div class="setting-scroll">
                <!-- 首次进入且无缓存：骨架占位 + 加载中（有缓存则直接渲染下方真实数据） -->
                <div v-if="firstLoading" class="license-box">
                    <div class="online-refreshing"><i class="online-spin"></i>{{$L('加载中...')}}</div>
                    <ul class="online-info">
                        <li><em>{{$L('账号')}}:</em><span class="online-skeleton"></span></li>
                        <li><em>{{$L('套餐')}}:</em><span class="online-skeleton sk-sm"></span></li>
                        <li><em>{{$L('使用人数')}}:</em><span class="online-skeleton sk-xs"></span></li>
                        <li><em>{{$L('授权有效期')}}:</em><span class="online-skeleton"></span></li>
                        <li><em>{{$L('当前状态')}}:</em><span class="online-skeleton sk-sm"></span></li>
                    </ul>
                </div>
                <template v-else>
                    <div v-if="onlineActive" class="license-box">
                        <!-- 后台刷新中指示（缓存秒开后仍刷新最新数据） -->
                        <div v-if="onlineRefreshing" class="online-refreshing"><i class="online-spin"></i>{{$L('刷新中')}}</div>
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
                    </div>
                    <Form v-else :model="onlineForm" v-bind="formOptions" @submit.native.prevent>
                        <FormItem :label="$L('邮箱')">
                            <Input
                                v-model="onlineForm.email"
                                :class="codeCountdown > 0 ? 'setting-send-input' : 'setting-input'"
                                search @on-search="emailSend"
                                :enter-button="sendBtnText"
                                :disabled="onlineBusy"
                                :placeholder="$L('请输入邮箱')"/>
                        </FormItem>
                        <FormItem v-if="codeSent" :label="$L('邮箱验证码')">
                            <Input v-model="onlineForm.code" class="setting-input" :placeholder="$L('请输入验证码')"/>
                            <div class="online-tip">{{$L('验证码已发送至(*)', maskedEmail)}}</div>
                        </FormItem>
                    </Form>
                </template>
            </div>
            <div v-if="!firstLoading" class="setting-footer">
                <template v-if="onlineActive">
                    <Button :loading="onlineAction === 'logout'" :disabled="onlineBusy && onlineAction !== 'logout'" type="primary" @click="onlineLogout">{{$L('退出在线授权')}}</Button>
                </template>
                <template v-else>
                    <Button :loading="onlineAction === 'login'" :disabled="onlineBusy && onlineAction !== 'login'" type="primary" @click="onlineLogin">{{$L('登录授权')}}</Button>
                    <Button :loading="onlineAction === 'trial'" :disabled="onlineBusy && onlineAction !== 'trial'" @click="trialSubmit">{{$L('申请试用')}}</Button>
                </template>
            </div>
        </div>
        </TabPane>
        <TabPane :label="$L('离线授权')" name="offline">
        <div class="setting-component-item">
        <div class="setting-scroll">
        <template v-if="onlineActive">
            <div class="license-box">
                <ul class="online-info">
                    <li><em>{{$L('当前状态')}}:</em><span class="online-link" @click="mode = 'online'">{{$L('已绑定在线授权')}}</span></li>
                    <li><em>SN:</em><span>{{formData.doo_sn}}</span></li>
                    <li><em>MAC:</em><span>{{infoJoin(formData.macs)}}</span></li>
                </ul>
            </div>
            <Form v-if="offlineRebindShow" :model="formData" v-bind="formOptions" @submit.native.prevent>
                <FormItem label="License">
                    <Input v-model="offlineRebindLicense" type="textarea" :autosize="{minRows: 2,maxRows: 5}" :placeholder="$L('请输入License...')" />
                </FormItem>
            </Form>
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
        </template>
        </div>
        <div class="setting-footer">
            <template v-if="onlineActive">
                <Button v-if="!offlineRebindShow" type="primary" @click="offlineRebindShow = true">{{$L('绑定离线 License')}}</Button>
                <template v-else>
                    <Button :loading="loadIng > 0" type="primary" @click="offlineRebindSubmit">{{$L('提交')}}</Button>
                    <Button :loading="loadIng > 0" @click="offlineRebindCancel">{{$L('取消')}}</Button>
                </template>
            </template>
            <template v-else>
                <Button :loading="loadIng > 0" type="primary" @click="submitForm">{{$L('提交')}}</Button>
                <Button :loading="loadIng > 0" @click="resetForm">{{$L('重置')}}</Button>
            </template>
        </div>
        </div>
        </TabPane>
        </Tabs>
    </div>
</template>

<style lang="scss" scoped>
.setting-scroll {
    flex: 1;
    overflow-y: auto;
}
.license-box {
    position: relative;
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
            align-items: center;
            gap: 6px;
            &.warning {
                font-weight: 500;
                color: #ed4014;
            }
            > em {
                flex-shrink: 0;
                font-style: normal;
                opacity: 0.8;
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
.online-refreshing {
    position: absolute;
    top: 6px;
    right: 0;
    display: flex;
    align-items: center;
    font-size: 12px;
    line-height: 18px;
    color: #808695;
    .online-spin {
        display: inline-block;
        width: 12px;
        height: 12px;
        margin-right: 5px;
        border: 2px solid #d9e3ef;
        border-top-color: #2d8cf0;
        border-radius: 50%;
        animation: license-spin 0.7s linear infinite;
    }
}
@keyframes license-spin {
    to { transform: rotate(360deg); }
}
.online-skeleton {
    display: inline-block;
    width: 150px;
    height: 14px;
    border-radius: 4px;
    background: linear-gradient(90deg, #eef0f3 25%, #e2e6ea 37%, #eef0f3 63%);
    background-size: 400% 100%;
    animation: license-skeleton 1.3s ease infinite;
    &.sk-sm { width: 90px; }
    &.sk-xs { width: 60px; }
}
@keyframes license-skeleton {
    0% { background-position: 100% 50%; }
    100% { background-position: 0 50%; }
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

            mode: 'online',
            tabInited: false,
            offlineRebindShow: false,
            offlineRebindLicense: '',
            onlineIng: 0,
            onlineAction: '',       // 当前进行中的在线操作：'' | 'login' | 'trial' | 'logout'，用于按钮级 loading/禁用互斥
            onlineForm: {
                email: '',
                code: '',
            },
            codeSent: false,        // 是否已发码（登录与试用共用同一套邮箱+验证码）
            maskedEmail: '',        // 发码成功后服务端返回的脱敏邮箱
            codeCountdown: 0,       // 重发倒计时（秒）
            codeTimer: null,
            firstLoading: true,     // 首次加载且无缓存：显示骨架占位
            onlineRefreshing: false,// 后台刷新在线授权数据中：显示「刷新中」指示
        }
    },
    mounted() {
        this.loadOnlineCache();     // 有缓存先秒开渲染
        this.onlineRefresh();       // 再后台刷新最新数据
    },
    beforeDestroy() {
        this.clearCodeTimer();
    },
    computed: {
        ...mapState(['userInfo', 'formOptions']),

        online() {
            return this.formData.online || {};
        },

        onlineActive() {
            return this.online.mode === 'online';
        },

        // 是否有在线操作进行中（登录/试用/退出），用于按钮互斥禁用
        onlineBusy() {
            return this.onlineAction !== '';
        },

        // 发送验证码按钮文案（仿 setting/email：倒计时内显示重发秒数）
        sendBtnText() {
            return this.codeCountdown > 0 ? this.$L('(*)秒后重发', this.codeCountdown) : this.$L('发送验证码');
        },

        // 在线授权数据缓存 key（按站点 host 区分，避免同浏览器多部署串台）
        onlineCacheKey() {
            return 'license-online-cache::' + (typeof window !== 'undefined' ? window.location.host : '');
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
            return this.$store.dispatch("call", {
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
                this.writeOnlineCache(data.online);     // 同步在线授权数据缓存
                // 首次加载决定默认 Tab：已绑在线 → 在线；未绑在线但已设离线授权 → 离线；其余 → 在线（在线优先）
                if (!this.tabInited) {
                    this.tabInited = true;
                    if (data.online && data.online.mode === 'online') {
                        this.mode = 'online';
                    } else if (String(data.license || '').trim()) {
                        this.mode = 'offline';
                    }
                }
            }).catch(({msg}) => {
                if (save) {
                    $A.modalError(msg);
                }
            }).finally(_ => {
                this.loadIng--;
                this.firstLoading = false;              // 首屏数据已到（成功或失败），撤下骨架
            });
        },

        onlineRefresh() {
            // 进入授权页静默刷新在线授权：成功后端会更新数据，失败不提示；无论结果都拉取最新展示
            this.onlineRefreshing = true;
            this.$store.dispatch("call", {
                url: 'license/refresh',
                method: 'post',
            }).catch(() => {
                // 刷新失败：静默
            }).finally(() => {
                this.systemSetting().finally(() => {
                    this.onlineRefreshing = false;
                });
            });
        },

        // 读取在线授权数据缓存：命中则立即渲染（秒开），并默认切到在线 Tab
        loadOnlineCache() {
            try {
                const raw = window.localStorage.getItem(this.onlineCacheKey);
                if (!raw) {
                    return;
                }
                const cached = JSON.parse(raw);
                if (cached && cached.mode === 'online') {
                    this.$set(this.formData, 'online', cached);
                    this.firstLoading = false;
                    if (!this.tabInited) {
                        this.tabInited = true;
                        this.mode = 'online';
                    }
                }
            } catch (e) {
                // 忽略缓存读取异常
            }
        },

        // 写入/清除在线授权数据缓存：在线则缓存 online 对象，非在线则清除
        writeOnlineCache(online) {
            try {
                if (online && online.mode === 'online') {
                    window.localStorage.setItem(this.onlineCacheKey, JSON.stringify(online));
                } else {
                    window.localStorage.removeItem(this.onlineCacheKey);
                }
            } catch (e) {
                // 忽略缓存写入异常
            }
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

        // 发送邮箱验证码（登录与试用共用），成功后开启 60s 倒计时并展示脱敏邮箱
        emailSend() {
            if (this.codeCountdown > 0) {
                return;
            }
            if (!this.onlineForm.email) {
                $A.messageError('请输入邮箱');
                return;
            }
            // 仿 setting/email：search 内联按钮 + spinner 全局 loading
            this.$store.dispatch("call", {
                url: 'license/email/send',
                data: {email: this.onlineForm.email},
                method: 'post',
                spinner: true,
            }).then(({data}) => {
                this.codeSent = true;
                this.maskedEmail = data?.email || '';
                this.startCodeCountdown();
            }).catch(({msg}) => {
                $A.messageError(msg);
            });
        },

        onlineLogin() {
            if (!this.onlineForm.email || !this.onlineForm.code) {
                $A.messageError('请输入邮箱和验证码');
                return;
            }
            this.confirmReplaceOffline(() => {
                this.onlineAction = 'login';
                this.onlineCall('license/login', {
                    email: this.onlineForm.email,
                    code: this.onlineForm.code,
                }, '授权成功').then(_ => {
                    this.resetOnlineForm();
                    this.systemSetting();
                }).catch(() => {
                    // 登录失败（如「该账号已申请过试用」「验证码无效」等，验证码多已被消费）：
                    // 清空验证码并解除重发倒计时，引导用户重新发码后再试
                    this.onlineForm.code = '';
                    this.clearCodeTimer();
                }).finally(() => {
                    this.onlineAction = '';
                });
            });
        },

        trialSubmit() {
            if (!this.onlineForm.email || !this.onlineForm.code) {
                $A.messageError('请输入邮箱和验证码');
                return;
            }
            this.confirmReplaceOffline(() => {
                this.onlineAction = 'trial';
                this.onlineCall('license/trial', {
                    email: this.onlineForm.email,
                    code: this.onlineForm.code,
                }, '试用已开通').then(_ => {
                    this.resetOnlineForm();
                    this.systemSetting();
                }).catch(() => {
                    // 试用失败（如「该账号已申请过试用」，验证码多已被消费）：
                    // 清空验证码并解除重发倒计时，引导用户重新发码后再试
                    this.onlineForm.code = '';
                    this.clearCodeTimer();
                }).finally(() => {
                    this.onlineAction = '';
                });
            });
        },

        onlineLogout() {
            $A.modalConfirm({
                content: '确定退出在线授权？',
                onOk: () => {
                    this.onlineAction = 'logout';
                    this.onlineCall('license/logout', {}, '已退出在线授权').then(_ => {
                        this.systemSetting();
                    }).catch(() => {
                        // 失败提示已由 onlineCall 弹出
                    }).finally(() => {
                        this.onlineAction = '';
                    });
                }
            });
        },

        startCodeCountdown() {
            this.clearCodeTimer();
            this.codeCountdown = 60;
            this.codeTimer = setInterval(() => {
                this.codeCountdown--;
                if (this.codeCountdown <= 0) {
                    this.clearCodeTimer();
                }
            }, 1000);
        },

        clearCodeTimer() {
            if (this.codeTimer) {
                clearInterval(this.codeTimer);
                this.codeTimer = null;
            }
            this.codeCountdown = 0;
        },

        resetOnlineForm() {
            this.onlineForm = {email: '', code: ''};
            this.codeSent = false;
            this.maskedEmail = '';
            this.clearCodeTimer();
        },
    }
}
</script>
