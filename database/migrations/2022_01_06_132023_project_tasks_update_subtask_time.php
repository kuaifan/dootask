<?php

use App\Models\ProjectTask;
use Illuminate\Database\Migrations\Migration;

class ProjectTasksUpdateSubtaskTime extends Migration
{
    /**
     * 子任务同步主任务（任务时间）
     *
     * @return void
     */
    public function up()
    {
        ProjectTask::where('parent_id', '>', 0)
            ->whereNull('end_at')
            ->chunkById(100, function ($lists) {
                /** @var ProjectTask $task */
                foreach ($lists as $task) {
                    $parent = ProjectTask::whereNotNull('end_at')->find($task->parent_id);
                    if ($parent) {
                        $task->start_at = $parent->start_at;
                        $task->end_at = $parent->end_at;
                        $task->save();
                    }
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
