<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTaskVisibilityUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_task_visibility_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('project_id')->index()->nullable()->default(0)->comment('项目ID');
            $table->bigInteger('task_id')->index()->nullable()->default(0)->comment('任务ID');
            $table->bigInteger('userid')->index()->nullable()->default(0)->comment('成员ID');
            $table->timestamps();
        });

        // 迁移旧数据
        DB::table('project_task_users')->where("owner",2)->orderBy('id')->chunk(100, function ($data) {
            foreach ($data as $item) {
                DB::table('project_task_visibility_users')->insert([
                    'project_id' => $item->project_id,
                    'task_id' => $item->task_id,
                    'userid' => $item->userid,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at
                ]);
            }
        });
        DB::table('project_task_users')->where("owner",2)->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_task_visibility_users');
    }
}
