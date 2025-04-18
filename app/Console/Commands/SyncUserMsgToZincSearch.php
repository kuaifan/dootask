<?php

namespace App\Console\Commands;

use App\Models\WebSocketDialogMsg;
use App\Module\ZincSearch\ZincSearchKeyValue;
use App\Module\ZincSearch\ZincSearchDialogMsg;
use Cache;
use Illuminate\Console\Command;

class SyncUserMsgToZincSearch extends Command
{
    /**
     * 更新数据
     * --f: 全量更新 (默认)
     * --i: 增量更新（从上次更新的最后一个ID接上）
     *
     * 清理数据
     * --c: 清除索引
     */

    protected $signature = 'zinc:sync-user-msg {--f} {--i} {--c} {--batch=1000}';
    protected $description = '同步聊天会话用户和消息到 ZincSearch';

    /**
     * @return int
     */
    public function handle(): int
    {
        // 使用缓存锁确保一次只能运行一个实例
        $lock = Cache::lock('zinc:sync-user-msg', 3600 * 6); // 锁定6小时
        if (!$lock->get()) {
            $this->error('命令已在运行中，请等待当前实例完成');
            return 1;
        }

        try {
            // 清除索引
            if ($this->option('c')) {
                $this->info('清除索引...');
                ZincSearchKeyValue::clear();
                ZincSearchDialogMsg::clear();
                $this->info("索引删除成功");
                return 0;
            }

            $this->info('开始同步聊天数据...');

            // 同步消息数据
            $this->syncDialogMsgs();

            // 完成
            $this->info("\n同步完成");
            return 0;
        } finally {
            // 确保无论如何都会释放锁
            $lock->release();
        }
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
        $lastKey = "sync:dialogUserMsgLastId";
        $lastId = $this->option('i') ? intval(ZincSearchKeyValue::get($lastKey, 0)) : 0;

        $num = 0;
        $count = WebSocketDialogMsg::where('id', '>', $lastId)->count();
        $batchSize = $this->option('batch');

        do {
            // 获取一批
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

            // 同步数据
            ZincSearchDialogMsg::batchSync($dialogMsgs);

            // 更新最后ID
            $lastId = $dialogMsgs->last()->id;
            ZincSearchKeyValue::set($lastKey, $lastId);
        } while (count($dialogMsgs) == $batchSize);

        $this->info("同步消息结束 - 最后ID {$lastId}");
    }
}
