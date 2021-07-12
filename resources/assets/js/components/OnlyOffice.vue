<template>
    <div class="component-only-office">
        <div :id="this.id" class="placeholder"></div>
    </div>
</template>

<style lang="scss" scoped>
.component-only-office {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    .placeholder {
        flex: 1;
        width: 100%;
        height: 100%;
    }
}
</style>
<script>

import {mapState} from "vuex";

export default {
    name: "OnlyOffice",
    props: {
        id: {
            type: String,
            default: () => {
                return "office_" + Math.round(Math.random() * 10000);
            }
        },
        value: {
            type: [Object, Array],
            default: function () {
                return {}
            }
        },
    },

    data() {
        return {
            serverUrl: 'http://10.22.22.3/',

            fileName: null,
            fileType: null,
            fileUrl: null,

            docEditor: null,
        }
    },

    mounted() {
        //
    },

    beforeDestroy() {
        if (this.docEditor !== null) {
            this.docEditor.destroyEditor();
            this.docEditor = null;
        }
    },

    computed: {
        ...mapState(['userToken', 'userInfo']),
    },

    watch: {
        value: {
            handler(val) {
                this.fileUrl = this.serverUrl + 'api/file/content/?id=' + val.id + '&token=' + this.userToken;
                this.fileType = this.getType(val.type);
                this.fileName = val.name;
            },
            immediate: true,
            deep: true,
        },

        fileUrl: {
            handler(url)  {
                if (!url) {
                    return;
                }
                const uri = new URL(this.$store.state.method.apiUrl('web-apps'));
                $A.loadScript(`http://${uri.hostname}:2224/web-apps/apps/api/documents/api.js`, () => {
                    this.loadFile()
                })
            },
            immediate: true,
        }
    },

    methods: {
        getType(type) {
            switch (type) {
                case 'word':
                    return 'docx';
                case 'excel':
                    return 'xlsx';
                case 'ppt':
                    return 'pptx'
            }
            return '';
        },

        loadFile() {
            if (!this.fileUrl) {
                return;
            }
            if (this.docEditor !== null) {
                this.docEditor.destroyEditor();
                this.docEditor = null;
            }
            //
            let lang = "zh";
            switch (this.getLanguage()) {
                case 'CN':
                case 'TC':
                    lang = "zh";
                    break;
                default:
                    lang = 'en';
                    break;
            }
            //
            const config = {
                "document": {
                    "fileType": this.fileType,
                    "key": this.fileType + '-' + this.value.id,
                    "title": this.fileName + '.' + this.fileType,
                    "url": this.fileUrl,
                },
                "editorConfig": {
                    "mode": "edit",
                    "lang": lang,
                    "user": {
                        "id": this.userInfo.userid,
                        "name": this.userInfo.nickname
                    },
                    "callbackUrl": this.serverUrl + 'api/file/content/office?id=' + this.value.id + '&token=' + this.userToken,
                }
            };
            this.$nextTick(() => {
                this.docEditor = new DocsAPI.DocEditor(this.id, config);
            })
        }
    }
}
</script>
