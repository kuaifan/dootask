<template>
    <div class="drawio-content">
        <iframe ref="myFlow" class="drawio-iframe" :src="url"></iframe>
        <div v-if="loadIng" class="drawio-loading"><Loading/></div>
    </div>
</template>

<style lang="scss" scoped>
.drawio-content {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    .drawio-iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: 0 0;
        border: 0;
        float: none;
        margin: -1px 0 0;
        max-width: none;
        outline: 0;
        padding: 0;
    }
    .drawio-loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
}
</style>
<script>
import {mapState} from "vuex";

export default {
    name: "Drawio",
    props: {
        value: {
            type: Object,
            default: function () {
                return {}
            }
        },
        title: {
            type: String,
            default: ''
        },
        readOnly: {
            type: Boolean,
            default: false
        },
    },
    data() {
        return {
            loadIng: true,

            url: null,
            bakData: '',
        }
    },
    created() {
        let language = 'en';
        switch (this.getLanguage()) {
            case 'CN':
            case 'TC':
                language = 'zh'
                break;
        }
        let lightbox = this.readOnly ? 1 : 0;
        let chrome = this.readOnly ? 0 : 1;
        let theme = this.themeIsDark ? 'dark' : 'kennedy';
        let title = this.title ? encodeURIComponent(this.title) : '';
        let query = `?title=${title}&chrome=${chrome}&lightbox=${lightbox}&ui=${theme}&lang=${language}&offline=1&pwa=0&embed=1&noLangIcon=1&noExitBtn=1&noSaveBtn=1&saveAndExit=0&spin=1&proto=json`;
        if (this.$Electron) {
            this.url = $A.originUrl(`drawio/webapp/index.html${query}`);
        } else {
            this.url = $A.apiUrl(`../drawio/webapp/${query}`);
        }
    },
    mounted() {
        window.addEventListener('message', this.handleMessage)
    },
    beforeDestroy() {
        window.removeEventListener('message', this.handleMessage)
    },
    watch: {
        value: {
            handler(val) {
                if (this.bakData == $A.jsonStringify(val)) {
                    return;
                }
                this.bakData = $A.jsonStringify(val);
                this.updateContent();
            },
            deep: true
        },
    },
    computed: {
        ...mapState(['themeIsDark'])
    },
    methods: {
        formatZoom(val) {
            return val + '%';
        },

        updateContent() {
            this.$refs.myFlow.contentWindow.postMessage(JSON.stringify({
                action: "load",
                autosave: 1,
                xml: this.value.xml,
            }), "*");
        },

        handleMessage(event) {
            const editWindow = this.$refs.myFlow.contentWindow;
            if (event.source !== editWindow) {
                return;
            }
            const payload = $A.jsonParse(event.data);
            switch (payload.event) {
                case "init":
                    this.loadIng = false;
                    this.updateContent();
                    break;

                case "load":
                    if (typeof this.value.xml === "undefined") {
                        editWindow.postMessage(JSON.stringify({
                            action: "template"
                        }), "*");
                    }
                    break;

                case "autosave":
                    const content = {
                        xml: payload.xml,
                    }
                    this.bakData = $A.jsonStringify(content);
                    this.$emit('input', content);
                    break;

                case "save":
                    this.$emit('saveData');
                    break;
            }
        }
    },
}
</script>
