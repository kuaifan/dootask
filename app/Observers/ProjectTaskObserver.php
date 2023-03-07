<?php

namespace App\Observers;

use App\Models\Deleted;
use App\Models\ProjectTask;
use App\Models\ProjectTaskUser;

class ProjectTaskObserver
{
    /**
     * Handle the ProjectTask "created" event.
     *
     * @param  \App\Models\ProjectTask  $projectTask
     * @return void
     */
    public function created(ProjectTask $projectTask)
    {
        //
    }

    /**
     * Handle the ProjectTask "updated" event.
     *
     * @param  \App\Models\ProjectTask  $projectTask
     * @return void
     */
    public function updated(ProjectTask $projectTask)
    {
        if ($projectTask->isDirty('archived_at')) {
            $userids = $this->userids($projectTask);
            if ($projectTask->archived_at) {
                Deleted::record('projectTask', $projectTask->id, $userids);
            } else {
                Deleted::forget('projectTask', $projectTask->id, $userids);
            }
        }
    }

    /**
     * Handle the ProjectTask "deleted" event.
     *
     * @param  \App\Models\ProjectTask  $projectTask
     * @return void
     */
    public function deleted(ProjectTask $projectTask)
    {
        Deleted::record('projectTask', $projectTask->id, $this->userids($projectTask));
    }

    /**
     * Handle the ProjectTask "restored" event.
     *
     * @param  \App\Models\ProjectTask  $projectTask
     * @return void
     */
    public function restored(ProjectTask $projectTask)
    {
        Deleted::forget('projectTask', $projectTask->id, $this->userids($projectTask));
    }

    /**
     * Handle the ProjectTask "force deleted" event.
     *
     * @param  \App\Models\ProjectTask  $projectTask
     * @return void
     */
    public function forceDeleted(ProjectTask $projectTask)
    {
        //
    }

    /**
     * @param ProjectTask $projectTask
     * @return array
     */
    private function userids(ProjectTask $projectTask)
    {
        return ProjectTaskUser::whereTaskId($projectTask->id)->pluck('userid')->toArray();
    }
}
