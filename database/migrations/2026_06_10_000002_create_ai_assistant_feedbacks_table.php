<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiAssistantFeedbacksTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ai_assistant_feedbacks')) {
            return;
        }
        Schema::create('ai_assistant_feedbacks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->default(0)->comment('用户ID');
            $table->string('session_key', 100)->default('')->comment('场景分类key（同ai_assistant_sessions）');
            $table->string('session_id', 100)->default('')->comment('前端会话ID（=检索日志context_key，松关联）');
            $table->bigInteger('local_id')->default(0)->comment('前端回复条目localId');
            $table->string('feedback', 10)->default('')->comment('like|dislike');
            $table->text('prompt')->nullable()->comment('用户问题（截断1000）');
            $table->string('answer_digest', 32)->default('')->comment('回复内容md5');
            $table->text('answer')->nullable()->comment('回复摘录（去reasoning截断2000）');
            $table->text('source_ids')->nullable()->comment('回复引用的kb source id列表 JSON');
            $table->string('model', 100)->default('')->comment('模型名');
            $table->timestamps();
            $table->unique(['userid', 'session_key', 'session_id', 'local_id'], 'uk_user_entry');
            $table->index(['feedback', 'created_at'], 'idx_feedback_created');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_assistant_feedbacks');
    }
}
