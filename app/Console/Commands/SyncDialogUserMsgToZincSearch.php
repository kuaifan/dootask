<?php

namespace App\Console\Commands;

use App\Models\WebSocketDialogMsg;
use App\Models\WebSocketDialogUser;
use App\Module\ZincSearch\ZincSearchKeyValue;
use App\Module\ZincSearch\ZincSearchUserMsg;
use Illuminate\Console\Command;

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

    protected $signature = 'zinc:sync-dialog-user-msg {--f} {--i} {--c} {--batch=1000}';
    protected $description = '同步聊天会话用户和消息到 ZincSearch';

    /**
     * SyncDialogUserMsgToElasticsearch constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        // 清除索引
        if ($this->option('c')) {
            $this->info('清除索引...');
            ZincSearchUserMsg::clear();
            ZincSearchKeyValue::clear();
            $this->info("索引删除成功");
            return 0;
        }

        $this->info('开始同步聊天数据...');

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
     * @param string $type
     * @param int $lastId
     */
    private function saveLastId(string $type, int $lastId = 0): void
    {
        ZincSearchKeyValue::set("sync", ["{$type}" => $lastId], true);
    }

    /**
     * 获取最后一个ID
     * @param $type
     * @return int
     */
    private function getLastId($type): int
    {
        if ($this->option("i")) {
            $array = ZincSearchKeyValue::getArray("sync");
            return intval($array[$type] ?? 0);
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
            ZincSearchUserMsg::batchSyncUsers($dialogUsers);

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

            // 批量索引数据
            ZincSearchUserMsg::batchSyncMsgs($dialogMsgs);

            $lastId = $dialogMsgs->last()->id;
            $this->saveLastId('dialog_msg', $lastId);
        } while (count($dialogMsgs) == $batchSize);

        $this->info("同步消息结束 - 最后ID {$lastId}");
    }
}
