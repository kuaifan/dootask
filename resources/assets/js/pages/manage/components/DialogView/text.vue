<template>
    <div class="content-text no-dark-content">
        <DialogMarkdown v-if="msg.type === 'md'" @click="viewText" :text="msg.text"/>
        <pre v-else @click="viewText" v-html="$A.formatTextMsg(msg.text, userId)"></pre>
    </div>
</template>

<script lang="ts">
import DialogMarkdown from "../DialogMarkdown.vue";

export default {
    components: {DialogMarkdown},
    props: {
        msg: Object,
    },
    methods: {
        viewText() {
            this.$emit('viewText');
        },
    },
}
</script>
