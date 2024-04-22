<template>
    <div class="page-login">
        <PageTitle :title="$L('登录')"/>
        <div class="login-body">
            <div class="login-logo no-dark-content" :class="{'can-click':needStartHome}" @click="goHome"></div>
            <div class="login-box">
                <div class="login-mode-switch">
                    <div class="login-mode-switch-box">
                        <ETooltip :disabled="$isEEUiApp || windowTouch" :content="$L(loginMode=='qrcode' ? '帐号登录' : '扫码登录')" placement="left">
                            <span class="login-mode-switch-icon" @click="switchLoginMode">
                                <svg v-if="loginMode=='qrcode'" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-icon="PcOutlined"><path d="M23 16a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h18a2 2 0 0 1 2 2v12ZM21 4H3v9h18V4ZM3 15v1h18v-1H3Zm3 6a1 1 0 0 1 1-1h10a1 1 0 1 1 0 2H7a1 1 0 0 1-1-1Z" fill="currentColor"></path></svg>
                                <svg v-else viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" data-icon="QrOutlined"><path d="M6.5 7.5a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-1a1 1 0 0 1-1-1v-1Z" fill="currentColor"></path><path d="M4.5 2.5c-1.1 0-2 .9-2 2v7c0 1.1.9 2 2 2h7c1.1 0 2-.9 2-2v-7c0-1.1-.9-2-2-2h-7Zm0 2h7v7h-7v-7ZM11 16a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm0 3.5a1 1 0 1 1 2 0v1a1 1 0 1 1-2 0v-1Zm4-7.5a1 1 0 1 1 2 0 1 1 0 0 1-2 0Zm3.5 0a1 1 0 0 1 1-1h1a1 1 0 1 1 0 2h-1a1 1 0 0 1-1-1ZM15 17c0-1.1.9-2 2-2h2.5c1.1 0 2 .9 2 2v2.5c0 1.1-.9 2-2 2H17c-1.1 0-2-.9-2-2V17Zm4.5 0H17v2.5h2.5V17Zm-15-2c-1.1 0-2 .9-2 2v2.5c0 1.1.9 2 2 2H7c1.1 0 2-.9 2-2V17c0-1.1-.9-2-2-2H4.5Zm0 2H7v2.5H4.5V17ZM15 4.5c0-1.1.9-2 2-2h2.5c1.1 0 2 .9 2 2V7c0 1.1-.9 2-2 2H17c-1.1 0-2-.9-2-2V4.5Zm4.5 0H17V7h2.5V4.5Z" fill="currentColor"></path></svg>
                            </span>
                        </ETooltip>
                    </div>
                </div>

                <div class="login-title">{{welcomeTitle}}</div>

                <div class="login-subtitle">{{$L(subTitle)}}</div>

                <transition name="login-mode">
                    <div v-if="loginMode=='qrcode'" class="login-qrcode" @click="qrcodeRefresh">
                        <VueQrcode :value="qrcodeUrl" :options="{width:200,margin:2}"></VueQrcode>
                    </div>
                </transition>
                <transition name="login-mode">
                    <div v-if="loginMode=='access'" class="login-access">
                        <Input
                            v-if="$isSoftware && cacheServerUrl"
                            :value="$A.getDomain(cacheServerUrl)"
                            prefix="ios-globe-outline"
                            size="large"
                            readonly
                            clearable
                            @on-clear="setServerUrl('')"/>

                        <Input
                            v-model="email"
                            ref="email"
                            prefix="ios-mail-outline"
                            :placeholder="$L('输入您的电子邮件')"
                            type="email"
                            size="large"
                            @on-enter="onLogin"
                            @on-blur="onBlur"
                            clearable/>

                        <Input
                            v-model="password"
                            ref="password"
                            prefix="ios-lock-outline"
                            :placeholder="$L('输入您的密码')"
                            type="password"
                            size="large"
                            @on-enter="onLogin"
                            clearable/>

                        <Input
                            v-if="loginType=='reg'"
                            v-model="password2"
                            ref="password2"
                            prefix="ios-lock-outline"
                            :placeholder="$L('输入确认密码')"
                            type="password"
                            size="large"
                            @on-enter="onLogin"
                            clearable/>
                        <Input
                            v-if="loginType=='reg' && needInvite"
                            v-model="invite"
                            ref="invite"
                            class="login-code"
                            :placeholder="$L('请输入注册邀请码')"
                            type="text"
                            size="large"
                            @on-enter="onLogin"
                            clearable><span slot="prepend">&nbsp;{{$L('邀请码')}}&nbsp;</span></Input>

                        <Input
                            v-if="loginType=='login' && codeNeed"
                            v-model="code"
                            ref="code"
                            class="login-code"
                            :placeholder="$L('输入图形验证码')"
                            type="text"
                            size="large"
                            @on-enter="onLogin"
                            clearable>
                            <Icon type="ios-checkmark-circle-outline" class="login-icon" slot="prepend"></Icon>
                            <div slot="append" class="login-code-end" @click="refreshCode">
                                <div v-if="codeLoad > 0" class="code-load"><Loading/></div>
                                <span v-else-if="codeUrl === 'error'" class="code-error">{{$L('加载失败')}}</span>
                                <img v-else :src="codeUrl"/>
                            </div>
                        </Input>

                        <Button type="primary" :loading="loadIng > 0 || loginJump" size="large" long @click="onLogin">{{$L(loginText)}}</Button>

                        <div v-if="loginType=='reg'" class="login-switch">{{$L('已经有帐号？')}}<a href="javascript:void(0)" @click="loginType='login'">{{$L('登录帐号')}}</a></div>
                        <div v-else class="login-switch">{{$L('还没有帐号？')}}<a href="javascript:void(0)" @click="loginType='reg'">{{$L('注册帐号')}}</a></div>
                    </div>
                </transition>
            </div>
            <div class="login-bottom">
                <Dropdown trigger="click" placement="bottom-start">
                    <div class="login-setting">
                        {{$L('设置')}}
                        <i class="taskfont">&#xe689;</i>
                    </div>
                    <DropdownMenu slot="list" class="login-setting-menu">
                        <Dropdown placement="right-start" transfer @on-click="setTheme">
                            <DropdownItem>
                                <div class="login-setting-item">
                                    {{$L('主题皮肤')}}
                                    <Icon type="ios-arrow-forward"></Icon>
                                </div>
                            </DropdownItem>
                            <DropdownMenu slot="list">
                                <DropdownItem
                                    v-for="(item, key) in themeList"
                                    :key="key"
                                    :name="item.value"
                                    :selected="themeConf === item.value">{{$L(item.name)}}</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                        <Dropdown placement="right-start" transfer @on-click="onLanguage">
                            <DropdownItem divided>
                                <div class="login-setting-item">
                                    {{currentLanguage}}
                                    <Icon type="ios-arrow-forward"></Icon>
                                </div>
                            </DropdownItem>
                            <DropdownMenu slot="list">
                                <DropdownItem
                                    v-for="(item, key) in languageList"
                                    :key="key"
                                    :name="key"
                                    :selected="languageName === key">{{item}}</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                    </DropdownMenu>
                </Dropdown>
                <div class="login-forgot">{{$L('忘记密码了？')}}<a href="javascript:void(0)" @click="forgotPassword">{{$L('重置密码')}}</a></div>
            </div>
        </div>

        <!--隐私政策提醒-->
        <Modal
            v-model="privacyShow"
            :title="$L('隐私协议')"
            :mask-closable="false">
            <div class="privacy-content">
                <div>{{$L('欢迎使用本软件！')}}</div>
                <p>{{$L('在您使用本软件前，请您认真阅读并了解相应的')}}<a target="_blank" :href="$A.apiUrl('privacy')">《{{ $L('隐私政策') }}》</a>, {{$L('以了解我们的服务内容和您相关个人信息的处理规则。')}}{{$L('我们将严格的按照隐私服务协议为您提供服务，保护您的个人信息。')}}</p>
            </div>
            <div slot="footer" class="adaption">
                <Button type="default" @click="onPrivacy(false)">{{$L('不同意')}}</Button>
                <Button type="primary" @click="onPrivacy(true)">{{$L('同意')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import {mapState} from "vuex";
import {Store} from "le5le-store";
import {languageList, languageName, setLanguage} from "../language";
import VueQrcode from "@chenfengyuan/vue-qrcode";

export default {
    components: {VueQrcode},
    data() {
        return {
            loadIng: 0,

            languageList,
            languageName,

            qrcodeVal: '',
            qrcodeTimer: null,
            qrcodeLoad: false,

            codeNeed: false,
            codeLoad: 0,
            codeKey: '',
            codeUrl: '',

            loginMode: 'access',
            loginType: 'login',
            loginJump: false,

            email: '',
            password: '',
            password2: '',
            code: '',
            invite: '',

            needStartHome: false,

            needInvite: false,

            subscribe: null,

            privacyShow: false,
        }
    },

    async mounted() {
        this.privacyShow = !!this.$isEEUiApp && (await $A.IDBString("cachePrivacyShow")) !== "no";
        this.email = await $A.IDBString("cacheLoginEmail") || ''
        //
        if (this.$isSoftware) {
            this.chackServerUrl().catch(_ => {});
        } else {
            this.setServerUrl('').catch(_ => {});
        }
        //
        this.qrcodeTimer = setInterval(this.qrcodeStatus, 2000);
        //
        this.subscribe = Store.subscribe('useSSOLogin', () => {
            this.inputServerUrl();
        });
    },

    beforeDestroy() {
        clearInterval(this.qrcodeTimer);
        if (this.subscribe) {
            this.subscribe.unsubscribe();
            this.subscribe = null;
        }
    },

    activated() {
        this.loginType = this.$route.query.type === 'reg' ? 'reg' : 'login'
        //
        this.getDemoAccount();
        this.getNeedStartHome();
        //
        if (this.$Electron) {
            this.$Electron.sendMessage('webTabDestroyAll')
            this.$Electron.sendMessage('childWindowDestroyAll')
        }
    },

    deactivated() {
        this.loginJump = false;
        this.password = "";
        this.password2 = "";
        this.code = "";
        this.invite = "";
    },

    computed: {
        ...mapState([
            'cacheServerUrl',

            'themeConf',
            'themeList',
        ]),

        currentLanguage() {
            return languageList[languageName] || 'Language'
        },

        welcomeTitle() {
            if (this.loginMode == 'qrcode') {
                return this.$L("扫码登录")
            }
            const title = window.systemInfo.title || "DooTask";
            return "Welcome " + title
        },

        subTitle() {
            const title = window.systemInfo.title || "DooTask";
            if (this.loginMode == 'qrcode') {
                return this.$L(`请使用${title}移动端扫描二维码。`)
            }
            if (this.loginType=='reg') {
                return this.$L(`输入您的信息以创建帐户。`)
            }
            return this.$L(`输入您的凭证以访问您的帐户。`)
        },

        loginText() {
            let text = this.loginType == 'login' ? '登录' : '注册';
            if (this.loginJump) {
                text += "成功..."
            }
            return text
        },

        qrcodeUrl() {
            return $A.mainUrl('login?qrcode=' + this.qrcodeVal)
        },
    },

    watch: {
        '$route' ({query}) {
            if (query.type=='reg'){
                this.$nextTick(()=>{
                    this.loginType = "reg"
                })
            }
        },
        loginMode() {
            this.qrcodeRefresh()
        },
        loginType(val) {
            if (val == 'reg') {
                this.getNeedInvite();
            }
        },
    },

    methods: {
        goHome() {
            if (this.needStartHome) {
                this.goForward('index');
            }
        },

        setTheme(mode) {
            this.$store.dispatch("setTheme", mode)
        },

        getDemoAccount() {
            if (this.isNotServer()) {
                return;
            }
            this.$store.dispatch("call", {
                url: 'system/demo',
            }).then(({data}) => {
                if (data.account) {
                    this.email = data.account;
                    this.password = data.password;
                }
            }).catch(_ => {
                //
            });
        },

        getNeedStartHome() {
            if (this.isNotServer()) {
                return
            }
            this.$store.dispatch("needHome").then(_ => {
                this.needStartHome = true
            }).catch(_ => {
                this.needStartHome = false
            })
        },

        getNeedInvite() {
            this.$store.dispatch("call", {
                url: 'users/reg/needinvite',
            }).then(({data}) => {
                this.needInvite = !!data.need;
            }).catch(_ => {
                this.needInvite = false;
            });
        },

        switchLoginMode() {
            this.chackServerUrl(true).then(() => {
                if (this.loginMode === 'qrcode') {
                    this.loginMode = 'access'
                } else {
                    this.loginMode = 'qrcode'
                }
            })
        },

        qrcodeRefresh() {
            if (this.loginMode == 'qrcode') {
                this.qrcodeVal = $A.randomString(32)
            }
        },

        qrcodeStatus() {
            if (this.$route.name !== 'login' || this.loginMode != 'qrcode') {
                return;
            }
            if (this.qrcodeLoad) {
                return;
            }
            this.qrcodeLoad = true
            //
            this.$store.dispatch("call", {
                url: 'users/login/qrcode?code=' + this.qrcodeVal,
            }).then(({data}) => {
                this.$store.dispatch("handleClearCache", data).then(this.goNext);
            }).catch(_ => {
                //
            }).finally(_ => {
                this.qrcodeLoad = false
            });
        },

        forgotPassword() {
            $A.modalWarning("请联系管理员！");
        },

        refreshCode() {
            if (this.codeLoad > 0) {
                return;
            }
            setTimeout(_ => {
                this.codeLoad++
            }, 600)
            this.$store.dispatch("call", {
                url: 'users/login/codejson',
            }).then(({data}) => {
                this.codeKey = data.key
                this.codeUrl = data.img
            }).catch(_ => {
                this.codeUrl = "error"
            }).finally(_ => {
                this.codeLoad--
            });
        },

        inputServerUrl() {
            if (this.privacyShow) {
                return
            }
            let value = $A.rightDelete(this.cacheServerUrl, "/api/");
            value = $A.leftDelete(value, "http://");
            $A.modalInput({
                title: "使用 SSO 登录",
                value,
                placeholder: "请输入服务器地址",
                onOk: (value) => {
                    if (!value) {
                        return '请输入服务器地址'
                    }
                    return this.inputServerChack($A.trim(value))
                }
            });
        },

        inputServerChack(value) {
            return new Promise((resolve, reject) => {
                let url = value;
                if (!/\/api\/$/.test(url)) {
                    url = url + ($A.rightExists(url, "/") ? "api/" : "/api/");
                }
                if (!/^https*:\/\//i.test(url)) {
                    url = `https://${url}`;
                }
                this.$store.dispatch("call", {
                    url: `${url}system/setting`,
                    checkNetwork: false,
                }).then(async ({data}) => {
                    if (typeof data.server_version === "undefined" && typeof data.all_group_mute === "undefined") {
                        reject(`服务器（${$A.getDomain(value)}）版本过低`)
                    } else {
                        await this.setServerUrl(url)
                        resolve()
                    }
                }).catch(({ret, msg}) => {
                    if (ret === -1001) {
                        if (!/^https*:\/\//i.test(value)) {
                            this.inputServerChack(`http://${value}`).then(resolve).catch(reject);
                            return;
                        }
                        msg = "服务器地址无效";
                    }
                    reject(msg)
                });
            })
        },

        chackServerUrl(tip) {
            return new Promise((resolve, reject) => {
                if (this.isNotServer()) {
                    this.inputServerUrl()
                    tip === true && this.$nextTick(_ => $A.messageWarning("请设置服务器"))
                    reject()
                } else {
                    resolve()
                }
            })
        },

        async setServerUrl(value) {
            await $A.IDBSet("cachePrivacyShow", value ? "no" : "yes")
            //
            if (value != this.cacheServerUrl) {
                await $A.IDBSet("cacheServerUrl", value)
                $A.reloadUrl();
            }
        },

        isNotServer() {
            let apiHome = $A.getDomain(window.systemInfo.apiUrl)
            return this.$isSoftware && (apiHome == "" || apiHome == "public")
        },

        onBlur() {
            if (this.loginType != 'login' || !this.email) {
                this.codeNeed = false;
                return;
            }
            this.loadIng++;
            this.$store.dispatch("call", {
                url: 'users/login/needcode',
                data: {
                    email: this.email,
                },
            }).then(() => {
                this.refreshCode()
                this.codeNeed = true
            }).catch(_ => {
                this.codeNeed = false
            }).finally(_ => {
                this.loadIng--
            });
        },

        onPrivacy(agree) {
            if (agree) {
                this.privacyShow = false
                this.chackServerUrl().catch(_ => {});
            } else {
                $A.eeuiAppGoDesktop()
            }
        },

        onLogin() {
            this.chackServerUrl(true).then(() => {
                this.email = $A.trim(this.email)
                this.password = $A.trim(this.password)
                this.password2 = $A.trim(this.password2)
                this.code = $A.trim(this.code)
                this.invite = $A.trim(this.invite)
                //
                if (!$A.isEmail(this.email)) {
                    $A.messageWarning("请输入正确的邮箱地址")
                    this.$refs.email.focus()
                    return
                }
                if (!this.password) {
                    $A.messageWarning("请输入密码")
                    this.$refs.password.focus()
                    return
                }
                if (this.loginType == 'reg') {
                    if (this.password != this.password2) {
                        $A.messageWarning("确认密码输入不一致")
                        this.$refs.password2.focus()
                        return
                    }
                }
                this.loadIng++
                this.$store.dispatch("call", {
                    url: 'users/login',
                    data: {
                        type: this.loginType,
                        email: this.email,
                        password: this.password,
                        code: this.code,
                        code_key: this.codeKey,
                        invite: this.invite,
                    },
                }).then(({data}) => {
                    $A.IDBSave("cacheLoginEmail", this.email)
                    this.codeNeed = false
                    //
                    this.loadIng++
                    this.$store.dispatch("handleClearCache", data)
                        .then(this.goNext)
                        .finally(_ => {
                            this.loadIng--
                        })
                }).catch(({data, msg}) => {
                    if (data.code === 'email') {
                        this.loginType = 'login'
                        $A.modalWarning(msg)
                    } else {
                        $A.modalError({
                            content: msg,
                            onOk: _ => {
                                this.$refs.code?.focus()
                            }
                        })
                    }
                    if (data.code === 'need') {
                        this.refreshCode()
                        this.codeNeed = true
                    }
                }).finally(_ => {
                    this.loadIng--
                })
            })
        },

        goNext() {
            this.loginJump = true
            const fromUrl = decodeURIComponent($A.getObject(this.$route.query, 'from'))
            if (fromUrl) {
                $A.IDBSet("clearCache", "login").then(_ => {
                    window.location.replace(fromUrl)
                })
            } else {
                this.goForward({name: 'manage-dashboard'}, true)
            }
        },

        onLanguage(l) {
            setLanguage(l)
        }
    }
}
</script>
