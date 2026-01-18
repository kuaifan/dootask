import MarkdownIt from "markdown-it";
import hljs from "highlight.js";
import mila from "markdown-it-link-attributes";
import mdKatex from "@traptitech/markdown-it-katex";

/**
 * Markdown
 */
const MarkdownUtils = {
    mdi: null,
    mds: null,

    /**
     * 解析Markdown
     * @param {*} text
     * @returns
     */
    formatMsg: (text) => {
        // 如果存在body标签，只取body中的内容
        const bodyMatch = text.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
        if (bodyMatch) {
            text = bodyMatch[1];
        }

        // 使用正则一次性替换所有的link、script、style标签
        text = text.replace(/<(link|script|style)[^>]*>[\s\S]*?<\/\1>|<(link|script|style)[^>]*\/?>/gi, '');

        // 处理图片标签
        const imgRegex = /<img\s+[^>]*?>/g;
        const imgArray = text.match(imgRegex);
        if (imgArray) {
            // 创建一个替换映射，避免多次字符串替换操作
            const replacements = {};
            imgArray.forEach(img => {
                replacements[img] = `<div class="no-size-image-box">${img}</div>`;
            });

            // 一次性完成所有替换
            for (const [original, replacement] of Object.entries(replacements)) {
                text = text.replace(original, replacement);
            }
        }

        // 处理a标签，确保所有链接在新窗口打开
        text = text.replace(/<a\s+([^>]*)>/gi, (match, attributes) => {
            // 如果已经有target属性，检查是否为_blank
            if (attributes.includes('target=')) {
                // 将已有的target属性替换为target="_blank"
                return match.replace(/target=(['"])[^'"]*\1/i, 'target="_blank"');
            } else {
                // 如果没有target属性，添加target="_blank"和rel="noopener noreferrer"
                return `<a ${attributes} target="_blank" rel="noopener noreferrer">`;
            }
        });

        return text;
    },

    /**
     * 高亮代码块
     * @param {*} str
     * @param {*} lang
     * @returns
     */
    highlightBlock: (str, lang = '') => {
        return `<pre class="code-block-wrapper"><div class="code-block-header"><span class="code-block-header__lang">${lang}</span><span class="code-block-header__copy">${$A.L('复制')}</span></div><code class="hljs code-block-body ${lang}">${str}</code></pre>`
    },

    /**
     * 使用DOMParser安全地提取HTML中的纯文本
     * @param {string} html - HTML字符串
     * @returns {string} 纯文本内容
     */
    extractTextWithDOMParser(html) {
        try {
            // 使用DOMParser解析HTML，避免直接操作页面DOM
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            // 获取纯文本内容，保留换行结构
            let text = '';

            // 遍历所有节点，提取文本并处理换行
            const walker = document.createTreeWalker(
                doc.body || doc.documentElement,
                NodeFilter.SHOW_TEXT | NodeFilter.SHOW_ELEMENT,
                {
                    acceptNode: function (node) {
                        if (node.nodeType === Node.TEXT_NODE) {
                            return NodeFilter.FILTER_ACCEPT;
                        }
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            const tagName = node.tagName.toLowerCase();
                            // 块级元素和换行元素需要添加换行符
                            if (['p', 'div', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tagName)) {
                                return NodeFilter.FILTER_ACCEPT;
                            }
                        }
                        return NodeFilter.FILTER_SKIP;
                    }
                }
            );

            let node;
            while (node = walker.nextNode()) {
                if (node.nodeType === Node.TEXT_NODE) {
                    text += node.textContent;
                } else {
                    const tagName = node.tagName.toLowerCase();
                    if (['p', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(tagName)) {
                        // 块级元素前后添加换行
                        if (text && !text.endsWith('\n')) {
                            text += '\n';
                        }
                    } else if (tagName === 'br') {
                        text += '\n';
                    }
                }
            }

            return text.trim();
        } catch (error) {
            // 降级处理：如果DOMParser失败，使用简单的正则替换
            return html
                .replace(/<[^>]*>/g, '') // 移除所有HTML标签
                .replace(/&lt;/g, '<')
                .replace(/&gt;/g, '>')
                .replace(/&amp;/g, '&')
                .replace(/&quot;/g, '"')
                .replace(/&#39;/g, "'")
                .trim();
        }
    },

    /**
     * 检测Markdown语法权重
     * @param {*} text
     * @returns
     */
    detectMarkdownSyntaxWeight(text) {
        if (!text) {
            return false;
        }

        // Markdown模式及其权重
        const patterns = [
            // 高权重 - 强烈表明是Markdown
            {regex: /^#{1,6}\s+.+$/m, weight: 0.4},           // 标题
            {regex: /```[\s\S]*?```/, weight: 0.4},           // 代码块
            {regex: /\[[^\]]+\]\([^)]+\)/, weight: 0.3},      // 链接
            {regex: /!\[[^\]]*\]\([^)]+\)/, weight: 0.3},     // 图片

            // 中等权重
            {regex: /\*\*[^*\s][^*]*[^*\s]\*\*/, weight: 0.2}, // 粗体
            {regex: /__[^_\s][^_]*[^_\s]__/, weight: 0.2},     // 粗体
            {regex: /~~[^~\s][^~]*[^~\s]~~/, weight: 0.2},     // 删除线

            // 低权重 - 需谨慎
            {regex: /`[^`\s][^`]*[^`\s]`/, weight: 0.15},      // 行内代码
            {regex: /^[-*+]\s+.+$/m, weight: 0.1},             // 无序列表
            {regex: /^\d+\.\s+.+$/m, weight: 0.1},             // 有序列表
            {regex: /^>\s+.+$/m, weight: 0.1}                  // 引用
        ];

        // 计算总权重
        let totalWeight = 0;
        patterns.forEach(pattern => {
            if (pattern.regex.test(text)) {
                totalWeight += pattern.weight;
            }
        });

        return totalWeight;
    },
}

const MarkdownPluginUtils = {
    // HTML转义函数
    escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    },

    // 验证输入
    validateInput(value, maxLength) {
        if (!value) return '';
        if (typeof value !== 'string') return '';
        if (value.length > maxLength) {
            return value.substring(0, maxLength) + '...';
        }
        return value;
    },

    // 清除空推理
    clearEmptyReasoning(text) {
        return text.replace(/:::\s*reasoning\s*[\r\n]*\s*:::/g, '');
    },

    // 修改初始化插件函数（推理）
    initReasoningPlugin(md) {
        md.block.ruler.before('fence', 'reasoning', (state, startLine, endLine, silent) => {
            const start = state.bMarks[startLine] + state.tShift[startLine];
            const max = state.eMarks[startLine];
            const firstLine = state.src.slice(start, max).trim();

            // 检查是否匹配 :::reasoning 开始标记
            const match = firstLine.match(/^:::\s*reasoning(?:\s+(\S+))?$/);
            if (!match) {
                return false;
            }

            if (silent) {
                return true;
            }

            let nextLine = startLine + 1;
            let content = [];

            // 查找结束标记 :::
            while (nextLine < endLine) {
                const lineStart = state.bMarks[nextLine] + state.tShift[nextLine];
                const lineMax = state.eMarks[nextLine];
                const currentLine = state.src.slice(lineStart, lineMax);

                if (currentLine.trim() === ':::') {
                    break;
                }

                content.push(state.getLines(nextLine, nextLine + 1, state.tShift[nextLine], true));
                nextLine++;
            }

            // 创建外层容器
            let token = state.push('reasoning_open', 'div', 1);
            token.attrs = [['class', 'apply-reasoning']];

            // 创建标签
            token = state.push('reasoning_label_open', 'div', 1);
            token.attrs = [['class', 'reasoning-label']];
            token = state.push('text', '', 0);
            token.content = $A.L('思考过程');
            state.push('reasoning_label_close', 'div', -1);

            // 创建内容容器
            token = state.push('reasoning_content_open', 'div', 1);
            token.attrs = [['class', 'reasoning-content']];

            // 处理内容
            if (content.length > 0) {
                state.md.block.parse(content.join('\n'), state.md, state.env, state.tokens);
            }

            // 关闭内容容器
            state.push('reasoning_content_close', 'div', -1);

            // 关闭外层容器
            state.push('reasoning_close', 'div', -1);

            state.line = nextLine + 1;
            return true;
        });
    }
};

export {MarkdownPluginUtils}

/**
 * 合并连续的工具使用
 * 例如：
 * > <tool-use>Tool: a</tool-use>
 * > <tool-use>Tool: b</tool-use>
 * > <tool-use>Tool: b</tool-use>
 * 合并为：
 * > <tool-use>Tool: a, b x 2</tool-use>
 *
 * @param {string} text
 * @returns {string}
 */
function mergeConsecutiveToolUse(text) {
    const toolUsePattern = /^>\s*<tool-use>Tool:\s*([^<]+)<\/tool-use>\s*$/;
    const lines = text.split('\n');
    const result = [];
    let toolGroup = [];

    const formatToolGroup = (tools) => {
        if (tools.length === 0) return '';
        if (tools.length === 1) return `> <tool-use>Tool: ${tools[0]}</tool-use>`;

        // 合并连续相同的工具
        const merged = [];
        let currentTool = tools[0];
        let count = 1;

        for (let i = 1; i < tools.length; i++) {
            if (tools[i] === currentTool) {
                count++;
            } else {
                merged.push(count > 1 ? `${currentTool} x ${count}` : currentTool);
                currentTool = tools[i];
                count = 1;
            }
        }
        merged.push(count > 1 ? `${currentTool} x ${count}` : currentTool);

        return `> <tool-use>Tool: ${merged.join(', ')}</tool-use>`;
    };

    let pendingEmptyLines = []; // 暂存空行

    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        const match = line.match(toolUsePattern);

        if (match) {
            // 工具使用行，丢弃暂存的空行，继续收集
            pendingEmptyLines = [];
            toolGroup.push(match[1].trim());
        } else if (line.trim() === '' && toolGroup.length > 0) {
            // 空行且已有工具组，暂存空行
            pendingEmptyLines.push(line);
        } else {
            // 非工具使用的非空行，结束当前工具组
            if (toolGroup.length > 0) {
                result.push(formatToolGroup(toolGroup));
                toolGroup = [];
            }
            // 输出暂存的空行
            result.push(...pendingEmptyLines);
            pendingEmptyLines = [];
            result.push(line);
        }
    }

    if (toolGroup.length > 0) {
        result.push(formatToolGroup(toolGroup));
    }
    // 输出末尾暂存的空行
    result.push(...pendingEmptyLines);

    return result.join('\n');
}

export function MarkdownConver(text) {
    if (text === '...') {
        return '<div class="input-blink"></div>'
    }
    if (MarkdownUtils.mdi === null) {
        MarkdownUtils.mdi = new MarkdownIt({
            html: true,
            breaks: true,
            linkify: false,
            typographer: true,
            highlight(code, language) {
                const validLang = !!(language && hljs.getLanguage(language))
                if (validLang) {
                    const lang = language ?? ''
                    return MarkdownUtils.highlightBlock(hljs.highlight(code, {language: lang}).value, lang)
                }
                return MarkdownUtils.highlightBlock(hljs.highlightAuto(code).value, '')
            },
        })
        MarkdownUtils.mdi.use(mila, {attrs: {target: '_blank', rel: 'noopener noreferrer'}})
        MarkdownUtils.mdi.use(mdKatex, {blockClass: 'katexmath-block rounded-md p-[10px]', errorColor: ' #cc0000'})
        MarkdownPluginUtils.initReasoningPlugin(MarkdownUtils.mdi);
    }
    text = MarkdownPluginUtils.clearEmptyReasoning(text);
    text = mergeConsecutiveToolUse(text);
    text = MarkdownUtils.mdi.render(text);
    return MarkdownUtils.formatMsg(text)
}

export function MarkdownPreview(text) {
    if (MarkdownUtils.mds === null) {
        MarkdownUtils.mds = MarkdownIt()
        MarkdownPluginUtils.initReasoningPlugin(MarkdownUtils.mds);
    }
    text = MarkdownPluginUtils.clearEmptyReasoning(text);
    return MarkdownUtils.mds.render(text)
}

export function isMarkdownFormat(html) {
    if (!html || html === '') {
        return false;
    }

    // 预处理：移除代码块避免误判
    const tmp = html.replace(/<p>/g, '\n').replace(/(^|\s+)```([\s\S]*)```/gm, '');

    // 快速检测：如果包含富文本标签，直接返回false
    if (/<\/(strong|s|em|u|ol|ul|li|blockquote|pre|img|a)>/i.test(tmp)) {
        return false;
    }

    // 检测mention标签
    if (/<span[^>]+?class="mention"[^>]*?>/i.test(tmp)) {
        return false;
    }

    // 使用DOMParser提取纯文本，替代原来的DOM操作
    const text = MarkdownUtils.extractTextWithDOMParser(html);

    // Markdown语法检测
    return MarkdownUtils.detectMarkdownSyntaxWeight(text) >= 0.3;
}
