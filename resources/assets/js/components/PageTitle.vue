<template>
    <div v-if="false"></div>
</template>

<script>
export default {
    name: 'PageTitle',
    props: {
        title: {
            type: [String, Number],
            default: ''
        },
    },

    data() {
        return {
            pagePath: ''
        }
    },

    activated() {
        this.updateTitle()
    },

    watch: {
        title: {
            handler() {
                this.initTitle()
            },
            immediate: true
        }
    },

    methods: {
        initTitle() {
            this.pagePath = this.$route.path;
            this.updateTitle()
        },

        updateTitle() {
            if (this.pagePath == '') {
                return;
            }
            let pageTitle = this.title;
            let {title} = document;
            if (pageTitle !== title && this.pagePath === this.$route.path) {
                this.setPageTile(pageTitle);
            }
        },

        setPageTile(title) {
            document.title = title;
        }
    }
}
</script>
