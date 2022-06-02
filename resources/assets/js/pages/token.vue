<template>
    <div class="token-transfer">
        <Loading/>
    </div>
</template>

<style lang="scss" scoped>
.token-transfer {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
</style>
<script>
export default {
    mounted() {
        this.goNext1();
    },

    methods: {
        goNext1() {
            const params = $A.urlParameterAll();
            if (params.token) {
                this.$store.dispatch("call", {
                    url: 'users/info',
                    header: {
                        token: params.token
                    }
                }).then(result => {
                    this.$store.dispatch("saveUserInfo", result.data);
                    this.goNext2();
                }).catch(_ => {
                    this.goForward({name: 'login'}, true);
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
