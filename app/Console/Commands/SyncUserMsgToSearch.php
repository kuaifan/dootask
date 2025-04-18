<?php

namespace App\Console\Commands;

use App\Models\WebSocketDialogMsg;
use App\Module\ZincSearch\ZincSearchKeyValue;
use App\Module\ZincSearch\ZincSearchUserMsg;
use Illuminate\Console\Command;

class SyncUserMsgToSearch extends Command
{
    /**
     * 更新数据
     * --f: 全量更新 (默认)
     * --i: 增量更新（从上次更新的最后一个ID接上）
     *
     * 清理数据
     * --c: 清除索引
     */

    protected $signature = 'search:sync-user-msg {--f} {--i} {--c} {--batch=1000}';
    protected $description = '同步聊天会话用户和消息到 ZincSearch';

    /**
     * @return int
     */
    public function handle(): int
    {
        // 清除索引
        if ($this->option('c')) {
            $this->info('清除索引...');
            ZincSearchKeyValue::clear();
            ZincSearchUserMsg::clear();
            $this->info("索引删除成功");
            return 0;
        }

        $this->info('开始同步聊天数据...');

        // 同步消息数据
        $this->syncDialogMsgs();

        // 完成
        $this->info("\n同步完成");
        return 0;
    }

    /**
     * 同步消息数据
     *
     * @return void
     */
    private function syncDialogMsgs(): void
    {
        $this->info("\n同步消息数据...");

        // 获取上次同步的最后ID
        $lastKey = "sync:userMsgLastId";
        $lastId = $this->option('i') ? intval(ZincSearchKeyValue::get($lastKey, 0)) : 0;

        $num = 0;
        $count = WebSocketDialogMsg::where('id', '>', $lastId)->count();
        $batchSize = $this->option('batch');

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

            // 更新最后ID
            $lastId = $dialogMsgs->last()->id;
            ZincSearchKeyValue::set($lastKey, $lastId);
        } while (count($dialogMsgs) == $batchSize);

        $this->info("同步消息结束 - 最后ID {$lastId}");
    }
}
