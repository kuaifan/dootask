<template>
    <div class="setting-item submit">
        <IFrame class="privacy-iframe" :src="$A.apiUrl('../privacy.html')"/>
    </div>
</template>

<style scoped>
.privacy-iframe {
    position: absolute;
    top: 0;
    right: 0;
    width: 100%;
    height: 100%;
    border: 0;
}
</style>
<script>
import IFrame from "../components/IFrame";
export default {
    components: {IFrame}
}
</script>
