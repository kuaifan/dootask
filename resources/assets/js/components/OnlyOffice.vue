<template>
    <div class="component-only-office">
        <div :id="this.id" class="placeholder"></div>
        <div v-if="loadIng > 0" class="office-loading"><Loading/></div>
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
    .office-loading {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
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
        code: {
            type: String,
            default: ''
        },
        value: {
            type: [Object, Array],
            default: function () {
                return {}
            }
        },
        readOnly: {
            type: Boolean,
            default: false
        },
        documentKey: Function
    },

    data() {
        return {
            loadIng: 0,

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
        ...mapState(['userToken', 'userInfo', 'themeIsDark']),

        fileType() {
            return this.getType(this.value.type);
        },

        fileName() {
            return this.value.name;
        },
    },

    watch: {
        'value.id': {
            handler(id)  {
                if (!id) {
                    return;
                }
                this.loadIng++;
                $A.loadScript($A.apiUrl("../office/web-apps/apps/api/documents/api.js"), (e) => {
                    this.loadIng--;
                    if (e !== null) {
                        $A.modalAlert("组件加载失败！");
                        return;
                    }
                    if (!this.documentKey) {
                        this.handleClose();
                        return
                    }
                    const documentKey = this.documentKey();
                    if (documentKey && documentKey.then) {
                        documentKey.then(this.loadFile);
                    } else {
                        this.loadFile();
                    }
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
            return type;
        },

        loadFile(keyAppend = '') {
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
            let fileKey = this.code || this.value.id;
            let fileName = $A.strExists(this.fileName, '.') ? this.fileName : (this.fileName + '.' + this.fileType);
            const config = {
                "document": {
                    "fileType": this.fileType,
                    "key": `${this.fileType}-${fileKey}-${keyAppend}`,
                    "title": fileName,
                    "url": `http://nginx/api/file/content/?id=${fileKey}&token=${this.userToken}`,
                },
                "editorConfig": {
                    "mode": "edit",
                    "lang": lang,
                    "user": {
                        "id": this.userInfo.userid,
                        "name": this.userInfo.nickname
                    },
                    "customization": {
                        "uiTheme": this.themeIsDark ? "theme-dark" : "theme-classic-light",
                    },
                    "callbackUrl": `http://nginx/api/file/content/office?id=${fileKey}&token=${this.userToken}`,
                }
            };
            if (/\/hideenOfficeTitle\//.test(window.navigator.userAgent)) {
                config.document.title = " ";
            }
            if ($A.leftExists(fileKey, "msgFile_")) {
                config.document.url = `http://nginx/api/dialog/msg/download/?msg_id=${$A.leftDelete(fileKey, "msgFile_")}&token=${this.userToken}`;
            } else if ($A.leftExists(fileKey, "taskFile_")) {
                config.document.url = `http://nginx/api/project/task/filedown/?file_id=${$A.leftDelete(fileKey, "taskFile_")}&token=${this.userToken}`;
            }
            if (this.readOnly) {
                config.editorConfig.mode = "view";
                config.editorConfig.callbackUrl = null;
                if (!config.editorConfig.user.id) {
                    let viewer = $A.getStorageInt("viewer")
                    if (!viewer) {
                        viewer = $A.randNum(1000, 99999);
                        $A.setStorage("viewer", viewer)
                    }
                    config.editorConfig.user.id = "viewer_" + viewer;
                    config.editorConfig.user.name = "Viewer_" + viewer
                }
            }
            this.$nextTick(() => {
                this.docEditor = new DocsAPI.DocEditor(this.id, config);
            })
        }
    }
}
</script>
