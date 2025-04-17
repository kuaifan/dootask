<?php

namespace App\Module\ZincSearch;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use Illuminate\Support\Facades\Log;

/**
 * 对话系统消息索引
 *
 * 使用方法:
 *
 * 1. 索引管理
 *    - 创建索引: ZincSearchUserMsg::generateIndex();
 *    - 检查索引: ZincSearchBase::indexExists(ZincSearchUserMsg::$indexName);
 *    - 清空索引: ZincSearchUserMsg::clear();
 *
 * 2. 会话用户操作
 *    - 单个同步: ZincSearchUserMsg::syncUser($dialogUser);
 *    - 批量同步: ZincSearchUserMsg::batchSyncUsers($dialogUsers);
 *    - 删除用户: ZincSearchUserMsg::deleteUser($dialogUser);
 *
 * 3. 会话消息操作
 *    - 单个同步: ZincSearchUserMsg::syncMsg($dialogMsg);
 *    - 批量同步: ZincSearchUserMsg::batchSyncMsgs($dialogMsgs);
 *    - 删除消息: ZincSearchUserMsg::deleteMsg($dialogMsg);
 *
 * 4. 搜索功能
 *    - 关键词搜索: ZincSearchUserMsg::searchByKeyword('用户ID', '关键词');
 *
 * Class ZincSearchUserMsg
 * @package App\Module\ZincSearch
 */
class ZincSearchUserMsg
{
    /**
     * 索引名称
     */
    protected static string $indexName = 'userMsg';

    // ==============================
    // 索引管理相关方法
    // ==============================

