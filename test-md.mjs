import MarkdownIt from "markdown-it";

const mdIt = MarkdownIt();

// 添加自定义规则
mdIt.block.ruler.before('fence', 'task-list', function (state, startLine, endLine, silent) {
    const start = state.bMarks[startLine] + state.tShift[startLine];
    const max = state.eMarks[startLine];
    const firstLine = state.src.slice(start, max).trim();

    // 检查开始标记，并获取status值
    const match = firstLine.match(/^:::\s*task-list(?:\s+(\S+))?$/);
    if (!match) {
        return false;
    }

    if (silent) {
        return true;
    }

    const status = match[1] || ''; // 获取status值，如果没有则为空字符串

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
    const taskItems = tasks.map((task, index) => [
        '<li>',
        showIndex ? `<div class="task-index">${index + 1}.</div>` : '',
        '<div class="task-item">',
        `<div class="title">${task.title}</div>`,
        task.desc ? `<div class="desc">${task.desc}</div>` : '',
        '</div>',
        '</li>'
    ].join(''));

    const htmls =  [
        '<div class="apply-create-task">',
        '<ul>',
        taskItems.join(''),
        '</ul>',
        '<div class="apply-button">',
        `<div class="apply-create-task-button${status ? ' ' + status : ''}">+添加任务</div>`,
        '</div>',
        '</div>'
    ];

    // 添加token
    const token = state.push('html_block', '', 0);
    token.content = htmls.join('');
    token.map = [startLine, nextLine];

    state.line = nextLine + 1;
    return true;
});

// 测试不同的情况
const testCase = `
::: task-list status
title: 1单个任务的测试
desc: 这个任务不应该显示序号
title: 2任务标题1
desc: 任务描述-1
123123

title: 3任务标题2
title: 没有描述的任务1

title:     4有多行描述的任务2
desc:     第一行描述
第二行描述
第三行描述
:::
`;

console.log(mdIt.render(testCase));
