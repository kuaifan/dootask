<?php

namespace App\Module;

use App\Models\ProjectTask;
use App\Models\ProjectTaskAiEvent;
use App\Models\ProjectTaskUser;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\WebSocketDialogMsg;
use Cache;
use Carbon\Carbon;

class AiTaskSuggestion
{
    /**
     * AI 助手的 userid
     */
    const AI_ASSISTANT_USERID = -1;

    /**
     * 相似度阈值
     */
    const SIMILAR_THRESHOLD = 0.7;

    /**
     * 检查是否满足执行条件
     */
    public static function shouldExecute(ProjectTask $task, string $eventType): bool
    {
        switch ($eventType) {
            case ProjectTaskAiEvent::EVENT_DESCRIPTION:
                // 描述为空或长度 < 20
                $content = trim($task->content ?? '');
                return empty($content) || mb_strlen($content) < 20;

            case ProjectTaskAiEvent::EVENT_SUBTASKS:
                // 无子任务且标题长度 > 10
                $hasSubtasks = ProjectTask::where('parent_id', $task->id)->exists();
                return !$hasSubtasks && mb_strlen($task->name) > 10;

            case ProjectTaskAiEvent::EVENT_ASSIGNEE:
                // 未指定负责人
                $hasOwner = ProjectTaskUser::where('task_id', $task->id)
                    ->where('owner', 1)
                    ->exists();
                return !$hasOwner;

            case ProjectTaskAiEvent::EVENT_SIMILAR:
                // 向量搜索暂未实现，跳过
                return false;

            default:
                return false;
        }
    }

    /**
     * 生成任务描述建议
     */
    public static function generateDescription(ProjectTask $task): ?array
    {
        $prompt = self::buildDescriptionPrompt($task);
        $result = self::callAi($prompt);

        if (empty($result)) {
            return null;
        }

        return [
            'type' => 'description',
            'content' => $result,
        ];
    }

    /**
     * 生成子任务拆分建议
     */
    public static function generateSubtasks(ProjectTask $task): ?array
    {
        $prompt = self::buildSubtasksPrompt($task);
        $result = self::callAi($prompt);

        if (empty($result)) {
            return null;
        }

        // 解析返回的子任务列表
        $subtasks = self::parseSubtasksList($result);
        if (empty($subtasks)) {
            return null;
        }

        return [
            'type' => 'subtasks',
            'content' => $subtasks,
        ];
    }

    /**
     * 生成负责人推荐
     */
    public static function generateAssignee(ProjectTask $task): ?array
    {
        // 获取项目成员
        $members = self::getProjectMembersInfo($task->project_id);
        if (empty($members)) {
            return null;
        }

        $prompt = self::buildAssigneePrompt($task, $members);
        $result = self::callAi($prompt);

        if (empty($result)) {
            return null;
        }

        // 解析推荐结果
        $recommendations = self::parseAssigneeRecommendations($result, $members);
        if (empty($recommendations)) {
            return null;
        }

        return [
            'type' => 'assignee',
            'content' => $recommendations,
        ];
    }

