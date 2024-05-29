<template>
    <VEditor
        v-if="ready"
        v-model="content"
        :leftToolbar="leftToolbar"
        :rightToolbar="rightToolbar"
        :tocNavPositionRight="tocNavPositionRight"
        :includeLevel="includeLevel"/>
    <Loading v-else/>
</template>

<script>
import {editorMixin} from "./mixin";

export default {
    name: 'VMEditor',
    mixins: [editorMixin],
    components: {
        VEditor: () => import('./engine/editor.vue')
    },
    data() {
        return {
            ready: false,
            content: '',
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

    watch: {
        value: {
            handler(val) {
                if (val == null) {
                    val = "";
                }
                this.content = val;
            },
            immediate: true
        },

        content(val) {
            this.$emit('input', val);
        },
    },
}
</script>
