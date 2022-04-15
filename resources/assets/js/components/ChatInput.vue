<template>
    <div class="chat-input-wrapper">
        <div ref="editor"></div>
    </div>
</template>

<style lang="scss">
.chat-input-wrapper {
    display: inline-block;
    width: 100%;
    .ql-editor {
        padding: 4px 7px;
        font-size: 14px;
        max-height: 100px;
        &.ql-blank {
            &::before {
                left: 7px;
                right: 7px;
                color: #ccc;
                font-style: normal;
            }
        }
    }
}
</style>

<script>
import Quill from 'quill';
import "quill/dist/quill.snow.css";

import "quill-mention";
import "quill-mention/dist/quill.mention.min.css";

export default {
    name: 'ChatInput',
    props: {
        value: {
            type: [String, Number],
            default: ''
        },
        placeholder: {
            type: String,
            default: ''
        },
        disabled: {
            type: Boolean,
            default: false
        },
        enterSend: {
            type: Boolean,
            default: true
        },
        options: {
            type: Object,
            required: false,
            default: () => ({})
        },
        maxlength: {
            type: Number
        },
    },
    data() {
        return {
            quill: null,
            _content: '',
            _options: {},
        };
    },
    mounted() {
        this.init();
    },
    beforeDestroy() {
        this.quill = null
        delete this.quill
    },
    watch: {
        // Watch content change
        value(newVal) {
            if (this.quill) {
                if (newVal && newVal !== this._content) {
                    this._content = newVal
                    this.quill.pasteHTML(newVal)
                } else if(!newVal) {
                    this.quill.setText('')
                }
            }
        },
        // Watch disabled change
        disabled(newVal) {
            if (this.quill) {
                this.quill.enable(!newVal)
            }
        }
    },
    methods: {
        init() {
            const atValues = [
                { id: 1, value: "Fredrik Sundqvist" },
                { id: 2, value: "Patrik Sjölin" }
            ];
            const hashValues = [
                { id: 3, value: "Fredrik Sundqvist 2" },
                { id: 4, value: "Patrik Sjölin 2" }
            ];

            // Options
            this._options = Object.assign({
                theme: null,
                readOnly: false,
                placeholder: this.placeholder,
                modules: {
                    keyboard: {
                        bindings: {
                            'short enter': {
                                key: 13,
                                shortKey: true,
                                handler: _ => {
                                    if (!this.enterSend) {
                                        this.$emit('on-send', this.quill)
                                        return false;
                                    }
                                    return true;
                                }
                            },
                            'enter': {
                                key: 13,
                                shiftKey: false,
                                handler: _ => {
                                    if (this.enterSend) {
                                        this.$emit('on-send', this.quill)
                                        return false;
                                    }
                                    return true;
                                }
                            }
                        }
                    },
                    mention: {
                        mentionDenotationChars: ["@", "#"],
                        source: function(searchTerm, renderList, mentionChar) {
                            let values;

                            if (mentionChar === "@") {
                                values = atValues;
                            } else {
                                values = hashValues;
                            }

                            if (searchTerm.length === 0) {
                                renderList(values, searchTerm);
                            } else {
                                const matches = [];
                                for (let i = 0; i < values.length; i++)
                                    if (
                                        ~values[i].value.toLowerCase().indexOf(searchTerm.toLowerCase())
                                    )
                                        matches.push(values[i]);
                                renderList(matches, searchTerm);
                            }
                        }
                    }
                }
            }, this.options)

            // Instance
            this.quill = new Quill(this.$refs.editor, this._options)
            this.quill.enable(false)

            // Set editor content
            if (this.value) {
                this.quill.pasteHTML(this.value)
            }

            // Disabled editor
            if (!this.disabled) {
                this.quill.enable(true)
            }

            // Mark model as touched if editor lost focus
            this.quill.on('selection-change', range => {
                if (!range) {
                    this.$emit('on-blur', this.quill)
                } else {
                    this.$emit('on-focus', this.quill)
                }
            })

            // Update model if text changes
            this.quill.on('text-change', _ => {
                if (this.maxlength > 0 && this.quill.getLength() > this.maxlength) {
                    this.quill.deleteText(this.maxlength, this.quill.getLength());
                }
                let html = this.$refs.editor.children[0].innerHTML
                const quill = this.quill
                const text = this.quill.getText()
                if (/^(\<p\>\<br\>\<\/p\>)+$/.test(html)) html = ''
                this._content = html
                this.$emit('input', this._content)
                this.$emit('on-change', { html, text, quill })
            })

            // Emit ready event
            this.$emit('on-ready', this.quill)
        },

        focus() {
            this.$nextTick(() => {
                this.quill && this.quill.focus()
            })
        },

        blur() {
            this.$nextTick(() => {
                this.quill && this.quill.blur()
            })
        }
    }
}
</script>
