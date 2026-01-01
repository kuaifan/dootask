<?php

namespace App\Observers;

use App\Models\Deleted;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Tasks\ManticoreSyncTask;

class ProjectObserver extends AbstractObserver
{
    /**
     * Handle the Project "created" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function created(Project $project)
    {
        self::taskDeliver(new ManticoreSyncTask('project_sync', $project->toArray()));
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

        // 检查是否有搜索相关字段变化
        $searchableFields = ['name', 'desc', 'archived_at'];
        $isDirty = false;
        foreach ($searchableFields as $field) {
            if ($project->isDirty($field)) {
                $isDirty = true;
                break;
            }
        }

        if ($isDirty) {
            if ($project->archived_at) {
                self::taskDeliver(new ManticoreSyncTask('project_delete', ['project_id' => $project->id]));
            } else {
                self::taskDeliver(new ManticoreSyncTask('project_sync', $project->toArray()));
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
        self::taskDeliver(new ManticoreSyncTask('project_delete', ['project_id' => $project->id]));
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
        self::taskDeliver(new ManticoreSyncTask('project_sync', $project->toArray()));
    }

    /**
     * Handle the Project "force deleted" event.
     *
     * @param  \App\Models\Project  $project
     * @return void
     */
    public function forceDeleted(Project $project)
    {
        self::taskDeliver(new ManticoreSyncTask('project_delete', ['project_id' => $project->id]));
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
