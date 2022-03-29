<script>
import {mapState} from "vuex";

export default {
    name: 'AceEditor',
    props: {
        value: {
            default: ''
        },
        options: {
            type: Object,
            default: () => ({})
        },
        theme: {
            type: String,
            default: 'auto'
        },
        ext: {
            type: String,
            default: 'txt'
        },
        height: {
            type: Number || null,
            default: null
        },
        width: {
            type: Number || null,
            default: null
        },
        wrap: {
            type: Boolean,
            default: false
        },
        readOnly: {
            type: Boolean,
            default: false
        },
    },
    render(createElement) {
        return createElement('div', {
            class: "no-dark-mode"
        })
    },
    data: () => ({
        code: '',
        editor: null,
        cursorPosition: {
            row: 0,
            column: 0
        },
        supportedModes: {
            "Apache_Conf": [
                "^htaccess|^htgroups|^htpasswd|^conf|htaccess|htgroups|htpasswd"
            ],
            "BatchFile": [
                "bat|cmd"
            ],
            "C_Cpp": [
                "cpp|c|cc|cxx|h|hh|hpp|ino"
            ],
            "CSharp": [
                "cs"
            ],
            "CSS": [
                "css"
            ],
            "Dockerfile": [
                "^Dockerfile"
            ],
            "golang": [
                "go|golang"
            ],
            "HTML": [
                "html|htm|xhtml|vue|we|wpy"
            ],
            "Java": [
                "java"
            ],
            "JavaScript": [
                "js|jsm|jsx"
            ],
            "JSON": [
                "json"
            ],
            "JSP": [
                "jsp"
            ],
            "LESS": [
                "less"
            ],
            "Lua": [
                "lua"
            ],
            "Makefile": [
                "^Makefile|^GNUmakefile|^makefile|^OCamlMakefile|make"
            ],
            "Markdown": [
                "md|markdown"
            ],
            "MySQL": [
                "mysql"
            ],
            "Nginx": [
                "nginx|conf"
            ],
            "INI": [
                "ini|conf|cfg|prefs"
            ],
            "ObjectiveC": [
                "m|mm"
            ],
            "Perl": [
                "pl|pm"
            ],
            "Perl6": [
                "p6|pl6|pm6"
            ],
            "pgSQL": [
                "pgsql"
            ],
            "PHP_Laravel_blade": [
                "blade.php"
            ],
            "PHP": [
                "php|inc|phtml|shtml|php3|php4|php5|phps|phpt|aw|ctp|module"
            ],
            "Powershell": [
                "ps1"
            ],
            "Python": [
                "py"
            ],
            "R": [
                "r"
            ],
            "Ruby": [
                "rb|ru|gemspec|rake|^Guardfile|^Rakefile|^Gemfile"
            ],
            "Rust": [
                "rs"
            ],
            "SASS": [
                "sass"
            ],
            "SCSS": [
                "scss"
            ],
            "SH": [
                "sh|bash|^.bashrc"
            ],
            "SQL": [
                "sql"
            ],
            "SQLServer": [
                "sqlserver"
            ],
            "Swift": [
                "swift"
            ],
            "Text": [
                "txt"
            ],
            "Typescript": [
                "ts|typescript|str"
            ],
            "VBScript": [
                "vbs|vb"
            ],
            "Verilog": [
                "v|vh|sv|svh"
            ],
            "XML": [
                "xml|rdf|rss|wsdl|xslt|atom|mathml|mml|xul|xbl|xaml"
            ],
            "YAML": [
                "yaml|yml"
            ],
            "Compress": [
                "tar|zip|7z|rar|gz|arj|z"
            ],
            "images": [
                "icon|jpg|jpeg|png|bmp|gif|tif|emf"
            ]
        },
    }),
    mounted() {
        $A.loadScriptS([
            'js/ace/ace.js',
            'js/ace/mode-json.js',
        ], () => {
            // set init editor size
            this.setSize(this.$el, {height: this.height, width: this.width})

            // init ace editor
            this.editor = window.ace.edit(this.$el, {
                wrap: this.wrap,
                showPrintMargin: false,
                readOnly: this.readOnly,
                keyboardHandler: 'vscode',
            })
            this.editor.session.setMode(`ace/mode/${this.getFileMode()}`)

            // emit 'mounted' event
            this.$emit('mounted', this.editor)

            // official syntax validation workers include 'coffee', 'css', 'html'
            // 'javascript', 'json', 'lua', 'php', 'xml' and 'xquery'
            if (this.editor.session.$worker) {
                this.editor.session.$worker.addEventListener('annotate', this.workerMessage, false)
            }

            // set value and clear selection
            this.editor.setValue(this.value)
            this.editor.clearSelection()

            // set ace editor options and theme
            this.editor.setOptions(this.options)
            this.editTheme && this.editor.setTheme(`ace/theme/${this.editTheme}`)

            // 设置快捷键
            this.editor.commands.addCommand({
                name: '保存文件',
                bindKey: {
                    win: 'Ctrl-S',
                    mac: 'Command-S'
                },
                exec: () => {
                    this.$emit("saveData")
                },
                readOnly: false
            });

            // 触发修改内容
            this.editor.getSession().on('change', () => {
                this.code = this.editor.getValue()
                this.$emit('input', this.code);
            });
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
        },

        /**
         * 获取文件类型
         * @returns {string}
         */
        getFileMode() {
            var ext = this.ext || "text";
            for (var name in this.supportedModes) {
                var data = this.supportedModes[name],
                    suffixs = data[0].split('|'),
                    mode = name.toLowerCase();
                for (var i = 0; i < suffixs.length; i++) {
                    if (ext == suffixs[i]) {
                        return mode;
                    }
                }
            }
            return 'text';
        }
    },
    computed: {
        ...mapState(['themeIsDark']),

        editTheme() {
            if (this.theme == 'auto') {
                if (this.themeIsDark) {
                    return "dracula-dark"
                } else {
                    return "chrome"
                }
            }
            return this.theme
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
        editTheme(newTheme) {
            if (newTheme && typeof newTheme === 'string') {
                this.editor && this.editor.setTheme(`ace/theme/${newTheme}`)
            }
        },
        /**
         * watching and set ext
         * @param newExt
         */
        ext(newExt) {
            if (newExt && typeof newExt === 'string') {
                this.editor && this.editor.session.setMode(`ace/mode/${this.getFileMode()}`)
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
         * watching and set readOnly
         * @param only
         */
        readOnly(only) {
            if (typeof only === 'boolean') {
                this.editor && this.editor.setReadOnly(only)
            }
        },
        /**
         * watching and set code
         * @param newCode
         */
        value(newCode) {
            if (!this.editor) {
                return
            }
            if (newCode == this.code) {
                return;
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
