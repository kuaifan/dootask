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
                <div>{{errorText}}</div>
            </div>
            <div slot="footer" v-if="success">
                <Button type="primary" @click="userLogout" long>{{$L('返回首页')}}</Button>
            </div>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.valid-wrap {
    height: 100vh;
    width: 100vw;
    display: flex;
    align-items: center;
    justify-content: center;

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

        .validation-text {
            padding: 10px;
            color: #333;
            font-size: 14px;
        }
    }
}
</style>
<script>
export default {
    data() {
        return {
            success: false,
            error: false,
            errorText: this.$L('链接已过期，已重新发送'),
        }
    },
    mounted() {
        this.verificationEmail();
    },
    methods: {
        verificationEmail() {
            this.$store.dispatch("call", {
                url: "users/email/verification",
                data: {
                    code: this.$route.query.code
                }
            }).then(() => {
                this.success = true;
                this.error = false;
            }).catch(({data, msg}) => {
                if (data.code === 2) {
                    this.goForward({name: 'index'}, true);
                } else {
                    this.success = false;
                    this.error = true;
                    this.errorText = this.$L(msg);
                }
            });
        },
        userLogout() {
            this.$store.dispatch("logout", false)
        }
    },
}
</script>
