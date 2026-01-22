<?php

namespace App\Tasks;

use App\Models\ProjectTask;
use App\Models\ProjectTaskAiEvent;
use App\Module\AiTaskSuggestion;

/**
 * AI 任务分析异步任务
 * 处理单个任务的所有 AI 事件
 */
class AiTaskAnalyzeTask extends AbstractTask
{
    protected int $taskId;

    public function __construct(int $taskId)
    {
        parent::__construct();
        $this->taskId = $taskId;
    }

    public function start()
    {
        $task = ProjectTask::with('project')->find($this->taskId);
        if (!$task || $task->deleted_at) {
            return;
        }

        // 获取该任务的所有待处理事件
        $events = ProjectTaskAiEvent::where('task_id', $this->taskId)
            ->whereIn('status', [
                ProjectTaskAiEvent::STATUS_PENDING,
                ProjectTaskAiEvent::STATUS_FAILED,
            ])
            ->get()
            ->keyBy('event_type');

        $suggestions = [];

        // 遍历所有事件类型
        foreach (ProjectTaskAiEvent::getEventTypes() as $eventType) {
            $event = $events->get($eventType);

            // 如果没有记录，跳过
            if (!$event) {
                continue;
            }

            // 如果是失败状态但不能重试，跳过
            if ($event->status === ProjectTaskAiEvent::STATUS_FAILED && !$event->canRetry()) {
                continue;
            }

            // 使用原子操作标记为处理中（防止并发重复处理）
            $updated = ProjectTaskAiEvent::where('id', $event->id)
                ->whereIn('status', [ProjectTaskAiEvent::STATUS_PENDING, ProjectTaskAiEvent::STATUS_FAILED])
                ->update(['status' => ProjectTaskAiEvent::STATUS_PROCESSING]);

            if (!$updated) {
                // 已被其他进程处理
                continue;
            }
            $event->status = ProjectTaskAiEvent::STATUS_PROCESSING;

            try {
                // 检查是否满足执行条件
                $shouldExecute = AiTaskSuggestion::shouldExecute($task, $eventType);

                if (!$shouldExecute) {
                    $event->markSkipped('不满足执行条件');
                    continue;
                }

                // 执行对应的分析
                $result = $this->executeAnalysis($task, $eventType);

                if ($result === null) {
                    $event->markSkipped('未生成有效建议');
                    continue;
                }

                // 收集建议
                $suggestions[] = $result;
                $event->markCompleted($result);

            } catch (\Exception $e) {
                $event->markFailed($e->getMessage());
                \Log::error("AiTaskAnalyzeTask error: task={$this->taskId}, type={$eventType}, error={$e->getMessage()}");
            }
        }

        // 如果有建议，发送消息
        if (!empty($suggestions)) {
            $msgId = AiTaskSuggestion::sendSuggestionMessage($task, $suggestions);

            // 更新所有事件的 msg_id
            if ($msgId) {
                ProjectTaskAiEvent::where('task_id', $this->taskId)
                    ->where('status', ProjectTaskAiEvent::STATUS_COMPLETED)
                    ->update(['msg_id' => $msgId]);
            }
        }
    }

    /**
     * 执行具体的分析
     * @param ProjectTask $task 任务对象
     * @param string $eventType 事件类型
     */
    private function executeAnalysis(ProjectTask $task, string $eventType): ?array
    {
        switch ($eventType) {
            case ProjectTaskAiEvent::EVENT_DESCRIPTION:
                return AiTaskSuggestion::generateDescription($task);

            case ProjectTaskAiEvent::EVENT_SUBTASKS:
                return AiTaskSuggestion::generateSubtasks($task);

            case ProjectTaskAiEvent::EVENT_ASSIGNEE:
                return AiTaskSuggestion::generateAssignee($task);

            case ProjectTaskAiEvent::EVENT_SIMILAR:
                return AiTaskSuggestion::findSimilarTasks($task);

            default:
                return null;
        }
    }

    public function end()
    {
    }
}
