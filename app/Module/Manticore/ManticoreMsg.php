<?php

namespace App\Module\Manticore;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use App\Module\Apps;
use App\Module\Base;
use App\Module\AI;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;

/**
 * Manticore Search 消息搜索类
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
     * @param int $dialogId 对话ID（0表示不限制）
     * @return array 搜索结果
     */
    public static function search(int $userid, string $keyword, string $searchType = 'hybrid', int $from = 0, int $size = 20, int $dialogId = 0): array
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
                    // 纯全文搜索
                    return self::formatSearchResults(
                        ManticoreBase::msgFullTextSearch($keyword, $userid, $size, $from, $dialogId)
                    );

                case 'vector':
                    // 纯向量搜索（需要先获取 embedding）
                    $embedding = ManticoreBase::getEmbedding($keyword);
                    if (empty($embedding)) {
                        // embedding 获取失败，降级到全文搜索
                        return self::formatSearchResults(
                            ManticoreBase::msgFullTextSearch($keyword, $userid, $size, $from, $dialogId)
                        );
                    }
                    return self::formatSearchResults(
                        ManticoreBase::msgVectorSearch($embedding, $userid, $size, $dialogId)
                    );

                case 'hybrid':
                default:
                    // 混合搜索
                    $embedding = ManticoreBase::getEmbedding($keyword);
                    return self::formatSearchResults(
                        ManticoreBase::msgHybridSearch($keyword, $embedding, $userid, $size, $dialogId)
                    );
            }
        } catch (\Exception $e) {
            Log::error('Manticore msg search error: ' . $e->getMessage());
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

    /**
     * 按对话搜索消息（用于对话列表搜索）
     * 
     * 返回包含匹配消息的对话列表，每个对话只返回一次
     * 当 Manticore 未安装时，回退到 MySQL LIKE 搜索
     *
     * @param int $userid 用户ID
     * @param string $keyword 搜索关键词
     * @param int $from 起始位置
     * @param int $size 返回数量
     * @return array 对话列表
     */
    public static function searchDialogs(int $userid, string $keyword, int $from = 0, int $size = 20): array
    {
        if (empty($keyword)) {
            return [];
        }

        // 未安装 Manticore 时使用 MySQL 回退搜索
        if (!Apps::isInstalled("search")) {
            return self::searchDialogsByMysql($userid, $keyword, $from, $size);
        }

        try {
            // 使用全文搜索获取更多结果，然后按对话分组
            $results = ManticoreBase::msgFullTextSearch($keyword, $userid, 100, 0);

            if (empty($results)) {
                return [];
            }

            // 收集所有对话ID
            $dialogIds = array_unique(array_column($results, 'dialog_id'));

            // 获取用户在这些对话中的信息
            $dialogUsers = WebSocketDialogUser::where('userid', $userid)
                ->whereIn('dialog_id', $dialogIds)
                ->get()
                ->keyBy('dialog_id');

            // 按对话分组，每个对话只保留最相关的消息
            $msgs = [];
            $seenDialogs = [];
            foreach ($results as $item) {
                $dialogId = $item['dialog_id'];
                
                // 每个对话只取第一条（最相关的）
                if (isset($seenDialogs[$dialogId])) {
                    continue;
                }
                $seenDialogs[$dialogId] = true;

                // 获取用户在该对话的信息
                $dialogUser = $dialogUsers->get($dialogId);
                if (!$dialogUser) {
                    continue;
                }

                $msgs[] = [
                    'id' => $dialogId,
                    'search_msg_id' => $item['msg_id'],
                    'user_at' => $dialogUser->updated_at ? Carbon::parse($dialogUser->updated_at)->format('Y-m-d H:i:s') : null,
                    'mark_unread' => $dialogUser->mark_unread,
                    'silence' => $dialogUser->silence,
                    'hide' => $dialogUser->hide,
                    'color' => $dialogUser->color,
                    'top_at' => $dialogUser->top_at ? Carbon::parse($dialogUser->top_at)->format('Y-m-d H:i:s') : null,
                    'last_at' => $dialogUser->last_at ? Carbon::parse($dialogUser->last_at)->format('Y-m-d H:i:s') : null,
                ];

                // 已达到需要的数量
                if (count($msgs) >= $from + $size) {
                    break;
                }
            }

            // 应用分页
            return array_slice($msgs, $from, $size);
        } catch (\Exception $e) {
            Log::error('Manticore searchDialogs error: ' . $e->getMessage());
            // 出错时回退到 MySQL 搜索
            return self::searchDialogsByMysql($userid, $keyword, $from, $size);
        }
    }

    /**
     * MySQL 回退搜索（按对话搜索消息）
     * 
     * 通过联表查询获取用户有权限的对话中匹配的消息
     *
     * @param int $userid 用户ID
     * @param string $keyword 搜索关键词
     * @param int $from 起始位置
     * @param int $size 返回数量
     * @return array 对话列表
     */
    private static function searchDialogsByMysql(int $userid, string $keyword, int $from = 0, int $size = 20): array
    {
        $items = DB::table('web_socket_dialog_users as u')
            ->select([
                'd.*',
                'u.top_at',
                'u.last_at',
                'u.mark_unread',
                'u.silence',
                'u.hide',
                'u.color',
                'u.updated_at as user_at',
                'm.id as search_msg_id'
            ])
            ->join('web_socket_dialogs as d', 'u.dialog_id', '=', 'd.id')
            ->join('web_socket_dialog_msgs as m', 'm.dialog_id', '=', 'd.id')
            ->where('u.userid', $userid)
            ->where('m.bot', 0)
            ->whereNull('d.deleted_at')
            ->where('m.key', 'like', "%{$keyword}%")
            ->orderByDesc('m.id')
            ->offset($from)
            ->limit($size)
            ->get()
            ->all();

        $msgs = [];
        foreach ($items as $item) {
            $msgs[] = [
                'id' => $item->id,
                'search_msg_id' => $item->search_msg_id,
                'user_at' => Carbon::parse($item->user_at)->format('Y-m-d H:i:s'),
                'mark_unread' => $item->mark_unread,
                'silence' => $item->silence,
                'hide' => $item->hide,
                'color' => $item->color,
                'top_at' => Carbon::parse($item->top_at)->format('Y-m-d H:i:s'),
                'last_at' => Carbon::parse($item->last_at)->format('Y-m-d H:i:s'),
            ];
        }
        return $msgs;
    }

    // ==============================
    // 权限计算方法
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
     * @param bool $withVector 是否同时生成向量（默认 false，向量由后台任务生成）
     * @return bool 是否成功
     */
    public static function sync(WebSocketDialogMsg $msg, bool $withVector = false): bool
    {
        if (!Apps::isInstalled("search")) {
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

            // 只有明确要求时才生成向量（默认不生成，由后台任务处理）
            $embedding = null;
            if ($withVector && !empty($content) && Apps::isInstalled('ai')) {
                $embeddingResult = ManticoreBase::getEmbedding($content);
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
     * @param bool $withVector 是否同时生成向量
     * @return int 成功同步的数量
     */
    public static function batchSync(iterable $msgs, bool $withVector = false): int
    {
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        $count = 0;
        foreach ($msgs as $msg) {
            if (self::sync($msg, $withVector)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 批量生成向量（供后台任务调用）
     *
     * @param array $msgIds 消息ID数组
     * @param int $batchSize 每批 embedding 数量
     * @return int 成功生成向量的数量
     */
    public static function generateVectorsBatch(array $msgIds, int $batchSize = 20): int
    {
        if (!Apps::isInstalled("search") || !Apps::isInstalled('ai') || empty($msgIds)) {
            return 0;
        }

        $count = 0;

        // 分批处理
        foreach (array_chunk($msgIds, $batchSize) as $batchIds) {
            // 获取消息
            $msgs = WebSocketDialogMsg::whereIn('id', $batchIds)
                ->whereIn('type', self::INDEXABLE_TYPES)
                ->where('bot', '!=', 1)
                ->whereNotNull('key')
                ->where('key', '!=', '')
                ->get()
                ->keyBy('id');

            if ($msgs->isEmpty()) {
                continue;
            }

            // 准备文本
            $texts = [];
            $idsArray = [];
            foreach ($batchIds as $id) {
                if (isset($msgs[$id])) {
                    $content = mb_substr($msgs[$id]->key ?? '', 0, self::MAX_CONTENT_LENGTH);
                    if (!empty($content)) {
                        $texts[] = $content;
                        $idsArray[] = $id;
                    }
                }
            }

            if (empty($texts)) {
                continue;
            }

            // 批量获取 embeddings
            $result = AI::getBatchEmbeddings($texts);

            if (Base::isError($result)) {
                continue;
            }

            $embeddings = $result['data'] ?? [];

            // 构建批量更新数据 [msg_id => vectorStr]
            $vectorData = [];
            foreach ($embeddings as $index => $embedding) {
                if (empty($embedding) || !is_array($embedding)) {
                    continue;
                }

                $msgId = $idsArray[$index] ?? null;
                if (!$msgId) {
                    continue;
                }

                $vectorData[$msgId] = '[' . implode(',', $embedding) . ']';
            }

            // 批量更新向量（优化：减少数据库操作次数）
            if (!empty($vectorData)) {
                $batchCount = ManticoreBase::batchUpdateMsgVectors($vectorData);
                $count += $batchCount;
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
        if (!Apps::isInstalled("search")) {
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
        if (!Apps::isInstalled("search")) {
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
        if (!Apps::isInstalled("search")) {
            return 0;
        }

        return ManticoreBase::getIndexedMsgCount();
    }

    // ==============================
    // 权限更新方法
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
        if (!Apps::isInstalled("search") || $dialogId <= 0) {
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
