<?php

namespace App\Module;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Illuminate\Support\Facades\Log;

class ElasticSearch
{
    /**
     * Elasticsearch客户端实例
     *
     * @var \Elastic\Elasticsearch\Client
     */
    public $client;

    /**
     * 当前操作的索引名称
     *
     * @var string
     */
    protected $index;

    /**
     * 构造函数
     *
     * @param null $index 默认索引名称
     * @throws \Elastic\Elasticsearch\Exception\ConfigException
     */
    public function __construct($index = null)
    {
        $host = env('ELASTICSEARCH_HOST', env('APP_IPPR') . '.15');
        $port = env('ELASTICSEARCH_PORT', '9200');
        $scheme = env('ELASTICSEARCH_SCHEME', 'http');
        $user = env('ELASTICSEARCH_USER', '');
        $pass = env('ELASTICSEARCH_PASS', '');
        $verifi = env('ELASTICSEARCH_VERIFI', false);
        $ca = env('ELASTICSEARCH_CA', '');
        $key = env('ELASTICSEARCH_KEY', '');
        $cert = env('ELASTICSEARCH_CERT', '');
        // 为8.x版本客户端配置连接
        $config = [
            'hosts' => ["{$scheme}://{$host}:{$port}"]
        ];

        // 如果设置了用户名和密码
        if (!empty($user)) {
            $config['basicAuthentication'] = [$user, $pass];
        }
        
        $config['SSLVerification'] = $verifi;
        if ($verifi) {
            $config['SSLCert'] = $cert;
            $config['CABundle'] = $ca;
            $config['SSLKey'] = $key;
        }
        // 8.x版本使用ClientBuilder::fromConfig创建客户端
        $this->client = ClientBuilder::fromConfig($config);

        if ($index) {
            $this->index = $index . env("ES_INDEX_SUFFIX", "");
        }
    }

    /**
     * 设置索引名称
     *
     * @param string $index
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * 检查索引是否存在
     *
     * @return bool
     * @throws \Exception
     */
    public function indexExists()
    {
        $params = ['index' => $this->index];
        return $this->client->indices()->exists($params)->asBool();
    }

