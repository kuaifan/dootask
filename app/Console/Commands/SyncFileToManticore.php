<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ManticoreSyncLock;
use App\Models\File;
use App\Module\Apps;
use App\Module\Manticore\ManticoreFile;
use App\Module\Manticore\ManticoreKeyValue;
use Illuminate\Console\Command;

class SyncFileToManticore extends Command
{
    use ManticoreSyncLock;

    /**
     * 更新数据
     * --f: 全量更新 (默认)
     * --i: 增量更新
     *
     * 清理数据
     * --c: 清除索引
     *
     * 其他选项
     * --sleep: 每批处理完成后休眠秒数
     */

    protected $signature = 'manticore:sync-files {--f} {--i} {--c} {--batch=100} {--sleep=3}';
    protected $description = '同步文件数据到 Manticore Search';

    public function handle(): int
    {
        if (!Apps::isInstalled("search")) {
            $this->error("应用「Manticore Search」未安装");
            return 1;
        }

        $this->registerSignalHandlers();

        if (!$this->acquireLock()) {
            return 1;
        }

        // 清除索引
        if ($this->option('c')) {
            $this->info('清除索引...');
            ManticoreKeyValue::clear();
            ManticoreFile::clear();
            $this->info("索引删除成功");
            $this->releaseLock();
            return 0;
        }

        $this->info('开始同步文件数据...');
        $this->syncFiles();

        $this->info("\n同步完成");
        $this->releaseLock();
        return 0;
    }

    /**
     * 同步文件数据
     */
    private function syncFiles(): void
    {
        $lastKey = "sync:manticoreFileLastId";
        $isIncremental = $this->option('i');
        $sleepSeconds = intval($this->option('sleep'));
        $batchSize = $this->option('batch');
        $maxFileSize = ManticoreFile::getMaxFileSize();

        $round = 0;

        do {
            $round++;
            $lastId = $isIncremental ? intval(ManticoreKeyValue::get($lastKey, 0)) : 0;

            if ($round === 1) {
                if ($lastId > 0) {
                    $this->info("\n增量同步文件数据（从ID {$lastId} 开始）...");
                } else {
                    $this->info("\n全量同步文件数据...");
                }
            }

            $count = File::where('id', '>', $lastId)
                ->where('type', '!=', 'folder')
                ->where('size', '<=', $maxFileSize)
                ->count();

            if ($count === 0) {
                if ($round === 1) {
                    $this->info("无待同步数据");
                }
                break;
            }

            $this->info("[第 {$round} 轮] 待同步 {$count} 个文件");

            $num = 0;
            $total = 0;

            do {
                if ($this->shouldStop) {
                    break;
                }

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
                $this->info("{$num}/{$count} ({$progress}%) 文件ID {$files->first()->id} ~ {$files->last()->id}");

                $this->setLock();

                $syncCount = ManticoreFile::batchSync($files);
                $total += $syncCount;

                $lastId = $files->last()->id;
                ManticoreKeyValue::set($lastKey, $lastId);
            } while (count($files) == $batchSize && !$this->shouldStop);

            $this->info("[第 {$round} 轮] 完成，同步 {$total} 个，最后ID {$lastId}");

            if ($isIncremental && !$this->shouldStop) {
                $newCount = File::where('id', '>', $lastId)
                    ->where('type', '!=', 'folder')
                    ->where('size', '<=', $maxFileSize)
                    ->count();

                if ($newCount > 0) {
                    $this->info("发现 {$newCount} 个新文件，{$sleepSeconds} 秒后继续...");
                    sleep($sleepSeconds);
                    continue;
                }
            }

            break;

        } while (!$this->shouldStop);

        $this->info("同步文件结束（共 {$round} 轮）- 最后ID: " . ManticoreKeyValue::get($lastKey, 0));
        $this->info("已索引文件数量: " . ManticoreFile::getIndexedCount());
    }
}
