<template>
    <div class="page-login">
        <PageTitle :title="$L('LDAP统一身份认证')"/>
        <div class="login-body">
            <div class="login-logo no-dark-mode" :class="{'can-click':needStartHome}" @click="goHome"></div>
            <div class="login-box">
                <div class="login-title">{{welcomeTitle}}</div>

                <div class="login-subtitle">{{$L('输入您的凭证以访问您的帐户。')}}</div>

                <div class="login-input">
                    <Input aria-label="" v-if="$Electron && cacheServerUrl" :value="$A.getDomain(cacheServerUrl)" prefix="ios-globe-outline" size="large" readonly clearable @on-clear="clearServerUrl"/>

                    <Input aria-label="用户名" ref="username" v-model="username" prefix="ios-mail-outline" :placeholder="$L('输入您的用户名')" size="large" @on-enter="onLogin" @on-blur="onBlur" />

                    <Input aria-label="密码" v-model="password" prefix="ios-lock-outline" :placeholder="$L('输入您的密码')" type="password" size="large" @on-enter="onLogin" />

                    <Input aria-label="验证码" v-if="codeNeed" v-model="code" class="login-code" :placeholder="$L('输入图形验证码')" size="large" @on-enter="onLogin">
                        <Icon type="ios-checkmark-circle-outline" class="login-icon" slot="prepend"></Icon>
                        <div slot="append" class="login-code-end" @click="reCode"><img alt="" :src="codeUrl"/></div>
                    </Input>

                    <Button type="primary" :loading="loadIng > 0 || loginJump" size="large" long @click="onLogin">{{$L(loginText)}}</Button>

                    <div class="login-switch"><a href="javascript:void(0)" @click="goLogin">{{$L('本地登录')}}</a></div>
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

export default {
    name: "login",
    data: function () {
        return {
            loadIng: 0,

            codeNeed: false,
            codeUrl: $A.apiUrl('users/login/codeimg?_=' + Math.random()),

            loginJump: false,

            username: $A.getStorageString("cacheLoginUser") || '',
            password: '',
            code: '',

            needStartHome: false,

            subscribe: null,
        }
    },
    mounted() {
        this.$refs.username.focus();
    },
    beforeDestroy() {
        if (this.subscribe) {
            this.subscribe.unsubscribe();
            this.subscribe = null;
        }
    },
    activated() {
        //
        if (this.$Electron) {
            this.$Electron.sendMessage('subWindowDestroyAll')
        }
    },
    deactivated() {
        this.loginJump = false;
        this.password = "";
        this.code = "";
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
            if (title === "PublicDooTask") {
                return "Public DooTask"
            } else {
                return "Welcome " + title
            }
        },

        loginText() {
            let text = '登录';

            if (this.loginJump) {
                text += "成功..."
            }
            return text
        }
    },
    watch: {
    },
    methods: {
        goHome() {
            if (this.needStartHome) {
                this.goForward({name: 'index'});
            }
        },

        /**
         * 跳转到本地账号登录
         */
        goLogin() {
            this.$router.push('/login');
        },

        setTheme(mode) {
            this.$store.dispatch("setTheme", mode)
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
            if (value !== this.cacheServerUrl) {
                $A.setStorage("cacheServerUrl", value)
                window.location.reload();
            }
        },

        clearServerUrl() {
            this.setServerUrl("")
        },

        isNotServer() {
            let apiHome = $A.getDomain(window.systemInfo.apiUrl)
            return this.$Electron && (apiHome === "" || apiHome === "public")
        },

        onBlur() {
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
                this.code = $A.trim(this.code)
                this.username = $A.trim(this.username)
                this.password = $A.trim(this.password)
                //
                if (!this.username) {
                    $A.messageWarning("请输入用户");
                    return;
                }
                if (!this.password) {
                    $A.messageWarning("请输入密码");
                    return;
                }
                this.loadIng++;
                this.$store.dispatch("call", {
                    url: 'ldap/login',
                    method: 'POST',
                    data: JSON.stringify({
                        username: this.username,
                        password: this.password,
                        code: this.code,
                    }),
                }).then(({data}) => {
                    this.loadIng--;
                    this.codeNeed = false;
                    $A.setStorage("cacheLoginUser", this.username)
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
            this.goNext2();
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

<style scoped>

</style>
