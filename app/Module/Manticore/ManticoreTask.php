<?php

namespace App\Module\Manticore;

use App\Models\ProjectTask;
use App\Models\ProjectTaskContent;
use App\Models\ProjectTaskUser;
use App\Models\ProjectTaskVisibilityUser;
use App\Module\Apps;
use App\Module\Base;
use App\Module\AI;
use Illuminate\Support\Facades\Log;

/**
 * Manticore Search 任务搜索类
 *
 * 权限逻辑说明：
 * - visibility = 1: 项目人员可见，通过 project_users 表过滤
 * - visibility = 2: 任务人员可见，通过 task_users 表过滤（ProjectTaskUser）
 * - visibility = 3: 指定成员可见，通过 task_users 表过滤（ProjectTaskUser + ProjectTaskVisibilityUser）
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
 * 3. 成员关系方法
 *    - 添加成员: addTaskUser($taskId, $userid);
 *    - 删除成员: removeTaskUser($taskId, $userid);
 *    - 同步所有成员: syncTaskUsers($taskId);
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

        if (!Apps::isInstalled("manticore")) {
            return [];
        }

        try {
            switch ($searchType) {
                case 'text':
                    return self::formatSearchResults(
                        ManticoreBase::taskFullTextSearch($keyword, $userid, $limit, 0)
                    );

                case 'vector':
                    $embedding = self::getEmbedding($keyword);
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
                    $embedding = self::getEmbedding($keyword);
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
     * 获取文本的 Embedding 向量
     *
     * @param string $text 文本
     * @return array 向量数组（空数组表示失败）
     */
    private static function getEmbedding(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        try {
            $result = AI::getEmbedding($text);
            if (Base::isSuccess($result)) {
                return $result['data'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Get embedding error: ' . $e->getMessage());
        }

        return [];
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
                'desc_preview' => $item['task_desc_preview'] ?? null,
                'content_preview' => $item['task_content_preview'] ?? null,
                'relevance' => $item['relevance'] ?? $item['similarity'] ?? $item['rrf_score'] ?? 0,
            ];
        }
        return $formatted;
    }

    // ==============================
    // 同步方法
    // ==============================

    /**
     * 同步单个任务到 Manticore
     *
     * @param ProjectTask $task 任务模型
     * @return bool 是否成功
     */
    public static function sync(ProjectTask $task): bool
    {
        if (!Apps::isInstalled("manticore")) {
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

            // 获取 embedding（如果 AI 可用）
            $embedding = null;
            if (!empty($searchableContent) && Apps::isInstalled('ai')) {
                $embeddingResult = self::getEmbedding($searchableContent);
                if (!empty($embeddingResult)) {
                    $embedding = '[' . implode(',', $embeddingResult) . ']';
                }
            }

            // 写入 Manticore
            $result = ManticoreBase::upsertTaskVector([
                'task_id' => $task->id,
                'project_id' => $task->project_id ?? 0,
                'userid' => $task->userid ?? 0,
                'visibility' => $task->visibility ?? 1,
                'task_name' => $task->name ?? '',
                'task_desc' => $task->desc ?? '',
                'task_content' => $taskContent,
                'content_vector' => $embedding,
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
            Log::warning('Get task content error: ' . $e->getMessage(), ['task_id' => $task->id]);
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
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $tasks): int
    {
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        $count = 0;
        foreach ($tasks as $task) {
            if (self::sync($task)) {
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
        if (!Apps::isInstalled("manticore")) {
            return false;
        }

        // 删除任务索引
        ManticoreBase::deleteTaskVector($taskId);
        // 删除任务成员关系
        ManticoreBase::deleteAllTaskUsers($taskId);

        return true;
    }

    /**
     * 更新任务可见性
     *
     * @param int $taskId 任务ID
     * @param int $visibility 可见性
     * @return bool 是否成功
     */
    public static function updateVisibility(int $taskId, int $visibility): bool
    {
        if (!Apps::isInstalled("manticore") || $taskId <= 0) {
            return false;
        }

        return ManticoreBase::updateTaskVisibility($taskId, $visibility);
    }

    /**
     * 清空所有索引
     *
     * @return bool 是否成功
     */
    public static function clear(): bool
    {
        if (!Apps::isInstalled("manticore")) {
            return false;
        }

        ManticoreBase::clearAllTaskVectors();
        ManticoreBase::clearAllTaskUsers();

        return true;
    }

    /**
     * 获取已索引任务数量
     *
     * @return int 数量
     */
    public static function getIndexedCount(): int
    {
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        return ManticoreBase::getIndexedTaskCount();
    }

    // ==============================
    // 成员关系方法
    // ==============================

    /**
     * 添加任务成员到 Manticore
     *
     * @param int $taskId 任务ID
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function addTaskUser(int $taskId, int $userid): bool
    {
        if (!Apps::isInstalled("manticore") || $taskId <= 0 || $userid <= 0) {
            return false;
        }

        return ManticoreBase::upsertTaskUser($taskId, $userid);
    }

    /**
     * 删除任务成员
     *
     * @param int $taskId 任务ID
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function removeTaskUser(int $taskId, int $userid): bool
    {
        if (!Apps::isInstalled("manticore") || $taskId <= 0 || $userid <= 0) {
            return false;
        }

        return ManticoreBase::deleteTaskUser($taskId, $userid);
    }

    /**
     * 删除指定可见成员（visibility=3 场景）
     *
     * 特殊处理：需要检查该用户是否仍是任务的负责人/协作人
     * 如果是，则不应该从 task_users 中删除
     *
     * @param int $taskId 任务ID
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function removeVisibilityUser(int $taskId, int $userid): bool
    {
        if (!Apps::isInstalled("manticore") || $taskId <= 0 || $userid <= 0) {
            return false;
        }

        try {
            // 检查用户是否仍是任务的负责人/协作人
            $isTaskMember = ProjectTaskUser::where('task_id', $taskId)
                ->where('userid', $userid)
                ->exists();

            // 检查是否是父任务的成员（子任务场景）
            $task = \App\Models\ProjectTask::find($taskId);
            $isParentTaskMember = false;
            if ($task && $task->parent_id > 0) {
                $isParentTaskMember = ProjectTaskUser::where('task_id', $task->parent_id)
                    ->where('userid', $userid)
                    ->exists();
            }

            // 如果仍是任务成员，不删除
            if ($isTaskMember || $isParentTaskMember) {
                return true;
            }

            // 从 Manticore 删除
            return ManticoreBase::deleteTaskUser($taskId, $userid);
        } catch (\Exception $e) {
            Log::error('Manticore removeVisibilityUser error: ' . $e->getMessage(), [
                'task_id' => $taskId,
                'userid' => $userid,
            ]);
            return false;
        }
    }

    /**
     * 同步任务的所有成员到 Manticore
     *
     * 包括：ProjectTaskUser 和 ProjectTaskVisibilityUser
     *
     * @param int $taskId 任务ID
     * @return bool 是否成功
     */
    public static function syncTaskUsers(int $taskId): bool
    {
        if (!Apps::isInstalled("manticore") || $taskId <= 0) {
            return false;
        }

        try {
            // 获取任务成员（负责人/协作人）
            $taskUserIds = ProjectTaskUser::where('task_id', $taskId)
                ->orWhere('task_pid', $taskId)
                ->pluck('userid')
                ->toArray();

            // 获取可见性指定成员
            $visibilityUserIds = ProjectTaskVisibilityUser::where('task_id', $taskId)
                ->pluck('userid')
                ->toArray();

            // 合并去重
            $allUserIds = array_unique(array_merge($taskUserIds, $visibilityUserIds));

            // 同步到 Manticore
            return ManticoreBase::syncTaskUsers($taskId, $allUserIds);
        } catch (\Exception $e) {
            Log::error('Manticore syncTaskUsers error: ' . $e->getMessage(), ['task_id' => $taskId]);
            return false;
        }
    }

    /**
     * 批量同步所有任务成员关系（全量同步）
     *
     * @param callable|null $progressCallback 进度回调
     * @return int 同步数量
     */
    public static function syncAllTaskUsers(?callable $progressCallback = null): int
    {
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        $count = 0;
        $lastId = 0;
        $batchSize = 1000;

        // 先清空 Manticore 中的 task_users 表
        ManticoreBase::clearAllTaskUsers();

        // 同步 ProjectTaskUser
        while (true) {
            $records = ProjectTaskUser::where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $record) {
                ManticoreBase::upsertTaskUser($record->task_id, $record->userid);
                // 如果有父任务，也添加到父任务
                if ($record->task_pid) {
                    ManticoreBase::upsertTaskUser($record->task_pid, $record->userid);
                }
                $count++;
                $lastId = $record->id;
            }

            if ($progressCallback) {
                $progressCallback($count);
            }
        }

        // 同步 ProjectTaskVisibilityUser
        $lastId = 0;
        while (true) {
            $records = ProjectTaskVisibilityUser::where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $record) {
                ManticoreBase::upsertTaskUser($record->task_id, $record->userid);
                $count++;
                $lastId = $record->id;
            }

            if ($progressCallback) {
                $progressCallback($count);
            }
        }

        return $count;
    }

    /**
     * 增量同步任务成员关系（只同步新增的）
     *
     * @param callable|null $progressCallback 进度回调
     * @return int 同步数量
     */
    public static function syncTaskUsersIncremental(?callable $progressCallback = null): int
    {
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        $count = 0;
        $batchSize = 1000;

        // 同步 ProjectTaskUser 新增
        $lastKey1 = "sync:manticoreTaskUserLastId";
        $lastId1 = intval(ManticoreKeyValue::get($lastKey1, 0));

        while (true) {
            $records = ProjectTaskUser::where('id', '>', $lastId1)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $record) {
                ManticoreBase::upsertTaskUser($record->task_id, $record->userid);
                if ($record->task_pid) {
                    ManticoreBase::upsertTaskUser($record->task_pid, $record->userid);
                }
                $count++;
                $lastId1 = $record->id;
            }

            ManticoreKeyValue::set($lastKey1, $lastId1);

            if ($progressCallback) {
                $progressCallback($count);
            }
        }

        // 同步 ProjectTaskVisibilityUser 新增
        $lastKey2 = "sync:manticoreTaskVisibilityUserLastId";
        $lastId2 = intval(ManticoreKeyValue::get($lastKey2, 0));

        while (true) {
            $records = ProjectTaskVisibilityUser::where('id', '>', $lastId2)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $record) {
                ManticoreBase::upsertTaskUser($record->task_id, $record->userid);
                $count++;
                $lastId2 = $record->id;
            }

            ManticoreKeyValue::set($lastKey2, $lastId2);

            if ($progressCallback) {
                $progressCallback($count);
            }
        }

        return $count;
    }
}

