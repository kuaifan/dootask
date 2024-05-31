const e=()=>{$A.dark.utils.addStyle("vmeditor-editor-dark-mode-style","style",`
        .vmeditor-wrapper .v-md-pre-wrapper.copy-code-mode .v-md-copy-code-btn,
        .vmpreview-wrapper .v-md-pre-wrapper.copy-code-mode .v-md-copy-code-btn {
            box-shadow: none
        }
        .vmeditor-wrapper .v-md-editor__toc-nav-title {
            font-size: 15px;
        }
        .vmeditor-wrapper .vuepress-markdown-body:not(.custom),
        .vmpreview-wrapper .vuepress-markdown-body:not(.custom) {
            padding: 1rem 1.5rem;
        }
        @media screen {
            .v-md-pre-wrapper {
                ${$A.dark.utils.reverseFilter()}
            }
        }`)},r={props:{value:{default:""},leftToolbar:{default:"undo redo clear | h bold italic strikethrough quote | todo-list ul ol table hr | tip link customImages code"},rightToolbar:{default:"sync-scroll preview toc fullscreen"},includeLevel:{type:Array,default:()=>[1,2,3,4]},tocNavPositionRight:{type:Boolean,default:!0}},created(){e()}},o={props:{value:{default:""}},created(){e()}};export{r as e,o as p};
