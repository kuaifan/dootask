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
        self::taskDeliver(new ManticoreSyncTask('project_user_add', [
            'project_id' => $projectUser->project_id,
            'userid' => $projectUser->userid,
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
        self::taskDeliver(new ManticoreSyncTask('project_user_remove', [
            'project_id' => $projectUser->project_id,
            'userid' => $projectUser->userid,
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
