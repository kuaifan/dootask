<?php

namespace App\Module\Manticore;

use App\Models\User;
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

        if (!Apps::isInstalled("manticore")) {
            return [];
        }

        try {
            switch ($searchType) {
                case 'text':
                    return self::formatSearchResults(
                        ManticoreBase::userFullTextSearch($keyword, $limit, 0)
                    );

                case 'vector':
                    $embedding = self::getEmbedding($keyword);
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
                    $embedding = self::getEmbedding($keyword);
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
                'userid' => $item['userid'],
                'nickname' => $item['nickname'],
                'email' => $item['email'],
                'tel' => $item['tel'],
                'profession' => $item['profession'],
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
     * 同步单个用户到 Manticore
     *
     * @param User $user 用户模型
     * @return bool 是否成功
     */
    public static function sync(User $user): bool
    {
        if (!Apps::isInstalled("manticore")) {
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
            // 构建用于搜索的文本内容
            $searchableContent = self::buildSearchableContent($user);

            // 获取 embedding（如果 AI 可用）
            $embedding = null;
            if (!empty($searchableContent) && Apps::isInstalled('ai')) {
                $embeddingResult = self::getEmbedding($searchableContent);
                if (!empty($embeddingResult)) {
                    $embedding = '[' . implode(',', $embeddingResult) . ']';
                }
            }

            // 写入 Manticore
            $result = ManticoreBase::upsertUserVector([
                'userid' => $user->userid,
                'nickname' => $user->nickname ?? '',
                'email' => $user->email ?? '',
                'tel' => $user->tel ?? '',
                'profession' => $user->profession ?? '',
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
     * @return string 可搜索的文本
     */
    private static function buildSearchableContent(User $user): string
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
        if (!empty($user->introduction)) {
            $parts[] = $user->introduction;
        }

        return implode(' ', $parts);
    }

    /**
     * 批量同步用户
     *
     * @param iterable $users 用户列表
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $users): int
    {
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        $count = 0;
        foreach ($users as $user) {
            if (self::sync($user)) {
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
        if (!Apps::isInstalled("manticore")) {
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
        if (!Apps::isInstalled("manticore")) {
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
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        return ManticoreBase::getIndexedUserCount();
    }
}

