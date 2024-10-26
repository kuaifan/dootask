<template>
    <div class="content-text no-dark-content">
        <DialogMarkdown v-if="msg.type === 'md'" @click="viewText" :text="msg.text"/>
        <pre v-else @click="viewText" v-html="$A.formatTextMsg(msg.text, userId)"></pre>

        <template v-if="translation">
            <div class="content-divider">
                <span></span>
                <div class="divider-label">{{ translation.label }}</div>
                <span></span>
            </div>
            <DialogMarkdown v-if="msg.type === 'md'" :text="translation.value"/>
            <pre v-else v-html="$A.formatTextMsg(translation.value, userId)"></pre>
        </template>
    </div>
</template>

<script lang="ts">
import {mapState} from "vuex";
import DialogMarkdown from "../DialogMarkdown.vue";
import {languageName} from "../../../../language";

export default {
    components: {DialogMarkdown},
    props: {
        msgId: Number,
        msg: Object,
    },
    computed: {
        ...mapState(['cacheTranslations']),

        translation() {
            const translation = this.cacheTranslations.find(item => {
                return item.key === `msg-${this.msgId}` && item.lang === languageName;
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
