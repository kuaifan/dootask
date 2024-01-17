<template>
</template>

<script>
import {languageName} from "../language";

export default {
    mounted() {
        if (languageName === "zh" || languageName === "zh-CHT") {
            window.location.href = $A.apiUrl("../site/zh/price.html")
        } else {
            window.location.href = $A.apiUrl("../site/en/price.html")
        }
    },
}
</script>
