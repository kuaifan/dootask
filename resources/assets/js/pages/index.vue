<template>
    <div></div>
</template>

<script>
export default {
    data() {
        return {}
    },
    mounted() {
        if (this.usrLogin) {
            this.goForward({path: '/manage/dashboard'}, true);
        } else {
            this.goForward({path: '/login'}, true);
        }
    },
    deactivated() {
        this.$destroy()
    }
}
</script>
