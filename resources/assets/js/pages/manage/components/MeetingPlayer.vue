<template>
    <div class="meeting-player">
        <div :id="id" class="player" :style="playerStyle"></div>
        <UserAvatar :userid="userid" :size="36" :borderWitdh="2"/>
        <div class="player-state">
            <i v-if="!audio" class="taskfont">&#xe7c7;</i>
            <i v-if="!video" class="taskfont">&#xe7c8;</i>
        </div>
    </div>
</template>

<script>
import {mapState} from "vuex";

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
            default: () => ({})
        },
        isLocal: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            timer: null
        }
    },
    mounted() {
        this.timer = setInterval(_ => {
            if (this.audio && !this.player.audioTrack.isPlaying) {
                this.play('audio')
            }
            if (this.video && !this.player.videoTrack.isPlaying) {
                this.play('video')
            }
        }, 3000)
    },
    beforeDestroy() {
        clearInterval(this.timer)
    },
    computed: {
        ...mapState(['cacheUserBasic']),
        userid() {
            if (this.player.uid) {
                console.log(parseInt( (this.player.uid+"").substring(6) ));
                return parseInt( (this.player.uid+"").substring(6) )
            }
            return 0
        },
        playerStyle() {
            const user = this.cacheUserBasic.find(({userid}) => userid == this.userid);
            if (user) {
                return {
                    backgroundImage: `url("${user.userimg}")`
                }
            }else{
                return {
                    backgroundColor: '#000000'
                }
            }
        },
        audio() {
            return !!this.player.audioTrack
        },
        video() {
            return !!this.player.videoTrack
        }
    },
    watch: {
        audio: {
            handler(b) {
                b && this.play('audio')
            },
            immediate: true
        },
        video: {
            handler(b) {
                b && this.play('video')
            },
            immediate: true
        }
    },
    methods: {
        play(type) {
            this.$nextTick(_ => {
                try {
                    if (type === 'audio') {
                        !this.isLocal && this.player.audioTrack.play();
                    } else if (type === 'video') {
                        this.player.videoTrack.play(this.id);
                    }
                } catch (e) {
                    console.log("Meeting Player Error", e);
                }
            })
        }
    }
}
</script>