    /**
     * 创建索引
     *
     * @param array $settings 索引设置
     * @param array $mappings 字段映射
     * @return array
     */
    public function createIndex($settings = [], $mappings = [])
    {
        $params = [
            'index' => $this->index
        ];

        $body = [];
        if (!empty($settings)) {
            $body['settings'] = $settings;
        }

        if (!empty($mappings)) {
            $body['mappings'] = $mappings;
        }

        if (!empty($body)) {
            $params['body'] = $body;
        }

        try {
            // 在8.x中，索引操作位于indices()命名空间
            return $this->client->indices()->create($params)->asArray();
        } catch (\Exception $e) {
            Log::error('创建Elasticsearch索引失败: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 删除索引
     * @return array
     */
    public function deleteIndex()
    {
        try {
            $params = ['index' => $this->index];
            return $this->client->indices()->delete($params)->asArray();
        } catch (\Exception $e) {
            Log::error('删除Elasticsearch索引失败: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 批量操作（批量添加/更新/删除文档）
     *
     * @param array $operations 批量操作的数据
     * @return array
     */
    public function bulk($operations)
    {
        try {
            // 在8.x中，批量操作API签名相同，但内部实现有所变化
            return $this->client->bulk($operations)->asArray();
        } catch (\Exception $e) {
            Log::error('批量操作失败: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 索引单个文档
     *
     * @param array $document 文档数据
     * @param string $id 文档ID
     * @param string|null $routing 路由值，用于父子文档
     * @return array
     */
    public function indexDocument($document, $id, $routing = null)
    {
        $params = [
            'index' => $this->index,
            'id' => $id,
            'body' => $document
        ];

        if ($routing) {
            $params['routing'] = $routing;
        }

        try {
            return $this->client->index($params)->asArray();
        } catch (\Exception $e) {
            Log::error('索引文档失败: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 删除文档
     *
     * @param string $id 文档ID
     * @param string|null $routing 路由值，用于父子文档
     * @return array
     */
    public function deleteDocument($id, $routing = null)
    {
        $params = [
            'index' => $this->index,
            'id' => $id
        ];

        if ($routing) {
            $params['routing'] = $routing;
        }

        try {
            return $this->client->delete($params)->asArray();
        } catch (MissingParameterException $e) {
            // 文档不存在时返回成功
            return ['result' => 'not_found', 'error' => $e->getMessage()];
        } catch (\Exception $e) {
            Log::error('删除文档失败: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 通用搜索方法
     *
     * @param array $query 搜索查询
     * @param int $from 起始位置
     * @param int $size 返回结果数量
     * @param array $sort 排序规则
     * @return array
     */
    public function search($query, $from = 0, $size = 10, $sort = [])
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query' => $query,
                'from' => $from,
                'size' => $size
            ]
        ];

        if (!empty($sort)) {
            $params['body']['sort'] = $sort;
        }

        try {
            return $this->client->search($params)->asArray();
        } catch (\Exception $e) {
            Log::error('搜索失败: ' . $e->getMessage());
            return ['error' => $e->getMessage(), 'hits' => ['total' => ['value' => 0], 'hits' => []]];
        }
    }

    /**
     * 刷新索引
     * @return array
     */
    public function refreshIndex()
    {
        $params = [
            'index' => $this->index
        ];

        try {
            return $this->client->indices()->refresh($params)->asArray();
        } catch (\Exception $e) {
            Log::error('刷新索引失败: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 检查索引映射
     * @return array
     */
    public function checkIndexMapping()
    {
        try {
            return $this->client->indices()->getMapping(['index' => $this->index])->asArray();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * 创建聊天系统索引 - 使用父子关系
     * @return array
     */
    public function createDialogUserMsgIndex()
    {
        // 定义映射
        $mappings = [
            'properties' => [
                // 共用字段
                'dialog_id' => ['type' => 'keyword'],
                'created_at' => ['type' => 'date'],
                'updated_at' => ['type' => 'date'],

                // dialog_users 字段
                'userid' => ['type' => 'keyword'],
                'top_at' => ['type' => 'date'],
                'last_at' => ['type' => 'date'],
                'mark_unread' => ['type' => 'integer'],
                'silence' => ['type' => 'integer'],
                'hide' => ['type' => 'integer'],
                'color' => ['type' => 'keyword'],

                // dialog_msgs 字段
                'msg_id' => ['type' => 'keyword'],
                'sender_userid' => ['type' => 'keyword'],
                'msg_type' => ['type' => 'keyword'],
                'key' => ['type' => 'text'],
                'bot' => ['type' => 'integer'],

                // Join字段定义父子关系
                'relationship' => [
                    'type' => 'join',
                    'relations' => [
                        'dialog_user' => 'dialog_msg' // dialog_user是父文档，dialog_msg是子文档
                    ]
                ],
            ]
        ];

        // 索引设置
        $settings = [
            'number_of_shards' => 5,
            'number_of_replicas' => 1,
            'refresh_interval' => '5s'
        ];

        return $this->createIndex($settings, $mappings);
    }

    /**
     * 构建对话系统特定的搜索 - 根据用户ID和消息关键词搜索会话
     *
     * @param string $userid 用户ID
     * @param string $keyword 消息关键词
     * @param int $size 返回结果数量
     * @return array
     */
    public function searchDialogsByUserAndKeyword($userid, $keyword, $size = 20)
    {
        // 注意这里的类型名称要与创建索引时的一致
        $query = [
            'bool' => [
                'must' => [
                    [
                        'term' => [
                            'userid' => $userid
                        ]
                    ],
                    [
                        'has_child' => [
                            'type' => 'dialog_msg',
                            'query' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'match_phrase' => [
                                                'key' => $keyword
                                            ]
                                        ],
                                        [
                                            'term' => [
                                                'bot' => 0
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            'inner_hits' => [
                                'size' => 1,
                                'sort' => [
                                    'msg_id' => 'desc'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // 开始搜索
        $results = $this->search($query, 0, $size, ['last_at' => 'desc']);

        // 处理搜索结果
        $searchMap = [];
        $hits = $results['hits']['hits'] ?? [];

        foreach ($hits as $hit) {
            if (isset($hit['inner_hits']['dialog_msg']['hits']['hits'][0])) {
                $msgHit = $hit['inner_hits']['dialog_msg']['hits']['hits'][0];
                $source = $hit['_source'];
                $msgSource = $msgHit['_source'];

                $searchMap[] = [
                    'id' => $source['dialog_id'],
                    'top_at' => $source['top_at'],
                    'last_at' => $source['last_at'],
                    'mark_unread' => $source['mark_unread'],
                    'silence' => $source['silence'],
                    'hide' => $source['hide'],
                    'color' => $source['color'],
                    'user_at' => $source['updated_at'],
                    'search_msg_id' => $msgSource['msg_id'],
                ];
            }
        }

        // 返回搜索结果
        return $searchMap;
    }

    /** ******************************************************************************************************** */
    /** ******************************************************************************************************** */
    /** ******************************************************************************************************** */

    const DUM = "dialog_user_msg";

    /**
     * 会话用户 - 生成文档ID
     * @param WebSocketDialogUser $dialogUser
     * @return string
     */
    public static function generateDialogUserDicId(WebSocketDialogUser $dialogUser)
    {
        return "user_{$dialogUser->userid}_dialog_{$dialogUser->dialog_id}";
    }


    /**
     * 会话用户 - 生成文档格式
     * @param WebSocketDialogUser $dialogUser
     * @return array
     */
    public static function generateDialogUserFormat(WebSocketDialogUser $dialogUser)
    {
        return [
            'dialog_id' => $dialogUser->dialog_id,
            'created_at' => $dialogUser->created_at,
            'updated_at' => $dialogUser->updated_at,

            'userid' => $dialogUser->userid,
            'top_at' => $dialogUser->top_at,
            'last_at' => $dialogUser->last_at,
            'mark_unread' => $dialogUser->mark_unread ? 1 : 0,
            'silence' => $dialogUser->silence ? 1 : 0,
            'hide' => $dialogUser->hide ? 1 : 0,
            'color' => $dialogUser->color,

            'relationship' => [
                'name' => 'dialog_user'
            ]
        ];
    }

    /**
     * 会话用户 - 同步到Elasticsearch
     * @param WebSocketDialogUser $dialogUser
     * @return void
     */
    public static function syncDialogUserToElasticSearch(WebSocketDialogUser $dialogUser)
    {
        try {
            $es = new self(self::DUM);
            $es->indexDocument(self::generateDialogUserFormat($dialogUser), self::generateDialogUserDicId($dialogUser));
        } catch (\Exception $e) {
            Log::error('syncDialogUserToElasticSearch: ' . $e->getMessage());
        }
    }

    /**
     * 会话用户 - 从Elasticsearch删除
     */
    public static function deleteDialogUserFromElasticSearch(WebSocketDialogUser $dialogUser)
    {
        try {
            $es = new self(self::DUM);

            $docId = "user_{$dialogUser->userid}_dialog_{$dialogUser->dialog_id}";

            // 删除用户-会话文档
            $es->deleteDocument($docId);

            // 注意：这里可能还需要删除所有关联的消息文档
            // 但由于父子关系，可以通过查询找到所有子文档并删除
            // 这里为简化，可以选择在后台任务中处理，或者直接依赖ES的级联删除功能

        } catch (\Exception $e) {
            Log::error('deleteDialogUserFromElasticSearch: ' . $e->getMessage());
        }
    }

    /** ******************************************************************************************************** */
    /** ******************************************************************************************************** */
    /** ******************************************************************************************************** */

    /**
     * 会话消息 - 生成父文档ID
     * @param WebSocketDialogMsg $dialogMsg
     * @param $userid
     * @return string
     */
    public static function generateDialogMsgParentId(WebSocketDialogMsg $dialogMsg, $userid)
    {
        return "user_{$userid}_dialog_{$dialogMsg->dialog_id}";
    }

    /**
     * 会话消息 - 生成文档ID
     * @param WebSocketDialogMsg $dialogMsg
     * @param $userid
     * @return string
     */
    public static function generateDialogMsgDicId(WebSocketDialogMsg $dialogMsg, $userid)
    {
        return "msg_{$dialogMsg->id}_user_{$userid}";
    }

    /**
     * 会话消息 - 生成文档格式
     * @param WebSocketDialogMsg $dialogMsg
     * @param $userid
     * @return array
     */
    public static function generateDialogMsgFormat(WebSocketDialogMsg $dialogMsg, $userid)
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

            'relationship' => [
                'name' => 'dialog_msg',
                'parent' => self::generateDialogMsgParentId($dialogMsg, $userid)
            ]
        ];
    }

    /**
     * 会话消息 - 同步到Elasticsearch
     */
    public static function syncDialogToElasticSearch(WebSocketDialogMsg $dialogMsg)
    {
        try {
            $es = new self(self::DUM);

            // 获取此会话的所有用户
            $dialogUsers = WebSocketDialogUser::whereDialogId($dialogMsg->dialog_id)->get();

            if ($dialogUsers->isEmpty()) {
                return;
            }

            $params = ['body' => []];

            foreach ($dialogUsers as $dialogUser) {
                $params['body'][] = [
                    'index' => [
                        '_index' => self::DUM,
                        '_id' => self::generateDialogMsgDicId($dialogMsg, $dialogUser->userid),
                        'routing' => self::generateDialogMsgParentId($dialogMsg, $dialogUser->userid)
                    ]
                ];
                $params['body'][] = self::generateDialogMsgFormat($dialogMsg, $dialogUser->userid);
            }

            if (!empty($params['body'])) {
                $es->bulk($params);
            }
        } catch (\Exception $e) {
            Log::error('syncDialogToElasticSearch: ' . $e->getMessage());
        }
    }

    /**
     * 会话消息 - 从Elasticsearch删除
     */
    public static function deleteDialogFromElasticSearch(WebSocketDialogMsg $dialogMsg)
    {
        try {
            $es = new self(self::DUM);

            // 获取此会话的所有用户
            $dialogUsers = WebSocketDialogUser::whereDialogId($dialogMsg->dialog_id)->get();

            if ($dialogUsers->isEmpty()) {
                return;
            }

            $params = ['body' => []];

            foreach ($dialogUsers as $dialogUser) {
                $params['body'][] = [
                    'delete' => [
                        '_index' => self::DUM,
                        '_id' => self::generateDialogMsgDicId($dialogMsg, $dialogUser->userid),
                        'routing' => self::generateDialogMsgParentId($dialogMsg, $dialogUser->userid)
                    ]
                ];
            }

            if (!empty($params['body'])) {
                $es->bulk($params);
            }
        } catch (\Exception $e) {
            Log::error('deleteDialogFromElasticSearch: ' . $e->getMessage());
        }
    }
}
