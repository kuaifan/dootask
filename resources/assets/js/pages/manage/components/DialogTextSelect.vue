<template>
    <div class="dialog-text-select">
        <div class="text-select-title" :class="{bordered: !source}">{{ $L('选择复制') }}</div>
        <ul v-if="source" class="text-select-tabs">
            <li :class="{active: tab === 'preview'}" @click="tab = 'preview'">{{ $L('预览') }}</li>
            <li :class="{active: tab === 'source'}" @click="tab = 'source'">{{ $L('原文') }}</li>
        </ul>
        <div class="text-select-content user-select-auto">
            <DialogMarkdown v-if="source && tab === 'preview'" class="no-dark-content" :text="source"/>
            <pre v-else>{{ currentText }}</pre>
        </div>
        <div class="text-select-button">
            <Button type="primary" size="large" icon="md-copy" long @click="onCopyAll">{{ $L('复制全部') }}</Button>
        </div>
    </div>
</template>

<script>
import DialogMarkdown from "./DialogMarkdown.vue";

export default {
    name: "DialogTextSelect",
    components: {DialogMarkdown},
    props: {
        // 消息显示的文本
        text: {
            type: String,
            default: ''
        },
        // Markdown 原文（仅 Markdown 消息）
        source: {
            type: String,
            default: ''
        },
    },

    data() {
        return {
            tab: 'preview',
        }
    },

    computed: {
        currentText() {
            return this.source && this.tab === 'source' ? this.source : this.text
        }
    },

    methods: {
        onCopyAll() {
            this.copyText(this.currentText)
        }
    }
}
</script>
