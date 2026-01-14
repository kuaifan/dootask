<?php

namespace App\Module\Manticore;

use App\Models\ProjectTask;
use App\Models\ProjectTaskContent;
use App\Models\ProjectTaskUser;
use App\Models\ProjectTaskVisibilityUser;
use App\Models\ProjectUser;
use App\Module\Apps;
use App\Module\Base;
use App\Module\AI;
use Illuminate\Support\Facades\Log;

/**
 * Manticore Search 任务搜索类
 *
 * 权限逻辑说明：
 * - visibility = 1: 项目人员可见，通过项目成员计算 allowed_users
 * - visibility = 2: 任务人员可见，通过任务成员计算 allowed_users
 * - visibility = 3: 指定成员可见，通过任务成员 + 可见性成员计算 allowed_users
 * - 子任务继承父任务的 allowed_users
 *
 * 使用方法:
 *
 * 1. 搜索方法
 *    - 搜索任务: search($userid, $keyword, $searchType, $limit);
 *
 * 2. 同步方法
 *    - 单个同步: sync(ProjectTask $task);
 *    - 批量同步: batchSync($tasks);
 *    - 删除索引: delete($taskId);
 *
 * 3. 权限更新方法
 *    - 更新权限: updateAllowedUsers($taskId);
 *    - 项目成员变更级联更新: cascadeUpdateByProject($projectId);
 *    - 父任务变更级联到子任务: cascadeToChildren($taskId);
 *
 * 4. 工具方法
 *    - 清空索引: clear();
 */
class ManticoreTask
{
    /**
     * 最大内容长度（字符）
     */
    public const MAX_CONTENT_LENGTH = 50000; // 50K 字符

