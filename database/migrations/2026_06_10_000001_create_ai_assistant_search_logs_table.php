<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiAssistantSearchLogsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ai_assistant_search_logs')) {
            return;
        }
        Schema::create('ai_assistant_search_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->default(0)->comment('用户ID（token推导）');
            $table->bigInteger('dialog_id')->default(0)->comment('对话ID（chat流程；invoke流程为0）');
            $table->string('context_key', 191)->default('')->comment('上下文标识（chat=插件context_key；invoke=前端session_id）');
            $table->string('source', 20)->default('')->comment('来源：chat|invoke');
            $table->string('query', 500)->default('')->comment('检索query（截断500）');
            $table->string('locale', 10)->default('')->comment('语种 zh|en');
            $table->text('source_ids')->nullable()->comment('命中source id列表 JSON');
            $table->decimal('top_score', 6, 4)->default(0)->comment('最高相似度 0-1');
            $table->integer('result_count')->default(0)->comment('命中数量');
            $table->integer('duration_ms')->default(0)->comment('检索耗时毫秒');
            $table->tinyInteger('empty')->default(0)->comment('是否空结果 0|1');
            $table->timestamps();
            $table->index('userid', 'idx_userid');
            $table->index('context_key', 'idx_context_key');
            $table->index(['empty', 'created_at'], 'idx_empty_created');
            $table->index('created_at', 'idx_created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_assistant_search_logs');
    }
}
