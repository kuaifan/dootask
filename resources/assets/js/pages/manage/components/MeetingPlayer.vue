<template>
    <div class="meeting-player">
        <div :id="id" class="player">
            <div class="player-bg" :style="playerStyle"></div>
        </div>
        <ETooltip :disabled="$isEEUiApp || windowTouch || !username">
            <div slot="content">
                {{username}}
            </div>
            <div class="meeting-avatar">
                <UserAvatar v-if="userid" :userid="userid" :size="36" :borderWitdh="2"/>
                <div v-else-if="tourist.userimg" class="common-avatar avatar-wrapper">
                    <div class="avatar-box online">
                        <em></em>
                        <EAvatar :size="36" :src="tourist.userimg"></EAvatar>
                    </div>
                </div>
            </div>
        </ETooltip>
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
            timer: null,
            tourist: {
                uid: '',
                nickname: '',
                userimg: '',
            }
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
                if( (this.player.uid + '').indexOf('88888') !== -1 ){
                    this.getTouristInfo();
                    return 0;
                }
                return parseInt( (this.player.uid+"").substring(6) ) || 0
            }
            return 0
        },
        username() {
            if (this.userid) {
                const user = this.cacheUserBasic.find(({userid}) => userid == this.userid);
                if (user) {
                    return user.nickname
                }
                return ''
            }
            return this.tourist.nickname || ''
        },
        playerStyle() {
            const user = this.cacheUserBasic.find(({userid}) => userid == this.userid);
            if (user && user.userimg) {
                return {
                    backgroundImage: `url("${user.userimg}")`
                }
            }else if(this.tourist.userimg){
                return {
                    backgroundImage: `url("${this.tourist.userimg}")`
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
        },
        getTouristInfo() {
            this.$store.dispatch("call", {
                url: 'users/meeting/tourist',
                data: {
                    tourist_id: this.player.uid
                }
            }).then(({data}) => {
                this.tourist = data;
            }).catch(({msg}) => {
                $A.modalError(msg);
            });
        }
    }
}
</script>
