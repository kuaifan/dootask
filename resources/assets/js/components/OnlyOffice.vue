<template>
    <div class="component-only-office">
        <IFrame v-if="isPreviewAndMobile" class="preview-iframe" :src="mobilePreviewUrl" @on-message="onMessage"/>
        <template v-else>
            <Alert v-if="loadError" class="load-error" type="error" show-icon>{{$L('组件加载失败！')}}</Alert>
            <div :id="id" class="placeholder"></div>
        </template>
        <div v-if="loading" class="office-loading"><Loading/></div>
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
<style lang="scss">
.component-only-office {
    .load-error {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1;
        padding: 8px;
        display: flex;
        align-items: center;

        .ivu-alert-icon {
            position: static;
            margin-right: 8px;
            margin-left: 4px;
        }
    }
}
</style>
<script>

import {mapState} from "vuex";
import IFrame from "../pages/manage/components/IFrame";

export default {
    name: "OnlyOffice",
    components: {IFrame},
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
        historyId: {
            type: Number,
            default: 0
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
            loading: false,
            loadError: false,

            docEditor: null,
        }
    },

    mounted() {
        if (this.isPreviewAndMobile) {
            this.loading = true;
        }
    },


    beforeDestroy() {
        if (this.docEditor !== null) {
            this.docEditor.destroyEditor();
            this.docEditor = null;
        }
    },

    computed: {
        ...mapState(['userInfo', 'themeIsDark']),

        fileType() {
            return this.getType(this.value.type);
        },

        fileName() {
            return this.value.name;
        },

        fileUrl() {
            const codeId = this.code || this.value.id;
            let fileUrl = `file/content/?id=${codeId}&token=${this.userToken}`;
            if (this.historyId > 0) {
                fileUrl += `&history_id=${this.historyId}`
            }
            return fileUrl;
        },

        isPreviewAndMobile() {
            return (this.readOnly || this.historyId > 0) && this.windowSmall
        },

        mobilePreviewUrl() {
            return $A.apiUrl(this.fileUrl) + "&down=preview"
        }
    },

    watch: {
        'value.id': {
            handler(id)  {
                if (!id) {
                    return;
                }
                if (this.isPreviewAndMobile) {
                    return;
                }
                this.loading = true;
                this.loadError = false;
                $A.loadScript($A.apiUrl("../office/web-apps/apps/api/documents/api.js"), (e) => {
                    this.loading = false;
                    if (e !== null) {
                        this.loadError = true;
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
        onMessage(data) {
            switch (data.action) {
                case 'ready':
                    this.loading = false;
                    break
            }
        },

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
            let codeId = this.code || this.value.id;
            let fileName = $A.strExists(this.fileName, '.') ? this.fileName : (this.fileName + '.' + this.fileType);
            let fileKey = `${this.fileType}-${keyAppend||codeId}`;
            if (this.historyId > 0) {
                fileKey += `-${this.historyId}`
            }
            const config = {
                "document": {
                    "fileType": this.fileType,
                    "title": fileName,
                    "key": fileKey,
                    "url": `http://nginx/api/${this.fileUrl}`,
                },
                "editorConfig": {
                    "mode": "edit",
                    "lang": lang,
                    "user": {
                        "id": String(this.userInfo.userid),
                        "name": this.userInfo.nickname
                    },
                    "customization": {
                        "uiTheme": this.themeIsDark ? "theme-dark" : "theme-classic-light",
                        "forcesave": true,
                        "help": false,
                    },
                    "callbackUrl": `http://nginx/api/file/content/office?id=${codeId}&token=${this.userToken}`,
                },
                "events": {
                    "onDocumentReady": this.onDocumentReady,
                },
            };
            if (/\/hideenOfficeTitle\//.test(window.navigator.userAgent)) {
                config.document.title = " ";
            }
            if ($A.leftExists(codeId, "msgFile_")) {
                config.document.url = `http://nginx/api/dialog/msg/download/?msg_id=${$A.leftDelete(codeId, "msgFile_")}&token=${this.userToken}`;
            } else if ($A.leftExists(codeId, "taskFile_")) {
                config.document.url = `http://nginx/api/project/task/filedown/?file_id=${$A.leftDelete(codeId, "taskFile_")}&token=${this.userToken}`;
            }
            if (this.readOnly || this.historyId > 0) {
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
        },

        onDocumentReady() {
            this.$emit("on-document-ready", this.docEditor)
        }
    }
}
</script>