    /**
     * 搜索任务（支持全文、向量、混合搜索）
     *
     * @param int $userid 用户ID（权限过滤）
     * @param string $keyword 搜索关键词
     * @param string $searchType 搜索类型: text/vector/hybrid
     * @param int $limit 返回数量
     * @return array 搜索结果
     */
    public static function search(int $userid, string $keyword, string $searchType = 'hybrid', int $limit = 20): array
    {
        if (empty($keyword)) {
            return [];
        }

        if (!Apps::isInstalled("search")) {
            return [];
        }

        try {
            switch ($searchType) {
                case 'text':
                    return self::formatSearchResults(
                        ManticoreBase::taskFullTextSearch($keyword, $userid, $limit, 0)
                    );

                case 'vector':
                    $embedding = ManticoreBase::getEmbedding($keyword);
                    if (empty($embedding)) {
                        return self::formatSearchResults(
                            ManticoreBase::taskFullTextSearch($keyword, $userid, $limit, 0)
                        );
                    }
                    return self::formatSearchResults(
                        ManticoreBase::taskVectorSearch($embedding, $userid, $limit)
                    );

                case 'hybrid':
                default:
                    $embedding = ManticoreBase::getEmbedding($keyword);
                    return self::formatSearchResults(
                        ManticoreBase::taskHybridSearch($keyword, $embedding, $userid, $limit)
                    );
            }
        } catch (\Exception $e) {
            Log::error('Manticore task search error: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * 格式化搜索结果
     *
     * @param array $results Manticore 返回的结果
     * @return array 格式化后的结果
     */
    private static function formatSearchResults(array $results): array
    {
        $formatted = [];
        foreach ($results as $item) {
            $formatted[] = [
                'task_id' => $item['task_id'],
                'id' => $item['task_id'],
                'project_id' => $item['project_id'],
                'userid' => $item['userid'],
                'visibility' => $item['visibility'],
                'name' => $item['task_name'],
                'desc_preview' => isset($item['task_desc']) ? mb_substr($item['task_desc'], 0, 300) : null,
                'content_preview' => isset($item['task_content']) ? mb_substr($item['task_content'], 0, 500) : null,
                'relevance' => $item['relevance'] ?? $item['similarity'] ?? $item['rrf_score'] ?? 0,
            ];
        }
        return $formatted;
    }

    // ==============================
    // 权限计算方法
    // ==============================

    /**
     * 获取任务的 allowed_users 列表
     *
     * 根据 visibility 计算有权限查看此任务的用户列表：
     * - visibility=1: 项目成员
     * - visibility=2: 任务成员（负责人/协作人）
     * - visibility=3: 任务成员 + 可见性指定成员
     * - 子任务: 还需要继承父任务的成员
     *
     * @param ProjectTask $task 任务模型
     * @param int $depth 递归深度（防止无限递归）
     * @param array $visited 已访问的任务ID（防止循环引用）
     * @return array 有权限的用户ID数组
     */
    public static function getAllowedUsers(ProjectTask $task, int $depth = 0, array $visited = []): array
    {
        // 防止无限递归：深度超过10层或循环引用
        if ($depth > 10 || in_array($task->id, $visited)) {
            return [];
        }
        $visited[] = $task->id;

        $userids = [];

        // 1. 根据 visibility 获取基础成员
        if ($task->visibility == 1) {
            // visibility=1: 项目成员
            $userids = ProjectUser::where('project_id', $task->project_id)
                ->pluck('userid')
                ->toArray();
        } else {
            // visibility=2,3: 任务成员（负责人/协作人）
            $userids = ProjectTaskUser::where('task_id', $task->id)
                ->orWhere('task_pid', $task->id)
                ->pluck('userid')
                ->toArray();

            // visibility=3: 加上可见性指定成员
            if ($task->visibility == 3) {
                $visUsers = ProjectTaskVisibilityUser::where('task_id', $task->id)
                    ->pluck('userid')
                    ->toArray();
                $userids = array_merge($userids, $visUsers);
            }
        }

        // 2. 如果是子任务，继承父任务成员
        if ($task->parent_id > 0) {
            $parentTask = ProjectTask::find($task->parent_id);
            if ($parentTask) {
                $parentUsers = self::getAllowedUsers($parentTask, $depth + 1, $visited);
                $userids = array_merge($userids, $parentUsers);
            }
        }

        return array_unique($userids);
    }

    // ==============================
    // 同步方法
    // ==============================

    /**
     * 同步单个任务到 Manticore（含 allowed_users）
     *
     * @param ProjectTask $task 任务模型
     * @param bool $withVector 是否同时生成向量（默认 false，向量由后台任务生成）
     * @return bool 是否成功
     */
    public static function sync(ProjectTask $task, bool $withVector = false): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        // 已归档或已删除的任务不索引
        if ($task->archived_at || $task->deleted_at) {
            return self::delete($task->id);
        }

        try {
            // 获取任务详细内容
            $taskContent = self::getTaskContent($task);

            // 构建用于搜索的文本内容
            $searchableContent = self::buildSearchableContent($task, $taskContent);

            // 只有明确要求时才生成向量（默认不生成，由后台任务处理）
            $embedding = null;
            if ($withVector && !empty($searchableContent) && Apps::isInstalled('ai')) {
                $embeddingResult = ManticoreBase::getEmbedding($searchableContent);
                if (!empty($embeddingResult)) {
                    $embedding = '[' . implode(',', $embeddingResult) . ']';
                }
            }

            // 获取任务的 allowed_users
            $allowedUsers = self::getAllowedUsers($task);

            // 写入 Manticore（含 allowed_users）
            $result = ManticoreBase::upsertTaskVector([
                'task_id' => $task->id,
                'project_id' => $task->project_id ?? 0,
                'userid' => $task->userid ?? 0,
                'visibility' => $task->visibility ?? 1,
                'task_name' => $task->name ?? '',
                'task_desc' => $task->desc ?? '',
                'task_content' => $taskContent,
                'content_vector' => $embedding,
                'allowed_users' => $allowedUsers,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Manticore task sync error: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'task_name' => $task->name,
            ]);
            return false;
        }
    }

    /**
     * 获取任务详细内容
     *
     * @param ProjectTask $task 任务模型
     * @return string 任务内容
     */
    private static function getTaskContent(ProjectTask $task): string
    {
        try {
            $content = ProjectTaskContent::where('task_id', $task->id)->first();
            if (!$content) {
                return '';
            }

            // 解析内容
            $contentData = Base::json2array($content->content);
            $text = '';

            // 提取文本内容（内容可能是 blocks 格式）
            if (is_array($contentData)) {
                $text = self::extractTextFromContent($contentData);
            } elseif (is_string($contentData)) {
                $text = $contentData;
            }

            // 限制内容长度
            return mb_substr($text, 0, self::MAX_CONTENT_LENGTH);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * 从内容数组中提取文本
     *
     * @param array $contentData 内容数据
     * @return string 提取的文本
     */
    private static function extractTextFromContent(array $contentData): string
    {
        $texts = [];

        // 处理 blocks 格式
        if (isset($contentData['blocks']) && is_array($contentData['blocks'])) {
            foreach ($contentData['blocks'] as $block) {
                if (isset($block['text'])) {
                    $texts[] = $block['text'];
                }
                if (isset($block['data']['text'])) {
                    $texts[] = $block['data']['text'];
                }
            }
        }

        // 处理其他格式
        if (isset($contentData['text'])) {
            $texts[] = $contentData['text'];
        }

        return implode(' ', $texts);
    }

    /**
     * 构建可搜索的文本内容
     *
     * @param ProjectTask $task 任务模型
     * @param string $taskContent 任务详细内容
     * @return string 可搜索的文本
     */
    private static function buildSearchableContent(ProjectTask $task, string $taskContent): string
    {
        $parts = [];

        if (!empty($task->name)) {
            $parts[] = $task->name;
        }
        if (!empty($task->desc)) {
            $parts[] = $task->desc;
        }
        if (!empty($taskContent)) {
            $parts[] = $taskContent;
        }

        return implode(' ', $parts);
    }

    /**
     * 批量同步任务
     *
     * @param iterable $tasks 任务列表
     * @param bool $withVector 是否同时生成向量
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $tasks, bool $withVector = false): int
    {
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        $count = 0;
        foreach ($tasks as $task) {
            if (self::sync($task, $withVector)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 删除任务索引
     *
     * @param int $taskId 任务ID
     * @return bool 是否成功
     */
    public static function delete(int $taskId): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        return ManticoreBase::deleteTaskVector($taskId);
    }

    /**
     * 清空所有索引
     *
     * @return bool 是否成功
     */
    public static function clear(): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        return ManticoreBase::clearAllTaskVectors();
    }

    /**
     * 获取已索引任务数量
     *
     * @return int 数量
     */
    public static function getIndexedCount(): int
    {
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        return ManticoreBase::getIndexedTaskCount();
    }

    // ==============================
    // 权限更新方法
    // ==============================

    /**
     * 更新任务的 allowed_users 权限列表
     * 重新计算并更新 Manticore 中的权限
     *
     * @param int $taskId 任务ID
     * @return bool 是否成功
     */
    public static function updateAllowedUsers(int $taskId): bool
    {
        if (!Apps::isInstalled("search") || $taskId <= 0) {
            return false;
        }

        try {
            $task = ProjectTask::find($taskId);
            if (!$task) {
                return false;
            }

            $userids = self::getAllowedUsers($task);
            return ManticoreBase::updateTaskAllowedUsers($taskId, $userids);
        } catch (\Exception $e) {
            Log::error('Manticore updateAllowedUsers error: ' . $e->getMessage(), ['task_id' => $taskId]);
            return false;
        }
    }

    /**
     * 级联更新项目下所有 visibility=1 任务的 allowed_users
     * 当项目成员变更时调用
     *
     * @param int $projectId 项目ID
     * @return int 更新的任务数量
     */
    public static function cascadeUpdateByProject(int $projectId): int
    {
        if (!Apps::isInstalled("search") || $projectId <= 0) {
            return 0;
        }

        try {
            // 获取项目成员
            $projectUsers = ProjectUser::where('project_id', $projectId)
                ->pluck('userid')
                ->toArray();

            // 分批更新该项目下所有 visibility=1 的任务
            $count = 0;
            ProjectTask::where('project_id', $projectId)
                ->where('visibility', 1)
                ->whereNull('deleted_at')
                ->whereNull('archived_at')
                ->chunk(100, function ($tasks) use ($projectUsers, &$count) {
                    foreach ($tasks as $task) {
                        // 对于子任务，需要合并父任务成员
                        $allowedUsers = $projectUsers;
                        if ($task->parent_id > 0) {
                            $parentTask = ProjectTask::find($task->parent_id);
                            if ($parentTask) {
                                $parentUsers = self::getAllowedUsers($parentTask);
                                $allowedUsers = array_unique(array_merge($allowedUsers, $parentUsers));
                            }
                        }
                        
                        ManticoreBase::updateTaskAllowedUsers($task->id, $allowedUsers);
                        $count++;
                    }
                });

            return $count;
        } catch (\Exception $e) {
            Log::error('Manticore cascadeUpdateByProject error: ' . $e->getMessage(), ['project_id' => $projectId]);
            return 0;
        }
    }

    /**
     * 级联更新所有子任务的 allowed_users
     * 当父任务的成员变更时调用
     *
     * @param int $taskId 父任务ID
     * @return void
     */
    public static function cascadeToChildren(int $taskId): void
    {
        if (!Apps::isInstalled("search") || $taskId <= 0) {
            return;
        }

        try {
            ProjectTask::where('parent_id', $taskId)
                ->whereNull('deleted_at')
                ->whereNull('archived_at')
                ->each(function ($child) {
                    $allowedUsers = self::getAllowedUsers($child);
                    ManticoreBase::updateTaskAllowedUsers($child->id, $allowedUsers);
                    // 递归处理子任务的子任务
                    self::cascadeToChildren($child->id);
                });
        } catch (\Exception $e) {
            Log::error('Manticore cascadeToChildren error: ' . $e->getMessage(), ['task_id' => $taskId]);
        }
    }

    // ==============================
    // 批量向量生成方法
    // ==============================

    /**
     * 批量生成任务向量
     * 用于后台异步处理，将已索引任务的向量批量生成
     *
     * @param array $taskIds 任务ID数组
     * @param int $batchSize 每批 embedding 数量（默认20）
     * @return int 成功处理的数量
     */
    public static function generateVectorsBatch(array $taskIds, int $batchSize = 20): int
    {
        if (!Apps::isInstalled("search") || !Apps::isInstalled("ai") || empty($taskIds)) {
            return 0;
        }

        try {
            // 1. 查询任务信息
            $tasks = ProjectTask::whereIn('id', $taskIds)
                ->whereNull('deleted_at')
                ->whereNull('archived_at')
                ->get();

            if ($tasks->isEmpty()) {
                return 0;
            }

            // 2. 提取每个任务的内容
            $taskContents = [];
            foreach ($tasks as $task) {
                $taskContent = self::getTaskContent($task);
                $searchableContent = self::buildSearchableContent($task, $taskContent);
                if (!empty($searchableContent)) {
                    // 限制内容长度
                    $searchableContent = mb_substr($searchableContent, 0, self::MAX_CONTENT_LENGTH);
                    $taskContents[$task->id] = $searchableContent;
                }
            }

            if (empty($taskContents)) {
                return 0;
            }

            // 3. 分批处理
            $successCount = 0;
            $chunks = array_chunk($taskContents, $batchSize, true);

            foreach ($chunks as $chunk) {
                $texts = array_values($chunk);
                $ids = array_keys($chunk);

                // 4. 批量获取 embedding
                $result = AI::getBatchEmbeddings($texts);
                if (!Base::isSuccess($result) || empty($result['data'])) {
                    continue;
                }

                $embeddings = $result['data'];

                // 5. 构建批量更新数据
                $vectorData = [];
                foreach ($ids as $index => $taskId) {
                    if (!isset($embeddings[$index]) || empty($embeddings[$index])) {
                        continue;
                    }
                    $vectorData[$taskId] = '[' . implode(',', $embeddings[$index]) . ']';
                }

                // 6. 批量更新向量
                if (!empty($vectorData)) {
                    $batchCount = ManticoreBase::batchUpdateTaskVectors($vectorData);
                    $successCount += $batchCount;
                }
            }

            return $successCount;
        } catch (\Exception $e) {
            Log::error('ManticoreTask generateVectorsBatch error: ' . $e->getMessage());
            return 0;
        }
    }
}
