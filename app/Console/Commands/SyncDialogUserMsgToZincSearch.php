<?php

namespace App\Console\Commands;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use App\Module\ElasticSearch\ElasticSearchKeyValue;
use App\Module\ElasticSearch\ElasticSearchUserMsg;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncDialogUserMsgToZincSearch extends Command
{
    /**
     * 更新数据
     * --f: 全量更新 (默认)
     * --i: 增量更新（从上次更新的最后一个ID接上）
     *
     * 清理数据
     * --c: 清除索引
     */

    protected $signature = 'zinc:sync-dialog-user-msg {--f} {--i} {--c} {--batch=500}';
    protected $description = '同步聊天会话用户和消息到 ZincSearch';
    protected $client = null;

    /**
     * SyncDialogUserMsgToElasticsearch constructor.
     */
    public function __construct()
    {
        parent::__construct();
        try {
            $this->es = new ElasticSearchUserMsg();
        } catch (\Exception $e) {
            $this->error('Elasticsearch连接失败: ' . $e->getMessage());
            exit(1);
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        $this->info('开始同步聊天数据...');

        // 清除索引
        if ($this->option('c')) {
            $this->info('清除索引...');
            if (!$this->es->indexExists()) {
                $this->saveLastId(true);
                $this->info('索引不存在');
                return 0;
            }
            $result = $this->es->deleteIndex();
            if (isset($result['error'])) {
                $this->error('删除索引失败: ' . $result['error']);
                return 1;
            }
            $this->saveLastId(true);
            $this->info('索引删除成功');
            return 0;
        }

        // 判断创建索引
        if (!$this->es->indexExists()) {
            $this->info('创建索引...');
            $result = ElasticSearchUserMsg::generateIndex();
            if (isset($result['error'])) {
                $this->error('创建索引失败: ' . $result['error']);
                return 1;
            }
            $this->saveLastId(true);
            $this->info('索引创建成功');
        }

        // 同步用户-会话数据
        $this->syncDialogUsers($this->option('batch'));

        // 同步消息数据
        $this->syncDialogMsgs($this->option('batch'));

        // 完成
        $this->info("\n同步完成");
        return 0;
    }

    /**
     * 保存最后一个ID
     * @param string|true $type
     * @param integer $lastId
     */
    private function saveLastId($type, $lastId = 0)
    {
        if ($type === true) {
            $setting = [];
        } else {
            $setting = ElasticSearchKeyValue::getArray('elasticSearch:sync');
            $setting[$type] = $lastId;
        }
        ElasticSearchKeyValue::save('elasticSearch:sync', $setting);
    }

    /**
     * 获取最后一个ID
     * @param $type
     * @return int
     */
    private function getLastId($type)
    {
        if ($this->option('i')) {
            $setting = ElasticSearchKeyValue::getArray('elasticSearch:sync');
            return intval($setting[$type] ?? 0);
        }
        return 0;
    }

    /**
     * 同步用户-会话数据（父文档）
     * @param $batchSize
     * @return void
     */
    private function syncDialogUsers($batchSize)
    {
        $this->info("\n同步用户数据...");
        $lastId = $this->getLastId('dialog_user');

        $num = 0;
        $count = WebSocketDialogUser::where('id', '>', $lastId)->count();

        do {
            // 获取一批用户-会话关系
            $dialogUsers = WebSocketDialogUser::where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($dialogUsers->isEmpty()) {
                break;
            }

            $num += count($dialogUsers);
            $progress = round($num / $count * 100, 2);
            $this->info("{$num}/{$count} ({$progress}%) 正在同步用户ID {$lastId} ~ {$dialogUsers->last()->id}");

            // 批量索引数据
            $params = ['body' => []];
            foreach ($dialogUsers as $dialogUser) {
                $params['body'][] = [
                    'index' => [
                        '_index' => ElasticSearchUserMsg::indexName(),
                        '_id' => ElasticSearchUserMsg::generateUserDicId($dialogUser),
                    ]
                ];
                $params['body'][] = ElasticSearchUserMsg::generateUserFormat($dialogUser);
            }

            if ($params['body']) {
                $result = $this->es->bulk($params);
                if (isset($result['errors']) && $result['errors']) {
                    $this->error('批量索引用户数据部分失败');
                    Log::error('Elasticsearch批量索引失败: ' . json_encode($result['items']));
                }
            }

            $lastId = $dialogUsers->last()->id;
            $this->saveLastId('dialog_user', $lastId);
        } while (count($dialogUsers) == $batchSize);

        $this->info("同步用户数据结束 - 最后ID {$lastId}");
    }

    /**
     * 同步消息数据（子文档）
     */
    private function syncDialogMsgs($batchSize)
    {
        $this->info("\n同步消息数据...");
        $lastId = $this->getLastId('dialog_msg');

        $num = 0;
        $count = WebSocketDialogMsg::where('id', '>', $lastId)->count();

        do {
            // 获取一批消息
            $dialogMsgs = WebSocketDialogMsg::where('id', '>', $lastId)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($dialogMsgs->isEmpty()) {
                break;
            }

            $num += count($dialogMsgs);
            $progress = round($num / $count * 100, 2);
            $this->info("{$num}/{$count} ({$progress}%) 正在同步消息ID {$lastId} ~ {$dialogMsgs->last()->id}");

            // 获取这些消息所属的会话对应的所有用户
            $dialogIds = $dialogMsgs->pluck('dialog_id')->unique()->toArray();
            $userDialogMap = [];

            if (!empty($dialogIds)) {
                $dialogUsers = WebSocketDialogUser::whereIn('dialog_id', $dialogIds)->get();

                foreach ($dialogUsers as $dialogUser) {
                    $userDialogMap[$dialogUser->dialog_id][] = $dialogUser->userid;
                }
            }

            // 批量索引消息数据
            $params = ['body' => []];
            foreach ($dialogMsgs as $dialogMsg) {
                // 如果该会话没有用户，跳过
                if (empty($userDialogMap[$dialogMsg->dialog_id])) {
                    continue;
                }

                // 为每个用户-会话关系创建子文档
                foreach ($userDialogMap[$dialogMsg->dialog_id] as $userid) {
                    $params['body'][] = [
                        'index' => [
                            '_index' => ElasticSearchUserMsg::indexName(),
                            '_id' => ElasticSearchUserMsg::generateMsgDicId($dialogMsg, $userid),
                            'routing' => ElasticSearchUserMsg::generateMsgParentId($dialogMsg, $userid) // 路由到父文档
                        ]
                    ];

                    $params['body'][] = ElasticSearchUserMsg::generateMsgFormat($dialogMsg, $userid);
                }
            }

            if (!empty($params['body'])) {
                // 分批处理
                $chunks = array_chunk($params['body'], 1000);
                foreach ($chunks as $chunk) {
                    $chunkParams = ['body' => $chunk];
                    $result = $this->es->bulk($chunkParams);
                    if (isset($result['errors']) && $result['errors']) {
                        $this->error('批量索引消息数据部分失败');
                        Log::error('Elasticsearch批量索引失败: ' . json_encode($result['items']));
                    }
                }
            }

            $lastId = $dialogMsgs->last()->id;
            $this->saveLastId('dialog_msg', $lastId);
        } while (count($dialogMsgs) == $batchSize);

        $this->info("同步消息结束 - 最后ID {$lastId}");
    }
}
