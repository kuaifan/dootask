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
     * 缓存锁实例
     */
    protected $lock;

    /**
     * @return int
     */
    public function handle(): int
    {
        // 注册信号处理器（仅在支持pcntl扩展的环境下）
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true); // 启用异步信号处理
            pcntl_signal(SIGINT, [$this, 'handleSignal']);  // Ctrl+C
            pcntl_signal(SIGTERM, [$this, 'handleSignal']); // kill
        }

        // 使用缓存锁确保一次只能运行一个实例
        $this->lock = Cache::lock('zinc:sync-user-msg', 3600 * 6); // 锁定6小时
        if (!$this->lock->get()) {
            $this->error('命令已在运行中，请等待当前实例完成');
            return 1;
        }

        // 清除索引
        if ($this->option('c')) {
            $this->info('清除索引...');
            ZincSearchKeyValue::clear();
            ZincSearchDialogMsg::clear();
            $this->info("索引删除成功");
            $this->lock?->release();
            return 0;
        }

        $this->info('开始同步聊天数据...');

        // 同步消息数据
        $this->syncDialogMsgs();

        // 完成
        $this->info("\n同步完成");
        $this->lock?->release();
        return 0;
    }

    /**
     * 处理终端信号
     *
     * @param int $signal
     * @return void
     */
    public function handleSignal(int $signal): void
    {
        // 释放锁
        if ($this->lock) {
            $this->lock->release();
        }
        exit(0);
    }

    /**
     * 同步消息数据
     *
     * @return void
     */
    private function syncDialogMsgs(): void
    {
        // 获取上次同步的最后ID
        $lastKey = "sync:dialogUserMsgLastId";
        $lastId = $this->option('i') ? intval(ZincSearchKeyValue::get($lastKey, 0)) : 0;

        if ($lastId > 0) {
            $this->info("\n同步消息数据（{$lastId}）...");
        } else {
            $this->info("\n同步消息数据...");
        }

        $num = 0;
        $count = WebSocketDialogMsg::where('id', '>', $lastId)->count();
        $batchSize = $this->option('batch');

        $total = 0;
        $lastNum = 0;

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
            if ($progress < 100) {
                $progress = number_format($progress, 2);
            }
            $this->info("{$num}/{$count} ({$progress}%) 正在同步消息ID {$dialogMsgs->first()->id} ~ {$dialogMsgs->last()->id} ({$total}|{$lastNum})");

            // 同步数据
            $lastNum = ZincSearchDialogMsg::batchSync($dialogMsgs);
            $total += $lastNum;

            // 更新最后ID
            $lastId = $dialogMsgs->last()->id;
            ZincSearchKeyValue::set($lastKey, $lastId);
        } while (count($dialogMsgs) == $batchSize);

        $this->info("同步消息结束 - 最后ID {$lastId}");
    }
}
