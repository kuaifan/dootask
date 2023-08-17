<template>
</template>

<script>
import {languageType} from "../language";

export default {
    mounted() {
        if (languageType === "zh" || languageType === "zh-CHT") {
            window.location.href = $A.apiUrl("../site/zh/price.html")
        } else {
            window.location.href = $A.apiUrl("../site/en/price.html")
        }
    },
}
</script>
