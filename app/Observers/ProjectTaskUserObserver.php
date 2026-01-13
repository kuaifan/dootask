<?php

namespace App\Observers;

use App\Models\Deleted;
use App\Models\ProjectTaskUser;
use App\Models\ProjectUser;
use App\Tasks\ManticoreSyncTask;

class ProjectTaskUserObserver extends AbstractObserver
{
    /**
     * Handle the ProjectTaskUser "created" event.
     *
     * @param  \App\Models\ProjectTaskUser  $projectTaskUser
     * @return void
     */
    public function created(ProjectTaskUser $projectTaskUser)
    {
        Deleted::forget('projectTask', $projectTaskUser->task_id, $projectTaskUser->userid);
        if ($projectTaskUser->task_pid) {
            Deleted::forget('projectTask', $projectTaskUser->task_pid, $projectTaskUser->userid);
        }

        // 更新任务权限
        self::taskDeliver(new ManticoreSyncTask('update_task_allowed_users', [
            'task_id' => $projectTaskUser->task_id,
        ]));
        // 如果是子任务，也更新父任务
        if ($projectTaskUser->task_pid) {
            self::taskDeliver(new ManticoreSyncTask('update_task_allowed_users', [
                'task_id' => $projectTaskUser->task_pid,
            ]));
        }
    }

    /**
     * Handle the ProjectTaskUser "updated" event.
     *
     * @param  \App\Models\ProjectTaskUser  $projectTaskUser
     * @return void
     */
    public function updated(ProjectTaskUser $projectTaskUser)
    {
        // userid 变更时需要更新任务权限（移交场景）
        if ($projectTaskUser->isDirty('userid')) {
            self::taskDeliver(new ManticoreSyncTask('update_task_allowed_users', [
                'task_id' => $projectTaskUser->task_id,
            ]));
            // 如果是子任务，也更新父任务
            if ($projectTaskUser->task_pid) {
                self::taskDeliver(new ManticoreSyncTask('update_task_allowed_users', [
                    'task_id' => $projectTaskUser->task_pid,
                ]));
            }
        }
    }

    /**
     * Handle the ProjectTaskUser "deleted" event.
     *
     * @param  \App\Models\ProjectTaskUser  $projectTaskUser
     * @return void
     */
    public function deleted(ProjectTaskUser $projectTaskUser)
    {
        if (!ProjectUser::whereProjectId($projectTaskUser->project_id)->whereUserid($projectTaskUser->userid)->exists()) {
            Deleted::record('projectTask', $projectTaskUser->task_id, $projectTaskUser->userid);
        }

        // 更新任务权限
        self::taskDeliver(new ManticoreSyncTask('update_task_allowed_users', [
            'task_id' => $projectTaskUser->task_id,
        ]));
    }

    /**
     * Handle the ProjectTaskUser "restored" event.
     *
     * @param  \App\Models\ProjectTaskUser  $projectTaskUser
     * @return void
     */
    public function restored(ProjectTaskUser $projectTaskUser)
    {
        //
    }

    /**
     * Handle the ProjectTaskUser "force deleted" event.
     *
     * @param  \App\Models\ProjectTaskUser  $projectTaskUser
     * @return void
     */
    public function forceDeleted(ProjectTaskUser $projectTaskUser)
    {
        //
    }
}
