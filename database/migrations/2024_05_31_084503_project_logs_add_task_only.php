<?php

use App\Models\ProjectLog;
use App\Models\ProjectTaskUser;
use App\Models\User;
use App\Models\UserTransfer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Swoole\Coroutine;

class ProjectLogsAddTaskOnly extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $isAdd = false;
        Schema::table('project_logs', function (Blueprint $table) use (&$isAdd) {
            if (!Schema::hasColumn('project_logs', 'task_only')) {
                $isAdd = true;
                $table->index('project_id');
                $table->index('task_id');
                $table->integer('task_only')->index()->nullable()->default(0)->after('task_id')->comment('仅任务日志：0否，1是');
            }
        });
        if ($isAdd) {
            // 更新数据
            go(function () {
                Coroutine::sleep(0.1);
                ProjectLog::whereDetail('移交子任务身份')->update(['task_only' => 1]);
                ProjectLog::whereDetail('移交任务身份')->update(['task_only' => 1]);
                UserTransfer::chunkById(100, function ($lists) {
                    /** @var UserTransfer $item */
                    foreach ($lists as $item) {
                        if (User::whereUserid($item->original_userid)->where("identity", "like", "%,disable,%")->exists()) {
                            ProjectTaskUser::transfer($item->original_userid, $item->new_userid);
                        }
                    }
                });
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
        Schema::table('project_logs', function (Blueprint $table) {
            $table->dropColumn("task_only");
        });
    }
}
