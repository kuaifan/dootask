<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTaskUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_task_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('project_id')->nullable()->default(0)->comment('项目ID');
            $table->bigInteger('task_id')->nullable()->default(0)->comment('任务ID');
            $table->bigInteger('task_pid')->nullable()->default(0)->comment('任务ID（如果是子任务则是父级任务ID）');
            $table->bigInteger('userid')->nullable()->default(0)->comment('成员ID');
            $table->tinyInteger('owner')->nullable()->default(0)->comment('是否任务负责人');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project_task_users');
    }
}
