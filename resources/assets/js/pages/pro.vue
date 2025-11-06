<template>
</template>

<script>
import {languageName} from "../language";

export default {
    mounted() {
        if (/^zh/.test(languageName)) {
            window.location.href = $A.mainUrl("site/zh/price.html")
        } else {
            window.location.href = $A.mainUrl("site/en/price.html")
        }
    },
}
</script>
