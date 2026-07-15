<?php

namespace App\Module\Manticore;

use App\Models\User;
use App\Models\UserTag;
use App\Module\Apps;
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
     * 同步单个用户到 Manticore（向量由引擎 Auto Embeddings 按行内文本自动生成）
     *
     * @param User $user 用户模型
     * @return bool 是否成功
     */
    public static function sync(User $user): bool
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
            $row = self::buildRow($user);
            // 脏检查：与已索引行完全一致则跳过。标签点赞/识别等高频事件经常不改变
            // Top-10 标签文本，跳过可省一次真实的向量化调用与整行重写
            if (self::rowUnchanged($row)) {
                return true;
            }
            return ManticoreBase::upsertUserVector($row);
        } catch (\Exception $e) {
            Log::error('Manticore user sync error: ' . $e->getMessage(), [
                'userid' => $user->userid,
                'nickname' => $user->nickname,
            ]);
            return false;
        }
    }

    /**
     * 判断待写入行与当前已索引行是否完全一致（文本字段逐一比较）
     */
    private static function rowUnchanged(array $row): bool
    {
        $existing = (new ManticoreBase())->queryOne(
            "SELECT nickname, email, profession, tags, introduction FROM user_vectors WHERE userid = ?",
            [$row['userid']]
        );
        if (!$existing) {
            return false;
        }
        foreach (['nickname', 'email', 'profession', 'tags', 'introduction'] as $field) {
            if ((string) ($existing[$field] ?? '') !== (string) $row[$field]) {
                return false;
            }
        }
        return true;
    }

    /**
     * 构建用户索引行数据（含标签 Top 10）
     *
     * @param User $user 用户模型
     * @return array 行数据
     */
    private static function buildRow(User $user): array
    {
        $tags = self::getUserTags($user->userid);

        return [
            'id' => $user->userid,
            'userid' => $user->userid,
            'nickname' => $user->nickname ?? '',
            'email' => $user->email ?? '',
            'profession' => $user->profession ?? '',
            'tags' => $tags,
            'introduction' => $user->introduction ?? '',
        ];
    }

    /**
     * 批量同步用户（每块一条多行 REPLACE，向量由引擎自动生成）
     *
     * @param iterable $users 用户列表
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $users): int
    {
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        $count = 0;
        $rows = [];
        foreach ($users as $user) {
            if ($user->bot) {
                $count++;
                continue;
            }
            if ($user->disable_at) {
                if (self::delete($user->userid)) {
                    $count++;
                }
                continue;
            }
            try {
                $rows[] = self::buildRow($user);
            } catch (\Exception $e) {
                Log::error('Manticore user batchSync build error: ' . $e->getMessage(), [
                    'userid' => $user->userid,
                ]);
            }
        }

        return $count + ManticoreBase::batchUpsertVectors('user', $rows);
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
}
