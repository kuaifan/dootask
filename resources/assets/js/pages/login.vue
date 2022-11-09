<template>
    <div class="page-login">
        <PageTitle :title="$L('登录')"/>
        <div class="login-body">
            <div class="login-logo no-dark-content" :class="{'can-click':needStartHome}" @click="goHome"></div>
            <div class="login-box">
                <div class="login-title">{{welcomeTitle}}</div>

                <div v-if="loginType=='reg'" class="login-subtitle">{{$L('输入您的信息以创建帐户。')}}</div>
                <div v-else class="login-subtitle">{{$L('输入您的凭证以访问您的帐户。')}}</div>

                <div class="login-input">
                    <Input
                        v-if="isSoftware && cacheServerUrl"
                        :value="$A.getDomain(cacheServerUrl)"
                        prefix="ios-globe-outline"
                        size="large"
                        readonly
                        clearable
                        @on-clear="clearServerUrl"/>

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
                        <div slot="append" class="login-code-end" @click="reCode"><img :src="codeUrl"/></div>
                    </Input>

                    <Button type="primary" :loading="loadIng > 0 || loginJump" size="large" long @click="onLogin">{{$L(loginText)}}</Button>

                    <div v-if="loginType=='reg'" class="login-switch">{{$L('已经有帐号？')}}<a href="javascript:void(0)" @click="loginType='login'">{{$L('登录帐号')}}</a></div>
                    <div v-else class="login-switch">{{$L('还没有帐号？')}}<a href="javascript:void(0)" @click="loginType='reg'">{{$L('注册帐号')}}</a></div>
                </div>
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
                                    :selected="themeMode === item.value">{{$L(item.name)}}</DropdownItem>
                            </DropdownMenu>
                        </Dropdown>
                        <Dropdown placement="right-start" transfer @on-click="setLanguage">
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
                                    :selected="getLanguage() === key">{{item}}</DropdownItem>
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
                <div>欢迎使用本软件！</div>
                <p>在您使用本软件前，请您认真阅读并了解相应的<a target="_blank" :href="$A.apiUrl('privacy')">《{{ $L('隐私政策') }}》</a>，以了解我们的服务内容和您相关个人信息的处理规则。我们将严格的按照隐私服务协议为您提供服务，保护您的个人信息。</p>
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

export default {
    data() {
        return {
            loadIng: 0,

            codeNeed: false,
            codeUrl: $A.apiUrl('users/login/codeimg?_=' + Math.random()),

            loginType: 'login',
            loginJump: false,

            email: $A.getStorageString("cacheLoginEmail") || '',
            password: '',
            password2: '',
            code: '',
            invite: '',

            needStartHome: false,

            needInvite: false,

            subscribe: null,

            privacyShow: !!this.$isEEUiApp && $A.getStorageString("cachePrivacyShow") !== "no",
        }
    },
    mounted() {
        this.getDemoAccount();
        this.getNeedStartHome();
        //
        if (this.isSoftware) {
            this.chackServerUrl().catch(_ => {});
        } else {
            this.clearServerUrl();
        }
        //
        this.subscribe = Store.subscribe('useSSOLogin', () => {
            this.inputServerUrl();
        });
    },

    beforeDestroy() {
        if (this.subscribe) {
            this.subscribe.unsubscribe();
            this.subscribe = null;
        }
    },

    activated() {
        this.loginType = 'login'
        //
        if (this.$Electron) {
            this.$Electron.sendMessage('subWindowDestroyAll')
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

            'themeMode',
            'themeList',
        ]),

        isSoftware() {
            return this.$Electron || this.$isEEUiApp;
        },

        currentLanguage() {
            return this.languageList[this.languageType] || 'Language'
        },

        welcomeTitle() {
            let title = window.systemInfo.title || "DooTask";
            return "Welcome " + title
        },

        loginText() {
            let text = this.loginType == 'login' ? '登录' : '注册';
            if (this.loginJump) {
                text += "成功..."
            }
            return text
        }
    },

    watch: {
        '$route' ({query}) {
            if (query.type=='reg'){
                this.$nextTick(()=>{
                    this.loginType = "reg"
                })
            }
        },
        loginType(val) {
            if (val == 'reg') {
                this.getNeedInvite();
            }
        }
    },

    methods: {
        goHome() {
            if (this.needStartHome) {
                this.goForward({name: 'index', query: {action: 'index'}});
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
                return;
            }
            this.$store.dispatch("call", {
                url: "system/get/starthome",
            }).then(({data}) => {
                this.needStartHome = !!data.need_start;
            }).catch(_ => {
                this.needStartHome = false;
            });
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

        forgotPassword() {
            $A.modalWarning("请联系管理员！");
        },

        reCode() {
            this.codeUrl = $A.apiUrl('users/login/codeimg?_=' + Math.random())
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
                }).then(() => {
                    this.setServerUrl(url)
                    resolve()
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
                    if (tip === true) {
                        $A.messageWarning("请设置服务器")
                    }
                    this.inputServerUrl()
                    reject()
                } else {
                    resolve()
                }
            })
        },

        setServerUrl(value) {
            $A.setStorage("cachePrivacyShow", value ? "no" : "yes")
            //
            if (value != this.cacheServerUrl) {
                $A.setStorage("cacheServerUrl", value)
                $A.reloadUrl();
            }
        },

        clearServerUrl() {
            this.setServerUrl("")
        },

        isNotServer() {
            let apiHome = $A.getDomain(window.systemInfo.apiUrl)
            return this.isSoftware && (apiHome == "" || apiHome == "public")
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
                this.reCode();
                this.codeNeed = true;
            }).catch(_ => {
                this.codeNeed = false;
            }).finally(_ => {
                this.loadIng--;
            });
        },

        onPrivacy(agree) {
            if (agree) {
                this.privacyShow = false
                this.inputServerUrl()
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
                    $A.messageWarning("请输入正确的邮箱地址");
                    this.$refs.email.focus();
                    return;
                }
                if (!this.password) {
                    $A.messageWarning("请输入密码");
                    this.$refs.password.focus();
                    return;
                }
                if (this.loginType == 'reg') {
                    if (this.password != this.password2) {
                        $A.messageWarning("确认密码输入不一致");
                        this.$refs.password2.focus();
                        return;
                    }
                }
                this.loadIng++;
                this.$store.dispatch("call", {
                    url: 'users/login',
                    data: {
                        type: this.loginType,
                        email: this.email,
                        password: this.password,
                        code: this.code,
                        invite: this.invite,
                    },
                }).then(({data}) => {
                    this.codeNeed = false;
                    $A.setStorage("cacheLoginEmail", this.email)
                    this.$store.dispatch("handleClearCache", data).then(() => {
                        this.goNext();
                    }).catch(_ => {
                        this.goNext();
                    });
                }).catch(({data, msg}) => {
                    if (data.code === 'email') {
                        $A.modalWarning(msg);
                    } else {
                        $A.modalError(msg);
                    }
                    if (data.code === 'need') {
                        this.reCode();
                        this.codeNeed = true;
                        this.$refs.code.focus();
                    }
                }).finally(_ => {
                    this.loadIng--;
                });
            })
        },

        goNext() {
            this.loginJump = true;
            const fromUrl = decodeURIComponent($A.getObject(this.$route.query, 'from'));
            if (fromUrl) {
                window.location.replace(fromUrl);
            } else {
                this.goForward({name: 'manage-dashboard'}, true);
            }
        },
    }
}
</script>
