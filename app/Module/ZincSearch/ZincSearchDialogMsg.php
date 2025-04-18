<?php

namespace App\Module\ZincSearch;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Swoole\Coroutine;

/**
 * ZincSearch 会话消息类
 *
 * 使用方法:
 *
 * 1. 基础方法
 *     - 清空所有数据: clear();
 *
 * 2. 搜索方法
 *    - 关键词搜索: search('用户ID', '关键词');
 *
 * 3. 基本方法
 *    - 单个同步: sync(WebSocketDialogMsg $dialogMsg);
 *    - 批量同步: batchSync(WebSocketDialogMsg[] $dialogMsgs);
 *    - 用户同步: userSync(WebSocketDialogUser $dialogUser);
 *    - 删除消息: delete(WebSocketDialogMsg|WebSocketDialogUser|int $data);
 */
class ZincSearchDialogMsg
{
    /**
     * 索引名称
     */
    protected static string $indexNameMsg = 'dialogMsg';
    protected static string $indexNameUser = 'dialogUser';

    // ==============================
    // 基础方法
    // ==============================

    /**
     * 确保索引存在
     */
    private static function ensureIndex(): bool
    {
        if (!ZincSearchBase::indexExists(self::$indexNameMsg)) {
            $mappings = [
                'properties' => [
                    // 拓展数据
                    'dialog_userid' => ['type' => 'keyword', 'index' => true],  // 对话ID+用户ID
                    'to_userid' => ['type' => 'numeric', 'index' => true],      // 此消息发给的用户ID

                    // 消息数据
                    'id' => ['type' => 'numeric', 'index' => true],
                    'dialog_id' => ['type' => 'numeric', 'index' => true],
                    'dialog_type' => ['type' => 'keyword', 'index' => true],
                    'session_id' => ['type' => 'numeric', 'index' => true],
                    'userid' => ['type' => 'numeric', 'index' => true],
                    'type' => ['type' => 'keyword', 'index' => true],
                    'key' => ['type' => 'text', 'index' => true],
                    'created_at' => ['type' => 'date', 'index' => true],
                    'updated_at' => ['type' => 'date', 'index' => true],
                ]
            ];
            $result = ZincSearchBase::createIndex(self::$indexNameMsg, $mappings);
            return $result['success'] ?? false;
        }
        if (!ZincSearchBase::indexExists(self::$indexNameUser)) {
            $mappings = [
                'properties' => [
                    // 拓展数据
                    'dialog_userid' => ['type' => 'keyword', 'index' => true],  // 对话ID+用户ID

                    // 用户数据
                    'id' => ['type' => 'numeric', 'index' => true],
                    'dialog_id' => ['type' => 'numeric', 'index' => true],
                    'userid' => ['type' => 'numeric', 'index' => true],
                    'top_at' => ['type' => 'date', 'index' => true],
                    'last_at' => ['type' => 'date', 'index' => true],
                    'mark_unread' => ['type' => 'numeric', 'index' => true],
                    'silence' => ['type' => 'numeric', 'index' => true],
                    'hide' => ['type' => 'numeric', 'index' => true],
                    'color' => ['type' => 'keyword', 'index' => true],
                    'created_at' => ['type' => 'date', 'index' => true],
                    'updated_at' => ['type' => 'date', 'index' => true],
                ]
            ];
            $result = ZincSearchBase::createIndex(self::$indexNameUser, $mappings);
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
        // 检查索引是否存在然后删除
        if (ZincSearchBase::indexExists(self::$indexNameMsg)) {
            $deleteResult = ZincSearchBase::deleteIndex(self::$indexNameMsg);
            if (!($deleteResult['success'] ?? false)) {
                return false;
            }
        }
        if (ZincSearchBase::indexExists(self::$indexNameUser)) {
            $deleteResult = ZincSearchBase::deleteIndex(self::$indexNameUser);
            if (!($deleteResult['success'] ?? false)) {
                return false;
            }
        }

        return self::ensureIndex();
    }

    // ==============================
    // 搜索方法
    // ==============================

    /**
     * 根据用户ID和消息关键词搜索会话
     *
     * @param string $userid 用户ID
     * @param string $keyword 消息关键词
     * @param int $from 起始位置
     * @param int $size 返回结果数量
     * @return array
     */
    public static function search(string $userid, string $keyword, int $from = 0, int $size = 20): array
    {
        $searchParams = [
            'query' => [
                'bool' => [
                    'must' => [
                        ['term' => ['to_userid' => $userid]],
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
            $result = ZincSearchBase::elasticSearch(self::$indexNameMsg, $searchParams);
            $hits = $result['data']['hits']['hits'] ?? [];

            // 收集所有的用户信息
            $dialogUserids = [];
            foreach ($hits as $hit) {
                $source = $hit['_source'];
                $dialogUserids[] = $source['dialog_userid'];
            }
            $userInfos = self::searchUser(array_unique($dialogUserids));

            // 组合返回结果，将用户信息合并到消息中
            $msgs = [];
            foreach ($hits as $hit) {
                $msgInfo = $hit['_source'];
                $userInfo = $userInfos[$msgInfo['dialog_userid']] ?? [];
                if ($userInfo) {
                    $msgs[] = [
                        'id' => $msgInfo['dialog_id'],
                        'search_msg_id' => $msgInfo['id'],
                        'user_at' =>  Carbon::parse($msgInfo['updated_at'])->format('Y-m-d H:i:s'),

                        'mark_unread' => $userInfo['mark_unread'],
                        'silence' => $userInfo['silence'],
                        'hide' => $userInfo['hide'],
                        'color' => $userInfo['color'],
                        'top_at' => Carbon::parse($userInfo['top_at'])->format('Y-m-d H:i:s'),
                        'last_at' =>  Carbon::parse($userInfo['last_at'])->format('Y-m-d H:i:s'),
                    ];
                }
            }
            return $msgs;
        } catch (\Exception $e) {
            Log::error('search: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 根据对话用户ID搜索用户信息
     * @param array $dialogUserids
     * @return array
     */
    private static function searchUser(array $dialogUserids): array
    {
        if (empty($dialogUserids)) {
            return [];
        }

        $userInfos = [];

        // 构建用户查询条件
        $userSearchParams = [
            'query' => [
                'bool' => [
                    'should' => []
                ]
            ],
            'size' => count($dialogUserids) // 确保取到所有符合条件的记录
        ];

        // 添加所有 dialog_userid 到查询条件
        foreach ($dialogUserids as $dialogUserid) {
            $userSearchParams['query']['bool']['should'][] = [
                'term' => ['dialog_userid' => $dialogUserid]
            ];
        }

        // 查询用户信息
        $userResult = ZincSearchBase::elasticSearch(self::$indexNameUser, $userSearchParams);
        $userHits = $userResult['data']['hits']['hits'] ?? [];

        // 以 dialog_userid 为键保存用户信息
        foreach ($userHits as $userHit) {
            $userSource = $userHit['_source'];
            $userInfos[$userSource['dialog_userid']] = $userSource;
        }

        return $userInfos;
    }

    // ==============================
    // 基本方法
    // ==============================

    /**
     * 生成 dialog_userid
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
    private static function generateMsgData(WebSocketDialogMsg $dialogMsg, WebSocketDialogUser $dialogUser): array
    {
        return [
            '_id' => self::$indexNameMsg . "_" . $dialogMsg->id . "_" . $dialogUser->userid,
            'dialog_userid' => self::generateDialogUserid($dialogUser),
            'to_userid' => $dialogUser->userid,

            'id' => $dialogMsg->id,
            'dialog_id' => $dialogMsg->dialog_id,
            'dialog_type' => $dialogMsg->dialog_type,
            'session_id' => $dialogMsg->session_id,
            'userid' => $dialogMsg->userid,
            'type' => $dialogMsg->type,
            'key' => $dialogMsg->key,
            'created_at' => $dialogMsg->created_at,
            'updated_at' => $dialogMsg->updated_at,
        ];
    }
    private static function generateUserData(WebSocketDialogUser $dialogUser): array
    {
        return [
            '_id' => self::$indexNameUser . "_" . $dialogUser->id,
            'dialog_userid' => self::generateDialogUserid($dialogUser),

            'id' => $dialogUser->id,
            'dialog_id' => $dialogUser->dialog_id,
            'userid' => $dialogUser->userid,
            'top_at' => $dialogUser->top_at,
            'last_at' => $dialogUser->last_at,
            'mark_unread' => $dialogUser->mark_unread,
            'silence' => $dialogUser->silence,
            'hide' => $dialogUser->hide,
            'color' => $dialogUser->color,
            'created_at' => $dialogUser->created_at,
            'updated_at' => $dialogUser->updated_at,
        ];
    }

    /**
     * 同步消息
     *
     * @param WebSocketDialogMsg $dialogMsg
     * @return bool
     */
    public static function sync(WebSocketDialogMsg $dialogMsg): bool
    {
        if (!self::ensureIndex()) {
            return false;
        }

        if ($dialogMsg->bot) {
            // 如果是机器人消息，跳过
            return true;
        }

        try {
            // 获取此会话的所有用户
            $dialogUsers = WebSocketDialogUser::whereDialogId($dialogMsg->dialog_id)->get();

            if ($dialogUsers->isEmpty()) {
                return true;
            }

            $msgs = [];
            $users = [];
            foreach ($dialogUsers as $dialogUser) {
                if (empty($dialogMsg->key)) {
                    // 如果消息没有关键词，跳过
                    continue;
                }
                if ($dialogUser->userid == 0) {
                    // 跳过系统用户
                    continue;
                }
                $msgs[] = self::generateMsgData($dialogMsg, $dialogUser);
                $users[$dialogUser->id] = self::generateUserData($dialogUser);
            }

            if ($msgs) {
                // 批量写入消息
                ZincSearchBase::addDocs(self::$indexNameMsg, $msgs);
            }

            if ($users) {
                // 批量写入用户
                ZincSearchBase::addDocs(self::$indexNameUser, array_values($users));
            }

            return true;
        } catch (\Exception $e) {
            Log::error('sync: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 批量同步消息
     *
     * @param WebSocketDialogMsg[] $dialogMsgs
     * @return int 成功同步的消息数
     */
    public static function batchSync($dialogMsgs): int
    {
        if (!self::ensureIndex()) {
            return 0;
        }

        $count = 0;
        try {
            $msgs = [];
            $users = [];
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
                if ($dialogMsg->bot) {
                    // 如果是机器人消息，跳过
                    continue;
                }
                /** @var WebSocketDialogUser $dialogUser */
                foreach ($userDialogs[$dialogMsg->dialog_id] as $dialogUser) {
                    if (empty($dialogMsg->key)) {
                        // 如果消息没有关键词，跳过
                        continue;
                    }
                    if ($dialogUser->userid == 0) {
                        // 跳过系统用户
                        continue;
                    }
                    $msgs[] = self::generateMsgData($dialogMsg, $dialogUser);
                    $users[$dialogUser->id] = self::generateUserData($dialogUser);
                    $count++;
                }
            }

            if ($msgs) {
                // 批量写入消息
                ZincSearchBase::addDocs(self::$indexNameMsg, $msgs);
            }

            if ($users) {
                // 批量写入用户
                ZincSearchBase::addDocs(self::$indexNameUser, array_values($users));
            }

        } catch (\Exception $e) {
            Log::error('batchSync: ' . $e->getMessage());
        }

        return $count;
    }

    /**
     * 同步用户
     * @param WebSocketDialogUser $dialogUser
     * @return bool
     */
    public static function userSync(WebSocketDialogUser $dialogUser): bool
    {
        if (!self::ensureIndex()) {
            return false;
        }
        $data = self::generateUserData($dialogUser);

        // 生成查询用户条件
        $searchParams = [
            'query' => [
                'bool' => [
                    'must' => [
                        ['term' => ['dialog_userid' => $data['dialog_userid']]]
                    ]
                ]
            ],
            'size' => 1
        ];

        try {
            // 查询用户是否存在
            $result = ZincSearchBase::elasticSearch(self::$indexNameUser, $searchParams);
            $hits = $result['data']['hits']['hits'] ?? [];

            // 同步用户（存在更新、不存在添加）
            $result = ZincSearchBase::addDoc(self::$indexNameUser, $data);
            if (!isset($result['success'])) {
                return false;
            }

            // 用户不存在，同步消息
            if (empty($hits)) {
                go(function () use ($dialogUser) {
                    Coroutine::sleep(0.1);

                    $lastId = 0;        // 上次同步的最后ID
                    $batchSize = 500;   // 每批处理的消息数量

                    // 分批同步消息
                    do {
                        // 获取一批
                        $dialogMsgs = WebSocketDialogMsg::whereDialogId($dialogUser->dialog_id)
                            ->where('id', '>', $lastId)
                            ->orderBy('id')
                            ->limit($batchSize)
                            ->get();

                        if ($dialogMsgs->isEmpty()) {
                            break;
                        }

                        // 同步数据
                        ZincSearchDialogMsg::batchSync($dialogMsgs);

                        // 更新最后ID
                        $lastId = $dialogMsgs->last()->id;
                    } while (count($dialogMsgs) == $batchSize);
                });
            }

            return true;
        } catch (\Exception $e) {
            Log::error('userSync: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除
     *
     * @param WebSocketDialogMsg|WebSocketDialogUser|int $data
     * @return int
     */
    public static function delete(mixed $data): int
    {
        $batchSize = 500;   // 每批处理的文档数量
        $totalDeleted = 0;  // 总共删除的文档数量
        $from = 0;

        // 根据数据类型生成查询条件
        if ($data instanceof WebSocketDialogMsg) {
            $query = [
                'field' => 'id',
                'term' => (string) $data->id
            ];
        } elseif ($data instanceof WebSocketDialogUser) {
            $query = [
                'field' => 'dialog_userid',
                'term' => self::generateDialogUserid($data),
            ];
        } else {
            $query = [
                'field' => 'id',
                'term' => (string) $data
            ];
        }

        try {
            while (true) {
                // 根据消息ID查找相关文档
                $result = ZincSearchBase::advancedSearch(self::$indexNameMsg, [
                    'search_type' => 'term',
                    'query' => $query,
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
                        ZincSearchBase::deleteDoc(self::$indexNameMsg, $hit['_id']);
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
            Log::error('delete: ' . $e->getMessage());
        }

        return $totalDeleted;
    }
}
