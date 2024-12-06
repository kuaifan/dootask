<template>
    <div class="content-record no-dark-content">
        <div class="dialog-record" :class="{playing: audioPlaying === msg.path}" :style="recordStyle(msg)" @click="playRecord">
            <div class="record-time">{{recordDuration(msg.duration)}}</div>
            <div class="record-icon taskfont"></div>
        </div>

        <template v-if="msg.text">
            <div class="content-divider">
                <span class="divider-full"></span>
            </div>
            <div class="content-additional">{{msg.text}}</div>
        </template>

        <template v-if="translation">
            <div class="content-divider">
                <span></span>
                <div class="divider-label translation-label" @click="viewText">{{ translation.label }}</div>
                <span></span>
            </div>
            <div class="content-additional">{{translation.content}}</div>
        </template>
    </div>
</template>

<script>
import {mapState} from "vuex";

export default {
    props: {
        msgId: Number,
        msg: Object,
    },
    computed: {
        ...mapState(['audioPlaying', 'cacheTranslations', 'cacheTranslationLanguage']),

        translation({cacheTranslations, msgId, cacheTranslationLanguage}) {
            const translation = cacheTranslations.find(item => {
                return item.key === `msg-${msgId}` && item.language === cacheTranslationLanguage;
            });
            return translation ? translation : null;
        },
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
        viewText(e) {
            this.$emit('viewText', e);
        },
    },
}
</script>
