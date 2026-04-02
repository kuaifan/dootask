<?php

namespace App\Tasks;

use App\Module\Apps;
use App\Models\ProjectTask;
use App\Models\ProjectTaskAiEvent;
use App\Module\Base;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Task;

/**
 * AI 任务建议定时任务
 * 扫描新建任务并投递分析任务
 */
class AiTaskLoopTask extends AbstractTask
{
    /**
     * 单次处理任务数量上限
     */
    const BATCH_SIZE = 5;

    /**
     * 任务创建后多久开始分析（秒）
     */
    const DELAY_SECONDS = 10;

    public function __construct()
    {
        parent::__construct();
    }

    public function start()
    {
        // 检查 AI 插件是否安装
        if (!Apps::isInstalled('ai')) {
            return;
        }

        // 检查系统级 AI 自动分析开关
        if (Base::settingFind('system', 'task_ai_auto_analyze', 'open') === 'close') {
            return;
        }

        // 查询待处理的任务
        $tasks = $this->findPendingTasks();

        foreach ($tasks as $task) {
            // 检查项目级 AI 自动分析开关
            if ($task->project && $task->project->ai_auto_analyze === 'close') {
                continue;
            }

            // 为任务创建事件记录
            $this->createEventRecords($task);

            // 投递异步分析任务
            Task::deliver(new AiTaskAnalyzeTask($task->id));
        }
    }

    /**
     * 查找待处理的任务
     */
    private function findPendingTasks(): \Illuminate\Support\Collection
    {
        $delayTime = Carbon::now()->subSeconds(self::DELAY_SECONDS);

        // 子查询：已经有 AI 事件记录的任务
        $processedTaskIds = ProjectTaskAiEvent::select('task_id')
            ->distinct()
            ->pluck('task_id');

        // 查询新建任务（未处理过的）
        $newTasks = ProjectTask::with('project')
            ->where('parent_id', 0) // 只处理主任务
            ->whereNull('deleted_at')
            ->whereNull('archived_at')
            ->where('created_at', '<=', $delayTime) // 创建超过延迟时间
            ->where('created_at', '>=', Carbon::now()->subDays(1)) // 只处理1天内的
            ->whereNotIn('id', $processedTaskIds)
            ->orderBy('created_at', 'asc')
            ->take(self::BATCH_SIZE)
            ->get();

        // 查询需要重试的任务（优先处理较早失败的）
        $retryTaskIds = ProjectTaskAiEvent::where('status', ProjectTaskAiEvent::STATUS_FAILED)
            ->where('retry_count', '<', ProjectTaskAiEvent::MAX_RETRY)
            ->select('task_id')
            ->distinct()
            ->orderBy('updated_at', 'asc')
            ->take(self::BATCH_SIZE - $newTasks->count())
            ->pluck('task_id');

        $retryTasks = ProjectTask::with('project')
            ->whereIn('id', $retryTaskIds)
            ->whereNull('deleted_at')
            ->get();

        return $newTasks->merge($retryTasks)->take(self::BATCH_SIZE);
    }

    /**
     * 为任务创建事件记录
     */
    private function createEventRecords(ProjectTask $task): void
    {
        foreach (ProjectTaskAiEvent::getEventTypes() as $eventType) {
            ProjectTaskAiEvent::firstOrCreate(
                [
                    'task_id' => $task->id,
                    'event_type' => $eventType,
                ],
                [
                    'status' => ProjectTaskAiEvent::STATUS_PENDING,
                    'retry_count' => 0,
                ]
            );
        }
    }

    public function end()
    {
    }
}
