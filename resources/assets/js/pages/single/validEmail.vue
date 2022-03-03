<template>
    <div class="valid-wrap">
        <div class="valid-box">
            <div class="valid-title">{{$L('验证邮箱')}}</div>
            <Spin size="large" v-if="!success && !error"></Spin>
            <div class="validation-text" v-if="success">
                <p>{{$L('您的邮箱已通过验证')}}</p>
                <p>{{$L('今后您可以通过此邮箱重置您的账号密码')}}</p>
            </div>
            <div class="validation-text" v-if="error">
                <div>{{$L('您的邮箱未通过验证，可能的原因：')}}</div>
                <div>{{$L('1.验证码已过期')}}</div>
                <div>{{$L('2.重复使用验证码')}}</div>
            </div>
            <div slot="footer" v-if="success">
                <Button type="primary" @click="userLogout" long>{{$L('返回首页')}}</Button>
            </div>
        </div>
    </div>
</template>

<script>

export default {
    name: "validEmail",
    components: {},
    data() {
        return {
            success: false,
            error: false,
        }
    },
    mounted() {
        this.verificationEmail();
    },
    watch: {},
    methods: {
        verificationEmail() {
            this.$store
                .dispatch("call", {
                    url: "users/email/verification",
                    data: {
                        code: this.$route.query.code
                    }
                })
                .then(({data}) => {
                    this.success = true;
                    this.error = false;
                })
                .catch(() => {
                    this.success = false;
                    this.error = true;
                });
        },
        userLogout() {
            this.$store.dispatch("logout", false)
        }
    },
}
</script>
<style lang="scss">
.valid-wrap {
    height: 100vh;
    width: 100vw;
    display: flex;
    align-items: center;
    justify-content: center;
}
.valid-box {
    width: 500px;
    background-color: #fff;
    padding: 5px 15px 20px 15px;
    border-radius: 10px;

    .valid-title {
        border-bottom: 1px solid #e8eaec;
        padding: 14px 16px;
        line-height: 1;
    }

    .validation-text{
        padding: 10px;
        color: #333;
        font-size: 14px;
    }
}
</style>
