<template>
    <div class="setting-item submit license-setting">
        <Tabs v-model="mode">
            <TabPane :label="$L('在线授权')" name="online">
                <div class="setting-component-item">
                    <div class="setting-scroll">
                        <!-- 首次进入且无缓存：骨架占位 + 加载中（有缓存则直接渲染下方真实数据） -->
                        <div v-if="firstLoading" class="license-box license-wrap">
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
                            <div v-if="onlineActive" class="license-box license-wrap">
                                <!-- 后台刷新中指示（缓存秒开后仍刷新最新数据） -->
                                <div v-if="onlineRefreshing" class="online-refreshing"><i class="online-spin"></i>{{$L('刷新中')}}</div>
                                <!-- 异常告警卡：仅提醒/冻结/设备不匹配时浮现，正常态不显示，避免噪音 -->
                                <div v-if="onlineAlert" class="online-alert" :class="onlineAlert.type">
                                    <i class="online-alert-ico">{{onlineAlert.type === 'error' ? '✕' : '!'}}</i>
                                    <div class="online-alert-main">
                                        <div class="online-alert-title">{{onlineAlert.title}}</div>
                                        <div class="online-alert-desc">{{onlineAlert.desc}}</div>
                                    </div>
                                </div>
                                <!-- 核心信息：正常态只看结果，不常驻 SN/MAC -->
                                <ul class="online-info">
                                    <li><em>{{$L('账号')}}:</em><span>{{online.account}}</span></li>
                                    <li><em>{{$L('套餐')}}:</em><span>{{online.plan || '-'}}</span></li>
                                    <li><em>{{$L('使用人数')}}:</em><span>{{online.people || $L('无限制')}}</span></li>
                                    <li><em>{{$L('授权有效期')}}:</em><span>{{online.valid_until ? fmt(online.valid_until) : $L('永久')}}</span></li>
                                    <li>
                                        <em>{{$L('当前状态')}}:</em>
                                        <span class="online-status" :class="'is-' + onlineHealth"><i class="online-status-dot"></i>{{stageText(online.status)}}</span>
                                    </li>
                                </ul>
                                <!-- 诊断详情：仅设备（SN/MAC）不匹配时展开，普通用户不受打扰 -->
                                <div v-if="onlineMismatch" class="online-diag">
                                    <div class="online-diag-title">{{$L('诊断详情')}}</div>
                                    <div class="online-diag-row" :class="{bad: !snMatch}"><em>{{$L('授权 SN')}}:</em><span>{{formData.info.sn}}</span></div>
                                    <div class="online-diag-row" :class="{bad: !snMatch}"><em>{{$L('当前 SN')}}:</em><span>{{formData.doo_sn}}<b>{{snMatch ? ' ✓' : ' ✕'}}</b></span></div>
                                    <div class="online-diag-row" :class="{bad: !macMatch}"><em>{{$L('授权 MAC')}}:</em><span>{{infoJoin(formData.info.mac)}}</span></div>
                                    <div class="online-diag-row" :class="{bad: !macMatch}"><em>{{$L('当前 MAC')}}:</em><span>{{infoJoin(formData.macs)}}<b>{{macMatch ? ' ✓' : ' ✕'}}</b></span></div>
                                </div>
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
                            <Button v-if="onlineAlert && onlineAlert.relogin" :loading="onlineAction === 'relogin'" :disabled="onlineBusy && onlineAction !== 'relogin'" type="primary" @click="onlineRelogin">{{$L('重新登录授权')}}</Button>
                            <Button :loading="onlineAction === 'logout'" :disabled="onlineBusy && onlineAction !== 'logout'" :type="onlineAlert && onlineAlert.relogin ? 'default' : 'primary'" @click="onlineLogout">{{$L('退出在线授权')}}</Button>
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
                            <div class="license-box license-wrap">
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
                            <Form ref="formData" :model="formData" v-bind="formOptions" @submit.native.prevent class="license-form">
                                <FormItem label="License" prop="license">
                                    <Input v-model="formData.license" type="textarea" :autosize="{minRows: 2,maxRows: 5}" :placeholder="$L('请输入License...')" />
                                </FormItem>
                                <FormItem>
                                    <div class="license-box">
                                        <ul v-if="formData.info.sn" class="offline-detail">
                                            <!-- SN/MAC：行尾标签直陈匹配与否，失配整行标红，无需悬停 -->
                                            <li class="offline-row" :class="{bad: !snMatch}">
                                                <em>SN:</em>
                                                <span class="v">{{formData.info.sn}}</span>
                                                <span class="offline-flag">{{snMatch ? $L('匹配') : $L('不匹配')}}</span>
                                            </li>
                                            <li class="offline-row" :class="{bad: !macMatch}">
                                                <em>MAC:</em>
                                                <span class="v">{{infoJoin(formData.info.mac)}}</span>
                                                <span class="offline-flag">{{macMatch ? $L('匹配') : $L('不匹配')}}</span>
                                            </li>
                                            <li class="offline-row">
                                                <em>{{$L('使用人数')}}:</em>
                                                <span class="v">{{formData.info.people || $L('无限制')}}（{{$L('已使用')}} {{formData.user_count}}）</span>
                                            </li>
                                            <li class="offline-row">
                                                <em>{{$L('到期时间')}}:</em>
                                                <span class="v">{{formData.info.expired_at || $L('永久')}}</span>
                                            </li>
                                            <!-- 低频字段折叠，默认更干净 -->
                                            <template v-if="offlineMore">
                                                <li class="offline-row"><em>IP:</em><span class="v">{{infoJoin(formData.info.ip)}}</span></li>
                                                <li class="offline-row"><em>{{$L('域名')}}:</em><span class="v">{{infoJoin(formData.info.domain)}}</span></li>
                                                <li class="offline-row"><em>{{$L('创建时间')}}:</em><span class="v">{{formData.info.created_at}}</span></li>
                                                <li class="offline-row"><em>{{$L('当前环境')}}:</em><span class="v">SN: {{formData.doo_sn}}, MAC: {{infoJoin(formData.macs)}}</span></li>
                                            </template>
                                            <li class="offline-more"><a @click="offlineMore = !offlineMore">{{offlineMore ? $L('收起') : $L('更多信息')}}</a></li>
                                        </ul>
                                        <ul v-else>
                                            <li>
                                                {{$L('加载中...')}}
                                            </li>
                                        </ul>
                                    </div>
                                </FormItem>
                                <FormItem :label="$L('提示')" v-if="formData.error?.length > 0">
                                    <div class="license-box">
                                        <ul>
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
        <!-- 本机有多条可用授权时，选择要使用哪一条 -->
        <Modal v-model="candidateShow" :title="$L('选择要使用的授权')" :mask-closable="false">
            <div class="online-candidates">
                <RadioGroup v-model="candidateChoice" vertical>
                    <Radio v-for="c in candidateList" :key="c.entitlement_id" :label="c.entitlement_id" class="online-candidate">
                        <div class="online-candidate-main">
                            <span class="online-candidate-plan">{{c.plan || $L('授权')}}</span>
                            <span v-if="c.occupied_by_self" class="online-candidate-self">{{$L('当前设备使用中')}}</span>
                        </div>
                        <div class="online-candidate-sub">
                            <span>{{$L('使用人数')}}: {{c.people ? c.people : $L('无限制')}}</span>
                            <span>{{$L('授权有效期')}}: {{candidateDuration(c)}}</span>
                        </div>
                    </Radio>
                </RadioGroup>
            </div>

            <div slot="footer" class="adaption">
                <Button @click="candidateShow = false">{{$L('取消')}}</Button>
                <Button type="primary" :loading="candidateSubmitting" @click="onlineLoginConfirm">{{$L('确定授权')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<style lang="scss" scoped>
.setting-scroll {
    flex: 1;
    overflow-y: auto;
}
.license-form {
    .ivu-form-item {
        margin-bottom: 12px;
    }
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
            margin-bottom: 4px;
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
        }
    }
}
.online-refreshing {
    position: absolute;
    top: 16px;
    right: 16px;
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
/* 在线授权：异常告警卡（正常态不渲染） */
.online-alert {
    display: flex;
    gap: 10px;
    max-width: calc(100vw - 20px);
    margin: 0 auto 14px;
    padding: 11px 13px;
    border-radius: 6px;
    &.warning { background: #fdf6ec; border: 1px solid #faecd8; }
    &.error   { background: #fef0ef; border: 1px solid #fcd7d3; }
    .online-alert-ico {
        flex-shrink: 0;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        color: #fff;
        font-size: 12px;
        font-style: normal;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    &.warning .online-alert-ico { background: #ff9900; }
    &.error   .online-alert-ico { background: #ed4014; }
    .online-alert-title { font-size: 13px; font-weight: 600; margin-bottom: 2px; }
    &.warning .online-alert-title { color: #b8791b; }
    &.error   .online-alert-title { color: #c0341a; }
    .online-alert-desc { font-size: 12px; line-height: 18px; }
    &.warning .online-alert-desc { color: #8a7455; }
    &.error   .online-alert-desc { color: #97544a; }
}
/* 在线授权：状态圆点 */
.online-status {
    display: inline-flex;
    align-items: center;
    .online-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        margin-right: 6px;
        background: #c5c8ce;
    }
    &.is-ok { color: #19be6b; .online-status-dot { background: #19be6b; } }
    &.is-warning { color: #ff9900; .online-status-dot { background: #ff9900; } }
    &.is-error { color: #ed4014; font-weight: 500; .online-status-dot { background: #ed4014; } }
}
/* 在线授权：诊断详情（仅 SN/MAC 不匹配时出现） */
.online-diag {
    max-width: calc(100vw - 20px);
    margin: 10px auto 0;
    margin-top: 10px;
    padding: 10px 12px;
    background: #f8f9fb;
    border-radius: 6px;
    .online-diag-title { font-size: 12px; color: #808695; margin-bottom: 6px; }
    .online-diag-row {
        font-size: 12.5px;
        line-height: 20px;
        display: flex;
        > em { font-style: normal; opacity: 0.55; width: 72px; flex-shrink: 0; }
        b { font-weight: 500; }
        &.bad { color: #ed4014; > em { opacity: 0.7; } }
    }
}
/* 离线授权：详情行 + 匹配标签 */
.offline-detail {
    .offline-row {
        /* 文字排版（字号/行高/label/gap）沿用默认信息行，只保留标红块所需的内边距与圆角 */
        > .v { flex: 1; word-break: break-all; }
        .offline-flag {
            flex-shrink: 0;
            font-size: 12px;
            padding: 1px 8px;
            border-radius: 9px;
            background: #e8f7f0;
            color: #19be6b;
        }
        &.bad {
            color: #ed4014;
            .offline-flag { background: #fdecea; color: #ed4014; }
        }
    }
    .offline-more {
        padding: 4px 0;
        a {
            font-size: 13px;
            color: #2d8cf0;
            cursor: pointer;
            &:hover { text-decoration: underline; }
        }
    }
}
body.window-portrait {
    .license-wrap {
        padding-top: 16px;
    }
}
/* 在线授权：多授权选择弹窗 */
.online-candidates {
    .ivu-radio-group {
        width: 100%;
    }
    .online-candidate {
        display: flex;
        width: 100%;
        margin: 0 0 8px;
        padding: 10px 12px;
        border: 1px solid #dcdee2;
        border-radius: 6px;
        white-space: normal;
        height: auto;
        line-height: 24px;
        align-items: center;
        flex-wrap: wrap;
        gap: 4px;
        &.ivu-radio-wrapper-checked {
            border-color: #84C56A;
            background: #f2fff0;
        }
        .online-candidate-main {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .online-candidate-plan {
            font-size: 14px;
            font-weight: 600;
        }
        .online-candidate-self {
            font-size: 12px;
            color: #2d8cf0;
        }
        .online-candidate-sub {
            font-size: 12px;
            color: #808695;
            span + span {
                margin-left: 8px;
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
                error: [],
                online: {},
            },

            mode: 'online',
            tabInited: false,
            offlineRebindShow: false,
            offlineRebindLicense: '',
            offlineMore: false,     // 离线详情：是否展开 IP/域名/创建时间/当前环境等低频字段
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
            candidateShow: false,       // 多授权选择弹窗
            candidateList: [],          // 候选授权列表
            candidateChoice: 0,         // 选中的 entitlement_id
            candidateSubmitting: false, // 确认签发中
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

        // 有效离线授权：当前非在线托管，且已设 license 为「真实有效、绑定本机的付费授权」。
        // 默认/试用 3 人版（people 1~3）或 sn/mac 与本机不匹配 → 该授权在本机本就无意义，视为无效。
        // 统一口径：既用于「进入页面是否默认切到离线 Tab」，也用于「绑定在线前是否需二次确认替换离线授权」。
        offlineValid() {
            if (this.onlineActive) {
                return false;
            }
            if (!String(this.formData.license || '').trim()) {
                return false;
            }
            const info = this.formData.info || {};
            const people = parseInt(info.people) || 0;
            const isTrialThree = people >= 1 && people <= 3;
            const snOk = this.existIntersection(this.formData.doo_sn, info.sn);
            const macOk = this.existIntersection(this.formData.macs, info.mac);
            return !isTrialThree && snOk && macOk;
        },

        // license 内嵌 SN/MAC 与本机是否匹配（在线/离线同源判断）；info 未加载时视为匹配，避免误报
        snMatch() {
            const info = this.formData.info || {};
            return !info.sn || this.existIntersection(this.formData.doo_sn, info.sn);
        },
        macMatch() {
            const info = this.formData.info || {};
            return !info.mac || this.existIntersection(this.formData.macs, info.mac);
        },
        // 在线授权：license 已加载且 SN 或 MAC 与本机不匹配（换机 / 网卡变化）
        onlineMismatch() {
            const info = this.formData.info || {};
            return !!info.sn && (!this.snMatch || !this.macMatch);
        },
        // 在线授权健康度：ok（绿）/ warning（黄）/ error（红），驱动状态点与告警卡配色
        onlineHealth() {
            const s = this.online.status;
            if (s === 'revoked' || s === 'frozen' || !this.snMatch) {
                return 'error';
            }
            if (s === 'reminder' || !this.macMatch) {
                return 'warning';
            }
            return 'ok';
        },
        // 在线授权异常告警卡内容：正常态返回 null（不显示）
        onlineAlert() {
            if (!this.onlineActive) {
                return null;
            }
            const s = this.online.status;
            // 设备标识（SN）变更：换机，必须重新登录
            if (this.onlineMismatch && !this.snMatch) {
                return {
                    type: 'error',
                    title: this.$L('授权与当前设备不匹配'),
                    desc: this.$L('检测到设备标识（SN）已变更，在线授权可能已失效。请重新登录授权，或先在原设备退出以释放座位。'),
                    relogin: true,
                };
            }
            if (s === 'frozen') {
                return {
                    type: 'error',
                    title: this.$L('在线授权已过期'),
                    desc: this.$L('新增用户已受限，请尽快联网以自动续期恢复。'),
                    relogin: false,
                };
            }
            // 仅网卡（MAC）变化：会随下次续期自动恢复
            if (this.onlineMismatch && !this.macMatch) {
                return {
                    type: 'warning',
                    title: this.$L('检测到网卡（MAC）变化'),
                    desc: this.$L('系统会在下次续期时自动恢复授权，通常无需处理。'),
                    relogin: false,
                };
            }
            if (s === 'reminder') {
                if (this.online.error_count > 0) {
                    return {
                        type: 'warning',
                        title: this.$L('续期失败，请检查网络'),
                        desc: this.$L('授权仍然有效，联网后会自动续期恢复。'),
                        relogin: false,
                    };
                }
                return {
                    type: 'warning',
                    title: this.$L('授权即将到期'),
                    desc: this.$L('请保持联网，系统会自动为你续期。'),
                    relogin: false,
                };
            }
            return null;
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
                // 首次加载决定默认 Tab：已绑在线 → 在线；未绑在线但存在有效离线授权 → 离线；其余 → 在线（在线优先）
                if (!this.tabInited) {
                    this.tabInited = true;
                    if (data.online && data.online.mode === 'online') {
                        this.mode = 'online';
                    } else if (this.offlineValid) {
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
                frozen: this.$L('已过期'),
                revoked: this.$L('已吊销'),
            }[status] || status || '-';
        },

        onlineCall(url, data, successMsg) {
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
            });
        },

        // 已绑定离线时，绑定在线前二次确认（仅当离线授权为真实有效绑定本机的付费授权）
        confirmReplaceOffline(onOk) {
            if (this.offlineValid) {
                $A.modalConfirm({
                    title: '绑定在线授权',
                    content: '当前已绑定离线授权，绑定在线后将替换当前授权，是否继续？',
                    onOk,
                });
            } else {
                onOk();
            }
        },

        // 发送邮箱验证码（登录与试用共用），成功后开启 60s 倒计时并展示脱敏邮箱。
        // 替换有效离线授权的二次确认前置到此处（在线绑定流程的起点），确认后才真正发码。
        emailSend() {
            if (this.codeCountdown > 0) {
                return;
            }
            if (!this.onlineForm.email) {
                $A.messageError('请输入邮箱');
                return;
            }
            this.confirmReplaceOffline(() => {
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
            });
        },

        // 替换离线授权的二次确认已前置到发码（emailSend），此处不再重复确认
        onlineLogin() {
            if (!this.onlineForm.email || !this.onlineForm.code) {
                $A.messageError('请输入邮箱和验证码');
                return;
            }
            this.onlineAction = 'login';
            this.$store.dispatch("call", {
                url: 'license/login',
                data: {
                    email: this.onlineForm.email,
                    code: this.onlineForm.code,
                },
                method: 'post',
            }).then(({data}) => {
                // 本机有多条可用授权：弹出选择框，验证码保留（confirm 复用同一验证码）
                if (data && data.select_required) {
                    this.showCandidateSelect(data.candidates || []);
                    return;
                }
                $A.messageSuccess(this.$L('授权成功'));
                this.resetOnlineForm();
                this.systemSetting();
            }).catch(({msg}) => {
                // 登录失败（如「该账号已申请过试用」「验证码无效」等，验证码多已被消费）：
                // 清空验证码并解除重发倒计时，引导用户重新发码后再试
                $A.modalError(msg);
                this.onlineForm.code = '';
                this.clearCodeTimer();
            }).finally(() => {
                this.onlineAction = '';
            });
        },

        // 本机有多条可用授权时，展示候选供用户选择（默认选中第一条 = 最新）
        showCandidateSelect(list) {
            this.candidateList = list;
            this.candidateChoice = list.length ? list[0].entitlement_id : 0;
            this.candidateShow = true;
        },

        // 用户选定授权后确认签发（复用登录时的邮箱 + 验证码）
        onlineLoginConfirm() {
            if (!this.candidateChoice) {
                $A.messageError('请选择要使用的授权');
                return;
            }
            this.candidateSubmitting = true;
            this.$store.dispatch("call", {
                url: 'license/login/confirm',
                data: {
                    email: this.onlineForm.email,
                    code: this.onlineForm.code,
                    entitlement_id: this.candidateChoice,
                },
                method: 'post',
            }).then(_ => {
                $A.messageSuccess(this.$L('授权成功'));
                this.candidateShow = false;
                this.resetOnlineForm();
                this.systemSetting();
            }).catch(({msg}) => {
                $A.modalError(msg);
            }).finally(() => {
                this.candidateSubmitting = false;
            });
        },

        // 候选授权的时长/人数展示
        candidateDuration(c) {
            if (c.duration_type === 'fixed') {
                return c.valid_until ? this.fmt(c.valid_until) : '-';
            }
            return this.$L('永久');
        },

        // 替换离线授权的二次确认已前置到发码（emailSend），此处不再重复确认
        trialSubmit() {
            if (!this.onlineForm.email || !this.onlineForm.code) {
                $A.messageError('请输入邮箱和验证码');
                return;
            }
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

        // 重新登录授权（换机 / 设备变更后）：释放当前座位并回到登录表单
        onlineRelogin() {
            $A.modalConfirm({
                title: '重新登录授权',
                content: '将释放当前设备占用的授权座位并回到登录，确定继续？',
                onOk: () => {
                    this.onlineAction = 'relogin';
                    this.onlineCall('license/logout', {}, '').then(_ => {
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
