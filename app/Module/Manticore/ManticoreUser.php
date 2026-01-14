<?php

namespace App\Module\Manticore;

use App\Models\User;
use App\Models\UserTag;
use App\Module\Apps;
use App\Module\Base;
use App\Module\AI;
use Illuminate\Support\Facades\Log;

/**
 * Manticore Search 用户搜索类（联系人搜索）
 *
 * 使用方法:
 *
 * 1. 搜索方法
 *    - 搜索用户: search($keyword, $searchType, $limit);
 *
 * 2. 同步方法
 *    - 单个同步: sync(User $user);
 *    - 批量同步: batchSync($users);
 *    - 删除索引: delete($userid);
 *
 * 3. 工具方法
 *    - 清空索引: clear();
 */
class ManticoreUser
{
    /**
     * 搜索用户（支持全文、向量、混合搜索）
     *
     * @param string $keyword 搜索关键词
     * @param string $searchType 搜索类型: text/vector/hybrid
     * @param int $limit 返回数量
     * @return array 搜索结果
     */
    public static function search(string $keyword, string $searchType = 'hybrid', int $limit = 20): array
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
                        ManticoreBase::userFullTextSearch($keyword, $limit, 0)
                    );

                case 'vector':
                    $embedding = ManticoreBase::getEmbedding($keyword);
                    if (empty($embedding)) {
                        return self::formatSearchResults(
                            ManticoreBase::userFullTextSearch($keyword, $limit, 0)
                        );
                    }
                    return self::formatSearchResults(
                        ManticoreBase::userVectorSearch($embedding, $limit)
                    );

                case 'hybrid':
                default:
                    $embedding = ManticoreBase::getEmbedding($keyword);
                    return self::formatSearchResults(
                        ManticoreBase::userHybridSearch($keyword, $embedding, $limit)
                    );
            }
        } catch (\Exception $e) {
            Log::error('Manticore user search error: ' . $e->getMessage());
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
                'userid' => $item['userid'],
                'nickname' => $item['nickname'],
                'email' => $item['email'],
                'profession' => $item['profession'],
                'tags' => $item['tags'] ?? '',
                'introduction_preview' => isset($item['introduction']) ? mb_substr($item['introduction'], 0, 200) : null,
                'relevance' => $item['relevance'] ?? $item['similarity'] ?? $item['rrf_score'] ?? 0,
            ];
        }
        return $formatted;
    }

    // ==============================
    // 同步方法
    // ==============================

    /**
     * 获取用户的标签（按认可数排序，最多10个）
     *
     * @param int $userid 用户ID
     * @return string 标签名称，空格分隔
     */
    public static function getUserTags(int $userid): string
    {
        $tags = UserTag::where('user_id', $userid)
            ->withCount('recognitions')
            ->orderByDesc('recognitions_count')
            ->limit(10)
            ->pluck('name')
            ->toArray();

        return implode(' ', $tags);
    }

    /**
     * 同步单个用户到 Manticore
     *
     * @param User $user 用户模型
     * @param bool $withVector 是否同时生成向量（默认 false，向量由后台任务生成）
     * @return bool 是否成功
     */
    public static function sync(User $user, bool $withVector = false): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        // 不处理机器人账号
        if ($user->bot) {
            return true;
        }

        // 不处理已禁用的账号
        if ($user->disable_at) {
            return self::delete($user->userid);
        }

        try {
            // 获取用户标签（Top 10）
            $tags = self::getUserTags($user->userid);

            // 构建用于搜索的文本内容
            $searchableContent = self::buildSearchableContent($user, $tags);

            // 只有明确要求时才生成向量（默认不生成，由后台任务处理）
            $embedding = null;
            if ($withVector && !empty($searchableContent) && Apps::isInstalled('ai')) {
                $embeddingResult = ManticoreBase::getEmbedding($searchableContent);
                if (!empty($embeddingResult)) {
                    $embedding = '[' . implode(',', $embeddingResult) . ']';
                }
            }

            // 写入 Manticore
            $result = ManticoreBase::upsertUserVector([
                'userid' => $user->userid,
                'nickname' => $user->nickname ?? '',
                'email' => $user->email ?? '',
                'profession' => $user->profession ?? '',
                'tags' => $tags,
                'introduction' => $user->introduction ?? '',
                'content_vector' => $embedding,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Manticore user sync error: ' . $e->getMessage(), [
                'userid' => $user->userid,
                'nickname' => $user->nickname,
            ]);
            return false;
        }
    }

    /**
     * 构建可搜索的文本内容
     *
     * @param User $user 用户模型
     * @param string $tags 用户标签（空格分隔）
     * @return string 可搜索的文本
     */
    private static function buildSearchableContent(User $user, string $tags = ''): string
    {
        $parts = [];

        if (!empty($user->nickname)) {
            $parts[] = $user->nickname;
        }
        if (!empty($user->email)) {
            $parts[] = $user->email;
        }
        if (!empty($user->profession)) {
            $parts[] = $user->profession;
        }
        if (!empty($tags)) {
            $parts[] = $tags;
        }
        if (!empty($user->introduction)) {
            $parts[] = $user->introduction;
        }

        return implode(' ', $parts);
    }

    /**
     * 批量同步用户
     *
     * @param iterable $users 用户列表
     * @param bool $withVector 是否同时生成向量
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $users, bool $withVector = false): int
    {
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        $count = 0;
        foreach ($users as $user) {
            if (self::sync($user, $withVector)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 删除用户索引
     *
     * @param int $userid 用户ID
     * @return bool 是否成功
     */
    public static function delete(int $userid): bool
    {
        if (!Apps::isInstalled("search")) {
            return false;
        }

        return ManticoreBase::deleteUserVector($userid);
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

        return ManticoreBase::clearAllUserVectors();
    }

    /**
     * 获取已索引用户数量
     *
     * @return int 数量
     */
    public static function getIndexedCount(): int
    {
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        return ManticoreBase::getIndexedUserCount();
    }

    // ==============================
    // 批量向量生成方法
    // ==============================

    /**
     * 批量生成用户向量
     * 用于后台异步处理，将已索引用户的向量批量生成
     *
     * @param array $userIds 用户ID数组
     * @param int $batchSize 每批 embedding 数量（默认20）
     * @return int 成功处理的数量
     */
    public static function generateVectorsBatch(array $userIds, int $batchSize = 20): int
    {
        if (!Apps::isInstalled("search") || !Apps::isInstalled("ai") || empty($userIds)) {
            return 0;
        }

        try {
            // 1. 查询用户信息
            $users = User::whereIn('userid', $userIds)
                ->where('bot', 0)
                ->whereNull('disable_at')
                ->get();

            if ($users->isEmpty()) {
                return 0;
            }

            // 2. 提取每个用户的内容（包含标签）
            $userContents = [];
            foreach ($users as $user) {
                $tags = self::getUserTags($user->userid);
                $searchableContent = self::buildSearchableContent($user, $tags);
                if (!empty($searchableContent)) {
                    $userContents[$user->userid] = $searchableContent;
                }
            }

            if (empty($userContents)) {
                return 0;
            }

            // 3. 分批处理
            $successCount = 0;
            $chunks = array_chunk($userContents, $batchSize, true);

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
                foreach ($ids as $index => $userid) {
                    if (!isset($embeddings[$index]) || empty($embeddings[$index])) {
                        continue;
                    }
                    $vectorData[$userid] = '[' . implode(',', $embeddings[$index]) . ']';
                }

                // 6. 批量更新向量
                if (!empty($vectorData)) {
                    $batchCount = ManticoreBase::batchUpdateUserVectors($vectorData);
                    $successCount += $batchCount;
                }
            }

            return $successCount;
        } catch (\Exception $e) {
            Log::error('ManticoreUser generateVectorsBatch error: ' . $e->getMessage());
            return 0;
        }
    }
}

