<template>
    <div class="page-login">
        <PageTitle :title="$L('登录')"/>
        <div class="login-body">
            <div class="login-logo"></div>
            <div class="login-box">
                <div class="login-title">Welcome Dootask</div>

                <div v-if="loginType=='reg'" class="login-subtitle">{{$L('输入您的信息以创建帐户。')}}</div>
                <div v-else class="login-subtitle">{{$L('输入您的凭证以访问您的帐户。')}}</div>

                <div class="login-input">
                    <Input v-model="email" prefix="ios-mail-outline" :placeholder="$L('输入您的电子邮件')" size="large" @on-enter="onLogin" @on-blur="onBlur" />
                    <Input v-model="password" prefix="ios-lock-outline" :placeholder="$L('输入您的密码')" type="password" size="large" @on-enter="onLogin" />

                    <Input v-if="loginType=='reg'" v-model="password2" prefix="ios-lock-outline" :placeholder="$L('输入确认密码')" type="password" size="large" @on-enter="onLogin" />

                    <Input v-if="codeNeed" v-model="code" class="login-code" :placeholder="$L('输入图形验证码')" size="large" @on-enter="onLogin">
                        <Icon type="ios-checkmark-circle-outline" class="login-icon" slot="prepend"></Icon>
                        <div slot="append" class="login-code-end" @click="reCode"><img :src="codeUrl"/></div>
                    </Input>
                    <Button type="primary" :loading="loadIng > 0" size="large" long @click="onLogin">{{$L(loginType=='login'?'登录':'注册')}}</Button>

                    <div v-if="loginType=='reg'" class="login-switch">{{$L('已经有帐号？')}}<a href="javascript:void(0)" @click="loginType='login'">{{$L('登录帐号')}}</a></div>
                    <div v-else class="login-switch">{{$L('还没有帐号？')}}<a href="javascript:void(0)" @click="loginType='reg'">{{$L('注册帐号')}}</a></div>
                </div>
            </div>
            <div class="login-bottom">
                <Dropdown trigger="click" @on-click="setLanguage" transfer>
                    <div class="login-language">
                        {{currentLanguage}}
                        <i class="iconfont">&#xe689;</i>
                    </div>
                    <Dropdown-menu slot="list">
                        <Dropdown-item v-for="(item, key) in languageList" :key="key" :name="key" :selected="getLanguage() === key">{{item}}</Dropdown-item>
                    </Dropdown-menu>
                </Dropdown>
                <div class="login-forgot">{{$L('忘记密码了？')}}<a href="javascript:void(0)" @click="forgotPassword">{{$L('重置密码')}}</a></div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            loadIng: 0,

            codeNeed: false,
            codeUrl: this.$store.state.method.apiUrl('users/login/codeimg'),

            loginType: 'login',
            email: '',
            password: '',
            password2: '',
            code: '',
        }
    },
    computed: {
        currentLanguage() {
            return this.languageList[this.languageType] || 'Language'
        }
    },
    methods: {
        forgotPassword() {
            $A.modalWarning("请联系管理员！");
        },

        reCode() {
            this.codeUrl = this.$store.state.method.apiUrl('users/login/codeimg?_=' + Math.random())
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
            }).catch(() => {
                this.loadIng--;
                this.codeNeed = false;
            });
        },

        onLogin() {
            if (!this.email) {
                return;
            }
            if (!this.password) {
                return;
            }
            if (this.loginType == 'reg') {
                if (this.password != this.password2) {
                    $A.noticeError("确认密码输入不一致");
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
                },
            }).then(({data}) => {
                this.loadIng--;
                this.$store.state.method.clearLocal();
                this.$store.dispatch("saveUserInfo", data);
                this.goNext();
            }).catch(({data, msg}) => {
                this.loadIng--;
                $A.noticeError(msg);
                if (data.code === 'need') {
                    this.reCode();
                    this.codeNeed = true;
                }
            });
        },

        goNext() {
            let fromUrl = decodeURIComponent($A.getObject(this.$route.query, 'from'));
            if (fromUrl) {
                window.location.replace(fromUrl);
            } else {
                this.goForward({path: '/manage/dashboard'}, true);
            }
        },
    }
}
</script>
