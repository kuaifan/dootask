<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Module\Apps;
use App\Module\Manticore\ManticoreFile;
use App\Module\Manticore\ManticoreKeyValue;
use Cache;
use Illuminate\Console\Command;

class SyncFileToManticore extends Command
{
    /**
     * 更新数据（MVA 方案：allowed_users 在同步时自动写入）
     * --f: 全量更新 (默认)
     * --i: 增量更新（从上次更新的最后一个ID接上）
     *
     * 清理数据
     * --c: 清除索引
     */

    protected $signature = 'manticore:sync-files {--f} {--i} {--c} {--batch=100}';
    protected $description = '同步文件内容到 Manticore Search（MVA 权限方案）';

    /**
     * @return int
     */
    public function handle(): int
    {
        if (!Apps::isInstalled("manticore")) {
            $this->error("应用「Manticore Search」未安装");
            return 1;
        }

        // 注册信号处理器（仅在支持pcntl扩展的环境下）
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true); // 启用异步信号处理
            pcntl_signal(SIGINT, [$this, 'handleSignal']);  // Ctrl+C
            pcntl_signal(SIGTERM, [$this, 'handleSignal']); // kill
        }

        // 检查锁，如果已被占用则退出
        $lockInfo = $this->getLock();
        if ($lockInfo) {
            $this->error("命令已在运行中，开始时间: {$lockInfo['started_at']}");
            return 1;
        }

        // 设置锁
        $this->setLock();

        // 清除索引
        if ($this->option('c')) {
            $this->info('清除索引...');
            ManticoreKeyValue::clear();
            ManticoreFile::clear();
            $this->info("索引删除成功");
            $this->releaseLock();
            return 0;
        }

        $this->info('开始同步文件数据（MVA 方案：allowed_users 自动内联）...');

        // 同步文件数据
        $this->syncFiles();

        // 完成
        $this->info("\n同步完成");
        $this->releaseLock();
        return 0;
    }

    /**
     * 获取锁信息
     *
     * @return array|null 如果锁存在返回锁信息，否则返回null
     */
    private function getLock(): ?array
    {
        $lockKey = md5($this->signature);
        return Cache::has($lockKey) ? Cache::get($lockKey) : null;
    }

    /**
     * 设置锁
     */
    private function setLock(): void
    {
        $lockKey = md5($this->signature);
        $lockInfo = [
            'started_at' => date('Y-m-d H:i:s')
        ];
        Cache::put($lockKey, $lockInfo, 600); // 10分钟（文件同步可能较慢）
    }

    /**
     * 释放锁
     */
    private function releaseLock(): void
    {
        $lockKey = md5($this->signature);
        Cache::forget($lockKey);
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
        $this->releaseLock();
        exit(0);
    }

    /**
     * 同步文件数据
     *
     * @return void
     */
    private function syncFiles(): void
    {
        // 获取上次同步的最后ID
        $lastKey = "sync:manticoreFileLastId";
        $lastId = $this->option('i') ? intval(ManticoreKeyValue::get($lastKey, 0)) : 0;

        if ($lastId > 0) {
            $this->info("\n同步文件数据（{$lastId}）...");
        } else {
            $this->info("\n同步文件数据...");
        }

        // 查询条件：排除文件夹，使用最大文件限制
        // 具体的文件类型大小检查在 ManticoreFile::sync 中进行
        $maxFileSize = ManticoreFile::getMaxFileSize();
        $query = File::where('id', '>', $lastId)
            ->where('type', '!=', 'folder')
            ->where('size', '<=', $maxFileSize);

        $num = 0;
        $count = $query->count();
        $batchSize = $this->option('batch');

        $total = 0;
        $lastNum = 0;

        do {
            // 获取一批
            $files = File::where('id', '>', $lastId)
                ->where('type', '!=', 'folder')
                ->where('size', '<=', $maxFileSize)
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($files->isEmpty()) {
                break;
            }

            $num += count($files);
            $progress = $count > 0 ? round($num / $count * 100, 2) : 100;
            if ($progress < 100) {
                $progress = number_format($progress, 2);
            }
            $this->info("{$num}/{$count} ({$progress}%) 正在同步文件ID {$files->first()->id} ~ {$files->last()->id} ({$total}|{$lastNum})");

            // 刷新锁
            $this->setLock();

            // 同步数据
            $lastNum = ManticoreFile::batchSync($files);
            $total += $lastNum;

            // 更新最后ID
            $lastId = $files->last()->id;
            ManticoreKeyValue::set($lastKey, $lastId);
        } while (count($files) == $batchSize);

        $this->info("同步文件结束 - 最后ID {$lastId}");
        $this->info("已索引文件数量: " . ManticoreFile::getIndexedCount());
    }
}
