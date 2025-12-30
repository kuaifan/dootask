<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Module\Apps;
use App\Module\SeekDB\SeekDBUser;
use App\Module\SeekDB\SeekDBKeyValue;
use Cache;
use Illuminate\Console\Command;

class SyncUserToSeekDB extends Command
{
    /**
     * 更新数据
     * --f: 全量更新 (默认)
     * --i: 增量更新（从上次更新的最后一个ID接上）
     *
     * 清理数据
     * --c: 清除索引
     */

    protected $signature = 'seekdb:sync-users {--f} {--i} {--c} {--batch=100}';
    protected $description = '同步用户数据到 SeekDB（联系人搜索）';

    /**
     * @return int
     */
    public function handle(): int
    {
        if (!Apps::isInstalled("seekdb")) {
            $this->error("应用「SeekDB」未安装");
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
            SeekDBUser::clear();
            SeekDBKeyValue::set('sync:seekdbUserLastId', 0);
            $this->info("索引删除成功");
            $this->releaseLock();
            return 0;
        }

        $this->info('开始同步用户数据...');
        $this->syncUsers();

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
        Cache::put($lockKey, ['started_at' => date('Y-m-d H:i:s')], 300);
    }

    private function releaseLock(): void
    {
        Cache::forget(md5($this->signature));
    }

    public function handleSignal(int $signal): void
    {
        $this->releaseLock();
        exit(0);
    }

    private function syncUsers(): void
    {
        $lastKey = "sync:seekdbUserLastId";
        $lastId = $this->option('i') ? intval(SeekDBKeyValue::get($lastKey, 0)) : 0;

        if ($lastId > 0) {
            $this->info("\n增量同步用户数据（从ID: {$lastId}）...");
        } else {
            $this->info("\n全量同步用户数据...");
        }

        // 只同步非机器人且未禁用的用户
        $query = User::where('userid', '>', $lastId)
            ->where('bot', 0)
            ->whereNull('disable_at');

        $num = 0;
        $count = $query->count();
        $batchSize = $this->option('batch');
        $total = 0;

        do {
            $users = User::where('userid', '>', $lastId)
                ->where('bot', 0)
                ->whereNull('disable_at')
                ->orderBy('userid')
                ->limit($batchSize)
                ->get();

            if ($users->isEmpty()) {
                break;
            }

            $num += count($users);
            $progress = $count > 0 ? round($num / $count * 100, 2) : 100;
            $this->info("{$num}/{$count} ({$progress}%) 正在同步用户ID {$users->first()->userid} ~ {$users->last()->userid}");

            $this->setLock();

            $synced = SeekDBUser::batchSync($users);
            $total += $synced;

            $lastId = $users->last()->userid;
            SeekDBKeyValue::set($lastKey, $lastId);
        } while (count($users) == $batchSize);

        $this->info("同步用户结束 - 最后ID {$lastId}，共同步 {$total} 个用户");
        $this->info("已索引用户数量: " . SeekDBUser::getIndexedCount());
    }
}

