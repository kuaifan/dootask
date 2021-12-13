<?php

use App\Models\Project;
use App\Models\ProjectTask;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProjectTasksAddArchivedFollow extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('project_tasks', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('project_tasks', 'archived_follow')) {
                $isAdd = true;
                $table->tinyInteger('archived_follow')->nullable()->default(0)->after('archived_userid')->comment('跟随项目归档（项目取消归档时任务也取消归档）');
            }
        });
        if ($isAdd) {
            // 更新数据
            Project::whereNotNull('archived_at')->chunkById(100, function ($lists) {
                foreach ($lists as $item) {
                    ProjectTask::whereProjectId($item->id)->whereArchivedAt(null)->update([
                        'archived_at' => $item->archived_at,
                        'archived_follow' => 1
                    ]);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropColumn("archived_follow");
        });
    }
}
