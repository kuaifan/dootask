<template>
    <div v-if="false"></div>
</template>

<script>
import {mapState} from "vuex";

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


    computed: {
        ...mapState([
            'userId',
            'dialogs',
        ]),


        msgAllUnread() {
            let num = 0;
            this.dialogs.map(({unread}) => {
                num += unread;
            })
            return num;
        },
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
            if (this.userId && this.msgAllUnread > 0) {
                title+= " (" + this.msgAllUnread + ")"
            }
            document.title = title;
        }
    }
}
</script>
