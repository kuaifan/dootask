<template>
    <h1 v-if="false"><slot/></h1>
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
        mounted () {
            this.pagePath = this.$route.path;
            this.updateTitle()
        },
        beforeUpdate () {
            this.updateTitle()
        },
        activated() {
            this.updateTitle()
        },
        watch: {
            title() {
                this.pagePath = this.$route.path;
                this.updateTitle()
            }
        },
        methods: {
            updateTitle () {
                let pageTitle;
                if (this.title) {
                    pageTitle = this.title;
                } else {
                    let slots = this.$slots.default;
                    if (typeof slots === 'undefined' || slots.length < 1 || typeof slots[0].text !== 'string') {
                        return;
                    }
                    let {text} = slots[0];
                    pageTitle = text;
                }
                let {title} = document;
                if (pageTitle !== title && this.pagePath === this.$route.path) this.setTile(pageTitle);
            },
            setTile(title) {
                document.title = title;
                let mobile = navigator.userAgent.toLowerCase();
                if (/iphone|ipad|ipod/.test(mobile)) {
                    let iframe = document.createElement('iframe');
                    iframe.style.display = 'none';
                    let iframeCallback = function () {
                        setTimeout(function () {
                            iframe.removeEventListener('load', iframeCallback);
                            document.body.removeChild(iframe)
                        }, 0)
                    };
                    iframe.addEventListener('load', iframeCallback);
                    document.body.appendChild(iframe)
                }
            }
        }
    }
</script>
