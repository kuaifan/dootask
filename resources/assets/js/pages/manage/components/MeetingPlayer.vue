<template>
    <div class="meeting-player">
        <div :id="id" class="player"></div>
        <UserAvatar :userid="player.uid" show-name/>
    </div>
</template>

<script>

export default {
    name: "MeetingPlayer",
    props: {
        id: {
            type: String,
            default: () => {
                return  "meeting-player-" + Math.round(Math.random() * 10000);
            }
        },
        player: {
            type: Object,
        },
    },
    data() {
        return {

        }
    },
    watch: {
        player: {
            handler(e) {
                this.$nextTick(_ => {
                    switch (e.mediaType) {
                        case 'video':
                            e.videoTrack.play(this.id);
                            break;
                        case 'audio':
                            e.audioTrack.play();
                            break;
                    }
                })
            },
            immediate: true
        }
    }
}
</script>
