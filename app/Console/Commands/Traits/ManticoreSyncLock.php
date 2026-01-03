<?php

namespace App\Console\Commands\Traits;

use Cache;

/**
 * Manticore 同步命令通用锁机制
 *
 * 提供：
 * - 锁的获取、设置、释放
 * - 信号处理（优雅退出）
 * - 通用的命令初始化检查
 */
trait ManticoreSyncLock
{
    private bool $shouldStop = false;

    /**
     * 获取锁信息
     */
    private function getLock(): ?array
    {
        $lockKey = $this->getLockKey();
        return Cache::has($lockKey) ? Cache::get($lockKey) : null;
    }

    /**
     * 设置锁（30分钟有效期，持续处理时需不断刷新）
     */
    private function setLock(): void
    {
        $lockKey = $this->getLockKey();
        Cache::put($lockKey, ['started_at' => date('Y-m-d H:i:s')], 1800);
    }

    /**
     * 释放锁
     */
    private function releaseLock(): void
    {
        $lockKey = $this->getLockKey();
        Cache::forget($lockKey);
    }

    /**
     * 获取锁的缓存键
     */
    private function getLockKey(): string
    {
        return md5($this->signature);
    }

    /**
     * 信号处理器（SIGINT/SIGTERM）
     */
    public function handleSignal(int $signal): void
    {
        $this->info("\n收到信号，将在当前批次完成后退出...");
        $this->shouldStop = true;
    }

    /**
     * 注册信号处理器
     */
    private function registerSignalHandlers(): void
    {
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
        }
    }

    /**
     * 检查命令是否可以启动（锁检查）
     *
     * @return bool 返回 true 表示可以启动，false 表示已被占用
     */
    private function acquireLock(): bool
    {
        $lockInfo = $this->getLock();
        if ($lockInfo) {
            $this->error("命令已在运行中，开始时间: {$lockInfo['started_at']}");
            return false;
        }
        $this->setLock();
        return true;
    }
}
