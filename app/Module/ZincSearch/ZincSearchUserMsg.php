<?php

namespace App\Module\ZincSearch;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use Illuminate\Support\Facades\Log;

/**
 * ZincSearch 会话消息类
 *
 * 使用方法:
 *
 * 1. 基础方法
 *     - 确保索引存在: ensureIndex();
 *     - 清空所有数据: clear();
 *
 * 2. 搜索方法
 *    - 关键词搜索: searchByKeyword('用户ID', '关键词');
 *
 * 3. 基本方法
 *    - 单个同步: syncMsg($dialogMsg);
 *    - 批量同步: batchSyncMsgs($dialogMsgs);
 *    - 删除消息: deleteMsg($dialogMsg);
 *
 * 4. 用户方法
 *    - 单个同步: syncUser($dialogUser);
 *    - 批量同步: batchSyncUsers($dialogUsers);
 *    - 删除消息: deleteUser($dialogUser);
 */
class ZincSearchUserMsg
{
    /**
     * 索引名称
     */
    protected static string $indexName = 'userMsg';

    // ==============================
    // 基础方法
    // ==============================

    /**
     * 确保索引存在
     */
    public static function ensureIndex(): bool
    {
        if (!ZincSearchBase::indexExists(self::$indexName)) {
            $mappings = [
                'properties' => [
                    // 关联字段
                    '_id' => ['type' => 'keyword', 'index' => true],
                    '_dialog_userid' => ['type' => 'keyword', 'index' => true],

                    // dialog_users 字段
                    'userid' => ['type' => 'keyword', 'index' => true],
                    'top_at' => ['type' => 'date', 'index' => true],
                    'last_at' => ['type' => 'date', 'index' => true],
                    'mark_unread' => ['type' => 'numeric', 'index' => true],
                    'silence' => ['type' => 'numeric', 'index' => true],
                    'hide' => ['type' => 'numeric', 'index' => true],
                    'color' => ['type' => 'keyword', 'index' => true],

                    // dialog_msgs 字段
                    'msg_id' => ['type' => 'keyword', 'index' => true],
                    'dialog_id' => ['type' => 'keyword', 'index' => true],
                    'sender_userid' => ['type' => 'keyword', 'index' => true],
                    'msg_type' => ['type' => 'keyword', 'index' => true],
                    'key' => ['type' => 'text', 'index' => true],
                    'bot' => ['type' => 'numeric', 'index' => true],
                    'created_at' => ['type' => 'date', 'index' => true],
                    'updated_at' => ['type' => 'date', 'index' => true],
                ]
            ];
            $result = ZincSearchBase::createIndex(self::$indexName, $mappings);
            return $result['success'] ?? false;
        }
        return true;
    }

    /**
     * 清空所有键值
     *
     * @return bool 是否成功
     */
    public static function clear(): bool
    {
        // 检查索引是否存在
        if (!ZincSearchBase::indexExists(self::$indexName)) {
            return true;
        }

        // 删除再重建索引
        $deleteResult = ZincSearchBase::deleteIndex(self::$indexName);
        if (!($deleteResult['success'] ?? false)) {
            return false;
        }

        return self::ensureIndex();
    }

    // ==============================
    // 搜索方法
    // ==============================

    /**
     * 构建对话系统特定的搜索 - 根据用户ID和消息关键词搜索会话
     *
     * @param string $userid 用户ID
     * @param string $keyword 消息关键词
     * @param int $from 起始位置
     * @param int $size 返回结果数量
     * @return array
     */
    public static function searchByKeyword(string $userid, string $keyword, int $from = 0, int $size = 20): array
    {
        $searchParams = [
            'query' => [
                'bool' => [
                    'must' => [
                        ['term' => ['userid' => $userid]],
                        ['term' => ['bot' => 0]],
                        ['match_phrase' => ['key' => $keyword]]
                    ]
                ]
            ],
            'from' => $from,
            'size' => $size,
            'sort' => [
                ['updated_at' => 'desc']
            ]
        ];

        try {
            $result = ZincSearchBase::elasticSearch(self::$indexName, $searchParams);
            return array_map(function ($hit) {
                $source = $hit['_source'];
                return [
                    'id' => $source['dialog_id'],
                    'top_at' => $source['top_at'],
                    'last_at' => $source['last_at'],
                    'mark_unread' => $source['mark_unread'],
                    'silence' => $source['silence'],
                    'hide' => $source['hide'],
                    'color' => $source['color'],
                    'user_at' => $source['updated_at'],
                    'search_msg_id' => $source['msg_id'],
                ];
            }, $result['data']['hits']['hits'] ?? []);
        } catch (\Exception $e) {
            Log::error('搜索对话消息失败: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'hits' => ['total' => ['value' => 0], 'hits' => []]
            ];
        }
    }

