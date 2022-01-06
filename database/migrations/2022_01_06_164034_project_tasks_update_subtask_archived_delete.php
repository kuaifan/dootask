<?php

use App\Models\ProjectTask;
use Illuminate\Database\Migrations\Migration;

class ProjectTasksUpdateSubtaskArchivedDelete extends Migration
{
    /**
     * 子任务同步主任务（归档、删除）
     *
     * @return void
     */
    public function up()
    {
        // 归档
        ProjectTask::whereParentId(0)
            ->whereNotNull('archived_at')
            ->chunkById(100, function ($lists) {
                /** @var ProjectTask $task */
                foreach ($lists as $task) {
                    ProjectTask::whereParentId($task->id)->update([
                        'archived_at' => $task->archived_at,
                        'archived_userid' => $task->archived_userid,
                        'archived_follow' => $task->archived_follow,
                    ]);
                }
            });

        // 删除
        ProjectTask::onlyTrashed()
            ->whereParentId(0)
            ->chunkById(100, function ($lists) {
                /** @var ProjectTask $task */
                foreach ($lists as $task) {
                    ProjectTask::whereParentId($task->id)->update([
                        'deleted_at' => $task->deleted_at,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
