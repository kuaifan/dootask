<?php

namespace App\Console\Commands;

use App\Models\ProjectTask;
use App\Module\Apps;
use App\Module\Manticore\ManticoreTask;
use App\Module\Manticore\ManticoreKeyValue;
use Cache;
use Illuminate\Console\Command;

class SyncTaskToManticore extends Command
{
    /**
     * 更新数据（MVA 方案：allowed_users 在同步时自动写入）
     * --f: 全量更新 (默认)
     * --i: 增量更新（从上次更新的最后一个ID接上）
     *
     * 清理数据
     * --c: 清除索引
     */

    protected $signature = 'manticore:sync-tasks {--f} {--i} {--c} {--batch=100}';
    protected $description = '同步任务数据到 Manticore Search（MVA 权限方案）';

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
            ManticoreTask::clear();
            $this->info("索引删除成功");
            $this->releaseLock();
            return 0;
        }

        $this->info('开始同步任务数据（MVA 方案：allowed_users 自动内联）...');
        $this->syncTasks();

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

    private function syncTasks(): void
    {
        $lastKey = "sync:manticoreTaskLastId";
        $lastId = $this->option('i') ? intval(ManticoreKeyValue::get($lastKey, 0)) : 0;

        if ($lastId > 0) {
            $this->info("\n同步任务数据（{$lastId}）...");
        } else {
            $this->info("\n同步任务数据...");
        }

        // 排除已归档和已删除的任务
        $query = ProjectTask::where('id', '>', $lastId)
            ->whereNull('archived_at')
            ->whereNull('deleted_at');

        $num = 0;
        $count = $query->count();
        $batchSize = $this->option('batch');

        $total = 0;
        $lastNum = 0;

        do {
            $tasks = ProjectTask::where('id', '>', $lastId)
                ->whereNull('archived_at')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($tasks->isEmpty()) {
                break;
            }

            $num += count($tasks);
            $progress = $count > 0 ? round($num / $count * 100, 2) : 100;
            if ($progress < 100) {
                $progress = number_format($progress, 2);
            }
            $this->info("{$num}/{$count} ({$progress}%) 正在同步任务ID {$tasks->first()->id} ~ {$tasks->last()->id} ({$total}|{$lastNum})");

            $this->setLock();

            $lastNum = ManticoreTask::batchSync($tasks);
            $total += $lastNum;

            $lastId = $tasks->last()->id;
            ManticoreKeyValue::set($lastKey, $lastId);
        } while (count($tasks) == $batchSize);

        $this->info("同步任务结束 - 最后ID {$lastId}");
        $this->info("已索引任务数量: " . ManticoreTask::getIndexedCount());
    }
}
