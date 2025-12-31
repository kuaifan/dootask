<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Module\Apps;
use App\Module\SeekDB\SeekDBProject;
use App\Module\SeekDB\SeekDBKeyValue;
use Cache;
use Illuminate\Console\Command;

class SyncProjectToSeekDB extends Command
{
    /**
     * 更新数据
     * --f: 全量更新 (默认)
     * --i: 增量更新（从上次更新的最后一个ID接上）
     * --u: 仅同步项目成员关系（不同步项目内容）
     *
     * 清理数据
     * --c: 清除索引
     */

    protected $signature = 'seekdb:sync-projects {--f} {--i} {--c} {--u} {--batch=100}';
    protected $description = '同步项目数据到 SeekDB';

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
            SeekDBProject::clear();
            SeekDBKeyValue::set('sync:seekdbProjectLastId', 0);
            $this->info("索引删除成功");
            $this->releaseLock();
            return 0;
        }

        // 仅同步项目成员关系
        if ($this->option('u')) {
            $this->info('开始同步项目成员关系...');
            $count = SeekDBProject::syncAllProjectUsers(function ($count) {
                if ($count % 1000 === 0) {
                    $this->info("  已同步 {$count} 条关系...");
                }
            });
            $this->info("项目成员关系同步完成，共 {$count} 条");
            $this->releaseLock();
            return 0;
        }

        $this->info('开始同步项目数据...');
        $this->syncProjects();

        // 同步项目成员关系
        if ($this->option('f') || (!$this->option('i') && !$this->option('u'))) {
            // 全量同步：清空后重建
            $this->info("\n全量同步项目成员关系...");
            $count = SeekDBProject::syncAllProjectUsers(function ($count) {
                if ($count % 1000 === 0) {
                    $this->info("  已同步 {$count} 条关系...");
                }
            });
            $this->info("项目成员关系同步完成，共 {$count} 条");
        } elseif ($this->option('i')) {
            // 增量同步：只同步新增的
            $this->info("\n增量同步项目成员关系...");
            $count = SeekDBProject::syncProjectUsersIncremental(function ($count) {
                if ($count % 1000 === 0) {
                    $this->info("  已同步 {$count} 条关系...");
                }
            });
            if ($count > 0) {
                $this->info("新增项目成员关系 {$count} 条");
            }
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

    private function syncProjects(): void
    {
        $lastKey = "sync:seekdbProjectLastId";
        $lastId = $this->option('i') ? intval(SeekDBKeyValue::get($lastKey, 0)) : 0;

        if ($lastId > 0) {
            $this->info("\n增量同步项目数据（从ID: {$lastId}）...");
        } else {
            $this->info("\n全量同步项目数据...");
        }

        // 只同步未归档的项目
        $query = Project::where('id', '>', $lastId)
            ->whereNull('archived_at');

        $num = 0;
        $count = $query->count();
        $batchSize = $this->option('batch');
        $total = 0;

        do {
            $projects = Project::where('id', '>', $lastId)
                ->whereNull('archived_at')
                ->orderBy('id')
                ->limit($batchSize)
                ->get();

            if ($projects->isEmpty()) {
                break;
            }

            $num += count($projects);
            $progress = $count > 0 ? round($num / $count * 100, 2) : 100;
            $this->info("{$num}/{$count} ({$progress}%) 正在同步项目ID {$projects->first()->id} ~ {$projects->last()->id}");

            $this->setLock();

            $synced = SeekDBProject::batchSync($projects);
            $total += $synced;

            $lastId = $projects->last()->id;
            SeekDBKeyValue::set($lastKey, $lastId);
        } while (count($projects) == $batchSize);

        $this->info("同步项目结束 - 最后ID {$lastId}，共同步 {$total} 个项目");
        $this->info("已索引项目数量: " . SeekDBProject::getIndexedCount());
    }
}

