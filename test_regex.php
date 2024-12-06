<?php

function cleanTaskList($text) {
    // 定义模式来匹配整个任务块
    $pattern = '/:::\s*(create-task-list|create-subtask-list)(.*?):::/s';
    
    // 替换函数
    $replacement = function($matches) {
        $content = $matches[2];
        
        // 提取所有标题和描述
        $lines = explode("\n", trim($content));
        $result = [];
        $currentTitle = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (preg_match('/^title:\s*(.+)$/', $line, $titleMatch)) {
                $currentTitle = $titleMatch[1];
                $result[] = $currentTitle;
            } elseif (preg_match('/^desc:\s*(.+)$/', $line, $descMatch)) {
                if (!empty($currentTitle)) {
                    $result[] = $descMatch[1];
                }
            }
        }
        
        return implode("\n", $result);
    };
    
    // 执行替换
    return preg_replace_callback($pattern, $replacement, $text);
}

// 测试代码
$input = <<<EOD
啊大家啊速度快放假阿斯达哦佛阿萨代发

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
:::
EOD;

$result = cleanTaskList($input);
echo $result;

?>
