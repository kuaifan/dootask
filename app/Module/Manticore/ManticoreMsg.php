<?php

namespace App\Module\Manticore;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use App\Module\Apps;
use App\Module\Base;
use App\Module\AI;
use Illuminate\Support\Facades\Log;

/**
 * Manticore Search 消息搜索类（MVA 权限方案）
 *
 * 使用方法:
 *
 * 1. 搜索方法
 *    - 搜索消息: search($userid, $keyword, $searchType, $from, $size);
 *
 * 2. 同步方法
 *    - 单个同步: sync(WebSocketDialogMsg $msg);
 *    - 批量同步: batchSync($msgs);
 *    - 删除索引: delete($msgId);
 *
 * 3. 权限更新方法
 *    - 更新对话权限: updateDialogAllowedUsers($dialogId);
 *
 * 4. 工具方法
 *    - 清空索引: clear();
 *    - 判断是否索引: shouldIndex($msg);
 */
class ManticoreMsg
{
    /**
     * 可索引的消息类型
     */
    public const INDEXABLE_TYPES = ['text', 'file', 'record', 'meeting', 'vote'];

    /**
     * 最大内容长度（字符）
     */
    public const MAX_CONTENT_LENGTH = 50000; // 50K 字符

    /**
     * 判断消息是否应该被索引
     *
     * @param WebSocketDialogMsg $msg 消息模型
     * @return bool 是否应该索引
     */
    public static function shouldIndex(WebSocketDialogMsg $msg): bool
    {
        // 1. 排除机器人消息
        if ($msg->bot === 1) {
            return false;
        }

        // 2. 检查消息类型
        if (!in_array($msg->type, self::INDEXABLE_TYPES)) {
            return false;
        }

        // 3. 排除 key 为空的消息
        if (empty($msg->key)) {
            return false;
        }

        return true;
    }

    /**
     * 搜索消息（支持全文、向量、混合搜索）
     *
     * @param int $userid 用户ID
     * @param string $keyword 搜索关键词
     * @param string $searchType 搜索类型: text/vector/hybrid
     * @param int $from 起始位置
     * @param int $size 返回数量
     * @return array 搜索结果
     */
    public static function search(int $userid, string $keyword, string $searchType = 'hybrid', int $from = 0, int $size = 20): array
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
                    // 纯全文搜索
                    return self::formatSearchResults(
                        ManticoreBase::msgFullTextSearch($keyword, $userid, $size, $from)
                    );

                case 'vector':
                    // 纯向量搜索（需要先获取 embedding）
                    $embedding = self::getEmbedding($keyword);
                    if (empty($embedding)) {
                        // embedding 获取失败，降级到全文搜索
                        return self::formatSearchResults(
                            ManticoreBase::msgFullTextSearch($keyword, $userid, $size, $from)
                        );
                    }
                    return self::formatSearchResults(
                        ManticoreBase::msgVectorSearch($embedding, $userid, $size)
                    );

