<?php

namespace App\Observers;

use App\Models\Deleted;
use App\Models\ProjectUser;
use App\Tasks\ManticoreSyncTask;

class ProjectUserObserver extends AbstractObserver
{
    /**
     * Handle the ProjectUser "created" event.
     *
     * @param  \App\Models\ProjectUser  $projectUser
     * @return void
     */
    public function created(ProjectUser $projectUser)
    {
        Deleted::forget('project', $projectUser->project_id, $projectUser->userid);
        
        // MVA 方案：更新项目的 allowed_users
        self::taskDeliver(new ManticoreSyncTask('update_project_allowed_users', [
            'project_id' => $projectUser->project_id,
        ]));
        // 异步级联更新该项目下所有 visibility=1 的任务
        self::taskDeliver(new ManticoreSyncTask('cascade_project_users', [
            'project_id' => $projectUser->project_id,
        ]));
    }

    /**
     * Handle the ProjectUser "updated" event.
     *
     * @param  \App\Models\ProjectUser  $projectUser
     * @return void
     */
    public function updated(ProjectUser $projectUser)
    {
        //
    }

    /**
     * Handle the ProjectUser "deleted" event.
     *
     * @param  \App\Models\ProjectUser  $projectUser
     * @return void
     */
    public function deleted(ProjectUser $projectUser)
    {
        Deleted::record('project', $projectUser->project_id, $projectUser->userid);
        
        // MVA 方案：更新项目的 allowed_users
        self::taskDeliver(new ManticoreSyncTask('update_project_allowed_users', [
            'project_id' => $projectUser->project_id,
        ]));
        // 异步级联更新该项目下所有 visibility=1 的任务
        self::taskDeliver(new ManticoreSyncTask('cascade_project_users', [
            'project_id' => $projectUser->project_id,
        ]));
    }

    /**
     * Handle the ProjectUser "restored" event.
     *
     * @param  \App\Models\ProjectUser  $projectUser
     * @return void
     */
    public function restored(ProjectUser $projectUser)
    {
        //
    }

    /**
     * Handle the ProjectUser "force deleted" event.
     *
     * @param  \App\Models\ProjectUser  $projectUser
     * @return void
     */
    public function forceDeleted(ProjectUser $projectUser)
    {
        //
    }
}