    // ==============================
    // 基本方法
    // ==============================

    /**
     * 生成文档ID（消息）
     *
     * @param WebSocketDialogMsg $dialogMsg
     * @param WebSocketDialogUser $dialogUser
     * @return string
     */
    private static function generateDocId(WebSocketDialogMsg $dialogMsg, WebSocketDialogUser $dialogUser): string
    {
        return "{$dialogMsg->id}_{$dialogUser->userid}";
    }

    /**
     * 生成文档ID（会话）
     *
     * @param WebSocketDialogUser $dialogUser
     * @return string
     */
    private static function generateDialogUserid(WebSocketDialogUser $dialogUser): string
    {
        return "{$dialogUser->dialog_id}_{$dialogUser->userid}";
    }

    /**
     * 生成文档内容
     *
     * @param WebSocketDialogMsg $dialogMsg
     * @param WebSocketDialogUser $dialogUser
     * @return array
     */
    private static function generateMsgFormat(WebSocketDialogMsg $dialogMsg, WebSocketDialogUser $dialogUser): array
    {
        return [
            '_id' => self::generateDocId($dialogMsg, $dialogUser),
            '_dialog_userid' => self::generateDialogUserid($dialogUser),

            'userid' => $dialogUser->userid,
            'top_at' => $dialogUser->top_at,
            'last_at' => $dialogUser->last_at,
            'mark_unread' => $dialogUser->mark_unread ? 1 : 0,
            'silence' => $dialogUser->silence ? 1 : 0,
            'hide' => $dialogUser->hide ? 1 : 0,
            'color' => $dialogUser->color,

            'msg_id' => $dialogMsg->id,
            'dialog_id' => $dialogMsg->dialog_id,
            'sender_userid' => $dialogMsg->userid,
            'msg_type' => $dialogMsg->type,
            'key' => $dialogMsg->key,
            'bot' => $dialogMsg->bot ? 1 : 0,
            'created_at' => $dialogMsg->created_at,
            'updated_at' => $dialogMsg->updated_at,
        ];
    }

