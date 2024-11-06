<template>
    <div class="vmpreview-wrapper" @click="handleClick">
        <v-md-preview :text="value"/>
    </div>
</template>

<style lang="scss" scoped>
.vmpreview-wrapper {
    width: 100%;
    height: 100%;
}
</style>
<script>
import VMdPreview from '@kangc/v-md-editor/lib/preview';
import '@kangc/v-md-editor/lib/style/preview.css';
import vuepressTheme from '@kangc/v-md-editor/lib/theme/vuepress.js';
import '@kangc/v-md-editor/lib/theme/style/vuepress.css';

// Prism
import Prism from 'prismjs';

// Language
import {languageName} from "../../../language";
import zhCN from '@kangc/v-md-editor/lib/lang/zh-CN';
import enUS from '@kangc/v-md-editor/lib/lang/en-US';

if (languageName === "zh" || languageName === "zh-CHT") {
    VMdPreview.lang.use('zh-CN', zhCN);
} else {
    VMdPreview.lang.use('en-US', enUS);
}

// Katex
import createKatexPlugin from '@kangc/v-md-editor/lib/plugins/katex/cdn';

VMdPreview.use(createKatexPlugin());

// Mermaid
import createMermaidPlugin from '@kangc/v-md-editor/lib/plugins/mermaid/cdn';
import '@kangc/v-md-editor/lib/plugins/mermaid/mermaid.css';

VMdPreview.use(createMermaidPlugin());

// TodoList
import createTodoListPlugin from '@kangc/v-md-editor/lib/plugins/todo-list/index';
import '@kangc/v-md-editor/lib/plugins/todo-list/todo-list.css';

VMdPreview.use(createTodoListPlugin());

// CopyCode
import createCopyCodePlugin from '@kangc/v-md-editor/lib/plugins/copy-code/index';
import '@kangc/v-md-editor/lib/plugins/copy-code/copy-code.css';

VMdPreview.use(createCopyCodePlugin());

import {previewMixin} from "../mixin";

export default {
    mixins: [previewMixin],

    components: {
        [VMdPreview.name]: VMdPreview,
    },

    created() {
        VMdPreview.use(vuepressTheme, {
            Prism,
            extend(md) {
                // md为 markdown-it 实例，可以在此处进行修改配置,并使用 plugin 进行语法扩展
                // md.set(option).use(plugin);
            },
        });
    },

    methods: {
        handleClick({target}) {
            if (target.nodeName === 'IMG') {
                const list = [...this.$el.querySelectorAll('img').values()].map(img => img.src)
                if (list.length === 0) {
                    return
                }
                this.$store.dispatch("previewImage", {index: target.src, list})
            }
        }
    }
}
</script>
