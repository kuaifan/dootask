<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexSome20231217 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('project_task_users', function (Blueprint $table) {
            $table->index('task_id');
            $table->index('task_pid');
        });
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->index('visibility');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return voidw
     */
    public function down()
    {
        Schema::table('project_task_users', function (Blueprint $table) {
            $table->dropIndex(['task_id']);
            $table->dropIndex(['task_pid']);
        });
        Schema::table('project_tasks', function (Blueprint $table) {
            $table->dropIndex(['visibility']);
        });
    }
}
