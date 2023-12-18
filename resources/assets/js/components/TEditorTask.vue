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
                    <DropdownItem @click.native="onEditing">{{ $L('编辑描述') }}</DropdownItem>
                    <DropdownItem v-if="operateLink" @click.native="onLinkPreview">{{ $L('打开链接') }}</DropdownItem>
                    <DropdownItem v-if="operateImg" @click.native="onImagePreview">{{ $L('查看图片') }}</DropdownItem>
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
                'advlist autolink lists link image charmap print preview hr anchor pagebreak',
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
                contextmenu: 'bold italic underline forecolor backcolor | link | codesample | uploadImages imagePreview | preview screenload',
                valid_elements: 'a[href|title|target=_blank],em,strong/b,div[align],span[style],a,br,p,img[src|alt|witdh|height],pre[class],code',
                extended_valid_elements: 'a[href|title|target=_blank]',
                toolbar: false
            },
            optionFull: {
                menubar: 'file edit view',
                valid_elements: 'a[href|title|target=_blank],em,strong/b,div[align],span[style],a,br,p,img[src|alt|witdh|height],pre[class],code',
                extended_valid_elements: 'a[href|title|target=_blank]',
                toolbar: 'uploadImages | bold italic underline | forecolor backcolor'
            },

            operateStyles: {},
            operateVisible: false,
            operateLink: null,
            operateImg: null,

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
        }
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

        onBlur() {
            this.$emit('on-blur');
        },

        onEditorInit(editor) {
            this.updateTouchContent();
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
            event.stopPropagation()
            this.operateVisible = false;
            this.operateLink = event.target.tagName === "A" ? event.target.href : null;
            this.operateImg = event.target.tagName === "IMG" ? event.target.src : null;
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

        onLinkPreview() {
            if (this.operateLink) {
                window.open(this.operateLink);
            }
        },

        onImagePreview() {
            const array = this.$refs.desc.getValueImages();
            if (array.length === 0) {
                $A.messageWarning("没有可预览的图片")
                return;
            }
            let index = Math.max(0, array.findIndex(item => item.src === this.operateImg));
            this.$store.dispatch("previewImage", {index, list: array})
        },
    }
}
</script>
