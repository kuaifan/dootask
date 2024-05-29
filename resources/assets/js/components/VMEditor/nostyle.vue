<template>
    <div class="vmpreview-nostyle" v-html="html"></div>
</template>

<script>
import nostyle from './engine/nostyle';

export default {
    name: 'VMPreviewNostyle',
    props: {
        value: {
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
            this.html = nostyle(this.value, {
                sanitize: false,
            }).replace(/href="/gi, 'target="_blank" href="');
        },
    },
    watch: {
        value() {
            this.translateMarkdown();
        }
    }
};
</script>
