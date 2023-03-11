<?php

namespace App\Observers;

use App\Models\Deleted;
use App\Models\ProjectTaskUser;
use App\Models\ProjectUser;

class ProjectTaskUserObserver
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
