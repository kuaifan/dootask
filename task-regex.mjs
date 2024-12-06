/**
 * 清理任务列表标记，只保留标题和描述
 * @param {string} text 输入文本
 * @returns {string} 处理后的文本
 */
export function cleanTaskList(text) {
    // 匹配整个任务块的模式
    const pattern = /:::\s*(create-task-list|create-subtask-list)([\s\S]*?):::/g;
    
    return text.replace(pattern, (match, type, content) => {
        // 分割内容为行
        const lines = content.trim().split('\n');
        const result = [];
        let currentTitle = '';
        
        lines.forEach(line => {
            line = line.trim();
            if (!line) return;
            
            const titleMatch = line.match(/^title:\s*(.+)$/);
            const descMatch = line.match(/^desc:\s*(.+)$/);
            
            if (titleMatch) {
                currentTitle = titleMatch[1];
                result.push(currentTitle);
            } else if (descMatch && currentTitle) {
                result.push(descMatch[1]);
            }
        });
        
        return result.join('\n');
    });
}

// 测试代码
const input = `啊大家啊速度快放假阿斯达哦佛阿萨代发

::: create-task-list
title: 任务标题1
desc: 任务描述1

title: 任务标题2
desc: 任务描述2

title: 任务标题3

title: 任务标题4
desc: 任务描述4
:::

1231231231

::: create-subtask-list
title: 任务标题5
desc: 任务描述5

title: 任务标题6
desc: 任务描述6

title: 任务标题7

title: 任务标题8
desc: 任务描述8
:::`;

console.log(cleanTaskList(input));
