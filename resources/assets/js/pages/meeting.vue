<template>
    <div>
        <MeetingManager/>
    </div>
</template>

<script>
import MeetingManager from "./manage/components/MeetingManager.vue";

export default {
    components: {
        MeetingManager,
    },
    mounted() {
        this.$store.dispatch("showMeetingWindow",{
            type: "join",
            meetingsign: 'sign',
            meetingid: 10812486455,
            meetingdisabled: true,
        })
    },
}
</script>
