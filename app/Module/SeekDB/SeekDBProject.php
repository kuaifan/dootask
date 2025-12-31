<?php

namespace App\Module\SeekDB;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Module\Apps;
use App\Module\Base;
use App\Module\AI;
use App\Module\SeekDB\SeekDBKeyValue;
use Illuminate\Support\Facades\Log;

/**
 * SeekDB 项目搜索类
 *
 * 使用方法:
 *
 * 1. 搜索方法
 *    - 搜索项目: search($userid, $keyword, $searchType, $limit);
 *
 * 2. 同步方法
 *    - 单个同步: sync(Project $project);
 *    - 批量同步: batchSync($projects);
 *    - 删除索引: delete($projectId);
 *
 * 3. 成员关系方法
 *    - 添加成员: addProjectUser($projectId, $userid);
 *    - 删除成员: removeProjectUser($projectId, $userid);
 *    - 同步所有成员: syncProjectUsers($projectId);
 *
 * 4. 工具方法
 *    - 清空索引: clear();
 */
class SeekDBProject
{
    /**
     * 搜索项目（支持全文、向量、混合搜索）
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

        if (!Apps::isInstalled("seekdb")) {
            return [];
        }

        try {
            switch ($searchType) {
                case 'text':
                    return self::formatSearchResults(
                        SeekDBBase::projectFullTextSearch($keyword, $userid, $limit, 0)
                    );

                case 'vector':
                    $embedding = self::getEmbedding($keyword);
                    if (empty($embedding)) {
                        return self::formatSearchResults(
                            SeekDBBase::projectFullTextSearch($keyword, $userid, $limit, 0)
                        );
                    }
                    return self::formatSearchResults(
                        SeekDBBase::projectVectorSearch($embedding, $userid, $limit)
                    );

                case 'hybrid':
                default:
                    $embedding = self::getEmbedding($keyword);
                    return self::formatSearchResults(
                        SeekDBBase::projectHybridSearch($keyword, $embedding, $userid, $limit)
                    );
            }
        } catch (\Exception $e) {
            Log::error('SeekDB project search error: ' . $e->getMessage());
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
     * @param array $results SeekDB 返回的结果
     * @return array 格式化后的结果
     */
    private static function formatSearchResults(array $results): array
    {
        $formatted = [];
        foreach ($results as $item) {
            $formatted[] = [
                'project_id' => $item['project_id'],
                'id' => $item['project_id'],
                'userid' => $item['userid'],
                'personal' => $item['personal'],
                'name' => $item['project_name'],
                'desc_preview' => $item['project_desc_preview'] ?? null,
                'relevance' => $item['relevance'] ?? $item['similarity'] ?? $item['rrf_score'] ?? 0,
            ];
        }
        return $formatted;
    }

    // ==============================
    // 同步方法
    // ==============================

