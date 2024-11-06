<template>
    <div class="task-editor" @click="onClickWrap" @touchstart="onTouchstart">
        <TEditor
            ref="desc"
            v-model="content"
            :plugins="plugins"
            :options="options"
            :option-full="optionFull"
            :placeholder="placeholder"
            :placeholderFull="placeholderFull"
            :readOnly="windowTouch"
            :readOnlyFull="false"
            :readOnlyImagePreview="false"
            @on-blur="onBlur"
            @on-editor-init="onEditorInit"
            @on-transfer-change="onTransferChange"
            inline/>
        <div class="task-editor-operate" :style="operateStyles" v-show="operateVisible">
            <Dropdown
                trigger="custom"
                :visible="operateVisible"
                @on-clickoutside="operateVisible = false"
                transfer>
                <div :style="{userSelect:operateVisible ? 'none' : 'auto', height: operateStyles.height}"></div>
                <DropdownMenu slot="list">
                    <DropdownItem v-if="operateMenu.checked" @click.native="onLiPreview">{{ $L(operateMenu.checked === 'checked' ? '标记未选' : '标记已选') }}</DropdownItem>
                    <DropdownItem v-if="operateMenu.link" @click.native="onLinkPreview">{{ $L('打开链接') }}</DropdownItem>
                    <DropdownItem v-if="operateMenu.img" @click.native="onImagePreview">{{ $L('查看图片') }}</DropdownItem>
                    <DropdownItem @click.native="onEditing">{{ $L('编辑描述') }}</DropdownItem>
                    <DropdownItem v-if="operateMenu.history" @click.native="onHistory">{{ $L('历史记录') }}</DropdownItem>
                </DropdownMenu>
            </Dropdown>
        </div>
    </div>
</template>

<style lang="scss" scoped>
.task-editor {
    position: relative;
    word-break: break-all;
    .task-editor-operate {
        position: absolute;
        top: 0;
        left: 0;
        width: 1px;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }
}
</style>
<script>
import TEditor from "./TEditor.vue";

