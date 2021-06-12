<template>
    <div></div>
</template>

<script>
export default {
    data() {
        return {}
    },
    mounted() {
        if (this.$store.state.userId > 0) {
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
