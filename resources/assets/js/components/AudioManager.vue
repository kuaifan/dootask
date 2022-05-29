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
            audioId: 0,
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
        setAudioPlay(msg) {
            const audio = this.$refs.audio;
            const ended = audio.ended || audio.paused;
            audio.controls = false;
            audio.loop = false;
            audio.volume = 1;
            if (/\d+/.test(msg)) {
                msg = msg == this.audioId
            }
            if (typeof msg === "boolean") {
                if (msg && !ended) {
                    audio.pause()
                }
                return
            }
            const {id, src, callback} = msg
            this.callback = callback || null;
            if (src === this.audioSrc) {
                if (ended) {
                    audio.play()
                } else {
                    audio.pause();
                }
            } else {
                this.audioId = id;
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
