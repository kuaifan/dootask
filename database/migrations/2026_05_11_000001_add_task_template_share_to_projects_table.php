<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTaskTemplateShareToProjectsTable extends Migration
{
    public function up()
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'task_template_share')) {
                $table->string('task_template_share', 20)->default('open')->after('ai_auto_analyze')->comment('共享模板开关');
            }
        });
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'task_template_share')) {
                $table->dropColumn('task_template_share');
            }
        });
    }
}
