<?php

namespace App\Module\Manticore;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Module\Apps;
use App\Module\Base;
use App\Module\AI;
use Illuminate\Support\Facades\Log;

/**
 * Manticore Search 项目搜索类
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
 * 3. 权限更新方法
 *    - 更新权限: updateAllowedUsers($projectId);
 *
 * 4. 工具方法
 *    - 清空索引: clear();
 */
class ManticoreProject
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

        if (!Apps::isInstalled("search")) {
            return [];
        }

        try {
            switch ($searchType) {
                case 'text':
                    return self::formatSearchResults(
                        ManticoreBase::projectFullTextSearch($keyword, $userid, $limit, 0)
                    );

                case 'vector':
                    $embedding = ManticoreBase::getEmbedding($keyword);
                    if (empty($embedding)) {
                        return self::formatSearchResults(
                            ManticoreBase::projectFullTextSearch($keyword, $userid, $limit, 0)
                        );
                    }
                    return self::formatSearchResults(
                        ManticoreBase::projectVectorSearch($embedding, $userid, $limit)
                    );

                case 'hybrid':
                default:
                    $embedding = ManticoreBase::getEmbedding($keyword);
                    return self::formatSearchResults(
                        ManticoreBase::projectHybridSearch($keyword, $embedding, $userid, $limit)
                    );
            }
        } catch (\Exception $e) {
            Log::error('Manticore project search error: ' . $e->getMessage());
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
                'project_id' => $item['project_id'],
                'id' => $item['project_id'],
                'userid' => $item['userid'],
                'personal' => $item['personal'],
                'name' => $item['project_name'],
                'desc_preview' => isset($item['project_desc']) ? mb_substr($item['project_desc'], 0, 300) : null,
                'relevance' => $item['relevance'] ?? $item['similarity'] ?? $item['rrf_score'] ?? 0,
            ];
        }
        return $formatted;
    }

    // ==============================
    // 同步方法
    // ==============================

    /**
     * 获取项目的 allowed_users 列表
     *
     * @param int $projectId 项目ID
     * @return array 有权限的用户ID数组
     */
    public static function getAllowedUsers(int $projectId): array
    {
        return ProjectUser::where('project_id', $projectId)
            ->pluck('userid')
            ->toArray();
    }

    /**
     * 同步单个项目到 Manticore（含 allowed_users）
     *
     * @param Project $project 项目模型
     * @param bool $withVector 是否同时生成向量（默认 false，向量由后台任务生成）
     * @return bool 是否成功
     */
    public static function sync(Project $project, bool $withVector = false): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        // 已归档的项目不索引
        if ($project->archived_at) {
            return self::delete($project->id);
        }

        try {
            // 构建用于搜索的文本内容
            $searchableContent = self::buildSearchableContent($project);

            // 只有明确要求时才生成向量（默认不生成，由后台任务处理）
            $embedding = null;
            if ($withVector && !empty($searchableContent) && Apps::isInstalled('ai')) {
                $embeddingResult = ManticoreBase::getEmbedding($searchableContent);
                if (!empty($embeddingResult)) {
                    $embedding = '[' . implode(',', $embeddingResult) . ']';
                }
            }

            // 获取项目成员列表（作为 allowed_users）
            $allowedUsers = self::getAllowedUsers($project->id);

            // 写入 Manticore（含 allowed_users）
            $result = ManticoreBase::upsertProjectVector([
                'project_id' => $project->id,
                'userid' => $project->userid ?? 0,
                'personal' => $project->personal ?? 0,
                'project_name' => $project->name ?? '',
                'project_desc' => $project->desc ?? '',
                'content_vector' => $embedding,
                'allowed_users' => $allowedUsers,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Manticore project sync error: ' . $e->getMessage(), [
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
     * @param bool $withVector 是否同时生成向量
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $projects, bool $withVector = false): int
    {
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        $count = 0;
        foreach ($projects as $project) {
            if (self::sync($project, $withVector)) {
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
        if (!Apps::isInstalled("search")) {
            return false;
        }

        return ManticoreBase::deleteProjectVector($projectId);
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

        return ManticoreBase::clearAllProjectVectors();
    }

    /**
     * 获取已索引项目数量
     *
     * @return int 数量
     */
    public static function getIndexedCount(): int
    {
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        return ManticoreBase::getIndexedProjectCount();
    }

    // ==============================
    // 权限更新方法
    // ==============================

    /**
     * 更新项目的 allowed_users 权限列表
     * 从 MySQL 获取最新的项目成员并更新到 Manticore
     *
     * @param int $projectId 项目ID
     * @return bool 是否成功
     */
    public static function updateAllowedUsers(int $projectId): bool
    {
        if (!Apps::isInstalled("search") || $projectId <= 0) {
            return false;
        }

        try {
            $userids = self::getAllowedUsers($projectId);
            return ManticoreBase::updateProjectAllowedUsers($projectId, $userids);
        } catch (\Exception $e) {
            Log::error('Manticore updateAllowedUsers error: ' . $e->getMessage(), ['project_id' => $projectId]);
            return false;
        }
    }

    // ==============================
    // 批量向量生成方法
    // ==============================

    /**
     * 批量生成项目向量
     * 用于后台异步处理，将已索引项目的向量批量生成
     *
     * @param array $projectIds 项目ID数组
     * @param int $batchSize 每批 embedding 数量（默认20）
     * @return int 成功处理的数量
     */
    public static function generateVectorsBatch(array $projectIds, int $batchSize = 20): int
    {
        if (!Apps::isInstalled("search") || !Apps::isInstalled("ai") || empty($projectIds)) {
            return 0;
        }

        try {
            // 1. 查询项目信息
            $projects = Project::whereIn('id', $projectIds)
                ->whereNull('archived_at')
                ->get();

            if ($projects->isEmpty()) {
                return 0;
            }

            // 2. 提取每个项目的内容
            $projectContents = [];
            foreach ($projects as $project) {
                $searchableContent = self::buildSearchableContent($project);
                if (!empty($searchableContent)) {
                    $projectContents[$project->id] = $searchableContent;
                }
            }

            if (empty($projectContents)) {
                return 0;
            }

            // 3. 分批处理
            $successCount = 0;
            $chunks = array_chunk($projectContents, $batchSize, true);

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
                foreach ($ids as $index => $projectId) {
                    if (!isset($embeddings[$index]) || empty($embeddings[$index])) {
                        continue;
                    }
                    $vectorData[$projectId] = '[' . implode(',', $embeddings[$index]) . ']';
                }

                // 6. 批量更新向量
                if (!empty($vectorData)) {
                    $batchCount = ManticoreBase::batchUpdateProjectVectors($vectorData);
                    $successCount += $batchCount;
                }
            }

            return $successCount;
        } catch (\Exception $e) {
            Log::error('ManticoreProject generateVectorsBatch error: ' . $e->getMessage());
            return 0;
        }
    }
}
