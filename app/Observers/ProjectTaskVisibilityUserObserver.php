<?php

namespace App\Observers;

use App\Models\ProjectTaskVisibilityUser;
use App\Tasks\ManticoreSyncTask;

/**
 * ProjectTaskVisibilityUser 观察者
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
        // 将指定成员添加到 Manticore 的 task_users 表
        self::taskDeliver(new ManticoreSyncTask('task_user_add', [
            'task_id' => $visibilityUser->task_id,
            'userid' => $visibilityUser->userid,
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
        // 通常不会更新，但如果更新了也同步
        self::taskDeliver(new ManticoreSyncTask('task_user_add', [
            'task_id' => $visibilityUser->task_id,
            'userid' => $visibilityUser->userid,
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
        // 从 Manticore 的 task_users 表删除该成员
        // 注意：需要检查该用户是否仍是任务的负责人/协作人
        // 如果是，则不应该删除（因为 ProjectTaskUser 仍存在）
        self::taskDeliver(new ManticoreSyncTask('task_visibility_user_remove', [
            'task_id' => $visibilityUser->task_id,
            'userid' => $visibilityUser->userid,
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

