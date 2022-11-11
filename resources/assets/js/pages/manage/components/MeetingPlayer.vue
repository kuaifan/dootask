<template>
    <div v-if="userid" class="meeting-player">
        <div :id="id" class="player" :style="playerStyle"></div>
        AA{{mediaType}}BB
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
        mediaType: {
            type: String,
            default: ""
        },
    },
    data() {
        return {

        }
    },
    computed: {
        ...mapState(['cacheUserBasic']),
        userid() {
            if (this.player.uid) {
                return parseInt($A.getMiddle(this.player.uid, null, '-'))
            }
            return 0
        },
        playerStyle() {
            const user = this.cacheUserBasic.find(({userid}) => userid == this.userid);
            if (user) {
                return {
                    backgroundImage: `url("${user.userimg}")`
                }
            }
            return null;
        },
        audio() {
            return !!this.player.audioTrack
        },
        video() {
            return !!this.player.videoTrack
        }
    },
    watch: {
        mediaType: {
            handler(type) {
                this.$nextTick(_ => {
                    this.play(type)
                })
            },
            immediate: true
        }
    },
    methods: {
        play(type) {
            try {
                if (type === 'audio') {
                    this.player.audioTrack.play();
                } else if (type === 'video') {
                    this.player.videoTrack.play(this.id);
                }
            } catch (e) {
                console.log("Meeting Player Error", e);
            }
        }
    }
}
</script>
