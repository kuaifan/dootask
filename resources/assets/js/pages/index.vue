<template>

</template>

<script>
export default {
    data() {
        return {}
    },

    mounted() {
        if (/^https?:/i.test(window.location.protocol)) {
            let redirect = null
            if (this.$router.mode === "hash") {
                if ($A.stringLength(window.location.pathname) > 2) {
                    redirect = `${window.location.origin}/#${window.location.pathname}${window.location.search}`
                }
            } else if (this.$router.mode === "history") {
                if ($A.strExists(window.location.href, "/#/")) {
                    redirect = window.location.href.replace("/#/", "/")
                }
            }
            if (redirect) {
                this.$store.dispatch("userUrl", redirect).then(redirect => {
                    window.location.href = redirect
                })
                throw SyntaxError()
            }
        }
    },

    activated() {
        this.start();
    },

    methods: {
        start() {
            if (this.userId > 0) {
                this.goForward({name: 'manage-dashboard'}, true);
            } else {
                this.goForward({name: 'login'}, true);
            }
        }
    },
};
</script>
