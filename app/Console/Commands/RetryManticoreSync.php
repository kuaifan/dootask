<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ManticoreSyncLock;
use App\Models\File;
use App\Models\ManticoreSyncFailure;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WebSocketDialogMsg;
use App\Module\Apps;
use App\Module\Manticore\ManticoreBase;
use App\Module\Manticore\ManticoreFile;
use App\Module\Manticore\ManticoreMsg;
use App\Module\Manticore\ManticoreProject;
use App\Module\Manticore\ManticoreTask;
use App\Module\Manticore\ManticoreUser;
use Illuminate\Console\Command;

class RetryManticoreSync extends Command
{
    use ManticoreSyncLock;

    protected $signature = 'manticore:retry-failures {--limit=100 : 每次处理的最大数量} {--stats : 显示统计信息}';
    protected $description = '重试 Manticore 同步失败的记录';

    public function handle(): int
    {
        if (!Apps::isInstalled("search")) {
            $this->error("应用「Manticore Search」未安装");
            return 1;
        }

        // 显示统计信息
        if ($this->option('stats')) {
            $this->showStats();
            return 0;
        }

        $this->registerSignalHandlers();

        if (!$this->acquireLock()) {
            return 1;
        }

        $this->info('开始重试失败的同步任务...');

        $limit = intval($this->option('limit'));
        $failures = ManticoreSyncFailure::getPendingRetries($limit);

        if ($failures->isEmpty()) {
            $this->info('无待重试的记录');
            $this->releaseLock();
            return 0;
        }

        $this->info("找到 {$failures->count()} 条待重试记录");

        $successCount = 0;
        $failCount = 0;

        foreach ($failures as $failure) {
            if ($this->shouldStop) {
                $this->info('收到停止信号，退出处理');
                break;
            }

            $this->setLock();

            $result = $this->retryOne($failure);

            if ($result) {
                $successCount++;
                $this->info("  [成功] {$failure->data_type}:{$failure->data_id} ({$failure->action})");
            } else {
                $failCount++;
                $this->warn("  [失败] {$failure->data_type}:{$failure->data_id} ({$failure->action}) - 第 {$failure->retry_count} 次");
            }
        }

        $this->info("\n重试完成: 成功 {$successCount}, 失败 {$failCount}");
        $this->releaseLock();

        return 0;
    }

    /**
     * 重试单条失败记录
     */
    private function retryOne(ManticoreSyncFailure $failure): bool
    {
        $type = $failure->data_type;
        $id = $failure->data_id;
        $action = $failure->action;

        try {
            if ($action === 'delete') {
                // 删除操作直接调用通用删除方法
                return ManticoreBase::deleteVector($type, $id);
            }

            // sync 操作需要根据类型获取模型并同步
            return $this->retrySyncByType($type, $id);
        } catch (\Throwable $e) {
            // 记录失败（会自动更新重试次数和时间）
            ManticoreSyncFailure::recordFailure($type, $id, $action, $e->getMessage());
            return false;
        }
    }

    /**
     * 根据类型重试同步
     */
    private function retrySyncByType(string $type, int $id): bool
    {
        switch ($type) {
            case 'msg':
                $model = WebSocketDialogMsg::find($id);
                if (!$model) {
                    // 数据已删除，移除失败记录
                    ManticoreSyncFailure::removeSuccess($type, $id, 'sync');
                    return true;
                }
                return ManticoreMsg::sync($model);

            case 'file':
                $model = File::find($id);
                if (!$model) {
                    ManticoreSyncFailure::removeSuccess($type, $id, 'sync');
                    return true;
                }
                return ManticoreFile::sync($model);

            case 'task':
                $model = ProjectTask::find($id);
                if (!$model) {
                    ManticoreSyncFailure::removeSuccess($type, $id, 'sync');
                    return true;
                }
                return ManticoreTask::sync($model);

            case 'project':
                $model = Project::find($id);
                if (!$model) {
                    ManticoreSyncFailure::removeSuccess($type, $id, 'sync');
                    return true;
                }
                return ManticoreProject::sync($model);

            case 'user':
                $model = User::find($id);
                if (!$model) {
                    ManticoreSyncFailure::removeSuccess($type, $id, 'sync');
                    return true;
                }
                return ManticoreUser::sync($model);

            default:
                return false;
        }
    }

    /**
     * 显示统计信息
     */
    private function showStats(): void
    {
        $stats = ManticoreSyncFailure::getStats();

        $this->info('Manticore 同步失败统计:');
        $this->info("  总数: {$stats['total']}");

        if (!empty($stats['by_type'])) {
            $this->info('  按类型:');
            foreach ($stats['by_type'] as $type => $count) {
                $this->info("    - {$type}: {$count}");
            }
        }

        if (!empty($stats['by_action'])) {
            $this->info('  按操作:');
            foreach ($stats['by_action'] as $action => $count) {
                $this->info("    - {$action}: {$count}");
            }
        }
    }
}
