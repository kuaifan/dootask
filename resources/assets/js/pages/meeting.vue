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
            meetingid: this.$route.params.meetingId,
            meetingSharekey: this.$route.params.sharekey,
            meetingdisabled: true,
        })
    },
}
</script>
