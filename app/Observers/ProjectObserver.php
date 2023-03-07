<?php

namespace App\Observers;

use App\Models\Deleted;
use App\Models\Project;
use App\Models\ProjectUser;

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function created(Project $project)
    {
        //
    }

    /**
     * Handle the Project "updated" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function updated(Project $project)
    {
        if ($project->isDirty('archived_at')) {
            $userids = $this->userids($project);
            if ($project->archived_at) {
                Deleted::record('project', $project->id, $userids);
            } else {
                Deleted::forget('project', $project->id, $userids);
            }
        }
    }

    /**
     * Handle the Project "deleted" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function deleted(Project $project)
    {
        Deleted::record('project', $project->id, $this->userids($project));
    }

    /**
     * Handle the Project "restored" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function restored(Project $project)
    {
        Deleted::forget('project', $project->id, $this->userids($project));
    }

    /**
     * Handle the Project "force deleted" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function forceDeleted(Project $project)
    {
        //
    }

    /**
     * @param Project $project
     * @return array
     */
    private function userids(Project $project)
    {
        return ProjectUser::whereProjectId($project->id)->pluck('userid')->toArray();
    }
}
