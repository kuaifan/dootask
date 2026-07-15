<?php

namespace App\Module\Manticore;

use App\Models\Project;
use App\Models\ProjectUser;
use App\Module\Apps;
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
     * 同步单个项目到 Manticore（含 allowed_users，向量由引擎 Auto Embeddings 自动生成）
     *
     * @param Project $project 项目模型
     * @return bool 是否成功
     */
    public static function sync(Project $project): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        // 已归档的项目不索引
        if ($project->archived_at) {
            return self::delete($project->id);
        }

        try {
            return ManticoreBase::upsertProjectVector(self::buildRow($project));
        } catch (\Exception $e) {
            Log::error('Manticore project sync error: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'project_name' => $project->name,
            ]);
            return false;
        }
    }

    /**
     * 构建项目索引行数据（含 allowed_users）
     *
     * @param Project $project 项目模型
     * @return array 行数据
     */
    private static function buildRow(Project $project): array
    {
        return [
            'id' => $project->id,
            'project_id' => $project->id,
            'userid' => $project->userid ?? 0,
            'personal' => $project->personal ?? 0,
            'project_name' => $project->name ?? '',
            'project_desc' => $project->desc ?? '',
            'allowed_users' => self::getAllowedUsers($project->id),
        ];
    }

    /**
     * 批量同步项目（每块一条多行 REPLACE，向量由引擎自动生成）
     *
     * @param iterable $projects 项目列表
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $projects): int
    {
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        $count = 0;
        $rows = [];
        foreach ($projects as $project) {
            if ($project->archived_at) {
                if (self::delete($project->id)) {
                    $count++;
                }
                continue;
            }
            try {
                $rows[] = self::buildRow($project);
            } catch (\Exception $e) {
                Log::error('Manticore project batchSync build error: ' . $e->getMessage(), [
                    'project_id' => $project->id,
                ]);
            }
        }

        return $count + ManticoreBase::batchUpsertVectors('project', $rows);
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
}
