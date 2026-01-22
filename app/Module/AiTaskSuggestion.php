<?php

namespace App\Module;

use App\Models\ProjectTask;
use App\Models\ProjectTaskAiEvent;
use App\Models\ProjectTaskUser;
use App\Models\ProjectUser;
use App\Models\User;
use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Module\Apps;
use App\Module\Manticore\ManticoreBase;
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
    const SIMILAR_THRESHOLD = 0.5;

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
                // 无子任务且标题长度 > 5
                $hasSubtasks = ProjectTask::where('parent_id', $task->id)->exists();
                return !$hasSubtasks && mb_strlen($task->name) > 5;

            case ProjectTaskAiEvent::EVENT_ASSIGNEE:
                // 未指定负责人
                $hasOwner = ProjectTaskUser::where('task_id', $task->id)->where('owner', 1)->exists();
                return !$hasOwner;

            case ProjectTaskAiEvent::EVENT_SIMILAR:
                // 需要安装 search 插件才能使用向量搜索
                return Apps::isInstalled('search');

            default:
                return false;
        }
    }

    /**
     * 生成任务描述建议
     * @param ProjectTask $task 任务对象
     */
    public static function generateDescription(ProjectTask $task): ?array
    {
        $language = self::getUserLanguageInfo($task->userid)['name'];
        $prompt = self::buildDescriptionPrompt($task, $language);
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
     * @param ProjectTask $task 任务对象
     */
    public static function generateSubtasks(ProjectTask $task): ?array
    {
        $language = self::getUserLanguageInfo($task->userid)['name'];
        $prompt = self::buildSubtasksPrompt($task, $language);
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
     * @param ProjectTask $task 任务对象
     */
    public static function generateAssignee(ProjectTask $task): ?array
    {
        // 获取当前任务已有的成员（负责人和协助人）
        $existingUserIds = ProjectTaskUser::where('task_id', $task->id)
            ->pluck('userid')
            ->toArray();

        // 获取项目成员，排除已有任务成员
        $members = self::getProjectMembersInfo($task->project_id);
        $members = array_filter($members, function ($member) use ($existingUserIds) {
            return !in_array($member['userid'], $existingUserIds);
        });
        $members = array_values($members); // 重新索引

        if (empty($members)) {
            return null;
        }

        $language = self::getUserLanguageInfo($task->userid)['name'];
        $prompt = self::buildAssigneePrompt($task, $members, $language);
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
     * @param ProjectTask $task 任务对象
     */
    public static function findSimilarTasks(ProjectTask $task): ?array
    {
        // 使用 AI 模块的 Embedding 搜索
        $searchText = $task->name;
        if (empty($searchText)) {
            return null;
        }

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

            // 获取用户语言对应的文案
            $lang = self::getUserLanguageInfo($task->userid)['code'];

            return [
                'type' => 'similar',
                'lang' => $lang,
                'content' => $similarTasks,
            ];
        } catch (\Exception $e) {
            \Log::error('AiTaskSuggestion::findSimilarTasks error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * 获取用户语言信息
     * @param int $userid 用户ID
     * @return array ['code' => 语言代码, 'name' => 语言名称]
     */
    private static function getUserLanguageInfo(int $userid): array
    {
        $user = User::find($userid);
        $code = $user->lang ?? 'zh';
        $name = Doo::getLanguages($code) ?: '简体中文';
        return ['code' => $code, 'name' => $name];
    }

    /**
     * 获取多语言标题和提示文案
     * @param string $lang 语言代码
     * @return array
     */
    private static function getLocalizedTitles(string $lang): array
    {
        $titles = [
            'zh' => [
                'description' => '建议补充任务描述',
                'subtasks' => '建议拆分子任务',
                'assignee' => '推荐负责人',
                'similar' => '发现相似任务',
                'similar_hint' => '以下任务与当前任务内容相似，可能是重复任务或可作为参考：',
            ],
            'zh-CHT' => [
                'description' => '建議補充任務描述',
                'subtasks' => '建議拆分子任務',
                'assignee' => '推薦負責人',
                'similar' => '發現相似任務',
                'similar_hint' => '以下任務與當前任務內容相似，可能是重複任務或可作為參考：',
            ],
            'en' => [
                'description' => 'Suggested Task Description',
                'subtasks' => 'Suggested Subtasks',
                'assignee' => 'Recommended Assignee',
                'similar' => 'Similar Tasks Found',
                'similar_hint' => 'The following tasks are similar and may be duplicates or references:',
            ],
            'ko' => [
                'description' => '작업 설명 추가 제안',
                'subtasks' => '하위 작업 분할 제안',
                'assignee' => '추천 담당자',
                'similar' => '유사한 작업 발견',
                'similar_hint' => '다음 작업은 현재 작업과 유사하며 중복되거나 참고할 수 있습니다:',
            ],
            'ja' => [
                'description' => 'タスク説明の追加を提案',
                'subtasks' => 'サブタスクの分割を提案',
                'assignee' => '推奨担当者',
                'similar' => '類似タスクを発見',
                'similar_hint' => '以下のタスクは現在のタスクと類似しており、重複している可能性があります：',
            ],
            'de' => [
                'description' => 'Vorgeschlagene Aufgabenbeschreibung',
                'subtasks' => 'Vorgeschlagene Unteraufgaben',
                'assignee' => 'Empfohlener Verantwortlicher',
                'similar' => 'Ähnliche Aufgaben gefunden',
                'similar_hint' => 'Die folgenden Aufgaben sind ähnlich und könnten Duplikate oder Referenzen sein:',
            ],
            'fr' => [
                'description' => 'Description de tâche suggérée',
                'subtasks' => 'Sous-tâches suggérées',
                'assignee' => 'Responsable recommandé',
                'similar' => 'Tâches similaires trouvées',
                'similar_hint' => 'Les tâches suivantes sont similaires et peuvent être des doublons ou des références:',
            ],
            'id' => [
                'description' => 'Saran Deskripsi Tugas',
                'subtasks' => 'Saran Pembagian Subtugas',
                'assignee' => 'Penanggung Jawab yang Direkomendasikan',
                'similar' => 'Tugas Serupa Ditemukan',
                'similar_hint' => 'Tugas berikut mirip dengan tugas saat ini dan mungkin duplikat atau referensi:',
            ],
            'ru' => [
                'description' => 'Предлагаемое описание задачи',
                'subtasks' => 'Предлагаемые подзадачи',
                'assignee' => 'Рекомендуемый ответственный',
                'similar' => 'Найдены похожие задачи',
                'similar_hint' => 'Следующие задачи похожи на текущую и могут быть дубликатами или справочными:',
            ],
        ];

        return $titles[$lang] ?? $titles['zh'];
    }

    /**
     * 转义用户输入以防止 Prompt 注入
     */
    private static function escapeUserInput(string $input, int $length = 500): string
    {
        // 移除可能影响 AI Prompt 解析的特殊字符
        $input = str_replace(['```', '---', '==='], '', $input);
        // 截断过长的输入
        return mb_substr(trim($input), 0, $length);
    }

    /**
     * 构建描述生成 Prompt
     * @param ProjectTask $task 任务对象
     * @param string $language 输出语言名称
     */
    private static function buildDescriptionPrompt(ProjectTask $task, string $language): string
    {
        $taskName = self::escapeUserInput($task->name, 100);
        $projectName = self::escapeUserInput($task->project->name ?? '未知项目', 100);
        $columnName = self::escapeUserInput($task->projectColumn->name ?? '未知栏目', 50);

        return <<<PROMPT
            你是一名任务规划助手，擅长根据任务标题推断并补充任务描述。

            所属项目：{$projectName}
            所属栏目：{$columnName}
            任务标题：{$taskName}

            你的任务：
            根据标题、项目和栏目信息，推断任务意图并生成实用的任务描述。

            生成原则：
            1. 基于标题关键词和上下文进行合理推断，内容要具体、可执行
            2. 使用 Markdown 格式，根据任务性质灵活组织结构（可包含目标、要求、验收标准等）
            3. 简单任务保持简洁，复杂任务可适当展开，避免空泛的套话

            输出语言：与任务标题的语言保持一致，如无法确定则使用{$language}

            输出要求：
            - 仅返回 Markdown 格式的描述内容
            - 禁止输出额外说明、引导语或与任务无关的内容
            PROMPT;
    }

    /**
     * 构建子任务拆分 Prompt
     * @param ProjectTask $task 任务对象
     * @param string $language 输出语言名称
     */
    private static function buildSubtasksPrompt(ProjectTask $task, string $language): string
    {
        $taskName = self::escapeUserInput($task->name, 100);
        $projectName = self::escapeUserInput($task->project->name ?? '未知项目', 100);
        $columnName = self::escapeUserInput($task->projectColumn->name ?? '未知栏目', 50);
        $content = self::escapeUserInput($task->content ?? '');

        return <<<PROMPT
            你是一名任务拆解助手，擅长将复杂任务分解为可执行的子任务。

            所属项目：{$projectName}
            所属栏目：{$columnName}
            任务标题：{$taskName}
            任务描述：{$content}

            你的任务：
            分析任务内容，拆解出关键的执行步骤作为子任务。

            拆解原则：
            1. 每个子任务聚焦单一可执行动作，避免含糊或重复
            2. 根据任务复杂度灵活决定数量（通常 2-5 个），简单任务少拆，复杂任务多拆
            3. 子任务之间保持合理的执行顺序或逻辑关系
            4. 子任务名称简洁明了，控制在 8-30 个字符内

            输出语言：与任务标题的语言保持一致，如无法确定则使用{$language}

            输出格式：
            1. [子任务名称]
            2. [子任务名称]
            ...

            输出要求：
            - 仅返回子任务列表，禁止输出额外说明或引导语
            PROMPT;
    }

    /**
     * 构建负责人推荐 Prompt
     * @param ProjectTask $task 任务对象
     * @param array $members 成员列表
     * @param string $language 输出语言名称
     */
    private static function buildAssigneePrompt(ProjectTask $task, array $members, string $language): string
    {
        $taskName = self::escapeUserInput($task->name, 100);
        $projectName = self::escapeUserInput($task->project->name ?? '未知项目', 100);
        $columnName = self::escapeUserInput($task->projectColumn->name ?? '未知栏目', 50);
        $taskContent = self::escapeUserInput($task->content ?? '');

        $membersText = '';
        foreach ($members as $member) {
            $nickname = self::escapeUserInput($member['nickname'], 20);
            $membersText .= "- {$nickname}（ID:{$member['userid']}）";
            if (!empty($member['profession'])) {
                $profession = self::escapeUserInput($member['profession'], 50);
                $membersText .= "，职位：{$profession}";
            }
            $membersText .= "，进行中：{$member['in_progress_count']}个";
            $membersText .= "，近期完成：{$member['completed_count']}个";
            $membersText .= "\n";
        }

        return <<<PROMPT
            你是一名任务分配助手，根据任务内容和成员情况推荐合适的负责人。

            所属项目：{$projectName}
            所属栏目：{$columnName}
            任务标题：{$taskName}
            任务描述：{$taskContent}

            可选成员：
            {$membersText}

            推荐原则：
            1. 分析任务内容，匹配成员职位或专业方向
            2. 优先推荐进行中任务较少的成员，平衡工作负载
            3. 近期完成任务多说明执行力强，可作为参考

            输出语言：推荐理由的语言与任务标题保持一致，如无法确定则使用{$language}

            输出格式：
            1. [userid]|[推荐理由]
            2. [userid]|[推荐理由]

            输出要求：
            - 推荐 1-2 名最合适的负责人，按优先级排序
            - 推荐理由需具体说明为何此人适合该任务，不超过 20 字
            - 仅返回推荐列表，禁止输出额外说明
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

        $addedUserIds = []; // 记录已添加的用户ID，防止重复

        foreach ($lines as $line) {
            $line = trim($line);
            $line = preg_replace('/^\d+[\.\)、]\s*/', '', $line);

            if (preg_match('/^(\d+)\|(.+)$/', $line, $matches)) {
                $userid = intval($matches[1]);
                $reason = trim($matches[2]);

                // 跳过已添加的用户
                if (in_array($userid, $addedUserIds)) {
                    continue;
                }

                if (isset($memberMap[$userid])) {
                    $recommendations[] = [
                        'userid' => $userid,
                        'nickname' => $memberMap[$userid]['nickname'],
                        'reason' => $reason,
                    ];
                    $addedUserIds[] = $userid;
                }
            }
        }

        return array_slice($recommendations, 0, 2); // 最多2个
    }

    /**
     * 通过 Embedding 搜索相似任务
     *
     * @param array $embedding 任务内容的向量表示
     * @param int $projectId 项目ID（用于过滤同项目任务）
     * @param int $excludeTaskId 排除的任务ID（当前任务）
     * @return array 相似任务列表
     */
    private static function searchSimilarByEmbedding(array $embedding, int $projectId, int $excludeTaskId): array
    {
        if (empty($embedding)) {
            return [];
        }

        try {
            // 使用 ManticoreBase 进行向量搜索
            // userid=0 跳过权限过滤，我们通过 project_id 过滤
            $results = ManticoreBase::taskVectorSearch($embedding, 0, 200);

            if (empty($results)) {
                return [];
            }

            // 获取当前任务的子任务ID列表
            $childTaskIds = ProjectTask::where('parent_id', $excludeTaskId)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();

            // 过滤：同项目、排除当前任务及其子任务、相似度阈值
            $similarTasks = [];
            foreach ($results as $item) {
                // 过滤不同项目的任务
                if ($item['project_id'] != $projectId) {
                    continue;
                }

                // 排除当前任务
                if ($item['task_id'] == $excludeTaskId) {
                    continue;
                }

                // 排除子任务
                if (in_array($item['task_id'], $childTaskIds)) {
                    continue;
                }

                // 相似度阈值
                $similarity = $item['similarity'] ?? 0;
                if ($similarity < self::SIMILAR_THRESHOLD) {
                    continue;
                }

                $similarTasks[] = [
                    'task_id' => $item['task_id'],
                    'name' => $item['task_name'] ?? '',
                    'similarity' => round($similarity, 2),
                ];

                // 最多返回 5 个相似任务
                if (count($similarTasks) >= 5) {
                    break;
                }
            }

            return $similarTasks;
        } catch (\Exception $e) {
            \Log::error('searchSimilarByEmbedding error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 构建 Markdown 消息
     * @param int $taskId 任务ID
     * @param array $suggestions 建议列表
     * @param int $msgId 消息ID
     * @param string $lang 语言代码
     */
    public static function buildMarkdownMessage(int $taskId, array $suggestions, int $msgId = 0, string $lang = 'zh'): string
    {
        $parts = [];
        $titles = self::getLocalizedTitles($lang);

        foreach ($suggestions as $suggestion) {
            // 如果 suggestion 中有 lang，使用它（similar 类型）
            $suggestionLang = $suggestion['lang'] ?? $lang;
            $suggestionTitles = ($suggestionLang !== $lang) ? self::getLocalizedTitles($suggestionLang) : $titles;

            switch ($suggestion['type']) {
                case 'description':
                    $parts[] = self::buildDescriptionMarkdown($taskId, $msgId, $suggestion['content'], $suggestionTitles);
                    break;
                case 'subtasks':
                    $parts[] = self::buildSubtasksMarkdown($taskId, $msgId, $suggestion['content'], $suggestionTitles);
                    break;
                case 'assignee':
                    $parts[] = self::buildAssigneeMarkdown($taskId, $msgId, $suggestion['content'], $suggestionTitles);
                    break;
                case 'similar':
                    $parts[] = self::buildSimilarMarkdown($taskId, $msgId, $suggestion['content'], $suggestionTitles);
                    break;
            }
        }

        return implode("\n\n---\n\n", $parts);
    }

    /**
     * 构建描述建议 Markdown
     * @param int $taskId 任务ID
     * @param int $msgId 消息ID
     * @param string $content 描述内容
     * @param array $titles 本地化标题
     */
    private static function buildDescriptionMarkdown(int $taskId, int $msgId, string $content, array $titles): string
    {
        $title = $titles['description'];
        return <<<MD
### {$title}

{$content}

:::ai-action{type="description" task="{$taskId}" msg="{$msgId}"}:::
MD;
    }

    /**
     * 构建子任务建议 Markdown
     * @param int $taskId 任务ID
     * @param int $msgId 消息ID
     * @param array $subtasks 子任务列表
     * @param array $titles 本地化标题
     */
    private static function buildSubtasksMarkdown(int $taskId, int $msgId, array $subtasks, array $titles): string
    {
        $title = $titles['subtasks'];
        $list = '';
        foreach ($subtasks as $i => $name) {
            $num = $i + 1;
            $list .= "{$num}. {$name}\n";
        }

        return <<<MD
### {$title}

{$list}
:::ai-action{type="subtasks" task="{$taskId}" msg="{$msgId}"}:::
MD;
    }

    /**
     * 构建负责人建议 Markdown
     * @param int $taskId 任务ID
     * @param int $msgId 消息ID
     * @param array $recommendations 推荐列表
     * @param array $titles 本地化标题
     */
    private static function buildAssigneeMarkdown(int $taskId, int $msgId, array $recommendations, array $titles): string
    {
        $title = $titles['assignee'];
        $list = '';
        foreach ($recommendations as $rec) {
            $stUserId = $rec['userid'];
            $viewUrl = "dootask://contact/{$stUserId}";
            $list .= "- **[{$rec['nickname']}]({$viewUrl})** - {$rec['reason']} :::ai-action{type=\"assignee\" task=\"{$taskId}\" msg=\"{$msgId}\" userid=\"{$stUserId}\"}:::\n";
        }

        return <<<MD
### {$title}

{$list}
MD;
    }

    /**
     * 构建相似任务 Markdown
     * @param int $taskId 任务ID
     * @param int $msgId 消息ID
     * @param array $similarTasks 相似任务列表
     * @param array $titles 本地化标题
     */
    private static function buildSimilarMarkdown(int $taskId, int $msgId, array $similarTasks, array $titles): string
    {
        $title = $titles['similar'];
        $hint = $titles['similar_hint'];
        $list = '';
        foreach ($similarTasks as $i => $st) {
            $num = $i + 1;
            $stTaskId = $st['task_id'];
            $viewUrl = "dootask://task/{$stTaskId}";
            $list .= "{$num}. **[#{$stTaskId}]({$viewUrl})** {$st['name']} :::ai-action{type=\"similar\" task=\"{$taskId}\" msg=\"{$msgId}\" related=\"{$stTaskId}\"}:::\n";
        }

        return <<<MD
### {$title}

{$hint}

{$list}
MD;
    }

    /**
     * 发送建议消息
     * @param ProjectTask $task 任务对象
     * @param array $suggestions 建议列表
     */
    public static function sendSuggestionMessage(ProjectTask $task, array $suggestions): ?int
    {
        if (empty($suggestions)) {
            return null;
        }

        // 如果任务没有对话，自动创建
        if (!$task->dialog_id) {
            $dialog = WebSocketDialog::createGroup($task->name, $task->relationUserids(), 'task');
            if ($dialog) {
                $task->dialog_id = $dialog->id;
                $task->save();
                $task->pushMsg('dialog');
            } else {
                return null;
            }
        }

        // 获取用户语言
        $lang = self::getUserLanguageInfo($task->userid)['code'];

        // 先发送消息获取 msg_id，然后更新消息内容带上 msg_id
        $tempMarkdown = self::buildMarkdownMessage($task->id, $suggestions, 0, $lang);
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
        if (Base::isError($result)) {
            return null;
        }
        $msgId = $result['data']->id ?? 0;
        if (empty($msgId)) {
            return null;
        }

        // 更新消息，带上真实的 msg_id
        $finalMarkdown = self::buildMarkdownMessage($task->id, $suggestions, $msgId, $lang);
        WebSocketDialogMsg::sendMsg(
            'change-' . $msgId,
            $task->dialog_id,
            'text',
            ['text' => $finalMarkdown, 'type' => 'md'],
            self::AI_ASSISTANT_USERID,
            true,  // push_self
        );
        return $msgId;
    }

    /**
     * 更新消息状态（采纳/忽略后）
     *
     * @param int $msgId 消息ID
     * @param int $dialogId 对话ID
     * @param string $type 建议类型
     * @param string $status 状态：applied/dismissed
     * @param int $userid 用户ID（assignee类型单独处理时使用）
     * @param int $related 关联任务ID（similar类型单独处理时使用）
     * @return array 更新后的消息数据
     */
    public static function updateMessageStatus(int $msgId, int $dialogId, string $type, string $status, int $userid = 0, int $related = 0): array
    {
        // 验证消息存在且属于指定对话
        $msg = WebSocketDialogMsg::where('id', $msgId)
            ->where('dialog_id', $dialogId)
            ->first();
        if (!$msg) {
            return Base::retError('消息不存在');
        }

        $content = $msg->msg['text'] ?? '';
        if (empty($content)) {
            return Base::retError('消息内容为空');
        }

        // 根据类型和参数构建匹配模式，添加 status 属性
        if ($type === 'assignee' && $userid > 0) {
            $pattern = '/(:::ai-action\{type="assignee"[^}]*userid="' . $userid . '"[^}]*)\}:::/';
        } elseif ($type === 'similar' && $related > 0) {
            $pattern = '/(:::ai-action\{type="similar"[^}]*related="' . $related . '"[^}]*)\}:::/';
        } else {
            $pattern = '/(:::ai-action\{type="' . preg_quote($type, '/') . '"[^}]*)\}:::/';
        }

        $newContent = preg_replace($pattern, '$1 status="' . $status . '"}:::', $content);

        // 更新消息并返回结果
        return WebSocketDialogMsg::sendMsg(
            'change-' . $msgId,
            $dialogId,
            'text',
            ['text' => $newContent, 'type' => 'md'],
            self::AI_ASSISTANT_USERID
        );
    }
}
