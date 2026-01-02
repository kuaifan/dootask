<?php

namespace App\Console\Commands;

use App\Models\WebSocketDialogMsg;
use App\Module\Apps;
use App\Module\Manticore\ManticoreMsg;
use App\Module\Manticore\ManticoreKeyValue;
use Cache;
use Illuminate\Console\Command;

class SyncMsgToManticore extends Command
{
    /**
     * 更新数据（MVA 方案：allowed_users 在同步时自动写入）
     * --f: 全量更新 (默认)
     * --i: 增量更新（从上次更新的最后一个ID接上）
     *
     * 清理数据
     * --c: 清除索引
     *
     * 其他选项
     * --dialog: 指定对话ID（仅同步该对话的消息）
     */

    protected $signature = 'manticore:sync-msgs {--f} {--i} {--c} {--batch=100} {--dialog=}';
    protected $description = '同步消息数据到 Manticore Search（MVA 权限方案）';

    /**
     * @return int
     */
    public function handle(): int
    {
        if (!Apps::isInstalled("manticore")) {
            $this->error("应用「Manticore Search」未安装");
            return 1;
        }

        // 注册信号处理器
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        }

        // 检查锁
        $lockInfo = $this->getLock();
        if ($lockInfo) {
            $this->error("命令已在运行中，开始时间: {$lockInfo['started_at']}");
            return 1;
        }

        $this->setLock();

        // 清除索引
        if ($this->option('c')) {
            $this->info('清除索引...');
            ManticoreMsg::clear();
            $this->info("索引删除成功");
            $this->releaseLock();
            return 0;
        }

        $dialogId = $this->option('dialog') ? intval($this->option('dialog')) : 0;

        if ($dialogId > 0) {
            $this->info("开始同步对话 {$dialogId} 的消息数据（MVA 方案：allowed_users 自动内联）...");
            $this->syncDialogMsgs($dialogId);
        } else {
            $this->info('开始同步消息数据（MVA 方案：allowed_users 自动内联）...');
            $this->syncMsgs();
        }

        $this->info("\n同步完成");
        $this->releaseLock();
        return 0;
    }

    private function getLock(): ?array
    {
        $lockKey = md5($this->signature);
        return Cache::has($lockKey) ? Cache::get($lockKey) : null;
    }

    private function setLock(): void
    {
        $lockKey = md5($this->signature);
        Cache::put($lockKey, ['started_at' => date('Y-m-d H:i:s')], 600);
    }

    private function releaseLock(): void
    {
        $lockKey = md5($this->signature);
        Cache::forget($lockKey);
    }

    public function handleSignal(int $signal): void
    {
        $this->releaseLock();
        exit(0);
    }

    /**
     * 同步所有消息
     */
    private function syncMsgs(): void
    {
        $lastKey = "sync:manticoreMsgLastId";
        $lastId = $this->option('i') ? intval(ManticoreKeyValue::get($lastKey, 0)) : 0;

        if ($lastId > 0) {
            $this->info("\n增量同步消息数据（从ID {$lastId} 开始）...");
        } else {
            $this->info("\n全量同步消息数据...");
        }

        // 构建基础查询条件
        // 排除：软删除、机器人消息、空 key 消息
        // 只包含：可索引的消息类型
        $baseQuery = WebSocketDialogMsg::where('id', '>', $lastId)
            ->whereNull('deleted_at')
            ->where('bot', '!=', 1)
            ->whereNotNull('key')
            ->where('key', '!=', '')
            ->whereIn('type', ManticoreMsg::INDEXABLE_TYPES);

        $num = 0;
        $count = $baseQuery->count();
        $batchSize = $this->option('batch');

        $total = 0;
        $lastNum = 0;

        do {
            $msgs = WebSocketDialogMsg::where('id', '>', $lastId)
                ->whereNull('deleted_at')
                ->where('bot', '!=', 1)
                ->whereNotNull('key')
                ->where('key', '!=', '')
                ->whereIn('type', ManticoreMsg::INDEXABLE_TYPES)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($msgs->isEmpty()) {
                break;
            }

            $num += count($msgs);
            $progress = $count > 0 ? round($num / $count * 100, 2) : 100;
            if ($progress < 100) {
                $progress = number_format($progress, 2);
            }
            $this->info("{$num}/{$count} ({$progress}%) 正在同步消息ID {$msgs->first()->id} ~ {$msgs->last()->id} ({$total}|{$lastNum})");

            $this->setLock();

            $lastNum = ManticoreMsg::batchSync($msgs);
            $total += $lastNum;

            $lastId = $msgs->last()->id;
            ManticoreKeyValue::set($lastKey, $lastId);
        } while (count($msgs) == $batchSize);

        $this->info("同步消息结束 - 最后ID {$lastId}");
        $this->info("已索引消息数量: " . ManticoreMsg::getIndexedCount());
    }

    /**
     * 同步指定对话的消息
     *
     * @param int $dialogId 对话ID
     */
    private function syncDialogMsgs(int $dialogId): void
    {
        $this->info("\n同步对话 {$dialogId} 的消息数据...");

        $baseQuery = WebSocketDialogMsg::where('dialog_id', $dialogId)
            ->whereNull('deleted_at')
            ->where('bot', '!=', 1)
            ->whereNotNull('key')
            ->where('key', '!=', '')
            ->whereIn('type', ManticoreMsg::INDEXABLE_TYPES);

        $num = 0;
        $count = $baseQuery->count();
        $batchSize = $this->option('batch');
        $lastId = 0;

        $total = 0;
        $lastNum = 0;

        do {
            $msgs = WebSocketDialogMsg::where('dialog_id', $dialogId)
                ->where('id', '>', $lastId)
                ->whereNull('deleted_at')
                ->where('bot', '!=', 1)
                ->whereNotNull('key')
                ->where('key', '!=', '')
                ->whereIn('type', ManticoreMsg::INDEXABLE_TYPES)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($msgs->isEmpty()) {
                break;
            }

            $num += count($msgs);
            $progress = $count > 0 ? round($num / $count * 100, 2) : 100;
            if ($progress < 100) {
                $progress = number_format($progress, 2);
            }
            $this->info("{$num}/{$count} ({$progress}%) 正在同步消息ID {$msgs->first()->id} ~ {$msgs->last()->id} ({$total}|{$lastNum})");

            $this->setLock();

            $lastNum = ManticoreMsg::batchSync($msgs);
            $total += $lastNum;

            $lastId = $msgs->last()->id;
        } while (count($msgs) == $batchSize);

        $this->info("同步对话 {$dialogId} 消息结束");
        $this->info("该对话已索引消息数量: " . \App\Module\Manticore\ManticoreBase::getDialogIndexedMsgCount($dialogId));
    }
}
