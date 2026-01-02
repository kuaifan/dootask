<?php

namespace App\Observers;

use App\Models\ProjectTaskVisibilityUser;
use App\Tasks\ManticoreSyncTask;

/**
 * ProjectTaskVisibilityUser 观察者（MVA 权限方案）
 *
 * 用于处理任务 visibility=3（指定成员可见）时的成员变更同步
 */
class ProjectTaskVisibilityUserObserver extends AbstractObserver
{
    /**
     * Handle the ProjectTaskVisibilityUser "created" event.
     *
     * @param  \App\Models\ProjectTaskVisibilityUser  $visibilityUser
     * @return void
     */
    public function created(ProjectTaskVisibilityUser $visibilityUser)
    {
        // MVA 方案：更新任务的 allowed_users（会自动 cascadeToChildren）
        self::taskDeliver(new ManticoreSyncTask('update_task_allowed_users', [
            'task_id' => $visibilityUser->task_id,
        ]));
    }

    /**
     * Handle the ProjectTaskVisibilityUser "updated" event.
     *
     * @param  \App\Models\ProjectTaskVisibilityUser  $visibilityUser
     * @return void
     */
    public function updated(ProjectTaskVisibilityUser $visibilityUser)
    {
        // MVA 方案：更新任务的 allowed_users（会自动 cascadeToChildren）
        self::taskDeliver(new ManticoreSyncTask('update_task_allowed_users', [
            'task_id' => $visibilityUser->task_id,
        ]));
    }

    /**
     * Handle the ProjectTaskVisibilityUser "deleted" event.
     *
     * @param  \App\Models\ProjectTaskVisibilityUser  $visibilityUser
     * @return void
     */
    public function deleted(ProjectTaskVisibilityUser $visibilityUser)
    {
        // MVA 方案：更新任务的 allowed_users（会自动 cascadeToChildren）
        self::taskDeliver(new ManticoreSyncTask('update_task_allowed_users', [
            'task_id' => $visibilityUser->task_id,
        ]));
    }

    /**
     * Handle the ProjectTaskVisibilityUser "restored" event.
     *
     * @param  \App\Models\ProjectTaskVisibilityUser  $visibilityUser
     * @return void
     */
    public function restored(ProjectTaskVisibilityUser $visibilityUser)
    {
        //
    }

    /**
     * Handle the ProjectTaskVisibilityUser "force deleted" event.
     *
     * @param  \App\Models\ProjectTaskVisibilityUser  $visibilityUser
     * @return void
     */
    public function forceDeleted(ProjectTaskVisibilityUser $visibilityUser)
    {
        //
    }
}
