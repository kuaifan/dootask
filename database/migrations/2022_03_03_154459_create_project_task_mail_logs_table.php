<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTaskMailLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_task_mail_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('userid')->nullable()->default(0)->comment('用户id');
            $table->integer('task_id')->nullable()->default(0)->comment('任务id');
            $table->string('email')->nullable()->default('')->comment('电子邮箱');
            $table->tinyInteger('type')->nullable()->default(0)->comment('提醒类型：1第一次任务提醒，2第二次任务超期提醒');
            $table->tinyInteger('is_send')->nullable()->default(0)->comment('邮件发送是否成功：0否，1是');
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
        Schema::dropIfExists('project_task_mail_logs');
    }
}