    /**
     * 创建聊天系统索引
     *
     * @return array
     */
    public static function generateIndex(): array
    {
        // 定义映射
        $mappings = [
            'properties' => [
                // 共用字段
                'dialog_id' => ['type' => 'keyword', 'index' => true],
                'created_at' => ['type' => 'date', 'index' => true],
                'updated_at' => ['type' => 'date', 'index' => true],

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
                'sender_userid' => ['type' => 'keyword', 'index' => true],
                'msg_type' => ['type' => 'keyword', 'index' => true],
                'key' => ['type' => 'text', 'index' => true],
                'bot' => ['type' => 'numeric', 'index' => true],

                // 关联字段
                '_join_type' => ['type' => 'keyword', 'index' => true],
                '_join_key' => ['type' => 'keyword', 'index' => true],
            ]
        ];

        try {
            return ZincSearchBase::createIndex(self::$indexName, $mappings);
        } catch (\Exception $e) {
            Log::error('创建聊天系统索引失败: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 清空对话系统索引
     * 删除索引并重新创建一个空索引
     *
     * @return bool 是否清空成功
     */
    public static function clear(): bool
    {
        try {
            // 检查索引是否存在
            if (!ZincSearchBase::indexExists(self::$indexName)) {
                return true; // 索引不存在视为已清空
            }

            // 删除索引
            $deleteResult = ZincSearchBase::deleteIndex(self::$indexName);
            if (!($deleteResult['success'] ?? false)) {
                Log::error('清空对话系统索引失败: ' . ($deleteResult['error'] ?? '未知错误'));
                return false;
            }

            // 重新创建索引
            $createResult = self::generateIndex();
            return $createResult['success'] ?? false;
        } catch (\Exception $e) {
            Log::error('清空对话系统索引异常: ' . $e->getMessage());
            return false;
        }
    }

    // ==============================
    // 搜索相关方法
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
        // 构建复杂的搜索查询
        $searchParams = [
            'search_type' => 'querystring',
            'query' => [
                'term' => "+userid:{$userid} +key:*{$keyword}*"
            ],
            'from' => $from,
            'max_results' => $size,
            'sort_fields' => ["updated_at:desc"]
        ];

        try {
            return ZincSearchBase::advancedSearch(self::$indexName, $searchParams);
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
    // 会话用户相关方法
    // ==============================

    /**
     * 会话用户 - 生成文档ID
     *
     * @param WebSocketDialogUser $dialogUser
     * @return string
     */
    public static function generateUserDocId(WebSocketDialogUser $dialogUser): string
    {
        return "dialog_{$dialogUser->dialog_id}_user_{$dialogUser->userid}";
    }

    /**
     * 会话用户 - 生成文档格式
     *
     * @param WebSocketDialogUser $dialogUser
     * @return array
     */
    public static function generateUserFormat(WebSocketDialogUser $dialogUser): array
    {
        return [
            'dialog_id' => $dialogUser->dialog_id,
            'created_at' => $dialogUser->created_at,
            'updated_at' => $dialogUser->updated_at,

            'userid' => $dialogUser->userid,
            'top_at' => $dialogUser->top_at,
            'last_at' => $dialogUser->last_at,
            'mark_unread' => $dialogUser->mark_unread ?: 0,
            'silence' => $dialogUser->silence ?: 0,
            'hide' => $dialogUser->hide ?: 0,
            'color' => $dialogUser->color,

            '_join_type' => 'dialog_user',
            '_join_key' => '' // 用户文档没有父文档
        ];
    }

    /**
     * 会话用户 - 同步到ZincSearch
     *
     * @param WebSocketDialogUser $dialogUser
     * @return void
     */
    public static function syncUser(WebSocketDialogUser $dialogUser): void
    {
        try {
            if (!ZincSearchBase::indexExists(self::$indexName)) {
                self::generateIndex();
            }
            $docFormat = self::generateUserFormat($dialogUser);
            ZincSearchBase::addDoc(self::$indexName, $docFormat);
        } catch (\Exception $e) {
            Log::error('syncUser: ' . $e->getMessage());
        }
    }

    /**
     * 批量同步会话用户
     *
     * @param array|iterable $dialogUsers WebSocketDialogUser对象集合
     * @return int 成功同步的用户数
     */
    public static function batchSyncUsers($dialogUsers): int
    {
        $count = 0;
        try {
            if (!ZincSearchBase::indexExists(self::$indexName)) {
                self::generateIndex();
            }

            $docs = [];
            foreach ($dialogUsers as $dialogUser) {
                $docs[] = self::generateUserFormat($dialogUser);
                $count++;
            }

            if (!empty($docs)) {
                ZincSearchBase::addDocs(self::$indexName, $docs);
            }
        } catch (\Exception $e) {
            Log::error('batchSyncUsers: ' . $e->getMessage());
        }

        return $count;
    }

    /**
     * 会话用户 - 从ZincSearch删除
     *
     * @param WebSocketDialogUser $dialogUser
     * @return void
     */
    public static function deleteUser(WebSocketDialogUser $dialogUser): void
    {
        try {
            $docId = self::generateUserDocId($dialogUser);

            // 首先查询相关消息
            $searchParams = [
                'search_type' => 'term',
                'query' => [
                    'field' => '_join_key',
                    'term' => $docId
                ],
                'from' => 0,
                'max_results' => 1000 // 限制一次查询返回的文档数
            ];

            $result = ZincSearchBase::advancedSearch(self::$indexName, $searchParams);
            $hits = $result['data']['hits']['hits'] ?? [];

            // 批量删除子文档
            $batch = [];
            foreach ($hits as $hit) {
                if (isset($hit['_id'])) {
                    ZincSearchBase::deleteDoc(self::$indexName, $hit['_id']);
                }
            }

            // 删除用户文档
            ZincSearchBase::deleteDoc(self::$indexName, $docId);
        } catch (\Exception $e) {
            Log::error('deleteUser: ' . $e->getMessage());
        }
    }

    // ==============================
    // 会话消息相关方法
    // ==============================

    /**
     * 会话消息 - 生成父文档ID
     *
     * @param WebSocketDialogMsg $dialogMsg
     * @param string $userid
     * @return string
     */
    public static function generateMsgJoinKey(WebSocketDialogMsg $dialogMsg, string $userid): string
    {
        return "dialog_{$dialogMsg->dialog_id}_user_{$userid}";
    }

    /**
     * 会话消息 - 生成文档ID
     *
     * @param WebSocketDialogMsg $dialogMsg
     * @param string $userid
     * @return string
     */
    public static function generateMsgDocId(WebSocketDialogMsg $dialogMsg, string $userid): string
    {
        return "msg_{$dialogMsg->id}_user_{$userid}";
    }

    /**
     * 会话消息 - 生成文档格式
     *
     * @param WebSocketDialogMsg $dialogMsg
     * @param string $userid
     * @return array
     */
    public static function generateMsgFormat(WebSocketDialogMsg $dialogMsg, string $userid): array
    {
        return [
            'dialog_id' => $dialogMsg->dialog_id,
            'created_at' => $dialogMsg->created_at,
            'updated_at' => $dialogMsg->updated_at,

            'msg_id' => $dialogMsg->id,
            'sender_userid' => $dialogMsg->userid,
            'msg_type' => $dialogMsg->type,
            'key' => $dialogMsg->key,
            'bot' => $dialogMsg->bot ? 1 : 0,

            '_join_type' => 'dialog_msg',
            '_join_key' => self::generateMsgJoinKey($dialogMsg, $userid)
        ];
    }

    /**
     * 会话消息 - 同步到ZincSearch
     *
     * @param WebSocketDialogMsg $dialogMsg
     * @return void
     */
    public static function syncMsg(WebSocketDialogMsg $dialogMsg): void
    {
        try {
            // 获取此会话的所有用户
            $dialogUsers = WebSocketDialogUser::whereDialogId($dialogMsg->dialog_id)->get();

            if ($dialogUsers->isEmpty()) {
                return;
            }

            $docs = [];
            foreach ($dialogUsers as $dialogUser) {
                $docId = self::generateMsgDocId($dialogMsg, $dialogUser->userid);
                $docFormat = self::generateMsgFormat($dialogMsg, $dialogUser->userid);
                $docs[] = $docFormat;
            }

            if (!empty($docs)) {
                if (ZincSearchBase::indexExists(self::$indexName)) {
                    ZincSearchBase::addDocs(self::$indexName, $docs);
                } else {
                    self::generateIndex();
                    ZincSearchBase::addDocs(self::$indexName, $docs);
                }
            }
        } catch (\Exception $e) {
            Log::error('syncMsg: ' . $e->getMessage());
        }
    }

    /**
     * 批量同步会话消息
     *
     * @param array|iterable $dialogMsgs WebSocketDialogMsg对象集合
     * @return int 成功同步的消息数
     */
    public static function batchSyncMsgs($dialogMsgs): int
    {
        $count = 0;
        try {
            $docs = [];
            $userDialogs = [];

            // 预处理：收集所有涉及的对话ID
            $dialogIds = [];
            foreach ($dialogMsgs as $message) {
                $dialogIds[] = $message->dialog_id;
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
            foreach ($dialogMsgs as $message) {
                if (isset($userDialogs[$message->dialog_id])) {
                    foreach ($userDialogs[$message->dialog_id] as $dialogUser) {
                        $docFormat = self::generateMsgFormat($message, $dialogUser->userid);
                        $docs[] = $docFormat;
                        $count++;
                    }
                }
            }

            // 批量写入
            if (!empty($docs)) {
                if (ZincSearchBase::indexExists(self::$indexName)) {
                    ZincSearchBase::addDocs(self::$indexName, $docs);
                } else {
                    self::generateIndex();
                    ZincSearchBase::addDocs(self::$indexName, $docs);
                }
            }
        } catch (\Exception $e) {
            Log::error('batchSyncMsgs: ' . $e->getMessage());
        }

        return $count;
    }

    /**
     * 会话消息 - 从ZincSearch删除
     *
     * @param WebSocketDialogMsg $dialogMsg
     * @return void
     */
    public static function deleteMsg(WebSocketDialogMsg $dialogMsg): void
    {
        try {
            // 获取此会话的所有用户
            $dialogUsers = WebSocketDialogUser::whereDialogId($dialogMsg->dialog_id)->get();

            if ($dialogUsers->isEmpty()) {
                return;
            }

            foreach ($dialogUsers as $dialogUser) {
                $docId = self::generateMsgDocId($dialogMsg, $dialogUser->userid);
                ZincSearchBase::deleteDoc(self::$indexName, $docId);
            }
        } catch (\Exception $e) {
            Log::error('deleteMsg: ' . $e->getMessage());
        }
    }
}
