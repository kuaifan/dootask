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
        maxItems: 200,
        maxTitleLength: 200,
        maxDescLength: 1000,
        buttonLabels: {
            task: '创建任务',
            subtask: '创建子任务'
        }
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

    // 修改初始化插件函数
    initCreateTaskPlugin(md) {
        md.block.ruler.before('fence', 'create-task', (state, startLine, endLine, silent) => {
            const start = state.bMarks[startLine] + state.tShift[startLine];
            const max = state.eMarks[startLine];
            const firstLine = state.src.slice(start, max).trim();

            // 检查开始标记，并获取status值
            const match = firstLine.match(/^:::\s*(create-task-list|create-subtask-list)(?:\s+(\S+))?$/);
            if (!match) {
                return false;
            }

            if (silent) {
                return true;
            }

            // 获取按钮标题和状态
            const listType = match[1] === 'create-task-list' ? 'task' : 'subtask';
            const buttonTitle = this.config.buttonLabels[listType] || '';
            const status = match[2] || '';

            let nextLine = startLine + 1;
            let content = [];

            // 查找结束标记
            while (nextLine < endLine) {
                const lineStart = state.bMarks[nextLine] + state.tShift[nextLine];
                const lineMax = state.eMarks[nextLine];
                const line = state.src.slice(lineStart, lineMax);

                if (line.trim() === ':::') {
                    break;
                }

                content.push(line);
                nextLine++;
            }

            // 解析任务
            const tasks = [];
            let currentTask = null;
            let isCollectingDesc = false;
            let descLines = [];

            content.forEach(line => {
                const titleMatch = line.trim().match(/^title:\s*(.+)$/);
                const descMatch = line.trim().match(/^desc:\s*(.*)$/);

                if (titleMatch) {
                    // 如果已经有一个任务在处理中，保存它
                    if (currentTask) {
                        if (descLines.length > 0) {
                            currentTask.desc = descLines.join('\n');
                        }
                        tasks.push(currentTask);
                    }

                    // 开始新的任务
                    currentTask = {title: titleMatch[1]};
                    isCollectingDesc = false;
                    descLines = [];
                } else if (descMatch) {
                    isCollectingDesc = true;
                    if (descMatch[1]) {
                        descLines.push(descMatch[1]);
                    }
                } else if (isCollectingDesc && line.trim() && !line.trim().startsWith('title:')) {
                    // 收集多行描述，但不包括空行和新的title行
                    descLines.push(line.trim());
                }
            });

            // 处理最后一个任务
            if (currentTask) {
                if (descLines.length > 0) {
                    currentTask.desc = descLines.join('\n');
                }
                tasks.push(currentTask);
            }

            // 生成HTML
            const showIndex = tasks.length > 1;
            const taskItems = tasks.slice(0, this.config.maxItems).map((task, index) => [
                '<li>',
                showIndex ? `<div class="task-index">${index + 1}.</div>` : '',
                '<div class="task-item">',
                `<div class="title">${this.escapeHtml(this.validateInput(task.title, this.config.maxTitleLength))}</div>`,
                task.desc && match[1] === 'create-task-list' ? `<div class="desc">${this.escapeHtml(this.validateInput(task.desc, this.config.maxDescLength))}</div>` : '',
                '</div>',
                '</li>'
            ].join(''));

            const htmls =  [
                '<div class="apply-create-task">',
                '<ul>',
                taskItems.join(''),
                '</ul>',
                '<div class="apply-button">',
                `<div class="apply-create-${listType}-button${status ? ' ' + status : ''}">${$A.L(buttonTitle)}</div>`,
                '</div>',
                '</div>'
            ];

            // 添加token
            const token = state.push('html_block', '', 0);
            token.content = htmls.join('');
            token.map = [startLine, nextLine];

            state.line = nextLine + 1;
            return true;
        })
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
        MarkdownPluginUtils.initCreateTaskPlugin(MarkdownUtils.mds);
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
