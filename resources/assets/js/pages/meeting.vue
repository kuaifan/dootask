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
        const {meetingId, sharekey} = this.$route.params;
        const {nickname, avatar, audio, video, type} = this.$route.query;
        this.$store.dispatch("showMeetingWindow",{
            type: ['direct', 'join'].includes(type) ? type : 'join',
            meetingid: meetingId,
            meetingSharekey: sharekey,
            meetingNickname: nickname,
            meetingAvatar: avatar,
            meetingAudio: audio,
            meetingVideo: video,
            meetingdisabled: true,
        })
    },
}
</script>
