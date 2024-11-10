<script>
export default {
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
    render() {
        return null
    }
}
</script>
