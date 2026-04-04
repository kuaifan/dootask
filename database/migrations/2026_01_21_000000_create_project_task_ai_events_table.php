<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectTaskAiEventsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('project_task_ai_events')) {
            return;
        }
        Schema::create('project_task_ai_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('task_id')->comment('任务ID');
            $table->string('event_type', 50)->comment('事件类型: description/subtasks/assignee/similar');
            $table->string('status', 20)->default('pending')->comment('状态: pending/processing/completed/failed/skipped');
            $table->tinyInteger('retry_count')->unsigned()->default(0)->comment('重试次数');
            $table->json('result')->nullable()->comment('执行结果');
            $table->text('error')->nullable()->comment('错误信息');
            $table->bigInteger('msg_id')->nullable()->default(0)->comment('消息ID');
            $table->timestamp('executed_at')->nullable()->comment('执行时间');
            $table->timestamps();

            $table->unique(['task_id', 'event_type'], 'uk_task_event');
            $table->index('status', 'idx_status');
            $table->index('created_at', 'idx_created');
        });
    }

    public function down()
    {
        Schema::dropIfExists('project_task_ai_events');
    }
}
