<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_task_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id')->comment('任务ID');
            $table->unsignedBigInteger('related_task_id')->comment('关联任务ID');
            $table->string('direction', 32)->default('mention')->comment('关系方向: mention/mentioned_by');
            $table->unsignedBigInteger('dialog_id')->nullable()->comment('来源会话ID');
            $table->unsignedBigInteger('msg_id')->nullable()->comment('来源消息ID');
            $table->unsignedBigInteger('userid')->nullable()->comment('提及人');
            $table->timestamps();

            $table->unique(['task_id', 'related_task_id', 'direction'], 'project_task_relations_unique');
            $table->index(['task_id', 'direction']);
            $table->index('related_task_id');
            $table->index('dialog_id');
            $table->index('msg_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_task_relations');
    }
};
