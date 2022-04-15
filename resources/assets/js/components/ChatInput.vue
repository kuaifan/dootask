<template>
    <div class="chat-input-wrapper">
        <div ref="editor"></div>
    </div>
</template>

<script>
import Quill from 'quill';
import "quill-mention";

export default {
    name: 'ChatInput',
    props: {
        dialogId: {
            type: Number,
            default: 0
        },
        taskId: {
            type: Number,
            default: 0
        },
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
            default: () => ({})
        },
        maxlength: {
            type: Number
        },
        defaultMenuOrientation: {
            type: String,
            default: "top"
        },
    },
    data() {
        return {
            quill: null,
            _content: '',
            _options: {},

            userList: null,
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
        },

        // Reset userList
        dialogId() {
            this.userList = null;
        },
        taskId() {
            this.userList = null;
        },
    },
    methods: {
        init() {
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
                        defaultMenuOrientation: this.defaultMenuOrientation,
                        renderItem: (data) => {
                            if (data.disabled === true) {
                                return `<div class="mention-item-disabled">${data.value}</div>`;
                            }
                            if (data.id === 0) {
                                return `<div class="mention-item-at">@</div><div class="mention-item-name">${data.value}</div><div class="mention-item-tip">${this.$L('提示所有成员')}</div>`;
                            }
                            if (data.avatar) {
                                return `<div class="mention-item-img${data.online ? ' online' : ''}"><img src="${data.avatar}"/><em></em></div><div class="mention-item-name">${data.value}</div>`;
                            }
                            return `<div class="mention-item-name">${data.value}</div>`;
                        },
                        renderLoading: () => {
                            return "Loading...";
                        },
                        source: (searchTerm, renderList, mentionChar) => {
                            this.getSource(mentionChar).then(values => {
                                if (searchTerm.length === 0) {
                                    renderList(values, searchTerm);
                                } else {
                                    const matches = [];
                                    for (let i = 0; i < values.length; i++) {
                                        if (~values[i].value.toLowerCase().indexOf(searchTerm.toLowerCase())) {
                                            matches.push(values[i]);
                                        }
                                    }
                                    renderList(matches, searchTerm);
                                }
                            })
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
        },

        getSource(mentionChar) {
            return new Promise(resolve => {
                switch (mentionChar) {
                    case "@": // @成员
                        if (this.userList !== null) {
                            resolve(this.userList)
                            return;
                        }
                        if (this.dialogId > 0) {
                            // 根据会话ID获取成员
                            this.$store.dispatch("call", {
                                url: 'dialog/group/user',
                                data: {
                                    dialog_id: this.dialogId,
                                    getuser: 1
                                }
                            }).then(({data}) => {
                                if (data.length > 0) {
                                    this.userList = [
                                        { id: 0, value: this.$L('所有人') },
                                        { id: 0, value: this.$L('会话内成员'), disabled: true },
                                    ];
                                    this.userList.push(...data.map(item => {
                                        return {
                                            id: item.userid,
                                            value: item.nickname,
                                            avatar: item.userimg,
                                            online: item.online,
                                        }
                                    }))
                                } else {
                                    this.userList = [];
                                }
                                resolve(this.userList)
                            }).catch(_ => {
                                resolve([]);
                            });
                            return;
                        } else if (this.taskId > 0) {
                            // 根据任务ID获取成员 todo
                            return;
                        }
                        break;

                    case "#": // #任务 todo
                        resolve([
                            { id: 3, value: "Fredrik Sundqvist 2" },
                            { id: 4, value: "Patrik Sjölin 2" }
                        ])
                        break;
                }
                resolve([])
            })
        }
    }
}
</script>
