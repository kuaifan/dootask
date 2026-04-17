<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAiAssistantSessionsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ai_assistant_sessions')) {
            return;
        }
        Schema::create('ai_assistant_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('userid')->default(0)->comment('用户ID');
            $table->string('session_key', 100)->default('')->comment('场景分类key');
            $table->string('session_id', 100)->default('')->comment('前端生成的会话ID');
            $table->string('scene_key', 200)->default('')->comment('具体场景标识');
            $table->string('title', 255)->default('')->comment('会话标题');
            $table->longText('data')->nullable()->comment('responses JSON');
            $table->longText('images')->nullable()->comment('图片映射 {imageId: relativePath}');
            $table->timestamps();
            $table->index('userid', 'idx_userid');
            $table->unique(['userid', 'session_key', 'session_id'], 'uk_user_session');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_assistant_sessions');
    }
}
