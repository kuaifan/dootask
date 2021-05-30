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
            this.goForward({path: '/dashboard'}, true);
        } else {
            this.goForward({path: '/users/login'}, true);
        }
    },
    deactivated() {
        this.$destroy()
    }
}
</script>