                case 'hybrid':
                default:
                    // 混合搜索
                    $embedding = self::getEmbedding($keyword);
                    return self::formatSearchResults(
                        ManticoreBase::msgHybridSearch($keyword, $embedding, $userid, $size)
                    );
            }
        } catch (\Exception $e) {
            Log::error('Manticore msg search error: ' . $e->getMessage());
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
            // 调用 AI 模块获取 embedding
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
                'id' => $item['msg_id'],
                'msg_id' => $item['msg_id'],
                'dialog_id' => $item['dialog_id'],
                'userid' => $item['userid'],
                'msg_type' => $item['msg_type'],
                'content_preview' => isset($item['content']) ? mb_substr($item['content'], 0, 200) : null,
                'created_at' => $item['created_at'] ?? null,
                'relevance' => $item['relevance'] ?? $item['similarity'] ?? $item['rrf_score'] ?? 0,
            ];
        }
        return $formatted;
    }

    // ==============================
    // 权限计算方法（MVA 方案核心）
    // ==============================

    /**
     * 获取消息的 allowed_users 列表
     * 
     * 对话的所有成员都有权限查看该对话的消息
     *
     * @param WebSocketDialogMsg $msg 消息模型
     * @return array 有权限的用户ID数组
     */
    public static function getAllowedUsers(WebSocketDialogMsg $msg): array
    {
        return self::getDialogUserIds($msg->dialog_id);
    }

    /**
     * 获取对话的所有成员ID
     *
     * @param int $dialogId 对话ID
     * @return array 成员用户ID数组
     */
    public static function getDialogUserIds(int $dialogId): array
    {
        if ($dialogId <= 0) {
            return [];
        }

        return WebSocketDialogUser::where('dialog_id', $dialogId)
            ->pluck('userid')
            ->toArray();
    }

    // ==============================
    // 同步方法
    // ==============================

    /**
     * 同步单个消息到 Manticore（含 allowed_users）
     *
     * @param WebSocketDialogMsg $msg 消息模型
     * @return bool 是否成功
     */
    public static function sync(WebSocketDialogMsg $msg): bool
    {
        if (!Apps::isInstalled("manticore")) {
            return false;
        }

        // 检查是否应该索引
        if (!self::shouldIndex($msg)) {
            // 不符合索引条件，尝试删除已存在的索引
            return ManticoreBase::deleteMsgVector($msg->id);
        }

        try {
            // 提取消息内容（使用 key 字段）
            $content = $msg->key ?? '';

            // 限制内容长度
            $content = mb_substr($content, 0, self::MAX_CONTENT_LENGTH);

            // 获取 embedding（如果有内容且 AI 可用）
            $embedding = null;
            if (!empty($content) && Apps::isInstalled('ai')) {
                $embeddingResult = self::getEmbedding($content);
                if (!empty($embeddingResult)) {
                    $embedding = '[' . implode(',', $embeddingResult) . ']';
                }
            }

            // 获取消息的 allowed_users
            $allowedUsers = self::getAllowedUsers($msg);

            // 写入 Manticore（含 allowed_users）
            $result = ManticoreBase::upsertMsgVector([
                'msg_id' => $msg->id,
                'dialog_id' => $msg->dialog_id,
                'userid' => $msg->userid,
                'msg_type' => $msg->type,
                'content' => $content,
                'content_vector' => $embedding,
                'allowed_users' => $allowedUsers,
                'created_at' => $msg->created_at ? $msg->created_at->timestamp : time(),
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Manticore msg sync error: ' . $e->getMessage(), [
                'msg_id' => $msg->id,
                'dialog_id' => $msg->dialog_id,
            ]);
            return false;
        }
    }

    /**
     * 批量同步消息
     *
     * @param iterable $msgs 消息列表
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $msgs): int
    {
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        $count = 0;
        foreach ($msgs as $msg) {
            if (self::sync($msg)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 删除消息索引
     *
     * @param int $msgId 消息ID
     * @return bool 是否成功
     */
    public static function delete(int $msgId): bool
    {
        if (!Apps::isInstalled("manticore")) {
            return false;
        }

        return ManticoreBase::deleteMsgVector($msgId);
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

        return ManticoreBase::clearAllMsgVectors();
    }

    /**
     * 获取已索引消息数量
     *
     * @return int 数量
     */
    public static function getIndexedCount(): int
    {
        if (!Apps::isInstalled("manticore")) {
            return 0;
        }

        return ManticoreBase::getIndexedMsgCount();
    }

    // ==============================
    // 权限更新方法（MVA 方案）
    // ==============================

    /**
     * 更新对话下所有消息的 allowed_users 权限列表
     * 从 MySQL 获取最新的对话成员并更新到 Manticore
     *
     * @param int $dialogId 对话ID
     * @return int 更新的消息数量
     */
    public static function updateDialogAllowedUsers(int $dialogId): int
    {
        if (!Apps::isInstalled("manticore") || $dialogId <= 0) {
            return 0;
        }

        try {
            $userids = self::getDialogUserIds($dialogId);
            return ManticoreBase::updateDialogAllowedUsers($dialogId, $userids);
        } catch (\Exception $e) {
            Log::error('Manticore updateDialogAllowedUsers error: ' . $e->getMessage(), ['dialog_id' => $dialogId]);
            return 0;
        }
    }
}
