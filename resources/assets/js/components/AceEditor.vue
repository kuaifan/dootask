<script>
export default {
    name: 'AceEditor',
    model: {
        prop: 'code',
        event: 'change'
    },
    props: {
        code: {
            type: String,
            default: ''
        },
        options: {
            type: Object,
            default: () => ({})
        },
        theme: {
            type: String,
            default: ''
        },
        language: {
            type: String,
            default: 'json'
        },
        height: {
            type: Number || null,
            default: null
        },
        width: {
            type: Number || null,
            default: null
        }
    },
    render(createElement) {
        return createElement('div')
    },
    data: () => ({
        editor: null,
        cursorPosition: {
            row: 0,
            column: 0
        }
    }),
    mounted() {
        $A.loadScriptS([
            'js/ace/ace.js',
            'js/ace/mode-json.js',
        ], () => {
            // set init editor size
            this.setSize(this.$el, {height: this.height, width: this.width})

            // init ace editor
            this.editor = window.ace.edit(this.$el)
            this.editor.session.setMode(`ace/mode/${this.language || 'json'}`)

            // emit 'mounted' event
            this.$emit('mounted', this.editor)

            // official syntax validation workers include 'coffee', 'css', 'html'
            // 'javascript', 'json', 'lua', 'php', 'xml' and 'xquery'
            if (this.editor.session.$worker) {
                this.editor.session.$worker.addEventListener('annotate', this.workerMessage, false)
            }

            // set value and clear selection
            this.editor.setValue(this.code)
            this.editor.clearSelection()

            // set ace editor options and theme
            this.editor.setOptions(this.options)
            this.theme && this.editor.setTheme(`ace/theme/${this.theme}`)
        });
    },
    methods: {
        /**
         * listening lint events from worker
         * @param data
         */
        workerMessage({data}) {
            // record current cursor position
            this.cursorPosition = this.editor.selection.getCursor()
            const [validationInfo] = data
            if (validationInfo && validationInfo.type === 'error') {
                this.$emit('validationFailed', validationInfo)
            } else {
                this.$emit('change', this.editor.getValue())
            }
        },
        /**
         * set editor size
         * @param dom
         * @param width
         * @param height
         */
        setSize(dom, {width = this.width, height = this.height}) {
            dom.style.width = width && typeof width === 'number' ? `${width}px` : '100%'
            dom.style.height = height && typeof height === 'number' ? `${height}px` : '100%'
            this.$nextTick(() => this.editor && this.editor.resize())
        }
    },
    watch: {
        /**
         * watching and set options
         * @param newOptions ace editor options
         */
        options(newOptions) {
            if (newOptions && typeof newOptions === 'object') {
                this.editor && this.editor.setOptions(newOptions)
            }
        },
        /**
         * watching and set theme
         * @param newTheme
         */
        theme(newTheme) {
            if (newTheme && typeof newTheme === 'string') {
                this.editor && this.editor.setTheme(`ace/theme/${this.theme}`)
            }
        },
        /**
         * watching and set language
         * @param newLanguage
         */
        language(newLanguage) {
            if (newLanguage && typeof newLanguage === 'string') {
                this.editor && this.editor.session.setMode(`ace/mode/${newLanguage}`)
            }
        },
        /**
         * watching and set width
         * @param newWidth
         */
        width(newWidth) {
            this.setSize(this.el, {width: newWidth})
        },
        /**
         * watching and set height
         * @param newHeight
         */
        height(newHeight) {
            this.setSize(this.el, {height: newHeight})
        },
        /**
         * watching and set code
         * @param newCode
         */
        code(newCode) {
            if (!this.editor) {
                return
            }
            this.editor.setValue(newCode)
            this.editor.clearSelection()
            const {row, column} = this.cursorPosition
            // move cursor to current position
            this.editor.selection.moveCursorTo(row, column)
        }
    },
    beforeDestroy() {
        if (this.editor) {
            if (this.editor.session.$worker) {
                this.editor.session.$worker.removeEventListener('message', this.workerMessage, false)
            }
            this.editor.destroy()
            this.editor.container.remove()
        }
    }
}
</script>