    /**
     * 搜索相似任务
     */
    public static function findSimilarTasks(ProjectTask $task): ?array
    {
        // 使用 AI 模块的 Embedding 搜索
        $searchText = $task->name . ' ' . ($task->content ?? '');

        try {
            $result = AI::getEmbedding($searchText);
            if (Base::isError($result) || empty($result['data'])) {
                return null;
            }

            $embedding = $result['data'];

            // 搜索相似任务（排除自己和子任务）
            $similarTasks = self::searchSimilarByEmbedding(
                $embedding,
                $task->project_id,
                $task->id
            );

            if (empty($similarTasks)) {
                return null;
            }

            return [
                'type' => 'similar',
                'content' => $similarTasks,
            ];
        } catch (\Exception $e) {
            \Log::error('AiTaskSuggestion::findSimilarTasks error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 转义用户输入以防止 Prompt 注入
     */
    private static function escapeUserInput(string $input): string
    {
        // 移除可能影响 AI Prompt 解析的特殊字符
        $input = str_replace(['```', '---', '==='], '', $input);
        // 截断过长的输入
        return mb_substr(trim($input), 0, 500);
    }

    /**
     * 构建描述生成 Prompt
     */
    private static function buildDescriptionPrompt(ProjectTask $task): string
    {
        $taskName = self::escapeUserInput($task->name);
        $projectName = self::escapeUserInput($task->project->name ?? '未知项目');

        return <<<PROMPT
你是一个专业的项目管理助手。请根据以下任务标题，生成结构化的任务描述。

任务标题：{$taskName}
所属项目：{$projectName}

请按以下格式生成任务描述（使用 Markdown）：

> **背景**：[描述任务的背景和上下文]
> **目标**：[明确任务要达成的目标]
> **验收标准**：
> - [验收标准1]
> - [验收标准2]
> - [验收标准3]

要求：
1. 内容要专业、简洁
2. 验收标准要具体、可衡量
3. 与用户输入语言保持一致
PROMPT;
    }

    /**
     * 构建子任务拆分 Prompt
     */
    private static function buildSubtasksPrompt(ProjectTask $task): string
    {
        $taskName = self::escapeUserInput($task->name);
        $content = self::escapeUserInput($task->content ?? '');

        return <<<PROMPT
你是一个专业的项目管理助手。请将以下任务拆分为可执行的子任务。

任务标题：{$taskName}
任务描述：{$content}

请返回 3-5 个子任务，每行一个，格式如下：
1. [子任务名称]
2. [子任务名称]
...

要求：
1. 每个子任务要具体、可执行
2. 子任务之间有合理的顺序
3. 子任务名称简洁明了（不超过30字）
4. 只返回子任务列表，不要其他内容
PROMPT;
    }

    /**
     * 构建负责人推荐 Prompt
     */
    private static function buildAssigneePrompt(ProjectTask $task, array $members): string
    {
        $membersText = '';
        foreach ($members as $member) {
            $nickname = self::escapeUserInput($member['nickname']);
            $membersText .= "- {$nickname}（ID:{$member['userid']}）";
            if (!empty($member['profession'])) {
                $profession = self::escapeUserInput($member['profession']);
                $membersText .= "，职位：{$profession}";
            }
            $membersText .= "，进行中任务：{$member['in_progress_count']}个";
            $membersText .= "，近期完成：{$member['completed_count']}个";
            if ($member['similar_count'] > 0) {
                $membersText .= "，处理过类似任务：{$member['similar_count']}个";
            }
            $membersText .= "\n";
        }

        $taskName = self::escapeUserInput($task->name);
        $taskContent = self::escapeUserInput($task->content ?? '');

        return <<<PROMPT
你是一个专业的项目管理助手。请根据任务内容和团队成员情况，推荐最合适的负责人。

任务标题：{$taskName}
任务描述：{$taskContent}

团队成员：
{$membersText}

请推荐 2 名最合适的负责人，按优先级排序，格式如下：
1. [userid]|[推荐理由，简短说明]
2. [userid]|[推荐理由，简短说明]

推荐依据：
1. 优先选择处理过类似任务的成员
2. 考虑当前工作负载（进行中任务较少的优先）
3. 考虑专业匹配度

只返回推荐列表，不要其他内容。
PROMPT;
    }

    /**
     * 调用 AI 接口
     */
    private static function callAi(string $prompt): ?string
    {
        try {
            // 使用 AI 模块调用
            $result = AI::invoke([
                ['system', '你是 DooTask 任务管理系统的 AI 助手，帮助用户管理任务。'],
                ['user', $prompt],
            ]);

            if (Base::isError($result)) {
                \Log::error('AiTaskSuggestion::callAi error: ' . ($result['msg'] ?? 'Unknown error'));
                return null;
            }

            return $result['data']['content'] ?? null;
        } catch (\Exception $e) {
            \Log::error('AiTaskSuggestion::callAi error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取项目成员信息
     */
    private static function getProjectMembersInfo(int $projectId): array
    {
        $projectUsers = ProjectUser::where('project_id', $projectId)->get();
        $members = [];

        foreach ($projectUsers as $pu) {
            $user = User::find($pu->userid);
            if (!$user || $user->bot || $user->disable_at) {
                continue;
            }

            // 获取进行中任务数量
            $inProgressCount = ProjectTask::join('project_task_users', 'project_tasks.id', '=', 'project_task_users.task_id')
                ->where('project_task_users.userid', $user->userid)
                ->whereNull('project_tasks.complete_at')
                ->whereNull('project_tasks.archived_at')
                ->whereNull('project_tasks.deleted_at')
                ->count();

            // 获取近期完成任务数量
            $completedCount = ProjectTask::join('project_task_users', 'project_tasks.id', '=', 'project_task_users.task_id')
                ->where('project_task_users.userid', $user->userid)
                ->whereNotNull('project_tasks.complete_at')
                ->where('project_tasks.complete_at', '>=', Carbon::now()->subDays(30))
                ->whereNull('project_tasks.deleted_at')
                ->count();

            $members[] = [
                'userid' => $user->userid,
                'nickname' => $user->nickname,
                'profession' => $user->profession ?? '',
                'in_progress_count' => $inProgressCount,
                'completed_count' => $completedCount,
                'similar_count' => 0, // TODO: 计算相似任务数量
            ];
        }

        return $members;
    }

    /**
     * 解析子任务列表
     */
    private static function parseSubtasksList(string $text): array
    {
        $lines = explode("\n", trim($text));
        $subtasks = [];

        foreach ($lines as $line) {
            $line = trim($line);
            // 移除序号前缀
            $line = preg_replace('/^\d+[\.\)、]\s*/', '', $line);
            if (!empty($line) && mb_strlen($line) <= 100) {
                $subtasks[] = $line;
            }
        }

        return array_slice($subtasks, 0, 5); // 最多5个
    }

    /**
     * 解析负责人推荐结果
     */
    private static function parseAssigneeRecommendations(string $text, array $members): array
    {
        $memberMap = [];
        foreach ($members as $m) {
            $memberMap[$m['userid']] = $m;
        }

        $lines = explode("\n", trim($text));
        $recommendations = [];

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\d+[\.\)、]\s*/', '', $line);

            if (preg_match('/^(\d+)\|(.+)$/', $line, $matches)) {
                $userid = intval($matches[1]);
                $reason = trim($matches[2]);

                if (isset($memberMap[$userid])) {
                    $recommendations[] = [
                        'userid' => $userid,
                        'nickname' => $memberMap[$userid]['nickname'],
                        'reason' => $reason,
                    ];
                }
            }
        }

        return array_slice($recommendations, 0, 2); // 最多2个
    }

    /**
     * 通过 Embedding 搜索相似任务
     */
    private static function searchSimilarByEmbedding(array $embedding, int $projectId, int $excludeTaskId): array
    {
        // TODO: 实现向量搜索
        // 当前先返回空数组，后续集成 SeekDB 或其他向量搜索
        return [];
    }

    /**
     * 构建 Markdown 消息
     */
    public static function buildMarkdownMessage(int $taskId, array $suggestions, int $msgId = 0): string
    {
        $parts = ["## AI 任务建议\n"];

        foreach ($suggestions as $suggestion) {
            switch ($suggestion['type']) {
                case 'description':
                    $parts[] = self::buildDescriptionMarkdown($taskId, $msgId, $suggestion['content']);
                    break;
                case 'subtasks':
                    $parts[] = self::buildSubtasksMarkdown($taskId, $msgId, $suggestion['content']);
                    break;
                case 'assignee':
                    $parts[] = self::buildAssigneeMarkdown($taskId, $msgId, $suggestion['content']);
                    break;
                case 'similar':
                    $parts[] = self::buildSimilarMarkdown($taskId, $msgId, $suggestion['content']);
                    break;
            }
        }

        return implode("\n---\n\n", $parts);
    }

    /**
     * 构建描述建议 Markdown
     */
    private static function buildDescriptionMarkdown(int $taskId, int $msgId, string $content): string
    {
        $applyUrl = "dootask://ai-apply/description/{$taskId}/{$msgId}";
        $dismissUrl = "dootask://ai-dismiss/description/{$taskId}/{$msgId}";

        return <<<MD
### 建议补充任务描述

{$content}

[✓ 采纳描述]({$applyUrl})  [✗ 忽略]({$dismissUrl})
MD;
    }

    /**
     * 构建子任务建议 Markdown
     */
    private static function buildSubtasksMarkdown(int $taskId, int $msgId, array $subtasks): string
    {
        $list = '';
        foreach ($subtasks as $i => $name) {
            $num = $i + 1;
            $list .= "{$num}. {$name}\n";
        }

        $applyUrl = "dootask://ai-apply/subtasks/{$taskId}/{$msgId}";
        $dismissUrl = "dootask://ai-dismiss/subtasks/{$taskId}/{$msgId}";

        return <<<MD
### 建议拆分子任务

{$list}
[✓ 创建子任务]({$applyUrl})  [✗ 忽略]({$dismissUrl})
MD;
    }

    /**
     * 构建负责人建议 Markdown
     */
    private static function buildAssigneeMarkdown(int $taskId, int $msgId, array $recommendations): string
    {
        $list = '';
        $buttons = '';
        foreach ($recommendations as $rec) {
            $list .= "- **{$rec['nickname']}** - {$rec['reason']}\n";
            $applyUrl = "dootask://ai-apply/assignee/{$taskId}/{$msgId}?userid={$rec['userid']}";
            $buttons .= "[指派给{$rec['nickname']}]({$applyUrl})  ";
        }

        return <<<MD
### 推荐负责人

{$list}
{$buttons}
MD;
    }

    /**
     * 构建相似任务 Markdown
     */
    private static function buildSimilarMarkdown(int $taskId, int $msgId, array $similarTasks): string
    {
        $list = '';
        foreach ($similarTasks as $i => $st) {
            $num = $i + 1;
            $viewUrl = "dootask://task/{$st['id']}";
            $addUrl = "dootask://ai-apply/similar/{$taskId}/{$msgId}?related={$st['id']}";
            $similarity = round($st['similarity'] * 100);
            $list .= "{$num}. **#{$st['id']} {$st['name']}** - 相似度 {$similarity}%\n";
            $list .= "   [查看任务]({$viewUrl})  [添加关联]({$addUrl})\n\n";
        }

        $dismissUrl = "dootask://ai-dismiss/similar/{$taskId}/{$msgId}";

        return <<<MD
### 发现相似任务

以下任务与当前任务内容相似，可能是重复任务或可作为参考：

{$list}
[全部忽略]({$dismissUrl})
MD;
    }

    /**
     * 发送建议消息
     */
    public static function sendSuggestionMessage(ProjectTask $task, array $suggestions): ?int
    {
        if (empty($suggestions) || empty($task->dialog_id)) {
            return null;
        }

        // 先发送消息获取 msg_id，然后更新消息内容带上 msg_id
        $tempMarkdown = self::buildMarkdownMessage($task->id, $suggestions, 0);

        $result = WebSocketDialogMsg::sendMsg(
            null,
            $task->dialog_id,
            'text',
            ['text' => $tempMarkdown, 'type' => 'md'],
            self::AI_ASSISTANT_USERID,
            true,  // push_self
            false, // push_retry
            true   // push_silence
        );

        if (Base::isSuccess($result)) {
            $msgId = $result['data']->id ?? 0;
            if ($msgId > 0) {
                // 更新消息，带上真实的 msg_id
                $finalMarkdown = self::buildMarkdownMessage($task->id, $suggestions, $msgId);
                WebSocketDialogMsg::sendMsg(
                    'change-' . $msgId,
                    $task->dialog_id,
                    'text',
                    ['text' => $finalMarkdown, 'type' => 'md'],
                    self::AI_ASSISTANT_USERID
                );
                return $msgId;
            }
        }

        return null;
    }

    /**
     * 更新消息状态（采纳/忽略后）
     */
    public static function updateMessageStatus(int $msgId, int $dialogId, string $type, string $status): void
    {
        // 验证消息存在且属于指定对话
        $msg = WebSocketDialogMsg::where('id', $msgId)
            ->where('dialog_id', $dialogId)
            ->first();
        if (!$msg) {
            return;
        }

        $content = $msg->msg['text'] ?? '';
        if (empty($content)) {
            return;
        }

        // 根据状态替换对应的按钮
        $statusText = $status === 'applied' ? '✓ 已采纳' : '✗ 已忽略';

        // 替换对应类型的按钮为状态文字
        $pattern = '/\[.*?\]\(dootask:\/\/ai-(apply|dismiss)\/' . preg_quote($type, '/') . '\/\d+\/\d+[^)]*\)\s*/';
        $newContent = preg_replace($pattern, '', $content);

        // 在对应标题后添加状态
        $sectionTitles = [
            'description' => '### 建议补充任务描述',
            'subtasks' => '### 建议拆分子任务',
            'assignee' => '### 推荐负责人',
            'similar' => '### 发现相似任务',
        ];

        if (isset($sectionTitles[$type])) {
            $title = $sectionTitles[$type];
            $newContent = str_replace($title, $title . "\n\n**{$statusText}**", $newContent);
        }

        // 更新消息
        WebSocketDialogMsg::sendMsg(
            'change-' . $msgId,
            $dialogId,
            'text',
            ['text' => $newContent, 'type' => 'md'],
            self::AI_ASSISTANT_USERID
        );
    }
}
