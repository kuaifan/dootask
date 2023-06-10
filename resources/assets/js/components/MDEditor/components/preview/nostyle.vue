<template>
    <div class="markdown-preview-nostyle" v-html="html"></div>
</template>

<script>
import marked from '../../config/marked';
export default {
    name: 'markdown-preview-nostyle',
    props: {
        initialValue: {
            type: String,
            default: ''
        },
    },
    data() {
        return {
            html: '',
        };
    },
    mounted() {
        this.translateMarkdown();
    },
    methods: {
        translateMarkdown() {
            this.html = marked(this.initialValue, {
                sanitize: false,
            }).replace(/href="/gi, 'target="_blank" href="');
        },
    },
    watch: {
        initialValue() {
            this.translateMarkdown();
        }
    }
};
</script>
