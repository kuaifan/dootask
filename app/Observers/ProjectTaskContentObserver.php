<?php

namespace App\Observers;

use App\Models\ProjectTask;
use App\Models\ProjectTaskContent;
use App\Tasks\ManticoreSyncTask;

class ProjectTaskContentObserver extends AbstractObserver
{
    /**
     * Handle the ProjectTaskContent "created" event.
     * 任务内容创建时，触发任务索引更新
     *
     * @param  \App\Models\ProjectTaskContent  $content
     * @return void
     */
    public function created(ProjectTaskContent $content)
    {
        $this->syncTaskToManticore($content->task_id);
    }

    /**
     * Handle the ProjectTaskContent "updated" event.
     * 任务内容更新时，触发任务索引更新
     *
     * @param  \App\Models\ProjectTaskContent  $content
     * @return void
     */
    public function updated(ProjectTaskContent $content)
    {
        // 只有内容变化时才需要更新
        if ($content->isDirty('content')) {
            $this->syncTaskToManticore($content->task_id);
        }
    }

    /**
     * Handle the ProjectTaskContent "deleted" event.
     * 任务内容删除时，触发任务索引更新
     *
     * @param  \App\Models\ProjectTaskContent  $content
     * @return void
     */
    public function deleted(ProjectTaskContent $content)
    {
        $this->syncTaskToManticore($content->task_id);
    }

    /**
     * 触发任务同步到 Manticore
     *
     * @param int|null $taskId 任务ID
     * @return void
     */
    private function syncTaskToManticore(?int $taskId)
    {
        if (!$taskId || $taskId <= 0) {
            return;
        }

        $task = ProjectTask::find($taskId);
        if (!$task || $task->archived_at || $task->deleted_at) {
            return;
        }

        self::taskDeliver(new ManticoreSyncTask('task_sync', $task->toArray()));
    }
}
