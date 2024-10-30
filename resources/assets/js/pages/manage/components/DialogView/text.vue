<template>
    <div class="content-text no-dark-content">
        <DialogMarkdown v-if="msg.type === 'md'" @click="viewText" :text="msg.text"/>
        <pre v-else @click="viewText" v-html="$A.formatTextMsg(msg.text, userId)"></pre>

        <template v-if="translation">
            <div class="content-divider">
                <span></span>
                <div class="divider-label translation-label" @click="viewText">{{ translation.label }}</div>
                <span></span>
            </div>
            <DialogMarkdown v-if="msg.type === 'md'" :text="translation.content"/>
            <pre v-else v-html="$A.formatTextMsg(translation.content, userId)"></pre>
        </template>
    </div>
</template>

<script>
import {mapState} from "vuex";
import DialogMarkdown from "../DialogMarkdown.vue";

export default {
    components: {DialogMarkdown},
    props: {
        msgId: Number,
        msg: Object,
    },
    computed: {
        ...mapState(['cacheTranslations', 'cacheTranslationLanguage']),

        translation({cacheTranslations, msgId, cacheTranslationLanguage}) {
            const translation = cacheTranslations.find(item => {
                return item.key === `msg-${msgId}` && item.language === cacheTranslationLanguage;
            });
            return translation ? translation : null;
        },
    },
    methods: {
        viewText(e) {
            this.$emit('viewText', e);
        },
    },
}
</script>
