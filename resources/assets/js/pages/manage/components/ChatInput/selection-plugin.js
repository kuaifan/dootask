// selection-plugin.js - 独立的 Quill 插件文件
import Quill from 'quill-hi';
import Emitter from "quill-hi/core/emitter";

// 通过 Quill.import 导入需要的核心模块
const Module = Quill.import('core/module');

class SelectionPlugin extends Module {
    static DEFAULTS = {
        immediate: true,
        minLength: 1,
        onTextSelected: null,
        onSelectionCleared: null,
        onSelectionChange: null
    };

    constructor(quill, options = {}) {
        super(quill, { ...SelectionPlugin.DEFAULTS, ...options });

        this.lastRange = null;
        this.debounceTimer = null;
        this.setupEventListeners();
    }

    setupEventListeners() {
        // 监听选择变化事件
        this.quill.on(Emitter.events.SELECTION_CHANGE, (range, oldRange, source) => {
            this.handleSelectionChange(range, oldRange, source);
        });

        // 监听文本变化事件，处理输入替换选中文本的情况
        this.quill.on(Emitter.events.TEXT_CHANGE, (delta, oldDelta, source) => {
            // 延迟检查，确保选择状态已更新
            setTimeout(() => {
                const currentRange = this.quill.getSelection();
                this.handleSelectionChange(currentRange, this.lastRange, source);
            }, 0);
        });
    }

    handleSelectionChange(range, oldRange, source) {
        // 防抖处理
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        this.debounceTimer = setTimeout(() => {
            this.processSelectionChange(range, oldRange, source);
            this.lastRange = range;
            this.debounceTimer = null;
        }, this.options.immediate ? 0 : 100);
    }

    processSelectionChange(range, oldRange, source) {
        // 通用选择变化回调
        if (this.options.onSelectionChange) {
            this.options.onSelectionChange(range, oldRange, source);
        }

        // 处理文本被选中的情况
        if (range && range.length >= this.options.minLength) {
            const selectedText = this.quill.getText(range.index, range.length);

            if (this.options.onTextSelected) {
                this.options.onTextSelected(selectedText, range, source);
            }

            // 发射自定义事件
            this.quill.emitter.emit('text-selected', {
                text: selectedText,
                range: range,
                source: source
            });
        }
        // 处理选择被清除的情况
        else if ((!range || range.length === 0) && oldRange && oldRange.length > 0) {
            if (this.options.onSelectionCleared) {
                this.options.onSelectionCleared(oldRange, source);
            }

            // 发射自定义事件
            this.quill.emitter.emit('selection-cleared', {
                previousRange: oldRange,
                source: source
            });
        }
    }

    // 公共 API 方法
    getSelectedText() {
        const range = this.quill.getSelection();
        if (range && range.length > 0) {
            return this.quill.getText(range.index, range.length);
        }
        return null;
    }

    hasSelection() {
        const range = this.quill.getSelection();
        return !!(range && range.length > 0);
    }

    selectText(index, length) {
        this.quill.setSelection(index, length, Emitter.sources.API);
    }

    clearSelection() {
        const range = this.quill.getSelection();
        if (range) {
            this.quill.setSelection(range.index, 0, Emitter.sources.API);
        }
    }

    // 清理资源
    destroy() {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
    }
}

// 注册插件
Quill.register('modules/selectionPlugin', SelectionPlugin);

// 导出给模块系统使用（可选）
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SelectionPlugin;
}
