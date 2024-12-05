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
        const array = text.match(/<img\s+[^>]*?>/g);
        if (array) {
            array.some(res => {
                text = text.replace(res, `<div class="no-size-image-box">${res}</div>`);
            })
        }
        return text
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
}

const MarkdownPluginUtils = {
    // 配置选项
    config: {
        maxTitleLength: 200,
        maxDescLength: 1000,
        maxItems: 200,
        defaultTitle: '创建任务'
    },

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

    // 解析任务项
    parseTaskItems(content) {
        const items = [];
        let currentItem = {};
        let itemCount = 0;

        content.forEach(line => {
            line = line.trim();
            if (!line) {
                if (Object.keys(currentItem).length > 0) {
                    items.push(currentItem);
                    currentItem = {};
                    itemCount++;
                }
                return;
            }

            if (itemCount >= this.config.maxItems) {
                return;
            }

            const [key, ...valueParts] = line.split(':');
            const value = valueParts.join(':').trim();

            if (key === 'title' && value) {
                if (Object.keys(currentItem).length > 0) {
                    items.push(currentItem);
                    currentItem = {};
                    itemCount++;
                }
                currentItem.title = this.validateInput(value, this.config.maxTitleLength);
            } else if (key === 'desc' && value) {
                currentItem.desc = this.validateInput(value, this.config.maxDescLength);
            }
        });

        if (Object.keys(currentItem).length > 0 && itemCount < this.config.maxItems) {
            items.push(currentItem);
        }

        return items;
    },

    // 生成HTML
    generateTaskHtml(items, status = null) {
        if (!Array.isArray(items) || items.length === 0) {
            return '';
        }

        const html = [
            '<div class="apply-create-task">',
            '<ul>'
        ];

        items.forEach((item, index) => {
            if (item.title) {
                html.push(`<li>`);
                html.push(`<div class="task-index">${index + 1}.</div>`);
                html.push(`<div class="task-item">`);
                html.push(`<div class="title">${this.escapeHtml(item.title)}</div>`);
                if (item.desc) {
                    html.push(`<div class="desc">${this.escapeHtml(item.desc)}</div>`);
                }
                html.push('</div>');
                html.push('</li>');
            }
        });

        html.push(
            '</ul>',
            `<div class="apply-button"><div class="apply-create-task-button ${status||''}">${this.escapeHtml($A.L(this.config.defaultTitle))}</div></div>`,
            '</div>'
        );
        return html.join('\n');
    },

    // 修改初始化插件函数
    initCreateTaskPlugin(md) {
        md.block.ruler.before('fence', 'create_task', (state, startLine, endLine, silent) => {
            try {
                const start = state.bMarks[startLine] + state.tShift[startLine];
                const max = state.eMarks[startLine];

                const match = state.src.slice(start, max).trim().match(/^```\s*CreateTask\s*(applying|applied)?$/);
                if (!match) {
                    return false;
                }

                if (silent) {
                    return true;
                }

                let nextLine = startLine + 1;
                let content = [];
                let found = false;

                while (nextLine < endLine) {
                    const line = state.src.slice(state.bMarks[nextLine], state.eMarks[nextLine]).trim();
                    if (line === '```') {
                        found = true;
                        break;
                    }
                    content.push(line);
                    nextLine++;
                }

                if (!found) {
                    return false;
                }

                // 创建 token 并设置为空字符串内容
                const token = state.push('html_block', '', 0);

                // 如果有内容，则解析并生成HTML
                if (content.length > 0) {
                    const items = this.parseTaskItems(content);
                    const html = this.generateTaskHtml(items, match[1]);
                    token.content = html || '';
                } else {
                    token.content = ''; // 空内容直接返回空字符串
                }

                token.map = [startLine, nextLine + 1];
                state.line = nextLine + 1;
                return true;

            } catch (error) {
                console.error('Error in create_task parser:', error);
                return false;
            }
        });
    }
};

export function MarkdownConver(text) {
    if (text === '...') {
        return '<div class="input-blink"></div>'
    }
    if (MarkdownUtils.mdi === null) {
        MarkdownUtils.mdi = new MarkdownIt({
            linkify: true,
            highlight(code, language) {
                const validLang = !!(language && hljs.getLanguage(language))
                if (validLang) {
                    const lang = language ?? ''
                    return MarkdownUtils.highlightBlock(hljs.highlight(code, {language: lang}).value, lang)
                }
                return MarkdownUtils.highlightBlock(hljs.highlightAuto(code).value, '')
            },
        })
        MarkdownUtils.mdi.use(mila, {attrs: {target: '_blank', rel: 'noopener'}})
        MarkdownUtils.mdi.use(mdKatex, {blockClass: 'katexmath-block rounded-md p-[10px]', errorColor: ' #cc0000'})
        MarkdownPluginUtils.initCreateTaskPlugin(MarkdownUtils.mdi);
    }
    return MarkdownUtils.formatMsg(MarkdownUtils.mdi.render(text))
}

export function MarkdownPreview(text) {
    if (MarkdownUtils.mds === null) {
        MarkdownUtils.mds = MarkdownIt()
    }
    return MarkdownUtils.mds.render(text)
}

export function isMarkdownFormat(html) {
    if (html === '') {
        return false
    }
    const tmp = html.replace(/<p>/g, '\n').replace(/(^|\s+)```([\s\S]*)```/gm, '')
    if (/<\/(strong|s|em|u|ol|ul|li|blockquote|pre|img|a)>/i.test(tmp)) {
        return false
    }
    if (/<span[^>]+?class="mention"[^>]*?>/i.test(tmp)) {
        return false
    }
    //
    const el = document.createElement('div')
    el.style.position = 'fixed'
    el.style.top = '0'
    el.style.left = '0'
    el.style.width = '10px'
    el.style.height = '10px'
    el.style.overflow = 'hidden'
    el.style.zIndex = '-9999'
    el.style.opacity = '0'
    el.innerHTML = html
    document.body.appendChild(el)
    const text = el.innerText
    document.body.removeChild(el)
    //
    if (
        /(^|\s+)#+\s(.*)$/m.test(text)              // 标题
        || /(^|\s+)\*\*(.*)\*\*/m.test(text)        // 粗体
        || /(^|\s+)__(.*)__/m.test(text)            // 粗体
        || /(^|\s+)\*(.*)\*/m.test(text)            // 斜体
        || /(^|\s+)_(.*)_/m.test(text)              // 斜体
        || /(^|\s+)~~(.*)~~/m.test(text)            // 删除线
        || /(^|\s+)\[(.*?)\]\((.*?)\)/m.test(text)  // 链接
        || /(^|\s+)!\[(.*?)\]\((.*?)\)/m.test(text) // 图片
        || /(^|\s+)`(.*?)`/m.test(text)             // 行内代码
        || /(^|\s+)```([\s\S]*?)```/m.test(text)    // 代码块
    ) {
        return true
    }
    return false
}
