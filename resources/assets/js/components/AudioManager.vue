<template>
    <audio
        ref="audio"
        class="common-audio"
        @ended="overAudio"
        @pause="onPause"
        @play="onPlay"></audio>
</template>

<style lang="scss" scoped>
.common-audio {
    position: absolute;
    top: 0;
    left: 0;
    width: 0;
    height: 0;
    opacity: 0;
    z-index: 0;
}
</style>
<script>
import {Store} from "le5le-store";

export default {
    name: 'AudioManager',
    data() {
        return {
            audioSubscribe: null,
            audioPlay: false,
            audioSrc: null,
            callback: null,
        }
    },
    mounted() {
        this.audioSubscribe = Store.subscribe('audioSubscribe', this.setAudioPlay);
    },
    beforeDestroy() {
        if (this.audioSubscribe) {
            this.audioSubscribe.unsubscribe();
            this.audioSubscribe = null;
        }
    },
    watch: {
        audioPlay(play) {
            if (typeof this.callback === "function") {
                this.callback(play)
            }
        }
    },
    methods: {
        setAudioPlay(info) {
            const audio = this.$refs.audio;
            const ended = audio.ended || audio.paused;
            audio.controls = false;
            audio.loop = false;
            if (info === false) {
                if (!ended) {
                    audio.pause()
                }
                return
            }
            const {src, callback} = info
            this.callback = callback || null;
            if (src === this.audioSrc) {
                if (ended) {
                    audio.play()
                } else {
                    audio.pause();
                }
            } else {
                this.audioSrc = src;
                if (!ended) {
                    audio.pause()
                }
                audio.src = src
                audio.play()
            }
        },

        overAudio() {
            this.audioPlay = false;
        },

        onPause() {
            this.audioPlay = false;
        },

        onPlay() {
            this.audioPlay = true;
        }
    }
}
</script>
