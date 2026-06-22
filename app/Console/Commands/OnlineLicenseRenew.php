<?php

namespace App\Console\Commands;

use App\Module\OnlineLicense;
use Illuminate\Console\Command;

/**
 * 在线授权续期（容器内独立进程按小时调用，无需 LARAVELS_TIMER、不经过 HTTP 转发）。
 *
 * 由 php 容器 supervisor 程序 [program:license] 循环调用：
 *   while true; do php artisan online-license:renew; sleep 3600; done
 */
class OnlineLicenseRenew extends Command
{
    protected $signature = 'online-license:renew';
    protected $description = '在线授权：本地状态机推进 + 租约将尽时自动续期';

    public function handle(): int
    {
        if (!OnlineLicense::enabled()) {
            return 0;
        }
        OnlineLicense::cron();
        $status = OnlineLicense::status();
        $this->info('online-license: ' . ($status['status'] ?? 'offline') . ' lease=' . ($status['lease_expired_at'] ?? '-'));
        return 0;
    }
}
