<?php

namespace App\Console\Commands;

use App\Console\Commands\Traits\ManticoreSyncLock;
use App\Models\File;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WebSocketDialogMsg;
use App\Module\Apps;
use App\Module\Manticore\ManticoreFile;
use App\Module\Manticore\ManticoreKeyValue;
use App\Module\Manticore\ManticoreMsg;
use App\Module\Manticore\ManticoreProject;
use App\Module\Manticore\ManticoreTask;
use App\Module\Manticore\ManticoreUser;
use Illuminate\Console\Command;

/**
 * 异步向量生成命令
 *
 * 用于后台批量生成已索引数据的向量，与全文索引解耦
 * 使用双指针追踪：sync:xxxLastId（全文已同步）和 vector:xxxLastId（向量已生成）
 *
 * 运行模式：
 * - 持续处理直到所有待处理数据完成
 * - 每批处理完成后休眠几秒，避免 API 过载
 * - 定时器只作为兜底触发机制
 */
class GenerateManticoreVectors extends Command
{
    use ManticoreSyncLock;

    protected $signature = 'manticore:generate-vectors
                            {--type=all : 类型 (msg/file/task/project/user/all)}
                            {--batch=50 : 每批 embedding 数量}
                            {--sleep=3 : 每批处理后休眠秒数}
                            {--reset : 重置向量进度指针}';

    protected $description = '批量生成 Manticore 已索引数据的向量';

    /**
     * 类型配置
     */
    private const TYPE_CONFIG = [
        'msg' => [
            'syncKey' => 'sync:manticoreMsgLastId',
            'vectorKey' => 'vector:manticoreMsgLastId',
            'class' => ManticoreMsg::class,
            'model' => WebSocketDialogMsg::class,
            'idField' => 'id',
        ],
        'file' => [
            'syncKey' => 'sync:manticoreFileLastId',
            'vectorKey' => 'vector:manticoreFileLastId',
            'class' => ManticoreFile::class,
            'model' => File::class,
            'idField' => 'id',
        ],
        'task' => [
            'syncKey' => 'sync:manticoreTaskLastId',
            'vectorKey' => 'vector:manticoreTaskLastId',
            'class' => ManticoreTask::class,
            'model' => ProjectTask::class,
            'idField' => 'id',
        ],
        'project' => [
            'syncKey' => 'sync:manticoreProjectLastId',
            'vectorKey' => 'vector:manticoreProjectLastId',
            'class' => ManticoreProject::class,
            'model' => Project::class,
            'idField' => 'id',
        ],
        'user' => [
            'syncKey' => 'sync:manticoreUserLastId',
            'vectorKey' => 'vector:manticoreUserLastId',
            'class' => ManticoreUser::class,
            'model' => User::class,
            'idField' => 'userid',
        ],
    ];

    public function handle(): int
    {
        if (!Apps::isInstalled("search")) {
            $this->error("应用「Manticore Search」未安装");
            return 1;
        }

        if (!Apps::isInstalled("ai")) {
            $this->error("应用「AI」未安装，无法生成向量");
            return 1;
        }

        $this->registerSignalHandlers();

        if (!$this->acquireLock()) {
            return 1;
        }

        $type = $this->option('type');
        $batchSize = intval($this->option('batch'));
        $sleepSeconds = intval($this->option('sleep'));
        $reset = $this->option('reset');

        if ($type === 'all') {
            $types = array_keys(self::TYPE_CONFIG);
        } else {
            if (!isset(self::TYPE_CONFIG[$type])) {
                $this->error("未知类型: {$type}。可用类型: msg, file, task, project, user, all");
                $this->releaseLock();
                return 1;
            }
            $types = [$type];
        }

        // 持续处理直到所有类型都没有待处理数据
        $round = 0;
        do {
            $round++;
            $totalPending = 0;

            foreach ($types as $t) {
                if ($this->shouldStop) {
                    break;
                }
                $pending = $this->processType($t, $batchSize, $reset && $round === 1);
                $totalPending += $pending;
            }

            // 如果还有待处理数据，休眠后继续
            if ($totalPending > 0 && !$this->shouldStop) {
                $this->info("\n--- 第 {$round} 轮完成，剩余 {$totalPending} 条待处理，{$sleepSeconds} 秒后继续 ---\n");
                sleep($sleepSeconds);
                $this->setLock(); // 刷新锁
            }
        } while ($totalPending > 0 && !$this->shouldStop);

        $this->info("\n向量生成完成（共 {$round} 轮）");
        $this->releaseLock();
        return 0;
    }

    /**
     * 处理单个类型的向量生成（每次处理一批）
     *
     * @param string $type 类型
     * @param int $batchSize 每批数量
     * @param bool $reset 是否重置进度
     * @return int 剩余待处理数量
     */
    private function processType(string $type, int $batchSize, bool $reset): int
    {
        $config = self::TYPE_CONFIG[$type];

        // 获取进度指针
        $syncLastId = intval(ManticoreKeyValue::get($config['syncKey'], 0));
        $vectorLastId = $reset ? 0 : intval(ManticoreKeyValue::get($config['vectorKey'], 0));

        if ($reset) {
            ManticoreKeyValue::set($config['vectorKey'], 0);
            $this->info("[{$type}] 已重置向量进度指针");
        }

        // 计算待处理范围
        $pendingCount = $syncLastId - $vectorLastId;
        if ($pendingCount <= 0) {
            return 0;
        }

        // 获取待处理的 ID 列表（每次处理 batchSize * 5 条，让 generateVectorsBatch 内部再分批调用 API）
        $modelClass = $config['model'];
        $idField = $config['idField'];
        $fetchCount = $batchSize * 5;

        $ids = $modelClass::where($idField, '>', $vectorLastId)
            ->where($idField, '<=', $syncLastId)
            ->orderBy($idField)
            ->limit($fetchCount)
            ->pluck($idField)
            ->toArray();

        if (empty($ids)) {
            return 0;
        }

        // 批量生成向量
        $manticoreClass = $config['class'];
        $successCount = $manticoreClass::generateVectorsBatch($ids, $batchSize);

        $currentLastId = end($ids);

        // 更新向量进度指针
        ManticoreKeyValue::set($config['vectorKey'], $currentLastId);

        $remaining = $pendingCount - count($ids);
        $this->info("[{$type}] 处理 " . count($ids) . " 条，成功 {$successCount}，ID: {$vectorLastId} -> {$currentLastId}，剩余 {$remaining}");

        // 刷新锁
        $this->setLock();

        return max(0, $remaining);
    }
}
