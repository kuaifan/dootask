<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUseCountToProjectTaskTemplates extends Migration
{
    public function up()
    {
        Schema::table('project_task_templates', function (Blueprint $table) {
            $table->unsignedInteger('use_count')->default(0)->after('is_default')->comment('累计使用次数');
            $table->timestamp('last_used_at')->nullable()->after('use_count')->comment('最近一次使用时间');
            $table->index(['use_count', 'last_used_at'], 'idx_template_usage');
        });
    }

    public function down()
    {
        Schema::table('project_task_templates', function (Blueprint $table) {
            $table->dropIndex('idx_template_usage');
            $table->dropColumn(['use_count', 'last_used_at']);
        });
    }
}
