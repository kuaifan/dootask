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

        // 同步任务成员到 Manticore
        self::taskDeliver(new ManticoreSyncTask('task_user_add', [
            'task_id' => $projectTaskUser->task_id,
            'userid' => $projectTaskUser->userid,
        ]));
        // 如果是子任务，同时添加到父任务
        if ($projectTaskUser->task_pid) {
            self::taskDeliver(new ManticoreSyncTask('task_user_add', [
                'task_id' => $projectTaskUser->task_pid,
                'userid' => $projectTaskUser->userid,
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
        //
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

        // 从 Manticore 删除任务成员关系
        self::taskDeliver(new ManticoreSyncTask('task_user_remove', [
            'task_id' => $projectTaskUser->task_id,
            'userid' => $projectTaskUser->userid,
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
