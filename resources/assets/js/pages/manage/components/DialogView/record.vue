<template>
    <div class="content-record no-dark-content">
        <div class="dialog-record" :class="{playing: audioPlaying === msg.path}" :style="recordStyle(msg)" @click="playRecord">
            <div class="record-time">{{recordDuration(msg.duration)}}</div>
            <div class="record-icon taskfont"></div>
        </div>
        <div v-if="msg.text" class="dialog-record-text">
            {{msg.text}}
        </div>
    </div>
</template>

<script lang="ts">
import {mapState} from "vuex";

export default {
    props: {
        msg: Object,
    },
    computed: {
        ...mapState(['audioPlaying']),
    },
    methods: {
        playRecord() {
            this.$emit('playRecord');
        },
        recordStyle(info) {
            const {duration} = info;
            const width = 50 + Math.min(180, Math.floor(duration / 200));
            return {
                width: width + 'px',
            };
        },
        recordDuration(duration) {
            const minute = Math.floor(duration / 60000),
                seconds = Math.floor(duration / 1000) % 60;
            if (minute > 0) {
                return `${minute}:${seconds}″`
            }
            return `${Math.max(1, seconds)}″`
        },
    },
}
</script>