    /**
     * 同步单个项目到 SeekDB
     *
     * @param Project $project 项目模型
     * @return bool 是否成功
     */
    public static function sync(Project $project): bool
    {
        if (!Apps::isInstalled("seekdb")) {
            return false;
        }

        // 已归档的项目不索引
        if ($project->archived_at) {
            return self::delete($project->id);
        }

        try {
            // 构建用于搜索的文本内容
            $searchableContent = self::buildSearchableContent($project);

            // 获取 embedding（如果 AI 可用）
            $embedding = null;
            if (!empty($searchableContent) && Apps::isInstalled('ai')) {
                $embeddingResult = self::getEmbedding($searchableContent);
                if (!empty($embeddingResult)) {
                    $embedding = '[' . implode(',', $embeddingResult) . ']';
                }
            }

            // 写入 SeekDB
            $result = SeekDBBase::upsertProjectVector([
                'project_id' => $project->id,
                'userid' => $project->userid ?? 0,
                'personal' => $project->personal ?? 0,
                'project_name' => $project->name ?? '',
                'project_desc' => $project->desc ?? '',
                'content_vector' => $embedding,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('SeekDB project sync error: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'project_name' => $project->name,
            ]);
            return false;
        }
    }

    /**
     * 构建可搜索的文本内容
     *
     * @param Project $project 项目模型
     * @return string 可搜索的文本
     */
    private static function buildSearchableContent(Project $project): string
    {
        $parts = [];

        if (!empty($project->name)) {
            $parts[] = $project->name;
        }
        if (!empty($project->desc)) {
            $parts[] = $project->desc;
        }

        return implode(' ', $parts);
    }

    /**
     * 批量同步项目
     *
     * @param iterable $projects 项目列表
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $projects): int
    {
        if (!Apps::isInstalled("seekdb")) {
            return 0;
        }

        $count = 0;
        foreach ($projects as $project) {
            if (self::sync($project)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 删除项目索引
     *
     * @param int $projectId 项目ID
     * @return bool 是否成功
     */
    public static function delete(int $projectId): bool
    {
        if (!Apps::isInstalled("seekdb")) {
            return false;
        }

        // 删除项目索引
        SeekDBBase::deleteProjectVector($projectId);
        // 删除项目成员关系
        SeekDBBase::deleteAllProjectUsers($projectId);

        return true;
    }

    /**
     * 清空所有索引
     *
     * @return bool 是否成功
     */
    public static function clear(): bool
    {
        if (!Apps::isInstalled("seekdb")) {
            return false;
        }

        SeekDBBase::clearAllProjectVectors();
        SeekDBBase::clearAllProjectUsers();

        return true;
    }

    /**
     * 获取已索引项目数量
     *
     * @return int 数量
     */
    public static function getIndexedCount(): int
    {
        if (!Apps::isInstalled("seekdb")) {
            return 0;
        }

        return SeekDBBase::getIndexedProjectCount();
    }

    // ==============================
    // 成员关系方法
    // ==============================

    /**
     * 添加项目成员到 SeekDB
     *
     * @param int $projectId 项目ID
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function addProjectUser(int $projectId, int $userid): bool
    {
        if (!Apps::isInstalled("seekdb") || $projectId <= 0 || $userid <= 0) {
            return false;
        }

        return SeekDBBase::upsertProjectUser($projectId, $userid);
    }

    /**
     * 删除项目成员
     *
     * @param int $projectId 项目ID
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function removeProjectUser(int $projectId, int $userid): bool
    {
        if (!Apps::isInstalled("seekdb") || $projectId <= 0 || $userid <= 0) {
            return false;
        }

        return SeekDBBase::deleteProjectUser($projectId, $userid);
    }

    /**
     * 同步项目的所有成员到 SeekDB
     *
     * @param int $projectId 项目ID
     * @return bool 是否成功
     */
    public static function syncProjectUsers(int $projectId): bool
    {
        if (!Apps::isInstalled("seekdb") || $projectId <= 0) {
            return false;
        }

        try {
            // 从 MySQL 获取项目成员
            $userids = ProjectUser::where('project_id', $projectId)
                ->pluck('userid')
                ->toArray();

            // 同步到 SeekDB
            return SeekDBBase::syncProjectUsers($projectId, $userids);
        } catch (\Exception $e) {
            Log::error('SeekDB syncProjectUsers error: ' . $e->getMessage(), ['project_id' => $projectId]);
            return false;
        }
    }

    /**
     * 批量同步所有项目成员关系（全量同步）
     *
     * @param callable|null $progressCallback 进度回调
     * @return int 同步数量
     */
    public static function syncAllProjectUsers(?callable $progressCallback = null): int
    {
        if (!Apps::isInstalled("seekdb")) {
            return 0;
        }

        $count = 0;
        $lastId = 0;
        $batchSize = 1000;

        // 先清空 SeekDB 中的 project_users 表
        SeekDBBase::clearAllProjectUsers();

        // 分批同步
        while (true) {
            $records = ProjectUser::where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $record) {
                SeekDBBase::upsertProjectUser($record->project_id, $record->userid);
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
     * 增量同步项目成员关系（只同步新增的）
     *
     * @param callable|null $progressCallback 进度回调
     * @return int 同步数量
     */
    public static function syncProjectUsersIncremental(?callable $progressCallback = null): int
    {
        if (!Apps::isInstalled("seekdb")) {
            return 0;
        }

        $count = 0;
        $batchSize = 1000;
        $lastKey = "sync:seekdbProjectUserLastId";
        $lastId = intval(SeekDBKeyValue::get($lastKey, 0));

        // 分批同步新增的记录
        while (true) {
            $records = ProjectUser::where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $record) {
                SeekDBBase::upsertProjectUser($record->project_id, $record->userid);
                $count++;
                $lastId = $record->id;
            }

            // 保存进度
            SeekDBKeyValue::set($lastKey, $lastId);

            if ($progressCallback) {
                $progressCallback($count);
            }
        }

        return $count;
    }
}

