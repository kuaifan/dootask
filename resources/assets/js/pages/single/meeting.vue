<template>
    <div>{{meetData}}</div>
</template>
<script>
export default {
    data() {
        return {
            loadIng: 0,
            meetData: {},
        }
    },
    async mounted() {
        this.meetData = await $A.IDBJson('single-meeting')
    }
}
</script>
