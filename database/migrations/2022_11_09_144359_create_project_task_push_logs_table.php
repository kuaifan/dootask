<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTaskPushLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_task_push_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('userid')->nullable()->default(0)->comment('用户id');
            $table->integer('task_id')->nullable()->default(0)->comment('任务id');
            $table->tinyInteger('type')->nullable()->default(0)->comment('提醒类型：0 任务开始提醒，1 距离到期提醒，2到期超时提醒');
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
        Schema::dropIfExists('project_task_push_logs');
    }
}