    /**
     * 同步消息
     *
     * @param WebSocketDialogMsg $dialogMsg
     * @return bool
     */
    public static function syncMsg(WebSocketDialogMsg $dialogMsg): bool
    {
        if (!self::ensureIndex()) {
            return false;
        }

        try {
            // 获取此会话的所有用户
            $dialogUsers = WebSocketDialogUser::whereDialogId($dialogMsg->dialog_id)->get();

            if ($dialogUsers->isEmpty()) {
                return true;
            }

            $docs = [];
            foreach ($dialogUsers as $dialogUser) {
                if (empty($dialogMsg->key)) {
                    // 如果消息没有关键词，跳过
                    continue;
                }
                if ($dialogUser->userid == 0) {
                    // 跳过系统用户
                    continue;
                }
                $docs[] = self::generateMsgFormat($dialogMsg, $dialogUser);
            }

            if (!empty($docs)) {
                ZincSearchBase::addDocs(self::$indexName, $docs);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('syncMsg: ' . $e->getMessage());
        }
        return false;
    }

    /**
     * 批量同步消息
     *
     * @param WebSocketDialogMsg[] $dialogMsgs
     * @return int 成功同步的消息数
     */
    public static function batchSyncMsgs($dialogMsgs): int
    {
        if (!self::ensureIndex()) {
            return 0;
        }

        $count = 0;
        try {
            $docs = [];
            $userDialogs = [];

            // 预处理：收集所有涉及的对话ID
            $dialogIds = [];
            foreach ($dialogMsgs as $dialogMsg) {
                $dialogIds[] = $dialogMsg->dialog_id;
            }
            $dialogIds = array_unique($dialogIds);

            // 获取所有相关的用户-对话关系
            if (!empty($dialogIds)) {
                $dialogUsers = WebSocketDialogUser::whereIn('dialog_id', $dialogIds)->get();

                // 按对话ID组织用户
                foreach ($dialogUsers as $dialogUser) {
                    $userDialogs[$dialogUser->dialog_id][] = $dialogUser;
                }
            }

            // 为每条消息准备所有相关用户的文档
            foreach ($dialogMsgs as $dialogMsg) {
                if (!isset($userDialogs[$dialogMsg->dialog_id])) {
                    // 如果该会话没有用户，跳过
                    continue;
                }
                foreach ($userDialogs[$dialogMsg->dialog_id] as $dialogUser) {
                    if (empty($dialogMsg->key)) {
                        // 如果消息没有关键词，跳过
                        continue;
                    }
                    if ($dialogUser->userid == 0) {
                        // 跳过系统用户
                        continue;
                    }
                    $docs[] = self::generateMsgFormat($dialogMsg, $dialogUser);
                    $count++;
                }
            }

            // 批量写入
            if (!empty($docs)) {
                ZincSearchBase::addDocs(self::$indexName, $docs);
            }
        } catch (\Exception $e) {
            Log::error('batchSyncMsgs: ' . $e->getMessage());
        }

        return $count;
    }

    /**
     * 删除消息
     *
     * @param WebSocketDialogMsg $dialogMsg
     * @return int
     */
    public static function deleteMsg(WebSocketDialogMsg $dialogMsg): int
    {
        $batchSize = 500;   // 每批处理的文档数量
        $totalDeleted = 0;  // 总共删除的文档数量
        $from = 0;

        try {
            while (true) {
                // 根据消息ID查找相关文档
                $result = ZincSearchBase::advancedSearch(self::$indexName, [
                    'search_type' => 'term',
                    'query' => [
                        'field' => 'msg_id',
                        'term' => (string) $dialogMsg->id
                    ],
                    'from' => $from,
                    'max_results' => $batchSize
                ]);
                $hits = $result['data']['hits']['hits'] ?? [];

                // 如果没有更多文档，退出循环
                if (empty($hits)) {
                    break;
                }

                // 删除本批次找到的所有文档
                foreach ($hits as $hit) {
                    if (isset($hit['_id'])) {
                        ZincSearchBase::deleteDoc(self::$indexName, $hit['_id']);
                        $totalDeleted++;
                    }
                }

                // 如果返回的文档数少于批次大小，说明已经没有更多文档了
                if (count($hits) < $batchSize) {
                    break;
                }

                // 移动到下一批
                $from += $batchSize;
            }
        } catch (\Exception $e) {
            Log::error('deleteMsg: ' . $e->getMessage());
        }

        return $totalDeleted;
    }

    // ==============================
    // 用户方法
    // ==============================

    /**
     * 同步用户
     *
     * @param WebSocketDialogUser $dialogUser
     * @return void
     */
    public static function syncUser(WebSocketDialogUser $dialogUser): void
    {
        $batchSize = 500;   // 每批处理的文档数量
        $lastId = 0;        // 上次处理的最后ID

        do {
            $dialogMsgs = WebSocketDialogMsg::whereDialogId($dialogUser->dialog_id)
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($dialogMsgs->isEmpty()) {
                break;
            }

            ZincSearchUserMsg::batchSyncMsgs($dialogMsgs);

            // 记录最后处理的ID
            $lastId = $dialogMsgs->last()->id;

        } while (count($dialogMsgs) == $batchSize);
    }

    /**
     * 批量同步用户
     *
     * @param WebSocketDialogUser[] $dialogUsers
     * @return void
     */
    public static function batchSyncUsers($dialogUsers): void
    {
        foreach ($dialogUsers as $dialogUser) {
            self::syncUser($dialogUser);
        }
    }

    /**
     * 删除用户
     *
     * @param WebSocketDialogUser $dialogUser
     * @return int
     */
    public static function deleteUser(WebSocketDialogUser $dialogUser): int
    {
        $batchSize = 500;  // 每批处理的文档数量
        $totalDeleted = 0;  // 总共删除的文档数量
        $from = 0;

        try {
            while (true) {
                // 根据消息ID查找相关文档
                $result = ZincSearchBase::advancedSearch(self::$indexName, [
                    'search_type' => 'term',
                    'query' => [
                        'field' => '_dialog_userid',
                        'term' => self::generateDialogUserid($dialogUser),
                    ],
                    'from' => $from,
                    'max_results' => $batchSize
                ]);
                $hits = $result['data']['hits']['hits'] ?? [];

                // 如果没有更多文档，退出循环
                if (empty($hits)) {
                    break;
                }

                // 删除本批次找到的所有文档
                foreach ($hits as $hit) {
                    if (isset($hit['_id'])) {
                        ZincSearchBase::deleteDoc(self::$indexName, $hit['_id']);
                        $totalDeleted++;
                    }
                }

                // 如果返回的文档数少于批次大小，说明已经没有更多文档了
                if (count($hits) < $batchSize) {
                    break;
                }

                // 移动到下一批
                $from += $batchSize;
            }
        } catch (\Exception $e) {
            Log::error('deleteUser: ' . $e->getMessage());
        }

        return $totalDeleted;
    }
}
