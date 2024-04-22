<template>
    <div class="drawio-content">
        <IFrame ref="frame" class="drawio-iframe" :src="url" @on-message="onMessage"/>
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
import IFrame from "../pages/manage/components/IFrame";
import {languageName} from "../language";

export default {
    name: "Drawio",
    components: {IFrame},
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
        let lang = languageName;
        switch (languageName) {
            case 'zh-CHT':
                lang = 'zh-tw'
                break;
        }
        let lightbox = this.readOnly ? 1 : 0;
        let chrome = this.readOnly ? 0 : 1;
        let theme = this.themeName === 'dark' ? 'dark' : 'kennedy';
        let title = this.title ? encodeURIComponent(this.title) : '';
        let query = `?title=${title}&chrome=${chrome}&lightbox=${lightbox}&ui=${theme}&lang=${lang}&offline=1&pwa=0&embed=1&noLangIcon=1&noExitBtn=1&noSaveBtn=1&saveAndExit=0&spin=1&proto=json`;
        if (this.$Electron) {
            this.url = $A.originUrl(`drawio/webapp/index.html${query}`);
        } else {
            this.url = $A.mainUrl(`drawio/webapp/${query}`);
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
        ...mapState(['themeName'])
    },
    methods: {
        formatZoom(val) {
            return val + '%';
        },

        updateContent() {
            this.$refs.frame.postMessage(JSON.stringify({
                action: "load",
                autosave: 1,
                xml: this.value.xml,
            }));
        },

        onMessage(data) {
            switch (data.event) {
                case "init":
                    this.loadIng = false;
                    this.updateContent();
                    break;

                case "load":
                    if (typeof this.value.xml === "undefined") {
                        this.$refs.frame.postMessage(JSON.stringify({
                            action: "template"
                        }));
                    }
                    break;

                case "autosave":
                    const content = {
                        xml: data.xml,
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
