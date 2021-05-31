<template>
    <div class="page-login">
        <PageTitle>{{$L('登录页面')}}</PageTitle>
        <div class="login-body">
            <div class="login-logo"></div>
            <div class="login-box">
                <div class="login-title">Welcome Dootask</div>
                <div class="login-subtitle">Enter your credentials to access your account.</div>
                <div class="login-input">
                    <Input v-model="email" prefix="ios-mail-outline" placeholder="Enter your email" size="large" @on-enter="onLogin" @on-blur="onBlur" />
                    <Input v-model="password" prefix="ios-lock-outline" placeholder="Enter your password" type="password" size="large" @on-enter="onLogin" />
                    <Input v-if="codeNeed" v-model="code" class="login-code" placeholder="Verification Code" size="large" @on-enter="onLogin">
                        <Icon type="ios-checkmark-circle-outline" class="login-icon" slot="prepend"></Icon>
                        <div slot="append" class="login-code-end" @click="reCode"><img :src="codeUrl"/></div>
                    </Input>
                    <Button type="primary" :loading="loadIng > 0" size="large" long @click="onLogin">Sign In</Button>
                </div>
            </div>
            <div class="login-forgot">Forgot your password? <a href="#">Reset Password</a></div>
        </div>
    </div>
</template>

<style lang="scss">
:global {
    .page-login {
        .login-body {
            .login-box {
                .login-input {
                    .ivu-input {
                        border-color: #f1f1f1;
                    }
                    .login-code {
                        .ivu-input-group-prepend,
                        .ivu-input-group-append {
                            background: transparent;
                            border-color: #f1f1f1;
                        }
                        .ivu-input {
                            border-left-color: transparent;
                            border-right-color: transparent;
                        }
                        .login-code-end {
                            margin: -6px -7px;
                            height: 38px;
                            overflow: hidden;
                            cursor: pointer;
                            img {
                                height: 100%;
                            }
                        }
                    }
                }
            }
        }
    }
}
</style>
<style lang="scss" scoped>
:global {
    .page-login {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #F3F6FE;
        .login-body {
            display: flex;
            flex-direction: column;
            align-items: center;
            .login-logo {
                width: 64px;
                height: 64px;
                background: url("../../statics/images/logo.svg") no-repeat center center;
                background-size: contain;
            }
            .login-box {
                margin-top: 32px;
                width: 450px;
                border-radius: 12px;
                background-color: #ffffff;
                box-shadow: 0 0 10px #e6ecfa;
                .login-title {
                    font-size: 24px;
                    font-weight: 600;
                    text-align: center;
                    margin-top: 36px;
                }
                .login-subtitle {
                    font-size: 14px;
                    text-align: center;
                    margin-top: 12px;
                    color: #AAAAAA;
                }
                .login-input {
                    margin: 40px;
                    > * {
                        margin-top: 26px;
                    }
                }
            }
            .login-forgot {
                color: #aaaaaa;
                margin-top: 36px;
            }
        }
    }
}
</style>

<script>
export default {
    data() {
        return {
            loadIng: 0,

            codeNeed: false,
            codeUrl: $A.apiUrl('users/login/codeimg'),

            loginType: 'login',
            email: '',
            password: '',
            code: '',
        }
    },
    methods: {
        reCode() {
            this.codeUrl = $A.apiUrl('users/login/codeimg?_=' + Math.random())
        },

        onBlur() {
            if (this.loginType != 'login') {
                this.codeNeed = false;
                return;
            }
            this.loadIng++;
            $A.ajaxc({
                url: $A.apiUrl('users/login/needcode'),
                data: {
                    email: this.email,
                },
                complete: () => {
                    this.loadIng--;
                },
                success: (res) => {
                    this.reCode();
                    this.codeNeed = res.ret === 1;
                }
            })
        },

        onLogin() {
            if (!this.email) {
                return;
            }
            if (!this.password) {
                return;
            }
            this.loadIng++;
            $A.ajaxc({
                url: $A.apiUrl('users/login?type=' + this.loginType),
                data: {
                    email: this.email,
                    password: this.password,
                    code: this.code,
                },
                complete: () => {
                    this.loadIng--;
                },
                success: (res) => {
                    if (res.ret === 1) {
                        this.$store.commit('userInfo', res.data);
                        //
                        this.goNext();
                    } else {
                        $A.noticeError(res.msg);
                        if (res.data.code === 'need') {
                            this.reCode();
                            this.codeNeed = true;
                        }
                    }
                }
            })
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
