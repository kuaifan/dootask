<?php

namespace App\Observers;

use App\Models\Deleted;
use App\Models\ProjectTask;
use App\Models\ProjectTaskUser;
use App\Models\ProjectTaskVisibilityUser;
use App\Models\ProjectUser;
use App\Tasks\ManticoreSyncTask;

class ProjectTaskObserver extends AbstractObserver
{
    /**
     * Handle the ProjectTask "created" event.
     *
     * @param  \App\Models\ProjectTask  $projectTask
     * @return void
     */
    public function created(ProjectTask $projectTask)
    {
        self::taskDeliver(new ManticoreSyncTask('task_sync', $projectTask->toArray()));
    }

    /**
     * Handle the ProjectTask "updated" event.
     *
     * @param  \App\Models\ProjectTask  $projectTask
     * @return void
     */
    public function updated(ProjectTask $projectTask)
    {
        if ($projectTask->isDirty('visibility')) {
            self::visibilityUpdate($projectTask);
        }
        if ($projectTask->isDirty('archived_at')) {
            if ($projectTask->archived_at) {
                Deleted::record('projectTask', $projectTask->id, self::userids($projectTask));
            } else {
                Deleted::forget('projectTask', $projectTask->id, self::userids($projectTask));
            }
        }

        // 检查是否有搜索相关字段变化或权限相关字段变化
        // visibility 变化会影响 allowed_users 来源
        // parent_id 变化会影响子任务继承
        // project_id 变化会影响 visibility=1 的任务权限
        $searchableFields = ['name', 'desc', 'archived_at', 'project_id', 'visibility', 'parent_id'];
        $isDirty = false;
        foreach ($searchableFields as $field) {
            if ($projectTask->isDirty($field)) {
                $isDirty = true;
                break;
            }
        }

        if ($isDirty) {
            if ($projectTask->archived_at) {
                self::taskDeliver(new ManticoreSyncTask('task_delete', ['task_id' => $projectTask->id]));
            } else {
                // 重新同步任务（会重新计算 allowed_users）
                self::taskDeliver(new ManticoreSyncTask('task_sync', $projectTask->toArray()));
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
        Deleted::record('projectTask', $projectTask->id, self::userids($projectTask));
        self::taskDeliver(new ManticoreSyncTask('task_delete', ['task_id' => $projectTask->id]));
    }

    /**
     * Handle the ProjectTask "restored" event.
     *
     * @param  \App\Models\ProjectTask  $projectTask
     * @return void
     */
    public function restored(ProjectTask $projectTask)
    {
        Deleted::forget('projectTask', $projectTask->id, self::userids($projectTask));
        self::taskDeliver(new ManticoreSyncTask('task_sync', $projectTask->toArray()));
    }

    /**
     * Handle the ProjectTask "force deleted" event.
     *
     * @param  \App\Models\ProjectTask  $projectTask
     * @return void
     */
    public function forceDeleted(ProjectTask $projectTask)
    {
        self::taskDeliver(new ManticoreSyncTask('task_delete', ['task_id' => $projectTask->id]));
    }

    /**
     * @param ProjectTask $projectTask
     * @param string[]|string $dataType
     * @return array
     */
    public static function userids(ProjectTask $projectTask, array|string $dataType = 'project')
    {
        if (!is_array($dataType)) {
            $dataType = [$dataType];
        }
        if (in_array('project', $dataType)) {
            return ProjectUser::whereProjectId($projectTask->project_id)->pluck('userid')->toArray();
        }
        if (in_array('projectOwnerUser', $dataType)) {
            return ProjectUser::whereProjectId($projectTask->project_id)->where('owner', 1)->pluck('userid')->toArray();
        }
        $array = [];
        if (in_array('task', $dataType)) {
            $array = array_merge($array, ProjectTaskUser::whereTaskId($projectTask->id)->orWhere('task_pid' ,$projectTask->id)->pluck('userid')->toArray());
        }
        if (in_array('visibility', $dataType)) {
            $array = array_merge($array, ProjectTaskVisibilityUser::whereTaskId($projectTask->id)->pluck('userid')->toArray());
        }
        return array_values(array_filter(array_unique($array)));
    }

    /**
     * 可见性更新
     * @param ProjectTask $projectTask
     */
    public static function visibilityUpdate(ProjectTask $projectTask)
    {
        $projectUserids = self::userids($projectTask);
        switch ($projectTask->visibility) {
            case 1:
                Deleted::forget('projectTask', $projectTask->id, $projectUserids);
                break;
            case 2:
            case 3:
                $dataType = $projectTask->visibility == 2 ? ['task'] : ['task', 'visibility'];
                $forgetUserids = self::userids($projectTask, $dataType);
                $projectOwnerUserIds = self::userids($projectTask, 'projectOwnerUser');
                $recordUserids = array_diff($projectUserids, $forgetUserids, $projectOwnerUserIds);
                Deleted::record('projectTask', $projectTask->id, $recordUserids);
                Deleted::forget('projectTask', $projectTask->id, $forgetUserids);
                break;
        }
        ProjectTask::whereParentId($projectTask->id)->change(['visibility' => $projectTask->visibility]);
    }
}
