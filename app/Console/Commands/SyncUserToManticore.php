<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Module\Apps;
use App\Module\Manticore\ManticoreUser;
use App\Module\Manticore\ManticoreKeyValue;
use Cache;
use Illuminate\Console\Command;

class SyncUserToManticore extends Command
{
    /**
     * 更新数据
     * --f: 全量更新 (默认)
     * --i: 增量更新（从上次更新的最后一个ID接上）
     *
     * 清理数据
     * --c: 清除索引
     */

    protected $signature = 'manticore:sync-users {--f} {--i} {--c} {--batch=100}';
    protected $description = '同步用户数据到 Manticore Search';

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
            ManticoreUser::clear();
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

    private function syncUsers(): void
    {
        $lastKey = "sync:manticoreUserLastId";
        $lastId = $this->option('i') ? intval(ManticoreKeyValue::get($lastKey, 0)) : 0;

        if ($lastId > 0) {
            $this->info("\n同步用户数据（{$lastId}）...");
        } else {
            $this->info("\n同步用户数据...");
        }

        // 排除机器人和已禁用账号
        $query = User::where('userid', '>', $lastId)
            ->where('bot', 0)
            ->whereNull('disable_at');

        $num = 0;
        $count = $query->count();
        $batchSize = $this->option('batch');

        $total = 0;
        $lastNum = 0;

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
            if ($progress < 100) {
                $progress = number_format($progress, 2);
            }
            $this->info("{$num}/{$count} ({$progress}%) 正在同步用户ID {$users->first()->userid} ~ {$users->last()->userid} ({$total}|{$lastNum})");

            $this->setLock();

            $lastNum = ManticoreUser::batchSync($users);
            $total += $lastNum;

            $lastId = $users->last()->userid;
            ManticoreKeyValue::set($lastKey, $lastId);
        } while (count($users) == $batchSize);

        $this->info("同步用户结束 - 最后ID {$lastId}");
        $this->info("已索引用户数量: " . ManticoreUser::getIndexedCount());
    }
}