export default {
    name: 'TEditorTask',
    components: {TEditor},
    props: {
        value: {
            default: ''
        },
        placeholder: {
            default: ''
        },
        placeholderFull: {
            default: ''
        },
    },

    data() {
        return {
            content: this.value,

            plugins: [
                'advlist autolink lists checklist link image charmap print preview hr anchor pagebreak',
                'searchreplace visualblocks visualchars code',
                'insertdatetime media nonbreaking save table directionality',
                'emoticons paste codesample',
                'autoresize'
            ],
            options: {
                statusbar: false,
                menubar: false,
                autoresize_bottom_margin: 2,
                min_height: 200,
                max_height: 380,
                contextmenu: 'checklist | bold italic underline forecolor backcolor | link | uploadImages imagePreview | history screenload',
                valid_elements: 'a[href|title|target=_blank],em,strong/b,div[align],span[style],a,br,p,img[src|alt|witdh|height],pre[class],code,ol[class],ul[class],li[class]',
                extended_valid_elements: 'a[href|title|target=_blank]',
                toolbar: false
            },
            optionFull: {
                menubar: 'file edit view',
                removed_menuitems: 'preview,print',
                contextmenu: 'checklist | bold italic underline forecolor backcolor | link | uploadImages imagePreview | screenload',
                valid_elements: 'a[href|title|target=_blank],em,strong/b,div[align],span[style],a,br,p,img[src|alt|witdh|height],pre[class],code,ol[class],ul[class],li[class]',
                extended_valid_elements: 'a[href|title|target=_blank]',
                toolbar: 'uploadImages | checklist | bold italic underline | forecolor backcolor',
                mobile: {
                    menubar: 'file edit view',
                },
            },

            operateStyles: {},
            operateVisible: false,
            operateHiddenTime: 0,
            operateMenu: {
                target: null,
                checked: null,
                link: null,
                img: null,
                history: true
            },

            listener: null,
        };
    },

    mounted() {
        let parent = this.$parent.$el.parentNode;
        while (parent) {
            if (parent.classList?.contains(".ivu-modal-wrap")) {
                this.listener = parent;
                parent.addEventListener("scroll", this.onTouchstart);
                break;
            }
            parent = parent.parentNode;
        }
        this.operateMenu.history = typeof this.$listeners['on-history'] === "function";
    },

    beforeDestroy() {
        this.listener?.removeEventListener("scroll", this.onTouchstart);
    },

    computed: {
        editor() {
            return this.$refs.desc.editor;
        },
    },

    watch: {
        value(val) {
            this.content = val;
        },
        content(val) {
            this.$emit('input', val);
        },
        operateVisible(val) {
            if (!val) {
                this.operateHiddenTime = Date.now();
            }
        },
    },

    methods: {
        getContent() {
            return this.$refs.desc.getContent();
        },

        updateContent(html) {
            this.content = html
        },

        onEditing() {
            this.$refs.desc.onFull()
        },

        onHistory() {
            this.$emit('on-history');
        },

        onBlur() {
            this.$emit('on-blur');
        },

        onEditorInit(editor) {
            this.updateTouchContent();
            this.updateHistoryContent(editor);
            this.$emit('on-editor-init', editor);
        },

        onTransferChange(visible) {
            if (visible) {
                return
            }
            if (!this.windowTouch) {
                return
            }
            setTimeout(_ => {
                this.updateTouchContent();
                this.onBlur();
            }, 100);
        },

        onClickWrap(event) {
            if (!this.windowTouch) {
                return
            }
            if (Date.now() - this.operateHiddenTime < 300) {
                return;
            }
            event.stopPropagation()
            this.operateVisible = false;
            this.operateMenu.target = event.target;
            this.operateMenu.checked = null;
            if (event.target.tagName === "LI" && event.target.parentNode.classList.contains("tox-checklist")) {
                this.operateMenu.checked = event.target.classList.contains("tox-checklist--checked") ? 'checked' : 'unchecked';
            }
            this.operateMenu.link = event.target.tagName === "A" ? event.target.href : null;
            this.operateMenu.img = event.target.tagName === "IMG" ? event.target.src : null;
            this.$nextTick(() => {
                const rect = this.$el.getBoundingClientRect();
                this.operateStyles = {
                    left: `${event.clientX - rect.left}px`,
                    top: `${event.clientY - rect.top}px`,
                }
                this.operateVisible = true;
            })
        },

        onTouchstart() {
            if (!this.windowTouch) {
                return
            }
            this.operateVisible = false;
        },

        updateTouchContent() {
            if (!this.windowTouch) {
                return
            }
            this.$nextTick(_ => {
                if (!this.editor) {
                    return;
                }
                if (this.content) {
                    this.editor.bodyElement.removeAttribute("data-mce-placeholder");
                    this.editor.bodyElement.removeAttribute("aria-placeholder");
                } else {
                    this.editor.bodyElement.setAttribute("data-mce-placeholder", this.placeholder);
                    this.editor.bodyElement.setAttribute("aria-placeholder", this.placeholder);
                }
                this.updateTouchLink(0);
            })
        },

        updateTouchLink(timeout) {
            if (!this.windowTouch) {
                return
            }
            setTimeout(_ => {
                if (!this.editor) {
                    return;
                }
                this.editor.bodyElement.querySelectorAll("a").forEach(item => {
                    if (item.__dataMceClick !== true) {
                        item.__dataMceClick = true;
                        item.addEventListener("click", event => {
                            event.preventDefault();
                            event.stopPropagation();
                            this.onClickWrap(event);
                        })
                    }
                })
                if (timeout < 300) {
                    this.updateTouchLink(timeout + 100);
                }
            }, timeout)
        },

        updateHistoryContent(editor) {
            editor.ui.registry.addMenuItem('history', {
                icon: 'insert-time',
                text: this.$L('历史记录'),
                onAction: () => {
                    this.onHistory();
                }
            });
        },

        onLiPreview() {
            if (!this.operateMenu.checked) {
                return;
            }
            if (this.operateMenu.checked === 'checked') {
                this.operateMenu.target.classList.remove("tox-checklist--checked");
            } else {
                this.operateMenu.target.classList.add("tox-checklist--checked");
            }
            this.$emit('on-blur', 'force');
        },

        onLinkPreview() {
            if (this.operateMenu.link) {
                window.open(this.operateMenu.link);
            }
        },

        onImagePreview() {
            const array = this.$refs.desc.getValueImages();
            if (array.length === 0) {
                $A.messageWarning("没有可预览的图片")
                return;
            }
            this.$store.dispatch("previewImage", {index: this.operateMenu.img, list: array})
        },
    }
}
</script>
