<template>
</template>

<script>
import {languageName} from "../language";

export default {
    mounted() {
        if (languageName === "zh" || languageName === "zh-CHT") {
            window.location.href = $A.mainUrl("site/zh/price.html")
        } else {
            window.location.href = $A.mainUrl("site/en/price.html")
        }
    },
}
</script>
