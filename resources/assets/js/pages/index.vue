<template>

</template>

<script>
import {languageType} from "../language";

export default {
    data() {
        return {}
    },

    computed: {
        isSoftware() {
            return this.$Electron || this.$isEEUiApp;
        },
    },

    mounted() {
        if (/^https*:/i.test(window.location.protocol)) {
            if (this.$router.mode === "hash") {
                if ($A.stringLength(window.location.pathname) > 2) {
                    window.location.href = `${window.location.origin}/#${window.location.pathname}${window.location.search}`
                }
            } else if (this.$router.mode === "history") {
                if ($A.strExists(window.location.href, "/#/")) {
                    window.location.href = window.location.href.replace("/#/", "/")
                }
            }
        }
    },

    activated() {
        this.start();
    },

    methods: {
        start() {
            if (this.isSoftware) {
                this.goNext()
                return;
            }
            //
            this.$store.dispatch("showSpinner", 1000)
            this.$store.dispatch("needHome").then(_ => {
                this.goIndex();
            }).catch(_ => {
                this.goNext();
            }).finally(_ => {
                this.$store.dispatch("hiddenSpinner")
            });
        },

        goIndex() {
            if (languageType === "zh" || languageType === "zh-CHT") {
                window.location.href = $A.apiUrl("../site/zh/index.html")
            } else {
                window.location.href = $A.apiUrl("../site/en/index.html")
            }
        },

        goNext() {
            if (this.userId > 0) {
                this.goForward({name: 'manage-dashboard'}, true);
            } else {
                this.goForward({name: 'login'}, true);
            }
        }
    },
};
</script>
