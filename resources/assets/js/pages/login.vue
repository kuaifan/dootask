<template>
    <div class="page-login">
        <PageTitle :title="$L('登录')"/>
        <div class="login-body">
            <div class="login-logo no-dark-mode" :class="{'can-click':needStartHome}" @click="goHome"></div>
            <div class="login-box">
                <div class="login-title">{{welcomeTitle}}</div>

                <div v-if="loginType=='reg'" class="login-subtitle">{{$L('输入您的信息以创建帐户。')}}</div>
                <div v-else class="login-subtitle">{{$L('输入您的凭证以访问您的帐户。')}}</div>

                <div class="login-input">
                    <Input v-if="$Electron && cacheServerUrl" :value="$A.getDomain(cacheServerUrl)" prefix="ios-globe-outline" size="large" readonly clearable @on-clear="clearServerUrl"/>

                    <Input v-model="email" prefix="ios-mail-outline" :placeholder="$L('输入您的电子邮件')" size="large" @on-enter="onLogin" @on-blur="onBlur" />

                    <Input v-model="password" prefix="ios-lock-outline" :placeholder="$L('输入您的密码')" type="password" size="large" @on-enter="onLogin" />

                    <Input v-if="loginType=='reg'" v-model="password2" prefix="ios-lock-outline" :placeholder="$L('输入确认密码')" type="password" size="large" @on-enter="onLogin" />
                    <Input v-if="loginType=='reg' && needInvite" v-model="invite" class="login-code" :placeholder="$L('请输入注册邀请码')" type="text" size="large" @on-enter="onLogin"><span slot="prepend">&nbsp;{{$L('邀请码')}}&nbsp;</span></Input>

                    <Input v-if="loginType=='login' && codeNeed" v-model="code" class="login-code" :placeholder="$L('输入图形验证码')" size="large" @on-enter="onLogin">
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
        }
    },
    mounted() {
        this.getDemoAccount();
        this.getNeedStartHome();
        //
        if (this.$Electron) {
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

        currentLanguage() {
            return this.languageList[this.languageType] || 'Language'
        },

        welcomeTitle() {
            let title = window.systemInfo.title || "DooTask";
            if (title == "PublicDooTask") {
                return "Public DooTask"
            } else {
                return "Welcome " + title
            }
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
                this.goForward({name: 'index'});
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
            $A.modalInput({
                title: "使用 SSO 登录",
                value: this.cacheServerUrl,
                placeholder: "请输入服务器地址",
                onOk: (value, cb) => {
                    if (value) {
                        if (!$A.leftExists(value, "http://") && !$A.leftExists(value, "https://")) {
                            value = "http://" + value;
                        }
                        if (!$A.rightExists(value, "/api/")) {
                            value = value + ($A.rightExists(value, "/") ? "api/" : "/api/");
                        }
                        this.$store.dispatch("call", {
                            url: value + 'system/setting',
                        }).then(() => {
                            this.setServerUrl(value)
                            cb()
                        }).catch(({msg}) => {
                            $A.modalError(msg || "服务器地址无效", 301);
                            cb()
                        });
                        return;
                    }
                    this.clearServerUrl();
                }
            });
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
            if (value != this.cacheServerUrl) {
                $A.setStorage("cacheServerUrl", value)
                window.location.reload();
            }
        },

        clearServerUrl() {
            this.setServerUrl("")
        },

        isNotServer() {
            let apiHome = $A.getDomain(window.systemInfo.apiUrl)
            return this.$Electron && (apiHome == "" || apiHome == "public")
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
                this.loadIng--;
                this.reCode();
                this.codeNeed = true;
            }).catch(_ => {
                this.loadIng--;
                this.codeNeed = false;
            });
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
                    return;
                }
                if (!this.password) {
                    $A.messageWarning("请输入密码");
                    return;
                }
                if (this.loginType == 'reg') {
                    if (this.password != this.password2) {
                        $A.messageWarning("确认密码输入不一致");
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
                    this.loadIng--;
                    this.codeNeed = false;
                    $A.setStorage("cacheLoginEmail", this.email)
                    this.$store.dispatch("handleClearCache", data).then(() => {
                        this.goNext1();
                    }).catch(_ => {
                        this.goNext1();
                    });
                }).catch(({data, msg}) => {
                    this.loadIng--;
                    if (data.code === 'email') {
                        $A.modalWarning(msg);
                    } else {
                        $A.modalError(msg);
                    }
                    if (data.code === 'need') {
                        this.reCode();
                        this.codeNeed = true;
                    }
                });
            })
        },

        goNext1() {
            this.loginJump = true;
            if (this.loginType == 'login') {
                this.goNext2();
            } else {
                // 新注册自动创建项目
                this.$store.dispatch("call", {
                    url: 'project/add',
                    data: {
                        name: this.$L('个人项目'),
                        desc: this.$L('注册时系统自动创建项目，你可以自由删除。')
                    },
                }).then(() => {
                    this.goNext2();
                }).catch(_ => {
                    this.goNext2();
                });
            }
        },

        goNext2() {
            let fromUrl = decodeURIComponent($A.getObject(this.$route.query, 'from'));
            if (fromUrl) {
                window.location.replace(fromUrl);
            } else {
                this.goForward({name: 'manage-dashboard'}, true);
            }
        }
    }
}
</script>
