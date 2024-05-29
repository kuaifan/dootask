<template>
    <VPreview
        v-if="ready"
        :value="value"/>
    <Loading v-else/>
</template>

<script>
import {previewMixin} from "./mixin";

export default {
    name: 'VMPreview',
    mixins: [previewMixin],
    components: {
        VPreview: () => import('./engine/preview.vue')
    },
    data() {
        return {
            ready: false,
        }
    },
    async mounted() {
        await $A.loadScriptS([
            'js/katex/katex.min.js',
            'js/katex/katex.min.css',
            'js/mermaid.min.js',
        ])
        this.ready = true;
    },
}
</script>
