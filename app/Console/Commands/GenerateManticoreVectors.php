<?php

namespace App\Console\Commands;

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
use Cache;
use Illuminate\Console\Command;

/**
 * 异步向量生成命令
 *
 * 用于后台批量生成已索引数据的向量，与全文索引解耦
 * 使用双指针追踪：sync:xxxLastId（全文已同步）和 vector:xxxLastId（向量已生成）
 */
class GenerateManticoreVectors extends Command
{
    protected $signature = 'manticore:generate-vectors
                            {--type=all : 类型 (msg/file/task/project/user/all)}
                            {--batch=20 : 每批 embedding 数量}
                            {--max=500 : 每轮最大处理数量}
                            {--reset : 重置向量进度指针}';

    protected $description = '批量生成 Manticore 已索引数据的向量（异步处理）';

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
        if (!Apps::isInstalled("manticore")) {
            $this->error("应用「Manticore Search」未安装");
            return 1;
        }

        if (!Apps::isInstalled("ai")) {
            $this->error("应用「AI」未安装，无法生成向量");
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

        $type = $this->option('type');
        $batchSize = intval($this->option('batch'));
        $maxCount = intval($this->option('max'));
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

        foreach ($types as $t) {
            $this->processType($t, $batchSize, $maxCount, $reset);
        }

        $this->info("\n向量生成完成");
        $this->releaseLock();
        return 0;
    }

    /**
     * 处理单个类型的向量生成
     */
    private function processType(string $type, int $batchSize, int $maxCount, bool $reset): void
    {
        $config = self::TYPE_CONFIG[$type];

        $this->info("\n========== 处理 {$type} ==========");

        // 获取进度指针
        $syncLastId = intval(ManticoreKeyValue::get($config['syncKey'], 0));
        $vectorLastId = $reset ? 0 : intval(ManticoreKeyValue::get($config['vectorKey'], 0));

        if ($reset) {
            ManticoreKeyValue::set($config['vectorKey'], 0);
            $this->info("已重置 {$type} 向量进度指针");
        }

        // 计算待处理范围
        $pendingCount = $syncLastId - $vectorLastId;
        if ($pendingCount <= 0) {
            $this->info("{$type}: 无待处理数据 (sync={$syncLastId}, vector={$vectorLastId})");
            return;
        }

        $this->info("{$type}: 待处理 {$pendingCount} 条 (ID {$vectorLastId} -> {$syncLastId})");

        // 限制本轮处理数量
        $toProcess = min($pendingCount, $maxCount);
        $this->info("{$type}: 本轮处理 {$toProcess} 条");

        // 获取待处理的 ID 列表
        $modelClass = $config['model'];
        $idField = $config['idField'];

        $processedCount = 0;
        $currentLastId = $vectorLastId;

        while ($processedCount < $toProcess) {
            $remainingCount = min($toProcess - $processedCount, $batchSize * 5);

            // 获取一批 ID
            $ids = $modelClass::where($idField, '>', $currentLastId)
                ->where($idField, '<=', $syncLastId)
                ->orderBy($idField)
                ->limit($remainingCount)
                ->pluck($idField)
                ->toArray();

            if (empty($ids)) {
                break;
            }

            // 批量生成向量
            $manticoreClass = $config['class'];
            $successCount = $manticoreClass::generateVectorsBatch($ids, $batchSize);

            $processedCount += count($ids);
            $currentLastId = end($ids);

            // 更新向量进度指针
            ManticoreKeyValue::set($config['vectorKey'], $currentLastId);

            $this->info("{$type}: 已处理 {$processedCount}/{$toProcess}，成功 {$successCount}，当前ID: {$currentLastId}");

            // 刷新锁
            $this->setLock();
        }

        $this->info("{$type}: 完成本轮向量生成，共处理 {$processedCount} 条");
    }

    private function getLock(): ?array
    {
        $lockKey = 'manticore:generate-vectors:lock';
        return Cache::has($lockKey) ? Cache::get($lockKey) : null;
    }

    private function setLock(): void
    {
        $lockKey = 'manticore:generate-vectors:lock';
        Cache::put($lockKey, ['started_at' => date('Y-m-d H:i:s')], 600);
    }

    private function releaseLock(): void
    {
        $lockKey = 'manticore:generate-vectors:lock';
        Cache::forget($lockKey);
    }

    public function handleSignal(int $signal): void
    {
        $this->info("\n收到信号，正在退出...");
        $this->releaseLock();
        exit(0);
    }
}
